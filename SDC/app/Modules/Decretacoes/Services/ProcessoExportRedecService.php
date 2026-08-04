<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Enums\Redec;
use App\Modules\Decretacoes\Filters\ProcessoFilter;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Support\Vigencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Exportacao das decretacoes agrupadas por REDEC.
 *
 * FLUXO: Request (filtros da tela + redec_id do modal)
 *        -> ProcessoQueryService::applyFilters()
 *        -> resolucao dos municipios de cada processo
 *        -> uma linha por processo x municipio, ordenada por REDEC
 *
 * DESTINO: GET /decretacoes/export/redec (botao "Exportar por REDEC")
 *
 * POR QUE NAO REUSA ProcessoExportService: aquele export existe para o Power BI
 * e le os municipios pela relacao `Processo::municipios()`, que junta
 * `municipios.id` = `dec_decreto_municipios.municipio_id`. Esse join descarta os
 * vinculos legados, cujo `municipio_id` guarda o id do cadastro da CEDEC - e o
 * municipio e justamente o que define a REDEC. Aqui a resolucao usa a ponte
 * confiavel (codigo IBGE embutido no protocolo FIDE), com `municipio_id` como
 * alternativa para os vinculos gravados pelo formulario atual. Mesma regra de
 * ProcessoFilter::orWhereVinculadoAosMunicipios().
 */
class ProcessoExportRedecService
{
    /** Expressao do codigo IBGE embutido no protocolo FIDE do vinculo. */
    private const SQL_IBGE_DO_VINCULO = "split_part(COALESCE(dm.n_protocolo_fide, ''), '-', 3)";

    public function __construct(
        private readonly ProcessoQueryService $queryService
    ) {
    }

    /**
     * Linhas do CSV: uma por processo x municipio.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLinhasPorRedec(Request $request): array
    {
        // `without`: o model traz `municipios`/`desastres` em $with por padrao e
        // aqui nenhum dos dois e usado - os municipios sao resolvidos abaixo.
        $processos = $this->queryService
            ->applyFilters($request)
            ->without(['municipios', 'desastres'])
            ->get();

        if ($processos->isEmpty()) {
            return [];
        }

        $municipiosPorProcesso = $this->resolveMunicipios($processos->pluck('id')->all());
        $redecPorMunicipioId   = ProcessoFilter::getRedecPorMunicipioId();

        $linhas = [];

        foreach ($processos as $processo) {
            $municipios = $municipiosPorProcesso[(int) $processo->id] ?? [];

            // Processo sem municipio vinculado ainda precisa aparecer: sai com a
            // REDEC do proprio processo e as colunas de municipio vazias.
            if (empty($municipios)) {
                $linhas[] = $this->montaLinha($processo, null, $redecPorMunicipioId);
                continue;
            }

            foreach ($municipios as $municipio) {
                $linhas[] = $this->montaLinha($processo, $municipio, $redecPorMunicipioId);
            }
        }

        return $this->ordenaPorRedec($linhas);
    }

    /**
     * Municipios de cada processo, resolvidos nos dois espacos de id numa unica
     * query.
     *
     * O LEFT JOIN escolhe a ponte por linha: protocolo com IBGE de 7 digitos
     * resolve por `municipios.codigo_ibge`; caso contrario por `municipios.id`.
     *
     * @param array<int, int> $processoIds
     * @return array<int, array<int, array<string, mixed>>> processo_id => municipios
     */
    private function resolveMunicipios(array $processoIds): array
    {
        if (empty($processoIds)) {
            return [];
        }

        $ibge = self::SQL_IBGE_DO_VINCULO;

        $rows = DB::table('dec_decreto_municipios as dm')
            ->leftJoin('municipios as m', function ($join) use ($ibge) {
                $join->whereRaw(
                    "((length({$ibge}) = 7 and m.codigo_ibge = {$ibge})"
                    . " or (length({$ibge}) <> 7 and m.id = dm.municipio_id))"
                );
            })
            ->whereIn('dm.entrada_processos_id', $processoIds)
            ->whereNull('dm.deleted_at')
            ->select(
                'dm.entrada_processos_id as processo_id',
                'dm.municipio_id',
                'dm.n_protocolo_fide as protocolo_municipio',
                'm.id as municipio_real_id',
                'm.nome as municipio_nome',
                'm.codigo_ibge',
                'm.uf'
            )
            ->get();

        $resultado = [];

        foreach ($rows as $row) {
            $processoId = (int) $row->processo_id;
            // Chave por municipio para nao duplicar linha quando o mesmo
            // municipio aparece em mais de um vinculo do mesmo processo.
            $chave = $row->municipio_real_id !== null
                ? 'm' . $row->municipio_real_id
                : 'c' . $row->municipio_id;

            $resultado[$processoId][$chave] = [
                'id'                  => $row->municipio_real_id !== null ? (int) $row->municipio_real_id : null,
                'nome'                => $row->municipio_nome,
                'codigo_ibge'         => $row->codigo_ibge,
                'uf'                  => $row->uf ?? 'MG',
                'protocolo_municipio' => $row->protocolo_municipio,
            ];
        }

        foreach ($resultado as &$municipios) {
            usort($municipios, fn ($a, $b) => strcmp((string) $a['nome'], (string) $b['nome']));
            $municipios = array_values($municipios);
        }

        return $resultado;
    }

