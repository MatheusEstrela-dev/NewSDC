<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Resources;

use App\Modules\Decretacoes\Enums\Redec;
use App\Modules\Decretacoes\Support\Vigencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Processo - substitui o ProcessoMapper.
 *
 * FLUXO DE DADOS (SAIDA):
 *   Processo (Model) -> ProcessoResource -> JSON (API/Frontend)
 *
 * PROTECAO: Usa safeGet() para nao quebrar se coluna for removida.
 * Se remover uma coluna do banco, apenas este arquivo precisa ser ajustado.
 *
 * USO:
 *   return ProcessoResource::make($processo);           // Um registro
 *   return ProcessoResource::collection($processos);   // Collection
 */
class ProcessoResource extends JsonResource
{
    /**
     * Transforma o resource em array para resposta JSON.
     *
     * DESTINO: API REST / Frontend (Inertia/Vue)
     *
     * @param Request $request Request HTTP atual
     * @return array Dados formatados para o frontend
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'processo' => $this->safeGet('processo'),
            'origem' => $this->safeGet('processo'), // Alias para frontend (municipal/estadual)
            'tipo_decreto' => $this->safeGet('tipo_decreto'),
            'status' => $this->safeGet('status'),
            'protocolo_fide' => $this->safeGet('n_protocolo_fide'),
            'decreto_municipal' => $this->safeGet('decreto_municipal'),
            'redec_id' => $this->safeGetInt('redec_id'),
            'redec_label' => Redec::labelFor($this->safeGet('redec_id')),
            'municipio_id' => $this->getFirstMunicipioId(), // Primeiro municipio para edicao

            // Datas - formatadas para API (ISO) e para exibicao (BR)
            'data_entrada' => $this->formatDate('data_entrada'),
            'data_entrada_formatada' => $this->formatDate('data_entrada', 'd/m/Y'),
            'data_ocorrencia' => $this->formatDate('data_ocorrencia_desastre'),
            'data_ocorrencia_desastre' => $this->formatDate('data_ocorrencia_desastre'),
            'data_decreto_municipal' => $this->formatDate('data_decreto_municipal'),
            'data_publicacao_mg' => $this->formatDate('data_publicacao_mg'),
            'data_decreto_estadual' => $this->formatDate('data_decreto_estadual'),
            'data_portaria_federal' => $this->formatDate('data_portaria_federal'),
            'data_publicacao_diario' => $this->formatDate('data_publicacao_diario'),
            'data_publicacao_domg' => $this->formatDate('data_publicacao_domg'),

            // Vigencia - campos calculados (regra em Support\Vigencia; padrao de 180 dias)
            'prazo_vigencia' => $this->safeGetInt('prazo_vigencia'),
            'prazo_vigencia_efetivo' => Vigencia::prazo($this->safeGet('prazo_vigencia')),
            'prazo_vigencia_padrao' => Vigencia::usouPrazoPadrao($this->safeGet('prazo_vigencia')),
            'data_vencimento' => $this->getDataVencimento()?->format('Y-m-d'),
            'data_vencimento_formatada' => $this->getDataVencimento()?->format('d/m/Y'),
            'dias_restantes' => $this->getDiasRestantes(),
            'vigente' => $this->isVigente(),
            'vencido' => $this->isVencido(),
            'proximo_vencer' => $this->isProximoVencer(),

            // Desastre - dados basicos
            'tipo_desastre_id' => $this->safeGetInt('tipo_desastre_id'),
            'cobrade_id' => $this->safeGetInt('tipo_desastre_id'), // Alias para frontend
            'tipo_desastre_nome' => $this->safeGet('tipo_desastre_nome') ?? $this->safeGet('tipo_desastre'),
            'tipo_desastre_cobrade' => $this->getTipoDesastreCobrade(),

            // Desastre - dados completos (quando carregados)
            'tipo_desastre' => $this->safeGet('tipo_desastre'),
            'tipo_desastre_completo' => $this->safeGet('tipo_desastre_completo'),
            'tipo_desastre_info' => $this->safeGet('tipo_desastre_completo'),

            // Totais de desastres (quando carregados)
            'totais' => $this->safeGet('totais'),

            // Pedidos de ajuda humanitaria (quando carregados)
            'pedidos_ah' => $this->formatPedidosAh(),

            // Outros campos
            'analista' => $this->safeGet('analista'),
            'reconhecimento' => $this->safeGet('reconhecimento'),
            // Status efetivo: `reconhecimento` (legado) ou `status` (formulario atual)
            'status_efetivo' => $this->safeGet('reconhecimento') ?? $this->safeGet('status'),
            'observacoes' => $this->safeGet('observacoes'),
            'orgao_responsavel_id' => $this->safeGetInt('orgao_responsavel_id'),
            'n_protocolo_fide' => $this->safeGet('n_protocolo_fide'),
            'processo_inserido_sei' => $this->safeGet('processo_inserido_sei'),
            'situacao_anormalidade' => $this->safeGet('tipo_desastre'),

            // Campos para edicao - Decreto Estadual
            'n_decreto_estadual' => $this->safeGet('n_decreto_estadual'),
            'n_edicao_domg' => $this->safeGet('n_edicao_domg'),

            // Campos para edicao - Reconhecimento Federal
            'portaria_reconhecimento_fed' => $this->safeGet('portaria_reconhecimento_fed'),
            'n_portaria_federal' => $this->safeGet('portaria_reconhecimento_fed'),
            'portaria_diario_oficial' => $this->safeGet('portaria_diario_oficial'),
            'n_edicao_dou' => $this->safeGet('portaria_diario_oficial'),
            'reconhecimento_federal' => $this->safeGet('reconhecimento_federal'),

            // Relacionamentos - sempre incluidos (Model tem $with)
            'municipios' => $this->mapMunicipios(),
            'desastres' => $this->mapDesastres(),
            'municipios_count' => count($this->mapMunicipios()),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Formato reduzido para listagens (tabelas, cards).
     *
     * DESTINO: Listagens no frontend (index, dashboard)
     *
     * @return array Dados minimos para exibicao em lista
     */
    public function toListArray(): array
    {
        return [
            'id' => $this->id,
            'processo' => $this->safeGet('processo'),
            'tipo_decreto' => $this->safeGet('tipo_decreto'),
            'status' => $this->safeGet('status'),
            'reconhecimento' => $this->safeGet('reconhecimento'),
            'status_efetivo' => $this->safeGet('reconhecimento') ?? $this->safeGet('status'),
            'protocolo_fide' => $this->safeGet('n_protocolo_fide'),
            'n_protocolo_fide' => $this->safeGet('n_protocolo_fide'),
            'data_entrada' => $this->formatDate('data_entrada'),
            'data_entrada_formatada' => $this->formatDate('data_entrada', 'd/m/Y'),
            'data_vencimento' => $this->getDataVencimento()?->format('Y-m-d'),
            'data_vencimento_formatada' => $this->getDataVencimento()?->format('d/m/Y'),
            'dias_restantes' => $this->getDiasRestantes(),
            'prazo_vigencia_efetivo' => Vigencia::prazo($this->safeGet('prazo_vigencia')),
            'vigente' => $this->isVigente(),
            'vencido' => $this->isVencido(),
            'proximo_vencer' => $this->isProximoVencer(),
            'tipo_desastre_nome' => $this->safeGet('tipo_desastre_nome') ?? $this->safeGet('tipo_desastre'),
            'tipo_desastre_cobrade' => $this->getTipoDesastreCobrade(),
            'analista' => $this->safeGet('analista'),
            'municipios_count' => $this->relationLoaded('municipios') ? $this->municipios->count() : 0,
        ];
    }

