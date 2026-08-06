<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

/**
 * Entrega de material a um beneficiario, dentro de um item da prestacao de
 * contas (RN-17).
 */
final readonly class EntregaBeneficiarioDTO
{
    public function __construct(
        public int $prestacaoContaItemId,
        public string $nomeBeneficiario,
        public int $qtd,
        public string $dataEntrega,
        public ?string $rg = null,
        public ?string $comunidade = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            prestacaoContaItemId: (int) ($data['prestacao_conta_item_id'] ?? 0),
            nomeBeneficiario:     (string) ($data['nome_beneficiario'] ?? ''),
            qtd:                  (int) ($data['qtd'] ?? 0),
            dataEntrega:          (string) ($data['data_entrega'] ?? ''),
            rg:                   self::texto($data['rg'] ?? null),
            comunidade:           self::texto($data['comunidade'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'prestacao_conta_item_id' => $this->prestacaoContaItemId,
            'nome_beneficiario'       => $this->nomeBeneficiario,
            'rg'                      => $this->rg,
            'comunidade'              => $this->comunidade,
            'qtd'                     => $this->qtd,
            'data_entrega'            => $this->dataEntrega,
        ];
    }

    private static function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
