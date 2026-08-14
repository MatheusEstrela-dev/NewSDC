<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * As tres etapas da cadeia de vistoria. No legado eram tres tabelas
 * distintas: sinc_cisterna_rel_fornecedor, _rel_compdec e _rel_cedec.
 */
enum EtapaVistoria: string
{
    case FORNECEDOR = 'fornecedor';
    case COMPDEC = 'compdec';
    case CEDEC = 'cedec';

    public function label(): string
    {
        return match ($this) {
            self::FORNECEDOR => 'Relatorio do Fornecedor',
            self::COMPDEC => 'Conferencia da COMPDEC',
            self::CEDEC => 'Fiscalizacao da CEDEC',
        };
    }

    public function proxima(): ?self
    {
        return match ($this) {
            self::FORNECEDOR => self::COMPDEC,
            self::COMPDEC => self::CEDEC,
            self::CEDEC => null,
        };
    }

    /**
     * Somente a etapa CEDEC preenche processo_sei, contrato, empenho,
     * placa_obras e engenheiro_art.
     */
    public function exigeDadosAdministrativos(): bool
    {
        return $this === self::CEDEC;
    }

    /**
     * Somente a etapa do fornecedor aloca o numero do QR Code.
     */
    public function alocaNumeroInstalacao(): bool
    {
        return $this === self::FORNECEDOR;
    }

    public function tabelaLegado(): string
    {
        return match ($this) {
            self::FORNECEDOR => 'sinc_cisterna_rel_fornecedor',
            self::COMPDEC => 'sinc_cisterna_rel_compdec',
            self::CEDEC => 'sinc_cisterna_rel_cedec',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
