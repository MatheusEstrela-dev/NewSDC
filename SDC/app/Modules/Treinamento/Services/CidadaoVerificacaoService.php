<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Exceptions\Auth\EmailChange\CodeExpiredException;
use App\Exceptions\Auth\EmailChange\InvalidCodeException;
use App\Exceptions\Auth\EmailChange\MaxResendsReachedException;
use App\Exceptions\Auth\EmailChange\ResendCooldownException;
use App\Exceptions\Auth\EmailChange\TooManyAttemptsException;
use App\Models\AuditLog;
use App\Modules\Treinamento\Mail\CidadaoVerificacaoCodigoMail;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\CidadaoEmailVerificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Verificacao do e-mail informado no cadastro publico do Portal de Treinamentos.
 *
 * Mesmo desenho de App\Services\Auth\EmailChangeService (magic code de 6 digitos,
 * hash no banco, TTL, teto de tentativas, cooldown de reenvio) e reaproveitando
 * as mesmas excecoes - o servidor e o cidadao passam pela mesma experiencia.
 * Mantido enxuto (SRP): quem traduz excecao em mensagem e o controller.
 */
class CidadaoVerificacaoService
{
    private const TABELA_AUDIT = 'cidadao_email_verificacoes';

    /**
     * Abre um pedido de verificacao e envia o codigo. Invalida qualquer pedido
     * ativo anterior do mesmo cidadao (so 1 pendente por vez).
     */
    public function emitir(Cidadao $cidadao, Request $request): CidadaoEmailVerificacao
    {
        return DB::transaction(function () use ($cidadao, $request) {
            CidadaoEmailVerificacao::activeFor($cidadao->id)
                ->update(['cancelled_at' => now()]);

            $codigo = $this->gerarCodigo();

            $verificacao = CidadaoEmailVerificacao::create([
                'cidadao_id' => $cidadao->id,
                'email' => $cidadao->email,
                'code_hash' => Hash::make($codigo),
                'expires_at' => now()->addMinutes(CidadaoEmailVerificacao::TTL_MINUTES),
                'requested_ip' => $request->ip(),
                'requested_user_agent' => $request->userAgent(),
            ]);

            // Mailable recebe PRIMITIVOS + afterCommit() pra so despachar apos o
            // COMMIT (padrao do modulo, ver InscricaoConfirmadaMail).
            Mail::to($cidadao->email)->queue(
                CidadaoVerificacaoCodigoMail::para(
                    $cidadao->name,
                    $cidadao->email,
                    $codigo,
                    $verificacao->expires_at,
                )->afterCommit()
            );

            $this->auditar($verificacao, 'emitido', ['email' => $cidadao->email]);

            return $verificacao;
        });
    }

    /**
     * Confirma o codigo e marca o e-mail como verificado.
     *
     * @throws CodeExpiredException|InvalidCodeException|TooManyAttemptsException
     */
    public function confirmar(Cidadao $cidadao, string $codigoInformado): CidadaoEmailVerificacao
    {
        $codigoInformado = trim($codigoInformado);

        // Fetch com lock em transacao propria: o increment da tentativa nao pode
        // ser desfeito junto com o rollback da InvalidCodeException.
        $verificacao = DB::transaction(function () use ($cidadao) {
            return CidadaoEmailVerificacao::activeFor($cidadao->id)
                ->lockForUpdate()
                ->latest()
                ->firstOrFail();
        });

        if ($verificacao->expires_at->isPast()) {
            throw new CodeExpiredException();
        }

        if ($verificacao->code_attempts >= CidadaoEmailVerificacao::MAX_ATTEMPTS) {
            throw new TooManyAttemptsException();
        }

        if (! Hash::check($codigoInformado, $verificacao->code_hash)) {
            DB::transaction(function () use ($verificacao) {
                $verificacao->increment('code_attempts');
                $verificacao->refresh();

                if ($verificacao->code_attempts >= CidadaoEmailVerificacao::MAX_ATTEMPTS) {
                    $verificacao->forceFill(['cancelled_at' => now()])->save();
                    $this->auditar($verificacao, 'cancelado', ['reason' => 'max_attempts']);
                }
            });
            $verificacao->refresh();

            throw new InvalidCodeException(
                remaining: CidadaoEmailVerificacao::MAX_ATTEMPTS - $verificacao->code_attempts
            );
        }

        return DB::transaction(function () use ($cidadao, $verificacao) {
            $cidadao->marcarEmailVerificado();
            $verificacao->forceFill(['used_at' => now()])->save();

            $this->auditar($verificacao, 'confirmado', ['email' => $verificacao->email]);

            return $verificacao;
        });
    }

    /**
     * Regera o codigo respeitando cooldown e teto de reenvios.
     *
     * @throws CodeExpiredException|MaxResendsReachedException|ResendCooldownException
     */
    public function reenviar(Cidadao $cidadao): CidadaoEmailVerificacao
    {
        return DB::transaction(function () use ($cidadao) {
            $verificacao = CidadaoEmailVerificacao::activeFor($cidadao->id)
                ->lockForUpdate()
                ->latest()
                ->firstOrFail();

            if ($verificacao->resend_count >= CidadaoEmailVerificacao::MAX_RESENDS_PER_REQUEST) {
                throw new MaxResendsReachedException();
            }

            $restante = $verificacao->resendCooldownRemaining();
            if ($restante > 0) {
                throw new ResendCooldownException(secondsRemaining: $restante);
            }

            $codigo = $this->gerarCodigo();

            // forceFill: campos de estado (code_attempts, resend_count,
            // last_resend_at, expires_at) ficam fora de $fillable de proposito,
            // contra mass-assignment. Aqui o service ja validou o que precisa.
            $verificacao->forceFill([
                'code_hash' => Hash::make($codigo),
                'code_attempts' => 0,
                'resend_count' => $verificacao->resend_count + 1,
                'last_resend_at' => now(),
                // Reenvio renova a janela inteira. Diferente do EmailChangeService,
                // aqui NAO recusamos pedido expirado: o cidadao ainda nao tem
                // conta utilizavel, e obriga-lo a refazer o cadastro do zero
                // esbarraria no unique de CPF/e-mail do proprio registro dele.
                'expires_at' => now()->addMinutes(CidadaoEmailVerificacao::TTL_MINUTES),
            ])->save();

            Mail::to($verificacao->email)->queue(
                CidadaoVerificacaoCodigoMail::para(
                    $cidadao->name,
                    $verificacao->email,
                    $codigo,
                    $verificacao->expires_at,
                )->afterCommit()
            );

            $this->auditar($verificacao, 'reenviado', ['resend_count' => $verificacao->resend_count]);

            return $verificacao;
        });
    }

    /**
     * 6 digitos criptograficamente seguros (random_int).
     */
    private function gerarCodigo(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * user_id fica null de proposito: cidadao nao e App\Models\User e a coluna
     * de audit_logs e FK nullable para users.
     *
     * @param  array<string, mixed>  $dados
     */
    private function auditar(CidadaoEmailVerificacao $verificacao, string $evento, array $dados = []): void
    {
        AuditLog::log(
            AuditLog::EVENT_UPDATE,
            self::TABELA_AUDIT,
            $verificacao->id,
            null,
            ['event' => $evento, 'cidadao_id' => $verificacao->cidadao_id] + $dados,
            null,
        );
    }
}
