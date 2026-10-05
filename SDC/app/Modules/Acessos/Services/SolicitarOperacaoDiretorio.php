<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services;

use App\Models\User;
use App\Modules\Acessos\Enums\AcaoDiretorio;
use App\Modules\Acessos\Enums\EstadoOperacaoAd;
use App\Modules\Acessos\Enums\OrigemOperacaoAd;
use App\Modules\Acessos\Exceptions\OperacaoDiretorioProibida;
use App\Modules\Acessos\Jobs\ExecutarOperacaoDiretorioJob;
use App\Modules\Acessos\Models\CadastroAcesso;
use App\Modules\Acessos\Models\OperacaoAd;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Caso de uso unico de pedido ao AD (spec 6.1), para a tela de Acessos e para
 * a automacao de Demandas. O web nunca fala com o AD: valida localmente e, numa
 * so transacao, grava a operacao, a auditoria e o job da fila `diretorio` (fila
 * database no mesmo Postgres, sem afterCommit). Duas solicitacoes iguais em voo
 * viram uma: a segunda recebe a operacao existente.
 */
final class SolicitarOperacaoDiretorio
{
    private const LOGIN_VALIDO = '/^[A-Za-z0-9._-]{1,20}$/';

    public function __construct(
        private readonly DonoDaConta $dono,
        private readonly GuardaDeAlvo $guarda,
    ) {}

    public function paraCadastro(CadastroAcesso $cadastro, AcaoDiretorio $acao, User $ator, ?string $motivo = null): OperacaoAd
    {
        $login = trim((string) $cadastro->login_ad);
        if ($login === '') {
            throw OperacaoDiretorioProibida::semLogin();
        }
        $motivo = $this->validar($login, $acao, $ator, $cadastro, $motivo);

        return $this->registrar(
            $login, $acao, $ator, $cadastro, OrigemOperacaoAd::ACESSOS, null, $motivo,
            static fn (): string => sprintf(
                'acessos:%d:%s:%d',
                $cadastro->getKey(),
                $acao->value,
                $cadastro->operacoesAd()->where('acao', $acao->value)->count() + 1,
            ),
        );
    }

    public function paraLogin(
        string $login,
        AcaoDiretorio $acao,
        User $ator,
        OrigemOperacaoAd $origem,
        ?int $origemId,
        string $chave,
    ): OperacaoAd {
        $login = trim($login);
        $cadastro = $this->cadastroDoLogin($login);
        $motivo = $this->validar($login, $acao, $ator, $cadastro, null);

        return $this->registrar($login, $acao, $ator, $cadastro, $origem, $origemId, $motivo, static fn (): string => $chave);
    }

    /** @return string|null motivo normalizado (null se vazio) */
    private function validar(string $login, AcaoDiretorio $acao, User $ator, ?CadastroAcesso $cadastro, ?string $motivo): ?string
    {
        if (preg_match(self::LOGIN_VALIDO, $login) !== 1) {
            throw OperacaoDiretorioProibida::loginInvalido();
        }
        if ($this->dono->eDoAtor($ator, $cadastro, $login)) {
            throw OperacaoDiretorioProibida::propriaConta();
        }
        if ($this->guarda->loginProtegido($login)) {
            throw OperacaoDiretorioProibida::contaProtegida();
        }

        $motivo = trim((string) $motivo);
        if ($motivo === '' && $acao->exigeMotivo()) {
            throw OperacaoDiretorioProibida::motivoObrigatorio();
        }

        return $motivo === '' ? null : $motivo;
    }

    /** @param Closure(): string $chave calculada dentro da transacao */
    private function registrar(
        string $login,
        AcaoDiretorio $acao,
        User $ator,
        ?CadastroAcesso $cadastro,
        OrigemOperacaoAd $origem,
        ?int $origemId,
        ?string $motivo,
        Closure $chave,
    ): OperacaoAd {
        try {
            return DB::transaction(function () use ($login, $acao, $ator, $cadastro, $origem, $origemId, $motivo, $chave): OperacaoAd {
                $chaveIdempotencia = $chave();
                $existente = $this->existente($login, $acao, $chaveIdempotencia);
                if ($existente !== null) {
                    return $existente;
                }

                $operacao = OperacaoAd::create([
                    'chave_idempotencia' => $chaveIdempotencia,
                    'cadastro_id' => $cadastro?->getKey(),
                    'login_ad' => $login,
                    'object_guid' => $cadastro?->object_guid,
                    'acao' => $acao,
                    'origem' => $origem,
                    'origem_id' => $origemId,
                    'solicitado_por_id' => $ator->getKey(),
                    'motivo' => $motivo,
                ]);
                $operacao->registrarAuditoria('solicitado', $motivo === null ? [] : ['motivo' => $motivo]);
                ExecutarOperacaoDiretorioJob::dispatch((string) $operacao->getKey());

                return $operacao;
            });
        } catch (UniqueConstraintViolationException $corrida) {
            // Outra requisicao gravou a mesma operacao entre a busca e o insert:
            // a transacao (com o job) foi desfeita; devolve a que venceu.
            return $this->existente($login, $acao, $chave()) ?? throw $corrida;
        }
    }

    /** Em voo para (lower(login), acao), ou ja gravada com a mesma chave de idempotencia. */
    private function existente(string $login, AcaoDiretorio $acao, string $chave): ?OperacaoAd
    {
        return OperacaoAd::query()
            ->whereRaw('lower(login_ad) = ?', [mb_strtolower($login)])
            ->where('acao', $acao->value)
            ->whereIn('estado', [EstadoOperacaoAd::SOLICITADO->value, EstadoOperacaoAd::ENVIADO->value])
            ->first()
            ?? OperacaoAd::query()->where('chave_idempotencia', $chave)->first();
    }

    /** Cadastro dono do login, se houver exatamente um (Demandas cita so o login). */
    private function cadastroDoLogin(string $login): ?CadastroAcesso
    {
        $cadastros = CadastroAcesso::query()
            ->whereRaw('lower(login_ad) = ?', [mb_strtolower($login)])
            ->limit(2)
            ->get();

        return $cadastros->count() === 1 ? $cadastros->first() : null;
    }
}
