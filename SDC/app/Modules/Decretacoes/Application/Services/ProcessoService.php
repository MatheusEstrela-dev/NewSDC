<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\Services;

use App\Modules\Decretacoes\Application\DTOs\ProcessoInputDTO;
use App\Modules\Decretacoes\Application\DTOs\ProcessoOutputDTO;
use App\Modules\Decretacoes\Domain\Entities\Processo;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;

/**
 * Service: Lógica de negócio e conversão para Processo
 * Seguindo papiro.md - LÓGICA E MAPPING
 */
class ProcessoService
{
    public function __construct(
        private readonly ProcessoRepositoryInterface $repository
    ) {
    }

    /**
     * Cadastrar novo processo
     * A. Lógica de Negócio (geração de protocolo)
     * B. Conversão DTO -> Model
     * C. Conversão Model -> DTO de Saída
     */
    public function cadastrar(ProcessoInputDTO $data): ProcessoOutputDTO
    {
        try {
            // A. Lógica de Negócio: Gerar protocolo FIDE
            $protocolo = $this->gerarProtocoloFide($data);

            // B. Conversão DTO -> Model (persistência)
            $modelData = $data->toArray();
            $modelData['n_protocolo_fide'] = $protocolo;

            $processo = $this->repository->create($modelData);

            // C. Conversão Model -> DTO de Saída
            return ProcessoOutputDTO::created(
                id: $processo->id,
                protocolo: $processo->n_protocolo_fide,
                createdAt: $processo->created_at->format('d/m/Y H:i'),
            );
        } catch (\Exception $e) {
            return ProcessoOutputDTO::error(
                "Erro ao cadastrar processo: " . $e->getMessage()
            );
        }
    }

    /**
     * Atualizar processo existente
     */
    public function atualizar(int $id, ProcessoInputDTO $data): ProcessoOutputDTO
    {
        try {
            $processo = $this->repository->update($id, $data->toArray());

            return ProcessoOutputDTO::updated(
                id: $processo->id,
                protocolo: $processo->n_protocolo_fide,
            );
        } catch (\Exception $e) {
            return ProcessoOutputDTO::error(
                "Erro ao atualizar processo: " . $e->getMessage()
            );
        }
    }

    /**
     * Gera protocolo FIDE conforme regra de negócio
     * Formato: MG-{TIPO}-{IBGE}-{ANO}-{SEQUENCIA}
     */
    private function gerarProtocoloFide(ProcessoInputDTO $data): string
    {
        $tipo = $data->origem === 'municipal' ? 'M' : 'E';
        $ibge = str_pad((string) $data->municipioId, 7, '0', STR_PAD_LEFT);
        $ano = date('Y');

        // Busca próxima sequência do ano
        $ultimoProcesso = Processo::whereYear('created_at', $ano)
            ->orderBy('id', 'desc')
            ->first();

        $sequencia = $ultimoProcesso
            ? (int) substr($ultimoProcesso->n_protocolo_fide ?? '0000', -4) + 1
            : 1;

        $sequenciaFormatada = str_pad((string) $sequencia, 4, '0', STR_PAD_LEFT);

        return "MG-{$tipo}-{$ibge}-{$ano}-{$sequenciaFormatada}";
    }
}
