<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\PowerBi\Support\SaidaBi;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Serve o formato de GET /api/v1/bi/entrada a partir de
 * bi_decretacoes.v_processos (sdc_powerbi), sem tocar o sdc.
 *
 * So atende a chamada sem filtros (alem de include_deleted); com filtro devolve
 * null e o controller cai no caminho ao vivo, que conhece os filtros do FIDE.
 */
final class DecretacoesLeituraMaterializada
{
    private const PARAMETROS_NEUTROS = ['bi-access', 'include_deleted'];

    private const COLUNAS_DATA = [
        'data_registro', 'data_fato', 'data_decreto_municipal', 'data_publicacao_mg', 'data_vencimento',
    ];

    private const COLUNAS_INTEIRAS = [
        'id', 'redec_id', 'prazo_vigencia_dias', 'dias_restantes', 'obitos', 'feridos', 'desalojados',
        'desabrigados', 'desaparecidos', 'outros_afetados', 'danos_humanos_quantidade',
        'danos_materiais_danificadas', 'danos_materiais_destruidas', 'danos_ambientais_marcados',
    ];

    private const COLUNAS_DECIMAIS = [
        'latitude_dec', 'longitude_dec', 'danos_materiais_valor', 'prejuizos_publicos_valor', 'prejuizos_privados_valor',
    ];

    /** Esta request pode ser atendida pelo materializado? */
    public function atende(Request $request): bool
    {
        return SaidaBi::materializada('decretacoes') && $request->except(self::PARAMETROS_NEUTROS) === [];
    }

    /**
     * @return list<array<string, mixed>> linhas no formato de ProcessoExportBIService
     */
    public function linhas(Request $request): array
    {
        $consulta = ConexaoPowerBi::leitura()->table('bi_decretacoes.v_processos')
            ->orderByDesc('data_registro')->orderByDesc('id')->orderBy('municipio_id');

        if (! $request->input('include_deleted', false)) {
            $consulta->where('deletado', false);
        }

        return $consulta->get()->map(fn (object $l): array => $this->formatar((array) $l))->all();
    }

    /** @return array<string, mixed> */
    private function formatar(array $l): array
    {
        $saida = [];
        foreach (self::ORDEM as $coluna) {
            $saida[$coluna] = $this->valor($coluna, $l);
        }

        return $saida;
    }

    private function valor(string $coluna, array $l): mixed
    {
        $v = $l[$coluna] ?? null;

        return match (true) {
            $coluna === 'latitude' => $this->grau($l['latitude_dec'] ?? null),
            $coluna === 'longitude' => $this->grau($l['longitude_dec'] ?? null),
            $v === null => null,
            in_array($coluna, self::COLUNAS_DATA, true) => $this->data((string) $v),
            in_array($coluna, ['data_criacao', 'data_delecao'], true) => Carbon::parse((string) $v)->jsonSerialize(),
            $coluna === 'deletado' => (bool) $v,
            in_array($coluna, self::COLUNAS_INTEIRAS, true) => (int) $v,
            in_array($coluna, self::COLUNAS_DECIMAIS, true) => (float) $v,
            default => $v,
        };
    }

    /** Parte inteira seguida de ponto, como o export ao vivo sempre fez. */
    private function grau(mixed $decimal): ?string
    {
        return $decimal !== null ? (string) intval((float) $decimal).'.' : null;
    }

    /** Date do Eloquent serializa como meia-noite no fuso da app, em UTC. */
    private function data(string $v): string
    {
        return Carbon::parse($v, config('app.timezone'))->startOfDay()->jsonSerialize();
    }

    /** Ordem das chaves do export ao vivo (relatorios do Power BI mapeiam por ela). */
    private const ORDEM = [
        'id', 'uf', 'municipio', 'codigo_ibge', 'macroregiao', 'latitude', 'longitude', 'latitude_dec',
        'longitude_dec', 'data_registro', 'data_criacao', 'deletado', 'data_delecao', 'redec_id', 'redec',
        'protocolo', 'cobrade', 'tipo_desastre', 'status', 'data_fato', 'data_decreto_municipal',
        'data_publicacao_mg', 'prazo_vigencia_dias', 'data_vencimento', 'dias_restantes', 'tipo_decreto',
        'processo', 'analista', 'obitos', 'feridos', 'desalojados', 'desabrigados', 'desaparecidos',
        'outros_afetados', 'danos_humanos_quantidade', 'danos_materiais_danificadas',
        'danos_materiais_destruidas', 'danos_materiais_valor', 'prejuizos_publicos_valor',
        'prejuizos_privados_valor', 'danos_ambientais_marcados',
        'danos_ambientais_agua', 'danos_ambientais_agua_intensidade',
        'danos_ambientais_ar', 'danos_ambientais_ar_intensidade',
        'danos_ambientais_solo', 'danos_ambientais_solo_intensidade',
        'danos_ambientais_hidrico', 'danos_ambientais_hidrico_intensidade',
        'danos_ambientais_incendio', 'danos_ambientais_incendio_intensidade',
    ];
}
