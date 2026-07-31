<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\EntradaCategoriaDesastre;
use App\Modules\Decretacoes\Models\EntradaDesastre;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\DTO\CampoData;
use App\Modules\Decretacoes\DTO\DesastreData;
use App\Modules\Decretacoes\DTO\DesastreSubmissionDTO;
use App\Modules\Decretacoes\DTO\ItemData;
use App\Modules\Decretacoes\DTO\MunicipioData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service consolidado para processamento de dados de desastres.
 *
 * FLUXO DE DADOS:
 *   DesastreSubmissionDTO -> DesastreDataService -> Multiplos Models -> Banco
 *
 * RESPONSABILIDADES:
 * - Processar submissoes de dados de desastre do formulario
 * - Persistir dados hierarquicos (Municipio > Categoria > Desastre > Campo)
 * - Gerenciar transacoes e rollback
 * - Remover duplicatas mantendo valor mais recente
 */
class DesastreDataService
{
    /**
     * Processa todos os dados de desastre de uma submissao.
     *
     * FLUXO: DTO -> Transacao -> Processamento por municipio -> Commit/Rollback
     *
     * @param DesastreSubmissionDTO $dto Dados completos da submissao
     * @param Processo $processo Processo pai
     * @return array{success: bool, message: string} Resultado da operacao
     */
    public function processDesastresData(DesastreSubmissionDTO $dto, Processo $processo): array
    {
        try {
            DB::beginTransaction();

            foreach ($dto->municipios as $municipioData) {
                $this->processMunicipio($municipioData, $processo);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Dados de desastres processados com sucesso',
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Erro ao processar dados de desastre', [
                'error' => $e->getMessage(),
                'processo_id' => $processo->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao processar dados de desastres: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Processa um municipio individual.
     *
     * FLUXO: MunicipioData -> Sync protocolo -> Iteracao categorias/desastres
     *
     * @param MunicipioData $municipioData Dados do municipio
     * @param Processo $processo Processo pai
     */
    private function processMunicipio(MunicipioData $municipioData, Processo $processo): void
    {
        $this->syncMunicipioProtocolo($municipioData, $processo);

        foreach ($municipioData->categorias as $categoriaData) {
            foreach ($categoriaData->desastres as $desastreData) {
                $this->processDesastre($municipioData, $desastreData, $processo);
            }
        }
    }

    /**
     * Processa um desastre individual.
     *
     * FLUXO: DesastreData -> EntradaCategoriaDesastre (upsert) -> Campos
     *
     * DESTINO: Tabela dec_entrada_categoria_desastres
     *
     * @param MunicipioData $municipioData Dados do municipio
     * @param DesastreData $desastreData Dados do desastre
     * @param Processo $processo Processo pai
     */
    private function processDesastre(MunicipioData $municipioData, DesastreData $desastreData, Processo $processo): void
    {
        // A descricao livre de areas/populacao afetada saiu do formulario de
        // Dados de Desastre: nao e mais gravada. O valor legado permanece na
        // coluna (nao sobrescrevemos com null) para nao perder historico.
        $entradaCategoria = EntradaCategoriaDesastre::updateOrCreate(
            [
                'municipio_id'        => $municipioData->id,
                'categoria_id'        => $desastreData->id,
                'entrada_processo_id' => $processo->id,
            ]
        );

        // Atualiza o ID no DTO para uso nos campos filhos
        $desastreData->entradaCategoriaDesastreId = $entradaCategoria->id;

        foreach ($desastreData->items as $itemData) {
            foreach ($itemData->campos as $campoData) {
                $this->persistDesastreCampo($municipioData, $desastreData, $itemData, $campoData, $processo);
            }
        }
    }

    /**
     * Sincroniza protocolo FIDE do municipio.
     *
     * FLUXO: MunicipioData.nProtocoloFide -> DecretoMunicipio (upsert)
     *
     * DESTINO: Tabela dec_decreto_municipios
     *
     * @param MunicipioData $municipioData Dados do municipio
     * @param Processo $processo Processo pai
     */
    private function syncMunicipioProtocolo(MunicipioData $municipioData, Processo $processo): void
    {
        if ($municipioData->nProtocoloFide === null) {
            return;
        }

        DecretoMunicipio::updateOrCreate(
            [
                'entrada_processos_id' => $processo->id,
                'municipio_id'         => $municipioData->id,
            ],
            [
                'n_protocolo_fide' => $municipioData->nProtocoloFide,
            ]
        );
    }

    /**
     * Persiste campo de desastre com deduplicacao e sincronizacao de timestamp.
     *
     * FLUXO: CampoData -> Formatacao -> EntradaDesastre (upsert) -> Deduplicacao -> Sync timestamp
     *
     * DESTINO: Tabela dec_entrada_desastres
     *
     * @param MunicipioData $municipioData Dados do municipio
     * @param DesastreData $desastreData Dados do desastre
     * @param ItemData $itemData Dados do item
     * @param CampoData $campoData Dados do campo
     * @param Processo $processo Processo pai
     */
    private function persistDesastreCampo(
        MunicipioData $municipioData,
        DesastreData $desastreData,
        ItemData $itemData,
        CampoData $campoData,
        Processo $processo
    ): void {
        if ($campoData->valor === null) {
            return;
        }

        // Formata valor baseado no tipo (number, currency, etc)
        $formattedValue = $campoData->getFormattedValue();

        $searchCriteria = [
            'municipio_id'                  => $municipioData->id,
            'item_campo_id'                 => $campoData->id,
            'entrada_processo_id'           => $processo->id,
            'item_id'                       => $itemData->id,
            'entrada_categoria_desastre_id' => $desastreData->entradaCategoriaDesastreId,
        ];

        $values = $searchCriteria + [
            'campo_titulo' => $campoData->titulo,
            'valor'        => $formattedValue,
        ];

        EntradaDesastre::updateOrCreate(
            ['id' => $campoData->entradaDesastreId],
            $values
        );

        // Remove duplicatas mantendo o mais recente
        $entradaDesastre = $this->deduplicate($searchCriteria);

        // Sincroniza timestamp do municipio
        if ($entradaDesastre) {
            DecretoMunicipio::where('entrada_processos_id', $processo->id)
                ->where('municipio_id', $municipioData->id)
                ->update(['updated_at' => $entradaDesastre->updated_at]);
        }
    }

    /**
     * Remove entradas duplicadas, mantendo o valor mais recente nao-zero.
     *
     * LOGICA:
     * 1. Busca todas entradas com mesmos criterios
     * 2. Prioriza entradas com valor != 0
     * 3. Mantem a mais recente (por updated_at, depois por id)
     * 4. Remove as demais
     *
     * @param array $searchCriteria Criterios de busca
     * @return EntradaDesastre|null Entrada mantida ou null
     */
    private function deduplicate(array $searchCriteria): ?EntradaDesastre
    {
        $entradas = EntradaDesastre::where($searchCriteria)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        if ($entradas->count() <= 1) {
            return $entradas->first();
        }

        // Prioriza entradas com valor nao-zerado
        $entradasNaoZeradas = $entradas->reject(fn($entrada) => $this->isZeroOrNull($entrada->valor));
        $entradaMantida = $entradasNaoZeradas->first() ?? $entradas->first();

        // Remove duplicatas
        $entradas->filter(fn($entrada) => $entrada->id !== $entradaMantida->id)
            ->each(fn($duplicado) => $duplicado->delete());

        return $entradaMantida;
    }

    /**
     * Verifica se valor e zero ou null.
     *
     * @param mixed $value Valor a verificar
     * @return bool True se zero ou null
     */
    private function isZeroOrNull(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $trimmed = trim((string) $value);

        return is_numeric($trimmed) && (float) $trimmed === 0.0;
    }
}