    // =========================================================================
    // METODOS SAFE - Protegem contra colunas removidas do banco
    // =========================================================================

    /**
     * Obtem valor com fallback seguro.
     * Nao quebra se a coluna nao existir no banco.
     *
     * @param string $key Nome da coluna
     * @param mixed $default Valor padrao se coluna nao existir
     * @return mixed Valor da coluna ou default
     */
    protected function safeGet(string $key, mixed $default = null): mixed
    {
        try {
            return $this->resource->getAttribute($key) ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Obtem valor como inteiro com fallback.
     *
     * @param string $key Nome da coluna
     * @param int|null $default Valor padrao
     * @return int|null Valor convertido para int
     */
    protected function safeGetInt(string $key, ?int $default = null): ?int
    {
        $value = $this->safeGet($key);
        return $value !== null ? (int) $value : $default;
    }

    /**
     * Formata data com fallback seguro.
     *
     * @param string $key Nome da coluna de data
     * @param string $format Formato de saida (padrao: Y-m-d)
     * @return string|null Data formatada ou null
     */
    protected function formatDate(string $key, string $format = 'Y-m-d'): ?string
    {
        $value = $this->safeGet($key);
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if ($value instanceof Carbon) {
                return $value->format($format);
            }
            // Tenta formato ISO primeiro
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                return Carbon::parse($value)->format($format);
            }
            // Tenta formato BR (dd/mm/yyyy)
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $value, $matches)) {
                return Carbon::createFromFormat('d/m/Y', $matches[0])->format($format);
            }
            // Fallback
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return null;
        }
    }

    // =========================================================================
    // METODOS CALCULADOS - Logica de negocios para vigencia
    // =========================================================================

    /**
     * Calcula data de vencimento do decreto.
     * Formula: data_publicacao_mg + prazo_vigencia (180 dias quando ausente).
     *
     * @return Carbon|null Data de vencimento ou null sem data de publicacao
     */
    protected function getDataVencimento(): ?Carbon
    {
        return Vigencia::vencimento($this->safeGet('data_publicacao_mg'), $this->safeGet('prazo_vigencia'));
    }

    /**
     * Calcula dias restantes ate o vencimento (assinado).
     *
     * @return int|null Negativo = vencido, 0 = vence hoje, null = sem publicacao
     */
    protected function getDiasRestantes(): ?int
    {
        return Vigencia::diasRestantes($this->safeGet('data_publicacao_mg'), $this->safeGet('prazo_vigencia'));
    }

    /**
     * Verifica se o decreto esta vigente (inclui o dia do vencimento).
     *
     * @return bool True enquanto nao passou do vencimento
     */
    public function isVigente(): bool
    {
        return Vigencia::isVigente($this->safeGet('data_publicacao_mg'), $this->safeGet('prazo_vigencia'));
    }

    /**
     * Verifica se o decreto ja venceu.
     */
    public function isVencido(): bool
    {
        return Vigencia::isVencido($this->safeGet('data_publicacao_mg'), $this->safeGet('prazo_vigencia'));
    }

    /**
     * Verifica se o decreto esta proximo de vencer (30 dias).
     *
     * @return bool True se vence em ate 30 dias
     */
    public function isProximoVencer(): bool
    {
        return Vigencia::isProximoVencer($this->safeGet('data_publicacao_mg'), $this->safeGet('prazo_vigencia'));
    }

    /**
     * Obtem codigo COBRADE do tipo de desastre.
     *
     * Prefere a coluna `cobrade` gravada no processo (padrao nacional
     * persistido); cai no enum de classificacao quando a linha ainda nao tem o
     * codigo preenchido.
     *
     * @return string|null Codigo COBRADE ou null
     */
    protected function getTipoDesastreCobrade(): ?string
    {
        $gravado = trim((string) ($this->safeGet('cobrade') ?? ''));

        if ($gravado !== '') {
            return $gravado;
        }

        $tipoDesastreId = $this->safeGetInt('tipo_desastre_id');
        if (!$tipoDesastreId) {
            return null;
        }

        try {
            $cobrade = include app_path('Enums/classificacao_desastres.php');
            foreach ($cobrade as $item) {
                if (($item['id'] ?? null) == $tipoDesastreId) {
                    return $item['cobrade'] ?? null;
                }
            }
        } catch (\Throwable) {
        }

        return null;
    }

    // =========================================================================
    // MAPEAMENTO DE RELACIONAMENTOS
    // =========================================================================

    /**
     * Obtem ID do primeiro municipio para edicao.
     *
     * @return int|null ID do primeiro municipio ou null
     */
    protected function getFirstMunicipioId(): ?int
    {
        try {
            $municipios = $this->mapMunicipios();

            return !empty($municipios) ? (int) $municipios[0]['id'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Mapeia municipios para array simplificado.
     *
     * DESTINO: Frontend (lista de municipios do processo)
     *
     * @return array Lista de municipios com id, nome e codigo_ibge
     */
    protected function mapMunicipios(): array
    {
        try {
            $preloadedMunicipios = $this->resource->getAttribute('_municipios');

            if (is_array($preloadedMunicipios) && !empty($preloadedMunicipios)) {
                return array_values(array_map(fn($m) => [
                    'id' => $m['id'] ?? null,
                    'nome' => $m['nome'] ?? null,
                    'codigo_ibge' => $m['codigo_ibge'] ?? null,
                ], $preloadedMunicipios));
            }

            return $this->municipios->map(fn($m) => [
                'id' => $m->id,
                'nome' => $m->nome,
                'codigo_ibge' => $m->codigo_ibge ?? null,
            ])->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Mapeia desastres para array simplificado.
     *
     * DESTINO: Frontend (lista de desastres do processo)
     *
     * @return array Lista de desastres com id, categoria_id e descricao
     */
    protected function mapDesastres(): array
    {
        try {
            return $this->desastres->map(fn($d) => [
                'id' => $d->id,
                'categoria_id' => $d->categoria_id ?? null,
                'descricao' => $d->descricao ?? null,
            ])->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Formata pedidos de ajuda humanitaria para o frontend.
     *
     * DESTINO: Tab de Pedidos AH no modal de detalhes
     *
     * @return array Lista de pedidos formatados
     */
    protected function formatPedidosAh(): array
    {
        $pedidosAh = $this->safeGet('pedidos_ah');

        if (!$pedidosAh || !is_iterable($pedidosAh)) {
            return [];
        }

        $result = [];
        foreach ($pedidosAh as $codigo => $items) {
            if (!is_iterable($items)) {
                continue;
            }
            foreach ($items as $item) {
                $result[] = [
                    'id' => $codigo,
                    'numero' => $item['codigo'] ?? $codigo,
                    'tipo' => $item['descricao_item'] ?? 'Ajuda Humanitaria',
                    'status' => $item['status'] ?? 'N/A',
                    'tp_item' => $item['tp_item'] ?? null,
                    'quantidade' => $item['total_qtd'] ?? 0,
                ];
            }
        }

        return $result;
    }
}