    /**
     * REDEC de uma linha, na ordem de confianca: a gravada no processo, depois a
     * do municipio (cadastro CEDEC por IBGE ou historico do modulo).
     *
     * @param array<string, mixed>|null $municipio
     * @param array<int, int> $redecPorMunicipioId
     */
    private function resolveRedecId(Processo $processo, ?array $municipio, array $redecPorMunicipioId): ?int
    {
        if ($processo->redec_id) {
            return (int) $processo->redec_id;
        }

        if ($municipio === null) {
            return null;
        }

        $porIbge = ProcessoFilter::getRedecPorIbge();

        if (! empty($municipio['codigo_ibge']) && isset($porIbge[$municipio['codigo_ibge']])) {
            return (int) $porIbge[$municipio['codigo_ibge']];
        }

        if ($municipio['id'] !== null && isset($redecPorMunicipioId[$municipio['id']])) {
            return (int) $redecPorMunicipioId[$municipio['id']];
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $municipio
     * @param array<int, int> $redecPorMunicipioId
     * @return array<string, mixed>
     */
    private function montaLinha(Processo $processo, ?array $municipio, array $redecPorMunicipioId): array
    {
        $redecId = $this->resolveRedecId($processo, $municipio, $redecPorMunicipioId);
        $redec   = $redecId !== null ? Redec::tryFrom($redecId) : null;

        $prazo      = $processo->getAttributes()['prazo_vigencia'] ?? null;
        $publicacao = $processo->data_publicacao_mg;

        return [
            'redec_id'               => $redecId,
            'redec'                  => $redec?->sigla(),
            'redec_regiao'           => $redec?->regiao(),
            'uf'                     => $municipio['uf'] ?? 'MG',
            'municipio'              => $municipio['nome'] ?? null,
            'codigo_ibge'            => $municipio['codigo_ibge'] ?? null,
            'processo_id'            => $processo->id,
            'protocolo'              => $processo->n_protocolo_fide,
            'protocolo_municipio'    => $municipio['protocolo_municipio'] ?? null,
            'tipo_processo'          => $processo->processo,
            'data_entrada'           => $this->data($processo->data_entrada),
            'data_ocorrencia'        => $this->data($processo->data_ocorrencia_desastre),
            'cobrade'                => $processo->tipo_desastre_cobrade,
            'tipo_desastre'          => $processo->tipo_desastre_nome,
            'situacao_anormalidade'  => $processo->situacao_anormalidade,
            'status'                 => $this->statusEfetivo($processo),
            'decreto_municipal'      => $processo->decreto_municipal,
            'data_decreto_municipal' => $this->data($processo->data_decreto_municipal),
            'data_publicacao_mg'     => $this->data($publicacao),
            'prazo_vigencia_dias'    => Vigencia::prazo($prazo),
            'data_vencimento'        => $this->data(Vigencia::vencimento($publicacao, $prazo)),
            'dias_restantes'         => Vigencia::diasRestantes($publicacao, $prazo),
            'situacao_vigencia'      => $this->situacaoVigencia($publicacao, $prazo),
            'analista'               => $processo->analista,
        ];
    }

    /**
     * Status efetivo do processo: `reconhecimento` (legado) ou, se vazio,
     * `status` (atual). Mesma regra de ProcessoFilter::sqlStatusEfetivo(), para
     * que o CSV mostre o mesmo status pelo qual a listagem filtra.
     */
    private function statusEfetivo(Processo $processo): ?string
    {
        $legado = trim((string) ($processo->reconhecimento ?? ''));

        return $legado !== '' ? $legado : ($processo->status ?: null);
    }

    private function situacaoVigencia(mixed $publicacao, mixed $prazo): string
    {
        if (empty($publicacao)) {
            return 'Sem data de publicacao';
        }

        if (Vigencia::isVencido($publicacao, $prazo)) {
            return 'Vencido';
        }

        return Vigencia::isProximoVencer($publicacao, $prazo) ? 'Proximo ao vencimento' : 'Vigente';
    }

    /** Datas no CSV saem em ISO (AAAA-MM-DD), estavel para Excel e Power BI. */
    private function data(mixed $valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        return substr((string) $valor, 0, 10);
    }

    /**
     * Ordena por REDEC (sem REDEC no fim), municipio e entrada mais recente.
     *
     * @param array<int, array<string, mixed>> $linhas
     * @return array<int, array<string, mixed>>
     */
    private function ordenaPorRedec(array $linhas): array
    {
        usort($linhas, function (array $a, array $b) {
            // Linhas sem REDEC vao para o fim, em vez de encabecar a planilha.
            $redecA = $a['redec_id'] ?? PHP_INT_MAX;
            $redecB = $b['redec_id'] ?? PHP_INT_MAX;

            return [$redecA, (string) $a['municipio'], (string) $b['data_entrada']]
               <=> [$redecB, (string) $b['municipio'], (string) $a['data_entrada']];
        });

        return $linhas;
    }
}
