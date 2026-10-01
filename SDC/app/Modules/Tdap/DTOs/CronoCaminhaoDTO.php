<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

use App\Modules\Tdap\Support\CalculoDeViagens;

final readonly class CronoCaminhaoDTO
{
    /**
     * @param  bool  $num_viagens_calculado  true = modo automatico do modal: o
     *     servidor recalcula `num_viagens` (CalculoDeViagens) em vez de confiar
     *     no numero vindo do navegador. false = valor informado a mao.
     */
    public function __construct(
        public int $cronograma_id,
        public int $caminhao_id,
        public ?int $comunidade_id,
        public float $agua_prevista,
        public int $num_viagens,
        public int $ordem,
        public bool $num_viagens_calculado = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            cronograma_id: (int) ($data['cronograma_id'] ?? 0),
            caminhao_id:   (int) ($data['caminhao_id'] ?? 0),
            comunidade_id: isset($data['comunidade_id']) && $data['comunidade_id'] !== '' ? (int) $data['comunidade_id'] : null,
            agua_prevista: (float) ($data['agua_prevista'] ?? 0),
            num_viagens:   (int) ($data['num_viagens'] ?? 0),
            ordem:         (int) ($data['ordem'] ?? 0),
            num_viagens_calculado: filter_var($data['num_viagens_calculado'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    /** Viagens a gravar: recalculadas no modo automatico, as informadas no manual. */
    public function viagensPara(float $capacidadeM3): int
    {
        return $this->num_viagens_calculado
            ? CalculoDeViagens::necessarias($this->agua_prevista, $capacidadeM3)
            : $this->num_viagens;
    }

    /**
     * Colunas de tdap_crono_caminhoes. `num_viagens_calculado` fica de fora:
     * e instrucao de como obter o numero, nao dado persistido.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cronograma_id' => $this->cronograma_id,
            'caminhao_id'   => $this->caminhao_id,
            'comunidade_id' => $this->comunidade_id,
            'agua_prevista' => $this->agua_prevista,
            'num_viagens'   => $this->num_viagens,
            'ordem'         => $this->ordem,
        ];
    }
}
