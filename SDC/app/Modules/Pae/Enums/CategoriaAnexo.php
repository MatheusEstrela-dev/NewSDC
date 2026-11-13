<?php

declare(strict_types=1);

namespace App\Modules\Pae\Enums;

/**
 * Pasta em que um anexo do protocolo PAE e arquivado, espelhando os anexos da
 * Resolucao GMG 83/2024, os anexos internos do Anexo B (o PAE propriamente
 * dito) e a cartografia entregue a parte (mapas em PDF, KMZ/KML e shapefile).
 * `Outros` recebe o que nao se encaixa e os anexos enviados antes da
 * categorizacao.
 */
enum CategoriaAnexo: string
{
    case AnexoA = 'anexo_a';
    case AnexoB = 'anexo_b';
    case AnexoB1 = 'anexo_b1';
    case AnexoB2 = 'anexo_b2';
    case AnexoB3 = 'anexo_b3';
    case AnexoB4 = 'anexo_b4';
    case AnexoB5 = 'anexo_b5';
    case AnexoB6 = 'anexo_b6';
    case AnexoB7 = 'anexo_b7';
    case AnexoC = 'anexo_c';
    case AnexoD = 'anexo_d';
    case AnexoE = 'anexo_e';
    case AnexoF = 'anexo_f';
    case AnexoG = 'anexo_g';
    case AnexoH = 'anexo_h';
    case AnexoI = 'anexo_i';
    case AnexoJ = 'anexo_j';
    case Mapas = 'mapas';
    case MapasKmz = 'mapas_kmz';
    case MapasShp = 'mapas_shp';
    case Outros = 'outros';

    public function rotulo(): string
    {
        // Titulos conforme os anexos entregues pelos empreendedores (ex.: PAEBM Dique Oeste).
        return match ($this) {
            self::AnexoB => 'Anexo B - PAE (Seção II)',
            self::AnexoB1 => 'B.1 - Lista de contatos',
            self::AnexoB2 => 'B.2 - GRAC',
            self::AnexoB3 => 'B.3 - Informações da ZAS',
            self::AnexoB4 => 'B.4 - ART',
            self::AnexoB5 => 'B.5 - ZAS (KMZ)',
            self::AnexoB6 => 'B.6 - Treinamentos internos',
            self::AnexoB7 => 'B.7 - Mapas',
            self::AnexoC => 'Anexo C - Simulado, estudos e testes',
            self::AnexoD => 'Anexo D - Abastecimento de água (PAAP)',
            self::AnexoE => 'Anexo E - Evacuação',
            self::AnexoG => 'Anexo G - Entrega do PAE no município',
            self::AnexoH => 'Anexo H - Treinamentos internos',
            self::AnexoJ => 'Anexo J - Admissibilidade',
            self::Mapas => 'Mapas',
            self::MapasKmz => 'KMZ / KML',
            self::MapasShp => 'Shapefile (SHP)',
            self::Outros => 'Outros',
            default => 'Anexo ' . mb_strtoupper(mb_substr($this->value, -1)),
        };
    }

    /** Pasta mae de um anexo interno; null para as pastas de primeiro nivel. */
    public function pai(): ?self
    {
        return match ($this) {
            self::AnexoB1, self::AnexoB2, self::AnexoB3, self::AnexoB4,
            self::AnexoB5, self::AnexoB6, self::AnexoB7 => self::AnexoB,
            self::MapasKmz, self::MapasShp => self::Mapas,
            default => null,
        };
    }

    /**
     * Subpasta fixa do anexo dentro do protocolo no armazenamento, igual para
     * todo protocolo: `anexo_c`, `anexo_b/anexo_b3`, `mapas/mapas_kmz`...
     */
    public function pastaNoServidor(): string
    {
        $pai = $this->pai();

        return $pai === null ? $this->value : $pai->value . '/' . $this->value;
    }

    /**
     * Lista {value, label, pai} na ordem da resolucao, pronta para o front.
     *
     * @return list<array{value: string, label: string, pai: ?string}>
     */
    public static function opcoes(): array
    {
        return array_map(
            static fn (self $categoria): array => [
                'value' => $categoria->value,
                'label' => $categoria->rotulo(),
                'pai' => $categoria->pai()?->value,
            ],
            self::cases(),
        );
    }
}
