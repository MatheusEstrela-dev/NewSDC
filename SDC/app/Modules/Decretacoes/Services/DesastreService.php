<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Models\Decreto\DecretoMunicipio;
use App\Models\Decreto\EntradaCategoriaDesastre;
use App\Models\Decreto\EntradaDesastre;
use App\Models\Decreto\EntradaProcesso;
use App\Modules\Decretacoes\DTOs\CampoData;
use App\Modules\Decretacoes\DTOs\DesastreData;
use App\Modules\Decretacoes\DTOs\DesastreSubmissionDTO;
use App\Modules\Decretacoes\DTOs\ItemData;
use App\Modules\Decretacoes\DTOs\MunicipioData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Consolidated service for processing disaster (desastre) data submissions.
 *
 * This service handles:
 * - Processing disaster submissions from form data
 * - Syncing municipality protocols
 * - Persisting disaster fields with deduplication
 * - Value formatting for different field types
 */
class DesastreService
{
    /**
     * Process all disaster data from form submission.
     *
     * @param array $data Form data array
     * @param EntradaProcesso $processo
     * @return array{success: bool, message: string}
     */
    public function processDesastresData(array $data, EntradaProcesso $processo): array
    {
        $dto = DesastreSubmissionDTO::fromArray($data);

        return $this->processSubmission($dto, $processo);
    }

    /**
     * Process the disaster submission DTO.
     *
     * @param DesastreSubmissionDTO $submissionDto
     * @param EntradaProcesso $processo
     * @return array{success: bool, message: string}
     */
    private function processSubmission(DesastreSubmissionDTO $submissionDto, EntradaProcesso $processo): array
    {
        try {
            DB::beginTransaction();

            foreach ($submissionDto->municipios as $municipioData) {
                $this->processMunicipio($municipioData, $processo);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Dados de desastres processados com sucesso',
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error processing disaster data', [
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
     * Process a single municipality with its categories and disasters.
     */
    private function processMunicipio(MunicipioData $municipioData, EntradaProcesso $processo): void
    {
        $this->syncMunicipioProtocolo($municipioData, $processo);

        foreach ($municipioData->categorias as $categoriaData) {
            foreach ($categoriaData->desastres as $desastreData) {
                $this->processDesastre($municipioData, $desastreData, $processo);
            }
        }
    }

    /**
     * Process a single disaster with its items and fields.
     */
    private function processDesastre(MunicipioData $municipioData, DesastreData $desastreData, EntradaProcesso $processo): void
    {
        $entradaCategoria = EntradaCategoriaDesastre::updateOrCreate(
            [
                'municipio_id'        => $municipioData->id,
                'categoria_id'        => $desastreData->id,
                'entrada_processo_id' => $processo->id,
            ],
            [
                'descricao' => $desastreData->descricao,
            ]
        );

        // Update the DTO with the generated ID for field persistence
        $desastreData->entradaCategoriaDesastreId = $entradaCategoria->id;

        foreach ($desastreData->items as $itemData) {
            foreach ($itemData->campos as $campoData) {
                $this->persistDesastreCampo(
                    $municipioData,
                    $desastreData,
                    $itemData,
                    $campoData,
                    $processo
                );
            }
        }
    }

    /**
     * Sync municipality protocol (FIDE) if provided.
     */
    private function syncMunicipioProtocolo(MunicipioData $municipioData, EntradaProcesso $processo): void
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
     * Persist a disaster field with deduplication and timestamp sync.
     */
    private function persistDesastreCampo(
        MunicipioData $municipioData,
        DesastreData $desastreData,
        ItemData $itemData,
        CampoData $campoData,
        EntradaProcesso $processo
    ): void {
        if ($campoData->valor === null) {
            return;
        }

        $formattedValue = $this->formatValue($campoData->valor, $campoData->tipo);

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

        // Deduplicate entries
        $entradaDesastre = $this->deduplicate($searchCriteria);

        // Update municipio timestamp
        if ($entradaDesastre) {
            DecretoMunicipio::where('entrada_processos_id', $processo->id)
                ->where('municipio_id', $municipioData->id)
                ->update(['updated_at' => $entradaDesastre->updated_at]);
        }
    }

    /**
     * Remove duplicate entries, keeping the most recent non-zero value.
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

        $entradasNaoZeradas = $entradas->reject(fn($entrada) => $this->isZeroOrNull($entrada->valor));
        $entradaMantida = $entradasNaoZeradas->first() ?? $entradas->first();

        // Remove duplicates
        $entradas->filter(fn($entrada) => $entrada->id !== $entradaMantida->id)
            ->each(fn($duplicado) => $duplicado->delete());

        return $entradaMantida;
    }

    /**
     * Check if a value is zero or null.
     */
    private function isZeroOrNull(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $trimmed = trim((string) $value);

        return is_numeric($trimmed) && (float) $trimmed === 0.0;
    }

    /**
     * Format a value based on its type.
     *
     * @param mixed $value The value to format
     * @param string $type The type: 'number', 'currency', or other
     * @return mixed The formatted value
     */
    private function formatValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'number'   => (int) str_replace('.', '', (string) $value),
            'currency' => (float) str_replace(',', '.', str_replace('.', '', (string) $value)),
            default    => $value,
        };
    }
}
