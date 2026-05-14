<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Vistoria;

final readonly class VistoriaDTO
{
    public function __construct(
        public string $nome,
        public ?string $edital,
        public ?string $lote,
        public int $placa_id,
        public ?string $modelo,
        public ?string $cor,
        public string $data,
        public ?string $ano,
        public float $capacidade,
        public ParecerVistoria $parecer,
        public ?string $ficha,
        public ?string $observacoes,
        /** @var array<string, array{0: bool, 1: ?string}> */
        public array $itensEstruturais,
        /** @var array<string, array{0: bool, 1: ?string}> */
        public array $itensTanque,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            nome:             trim((string) ($data['nome'] ?? '')),
            edital:           self::nullable($data['edital'] ?? null),
            lote:             self::nullable($data['lote'] ?? null),
            placa_id:         (int) ($data['placa_id'] ?? 0),
            modelo:           self::nullable($data['modelo'] ?? null),
            cor:              self::nullable($data['cor'] ?? null),
            data:             (string) ($data['data'] ?? ''),
            ano:              self::nullable($data['ano'] ?? null),
            capacidade:       (float) ($data['capacidade'] ?? 0),
            parecer:          ParecerVistoria::from((string) ($data['parecer'] ?? 'aprovada')),
            ficha:            self::nullable($data['ficha'] ?? null),
            observacoes:      self::nullable($data['observacoes'] ?? null),
            itensEstruturais: self::extrairItens($data, Vistoria::ITENS_ESTRUTURAIS),
            itensTanque:      self::extrairItens($data, Vistoria::ITENS_TANQUE),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $base = [
            'nome'        => $this->nome,
            'edital'      => $this->edital,
            'lote'        => $this->lote,
            'placa_id'    => $this->placa_id,
            'modelo'      => $this->modelo,
            'cor'         => $this->cor,
            'data'        => $this->data,
            'ano'         => $this->ano,
            'capacidade'  => $this->capacidade,
            'parecer'     => $this->parecer->value,
            'ficha'       => $this->ficha,
            'observacoes' => $this->observacoes,
        ];

        foreach ($this->itensEstruturais as $campo => [$check, $obs]) {
            $base[$campo] = $check;
            $base["{$campo}_obs"] = $obs;
        }
        foreach ($this->itensTanque as $campo => [$check, $obs]) {
            $base[$campo] = $check;
            $base["{$campo}_obs"] = $obs;
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>    $campos
     * @return array<string, array{0: bool, 1: ?string}>
     */
    private static function extrairItens(array $data, array $campos): array
    {
        $out = [];
        foreach ($campos as $campo) {
            $check = (bool) ($data[$campo] ?? false);
            $obs   = self::nullable($data["{$campo}_obs"] ?? null);
            $out[$campo] = [$check, $obs];
        }

        return $out;
    }

    private static function nullable(mixed $value): ?string
    {
        if ($value === null) return null;
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
