<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusCertificado;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\CredencialConsulta;
use App\Modules\Treinamento\Models\CredencialQr;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Support\VersoesDoCurso;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Credencial por QR Code do servidor: token fixo (trocavel) e a consulta que a
 * equipe da CEDEC faz ao le-lo. So consulta; nao registra presenca.
 */
class CredencialQrService
{
    private const CACHE_QR_TTL = 86400;

    public function __construct(
        private readonly GeradorQrCodeService $qr,
        private readonly TrilhaService $trilha,
    ) {}

    /**
     * Credencial do servidor, criando o token na primeira vez.
     *
     * @return array{qr_png_base64: string, url: string, gerado_em: string, cpf_mascarado: string}
     */
    public function paraServidor(User $servidor): array
    {
        $credencial = CredencialQr::query()->firstOrCreate(
            ['user_id' => $servidor->getKey()],
            ['token' => $this->novoToken(), 'gerado_em' => now()],
        );

        return $this->apresentar($credencial) + ['cpf_mascarado' => $this->mascararCpf((string) $servidor->cpf)];
    }

    /**
     * Troca o token: o antigo deixa de valer.
     */
    public function regerar(User $servidor): void
    {
        CredencialQr::query()->updateOrCreate(
            ['user_id' => $servidor->getKey()],
            ['token' => $this->novoToken(), 'gerado_em' => now()],
        );
    }

    /**
     * Resultado da consulta, ou null se nenhum servidor corresponde ao codigo.
     * O codigo pode ser o token do QR, o CPF do servidor (com ou sem mascara) ou
     * o codigo de verificacao de um certificado dele. Registra a consulta.
     *
     * @return array<string, mixed>|null
     */
    public function consultar(string $codigo, User $consultor): ?array
    {
        $servidor = $this->resolverServidor(trim($codigo));

        if ($servidor === null) {
            return null;
        }

        CredencialConsulta::query()->create([
            'user_id_consultado' => $servidor->getKey(),
            'consultado_por' => $consultor->getKey(),
        ]);

        $inscricoes = Inscricao::query()
            ->with('treinamento')
            ->where('inscrito_type', $servidor->getMorphClass())
            ->where('inscrito_id', $servidor->getKey())
            ->get();

        return [
            'servidor' => [
                'nome' => $servidor->name,
                'cpf_mascarado' => $this->mascararCpf((string) $servidor->cpf),
                'orgao' => $servidor->orgaoPrincipal?->nome,
            ],
            'certificados' => $this->certificados($inscricoes->pluck('id')->all()),
            'em_andamento' => $this->emAndamento($inscricoes),
            'consultado_em' => now()->toIso8601String(),
        ];
    }

    /**
     * Servidor dono do codigo: token da credencial, CPF (11 digitos) ou codigo
     * de certificado valido emitido para um servidor. Cidadao fica de fora.
     */
    private function resolverServidor(string $codigo): ?User
    {
        $userId = CredencialQr::query()->where('token', $codigo)->value('user_id');

        if ($userId === null) {
            $cpf = preg_replace('/\D/', '', $codigo);
            $userId = strlen($cpf) === 11 && preg_match('/^[\d.\-\s]+$/', $codigo)
                ? User::query()->where('cpf', $cpf)->value('id')
                : $this->servidorDoCertificado($codigo);
        }

        return $userId === null ? null : User::query()->with('orgaoPrincipal')->find($userId);
    }

    private function servidorDoCertificado(string $codigo): ?int
    {
        $inscricao = Certificado::query()
            ->where('codigo_verificacao', mb_strtoupper($codigo))
            ->where('status', StatusCertificado::GERADO->value)
            ->first()
            ?->inscricao;

        return $inscricao !== null && $inscricao->inscrito_type === (new User())->getMorphClass()
            ? (int) $inscricao->inscrito_id
            : null;
    }

    /**
     * @param  list<int>  $inscricaoIds
     * @return list<array{titulo: string, versao_rotulo: string|null, emitido_em: string, codigo_verificacao: string|null}>
     */
    private function certificados(array $inscricaoIds): array
    {
        $certificados = Certificado::query()
            ->with('treinamento')
            ->whereIn('inscricao_id', $inscricaoIds)
            ->where('status', StatusCertificado::GERADO->value)
            ->orderByDesc('emitido_em')
            ->get();
        $rotulos = VersoesDoCurso::rotulos($certificados->pluck('treinamento'));

        return $certificados
            ->map(fn (Certificado $c) => [
                'titulo' => $c->treinamento->titulo,
                'versao_rotulo' => $rotulos[$c->treinamento->id] ?? null,
                'emitido_em' => ($c->emitido_em ?? $c->created_at)->toDateString(),
                'codigo_verificacao' => $c->codigo_verificacao,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Inscricao>  $inscricoes
     * @return list<array{titulo: string, versao_rotulo: string|null, progresso: int|null}>
     */
    private function emAndamento(Collection $inscricoes): array
    {
        $andamento = $inscricoes
            ->filter(fn (Inscricao $i) => $i->status === StatusInscricao::APROVADA && $i->concluido_em === null);
        $rotulos = VersoesDoCurso::rotulos($andamento->pluck('treinamento'));

        return $andamento
            ->map(fn (Inscricao $i) => [
                'titulo' => $i->treinamento->titulo,
                'versao_rotulo' => $rotulos[$i->treinamento->id] ?? null,
                'progresso' => (int) round($this->trilha->estado($i)->percentual),
            ])
            ->values()
            ->all();
    }

    /**
     * 123.456.789-09 -> ***.456.789-**
     */
    private function mascararCpf(string $cpf): string
    {
        $d = preg_replace('/\D/', '', $cpf);

        return strlen($d) === 11 ? '***.'.substr($d, 3, 3).'.'.substr($d, 6, 3).'-**' : '';
    }

    /**
     * @return array{qr_png_base64: string, url: string, gerado_em: string}
     */
    private function apresentar(CredencialQr $credencial): array
    {
        $url = route('treinamentos.validar-participante.consultar', $credencial->token);

        return [
            // Gerar o PNG custa CPU a cada abertura de Meus cursos; o token muda ao
            // regerar, entao a chave (hash do token) nunca serve um QR antigo.
            'qr_png_base64' => Cache::remember(
                'treinamento:credencial_qr:'.hash('sha256', $credencial->token),
                self::CACHE_QR_TTL,
                fn () => base64_encode($this->qr->gerarPng($url)),
            ),
            'url' => $url,
            'gerado_em' => $credencial->gerado_em->toIso8601String(),
        ];
    }

    private function novoToken(): string
    {
        return Str::random(40);
    }
}
