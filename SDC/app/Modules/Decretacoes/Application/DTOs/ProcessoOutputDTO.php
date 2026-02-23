<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\DTOs;

/**
 * DTO: Dados de Saída após operação no Processo
 * Seguindo papiro.md - DADOS DE SAÍDA
 */
final class ProcessoOutputDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $nProtocoloFide,
        public readonly string $status,
        public readonly string $mensagem,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * Cria DTO de sucesso para criação
     */
    public static function created(int $id, string $protocolo, string $createdAt): self
    {
        return new self(
            id: $id,
            nProtocoloFide: $protocolo,
            status: 'success',
            mensagem: "Processo criado com sucesso. Protocolo: {$protocolo}",
            createdAt: $createdAt,
        );
    }

    /**
     * Cria DTO de sucesso para atualização
     */
    public static function updated(int $id, string $protocolo): self
    {
        return new self(
            id: $id,
            nProtocoloFide: $protocolo,
            status: 'success',
            mensagem: "Processo atualizado com sucesso.",
        );
    }

    /**
     * Cria DTO de erro
     */
    public static function error(string $mensagem): self
    {
        return new self(
            id: 0,
            nProtocoloFide: '',
            status: 'error',
            mensagem: $mensagem,
        );
    }

    /**
     * Converte para array para response JSON
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'n_protocolo_fide' => $this->nProtocoloFide,
            'status' => $this->status,
            'mensagem' => $this->mensagem,
            'created_at' => $this->createdAt,
        ];
    }
}
