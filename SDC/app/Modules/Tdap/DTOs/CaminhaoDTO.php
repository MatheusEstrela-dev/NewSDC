<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

use App\Modules\Tdap\Support\Documento;

final readonly class CaminhaoDTO
{
    public function __construct(
        public int $prestador_id,
        public string $placa,
        public ?string $marca,
        public ?string $modelo,
        public ?string $cor,
        public ?string $ano,
        public float $capacidade_m3,
        /**
         * `null` = nao informado.
         *
         * Era `bool` com default `true`, e um PUT sem o campo REATIVAVA o
         * caminhao: `?? true` nao distingue "nao mandou" de "mandou false", e
         * o toArray ia inteiro para o `update()`. A tela sempre manda, entao o
         * estrago so aparecia por chamada fora do formulario -- que e
         * exatamente onde ninguem procuraria.
         */
        public ?bool $ativo,
        public ?string $observacoes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            prestador_id:  (int) ($data['prestador_id'] ?? 0),
            // Documento::placa, e nao um `mb_strtoupper` solto: sem a limpeza
            // de separador, quem monta o DTO fora do formulario grava a placa
            // com hifen -- o formato legado que a busca precisa reconciliar.
            placa:         (string) Documento::placa((string) ($data['placa'] ?? '')),
            marca:         self::nullable($data['marca'] ?? null),
            modelo:        self::nullable($data['modelo'] ?? null),
            cor:           self::nullable($data['cor'] ?? null),
            ano:           self::nullable($data['ano'] ?? null),
            capacidade_m3: (float) ($data['capacidade_m3'] ?? 0),
            ativo:         isset($data['ativo']) ? (bool) $data['ativo'] : null,
            observacoes:   self::nullable($data['observacoes'] ?? null),
        );
    }

    /**
     * Campo nao informado nao vai para o banco.
     *
     * No `create` o default da coluna (`ativo = true`) assume; no `update` a
     * coluna fica como esta. Mandar sempre a chave era o que transformava
     * silencio em "reative isto".
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'prestador_id'  => $this->prestador_id,
            'placa'         => $this->placa,
            'marca'         => $this->marca,
            'modelo'        => $this->modelo,
            'cor'           => $this->cor,
            'ano'           => $this->ano,
            'capacidade_m3' => $this->capacidade_m3,
            'ativo'         => $this->ativo,
            'observacoes'   => $this->observacoes,
        ], fn (string $campo): bool => $campo !== 'ativo' || $this->ativo !== null, ARRAY_FILTER_USE_KEY);
    }

    private static function nullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
