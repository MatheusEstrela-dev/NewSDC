<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Requests;

use Illuminate\Validation\Rule;

/**
 * As regras dos metadados da camada, num lugar so.
 *
 * Enviar e editar validam o MESMO conjunto de campos -- a diferenca entre os
 * dois e o arquivo, que so existe no envio. Duplicar as regras faria o
 * formulario de edicao aceitar o que o de envio recusa na primeira vez que uma
 * delas mudasse de lado.
 */
final class RegrasDeMetadado
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function regras(?string $dominio): array
    {
        $dominios = (array) config('geoespacial.dominios');

        return [
            'dominio' => ['required', Rule::in(array_keys($dominios))],
            'nome' => ['required', 'string', 'max:255'],
            // Emissao, validade e nivel NAO existem dentro do KML -- so no nome
            // do arquivo. Extrair de nome de arquivo externo e contrato que
            // ninguem garante, entao o operador informa.
            'emitido_em' => ['required', 'date'],
            'valido_ate' => ['nullable', 'date', 'after_or_equal:emitido_em'],
            // O nivel pertence ao vocabulario do DOMINIO, e nao a um varchar
            // livre: 'muito_alto' em camada hidro e 'alto' em geologica pintam
            // legendas diferentes, e nivel fora da lista sai da tela sem cor e
            // sem rotulo. O select da tela ja restringe; isto fecha o servidor.
            'nivel' => ['required', 'string', 'max:40', Rule::in(self::niveisDe($dominios, $dominio))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function mensagens(): array
    {
        return [
            'dominio.in' => 'Dominio desconhecido.',
            'nivel.in' => 'Nivel invalido para o dominio escolhido.',
            'valido_ate.after_or_equal' => 'A validade nao pode ser anterior a emissao.',
        ];
    }

    /**
     * Niveis do dominio informado. Dominio invalido devolve lista vazia de
     * proposito: sem ela o nivel passaria por qualquer texto quando o dominio
     * fosse lixo, e o erro apareceria so no dominio -- confuso para quem le.
     *
     * @param array<string, mixed> $dominios
     * @return list<string>
     */
    private static function niveisDe(array $dominios, ?string $dominio): array
    {
        if ($dominio === null || ! isset($dominios[$dominio])) {
            return [];
        }

        return array_values((array) ($dominios[$dominio]['niveis'] ?? []));
    }
}
