<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * ViewModel: Dados do formulário de Processo
 * Implementa Arrayable para passagem direta ao Inertia
 */
final class ProcessoFormDataDTO implements Arrayable
{
    public function __construct(
        public readonly array $tiposDesastre,
        public readonly array $cobrades,
        public readonly array $municipios,
        public readonly array $redecs,
        public readonly array $analistas,
        public readonly array $statusOptions,
        public readonly array $origemOptions,
        public readonly array $situacaoOptions,
        public readonly ?array $processo = null,
    ) {
    }

    public static function forCreate(
        array $tiposDesastre = [],
        array $cobrades = [],
        array $municipios = [],
        array $redecs = [],
        array $analistas = []
    ): self {
        return new self(
            tiposDesastre: $tiposDesastre,
            cobrades: $cobrades,
            municipios: $municipios,
            redecs: $redecs,
            analistas: $analistas,
            statusOptions: [
                ['value' => 'em_analise', 'label' => 'Em Análise'],
                ['value' => 'aprovado', 'label' => 'Aprovado'],
                ['value' => 'rejeitado', 'label' => 'Rejeitado'],
                ['value' => 'pendente', 'label' => 'Pendente'],
            ],
            origemOptions: [
                ['value' => 'municipal', 'label' => 'Municipal'],
                ['value' => 'estadual', 'label' => 'Estadual'],
            ],
            situacaoOptions: [
                ['value' => 'N1', 'label' => 'N1 - Situação de Emergência'],
                ['value' => 'SE', 'label' => 'SE - Estado de Calamidade Pública'],
            ],
            processo: null,
        );
    }

    public static function forEdit(array $processo, ...$params): self
    {
        $dto = self::forCreate(...$params);
        return new self(
            tiposDesastre: $dto->tiposDesastre,
            cobrades: $dto->cobrades,
            municipios: $dto->municipios,
            redecs: $dto->redecs,
            analistas: $dto->analistas,
            statusOptions: $dto->statusOptions,
            origemOptions: $dto->origemOptions,
            situacaoOptions: $dto->situacaoOptions,
            processo: $processo,
        );
    }

    /**
     * Arrayable: Conversão automática pelo Inertia
     */
    public function toArray(): array
    {
        return [
            'tiposDesastre' => $this->tiposDesastre,
            'cobrades' => $this->cobrades,
            'municipios' => $this->municipios,
            'redecs' => $this->redecs,
            'analistas' => $this->analistas,
            'statusOptions' => $this->statusOptions,
            'origemOptions' => $this->origemOptions,
            'situacaoOptions' => $this->situacaoOptions,
            'processo' => $this->processo,
        ];
    }
}
