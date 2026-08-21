<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Catalogo das 27 unidades federativas (26 estados + DF).
 *
 * Fonte unica para a REGRA de validacao (`Rule::in(UnidadeFederativa::siglas())`)
 * e para as OPCOES do select no front. Sem isso a UF era um texto livre de dois
 * caracteres: 'XX' entrava no cadastro e o filtro por UF nunca mais casava.
 */
final class UnidadeFederativa
{
    /** @var array<string, string> sigla => nome */
    private const CATALOGO = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins',
    ];

    /**
     * @return array<int, string>
     */
    public static function siglas(): array
    {
        return array_keys(self::CATALOGO);
    }

    /**
     * Opcoes prontas para `<select>` do front.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::CATALOGO as $sigla => $nome) {
            $options[] = ['value' => $sigla, 'label' => "{$sigla} — {$nome}"];
        }

        return $options;
    }

    public static function existe(?string $sigla): bool
    {
        return $sigla !== null && array_key_exists(mb_strtoupper($sigla), self::CATALOGO);
    }
}
