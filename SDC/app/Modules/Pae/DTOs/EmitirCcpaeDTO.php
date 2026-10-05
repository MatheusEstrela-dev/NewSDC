<?php

declare(strict_types=1);

namespace App\Modules\Pae\DTOs;

use Carbon\CarbonImmutable;

readonly class EmitirCcpaeDTO
{
    public function __construct(
        public string $codigo,
        public CarbonImmutable $dtEmissao,
        public bool $empreendimentoNovo,
        public ?CarbonImmutable $dtLicencaOperacao,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            codigo: trim((string) $data['codigo']),
            dtEmissao: CarbonImmutable::parse($data['dt_emissao'])->startOfDay(),
            empreendimentoNovo: filter_var($data['empreendimento_novo'] ?? false, FILTER_VALIDATE_BOOL),
            dtLicencaOperacao: empty($data['dt_licenca_operacao'])
                ? null
                : CarbonImmutable::parse($data['dt_licenca_operacao'])->startOfDay(),
        );
    }
}
