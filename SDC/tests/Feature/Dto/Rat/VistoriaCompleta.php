<?php

declare(strict_types=1);

namespace Tests\Feature\Dto\Rat;

use App\Modules\Rat\Http\Requests\RatVistoriaRequest;

/**
 * Payload plano (formato da API) com TODAS as colunas que a
 * RatVistoriaRequest valida, gerado a partir das proprias regras: se uma regra
 * nova aparecer sem mapeamento no DTO, os testes que usam este payload acusam.
 *
 * Cada texto e distinto por coluna (prefixo do md5 do nome), para que um
 * mapeamento trocado entre duas colunas nao passe despercebido.
 */
trait VistoriaCompleta
{
    /** @return array<string, mixed> */
    protected function vistoriaPlanaCompleta(): array
    {
        $payload = [];

        foreach ((new RatVistoriaRequest())->rules() as $coluna => $regras) {
            $payload[$coluna] = $this->amostraDaRegra($coluna, $regras);
        }

        return $payload;
    }

    /**
     * Valor que o DTO deve entregar ao toArray() para a amostra enviada.
     * v_ha_idosos/v_ha_criancas: a Request aceita texto e o DTO normaliza
     * para booleano ('sim' -> true).
     */
    protected function esperadoNoDto(string $coluna, mixed $enviado): mixed
    {
        return in_array($coluna, ['v_ha_idosos', 'v_ha_criancas'], true) ? true : $enviado;
    }

    /**
     * Valor gravado no banco, normalizado para comparacao: booleano em coluna
     * varchar (v_ha_idosos) vira '1'; decimal volta como texto.
     */
    protected function normalizarDoBanco(string $coluna, mixed $valor): mixed
    {
        if (in_array($coluna, ['v_ha_idosos', 'v_ha_criancas'], true)) {
            return $valor === null ? null : in_array((string) $valor, ['1', 'true', 't'], true);
        }

        if (in_array($coluna, ['v_latitude', 'v_longitude'], true)) {
            return $valor === null ? null : (float) $valor;
        }

        if (is_bool($valor) || $valor === null) {
            return $valor;
        }

        return is_numeric($valor) && in_array($coluna, ['v_numero_pavimentos', 'v_numero_moradores', 'v_numero_moradias_terreno', 'v_municipio'], true)
            ? (int) $valor
            : $valor;
    }

    /** @param list<mixed> $regras */
    private function amostraDaRegra(string $coluna, array $regras): mixed
    {
        if (in_array('boolean', $regras, true)) {
            return true;
        }

        if (in_array('integer', $regras, true)) {
            return $coluna === 'v_municipio' ? 3106200 : 7;
        }

        if (in_array('numeric', $regras, true)) {
            return $coluna === 'v_latitude' ? -19.9191 : -43.9386;
        }

        if (in_array('regex:/^\d{11}$/', $regras, true)) {
            return '12345678901';
        }

        if (in_array('regex:/^\d{8}$/', $regras, true)) {
            return '30140071';
        }

        if (in_array('regex:/^\d+$/', $regras, true)) {
            return '31988887777';
        }

        if (in_array($coluna, ['v_ha_idosos', 'v_ha_criancas'], true)) {
            return 'sim';
        }

        $max = 12;
        foreach ($regras as $regra) {
            if (is_string($regra) && str_starts_with($regra, 'max:')) {
                $max = min($max, (int) substr($regra, 4));
            }
        }

        return substr('T'.md5($coluna), 0, $max);
    }
}
