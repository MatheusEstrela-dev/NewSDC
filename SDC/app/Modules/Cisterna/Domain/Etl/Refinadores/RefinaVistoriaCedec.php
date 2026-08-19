<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\DeduplicaVistorias;
use App\Modules\Cisterna\Domain\Etl\MapaItensLegado;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Diferente do COMPDEC, sinc_cisterna_rel_cedec aponta direto para a cisterna
 * (coluna cisterna_id). E a unica etapa com dados administrativos.
 */
class RefinaVistoriaCedec implements Refinador
{
    public function __construct(
        private readonly DeduplicaVistorias $dedup,
    ) {}

    public function recurso(): string
    {
        return 'vistorias';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_rel_cedec';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        // Reenvio do mesmo formulario. O legado nao prevenia double-submit, e o
        // dominio tem UNIQUE (beneficiario_id, etapa): sem descartar a copia
        // aqui, ela chega no insert e vira violacao de constraint. Medido na
        // carga real: 675 linhas para 658 beneficiarios.
        if (! in_array($legacyId, $this->vencedores(), true)) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Reenvio do mesmo formulario: outra linha do mesmo beneficiario e mais completa.');

            return;
        }
        $beneficiarioId = CisternaBeneficiario::where('legacy_id', (int) ($doc['cisterna_id'] ?? 0))->value('id');

        if ($beneficiarioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Beneficiario de origem '.($doc['cisterna_id'] ?? 'null').' nao encontrado.', $doc);

            return;
        }

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria fiscalizacao da CEDEC para o beneficiario {$beneficiarioId}.");

            return;
        }

        $crea = trim((string) ($doc['crea_mg'] ?? ''));

        $atributos = [
            'beneficiario_id' => (int) $beneficiarioId,
            'etapa' => EtapaVistoria::CEDEC->value,
            'numero_instalacao' => null,
            'engenheiro_crea' => $crea === '' ? null : mb_substr($crea, 0, 30),
            'engenheiro_art' => $this->texto($doc['art'] ?? null, 50),
            'data_relatorio' => $this->data($doc['data_relatorio'] ?? null),
            'local_relatorio' => $this->texto($doc['local_relatorio'] ?? null, 255),

            // Exclusivos da etapa CEDEC.
            'processo_sei' => $this->texto($doc['processo_sei'] ?? null, 100),
            'contrato' => $this->texto($doc['contrato'] ?? null, 100),
            'empenho' => $this->texto($doc['empenho'] ?? null, 100),
            'placa_obras' => $this->inteiroOuNulo($doc['placa_obras'] ?? null),

            'concluida_em' => $crea === ''
                ? null
                : ($this->data($doc['data_relatorio'] ?? null) ?? now()->toDateTimeString()),
            'legacy_id' => $legacyId,
        ];

        try {
            DB::transaction(function () use ($atributos, $doc, $legacyId): void {
                $existente = CisternaVistoria::where('etapa', EtapaVistoria::CEDEC->value)
                    ->where('legacy_id', $legacyId)
                    ->first();

                $vistoria = $existente ?? CisternaVistoria::create($atributos);

                if ($existente !== null) {
                    $existente->update($atributos);
                }

                $vistoria->itensConferidos()->delete();

                foreach (MapaItensLegado::paraEtapa(EtapaVistoria::CEDEC, $doc) as $linha) {
                    $vistoria->itensConferidos()->create($linha);
                }

                $existente === null
                    ? RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $vistoria->id)
                    : RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $vistoria->id);
            });
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar fiscalizacao da CEDEC: '.$e->getMessage(), $doc);
        }
    }

    private function texto(mixed $valor, int $limite): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function inteiroOuNulo(mixed $valor): ?int
    {
        $digitos = preg_replace('/\D/', '', (string) ($valor ?? '')) ?? '';

        return $digitos === '' ? null : (int) $digitos;
    }

    private function data(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($texto)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * legacy_ids que sobrevivem a deduplicacao, memoizados: a lista sai de uma
     * varredura da tabela inteira e nao muda no meio da carga.
     *
     * @var array<int, int>|null
     */
    private ?array $vencedores = null;

    /**
     * @return array<int, int>
     */
    private function vencedores(): array
    {
        return $this->vencedores ??= $this->dedup->vencedores($this->tabelaLegado(), 'cisterna_id');
    }
}
