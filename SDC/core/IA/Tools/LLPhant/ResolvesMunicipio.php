<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use Illuminate\Support\Facades\DB;

/**
 * Localiza um municipio mineiro pelo nome informado pelo usuario.
 *
 * Prefere o nome exato (sem diferenciar maiusculas) e so cai para a busca
 * parcial quando nao ha correspondencia exata, para "Itabira" nao virar
 * "Itabirito".
 */
trait ResolvesMunicipio
{
    protected function resolverMunicipio(string $nome): ?object
    {
        $nome = trim($nome);

        if ($nome === '') {
            return null;
        }

        $base = fn () => DB::connection('pgsql_ai')
            ->table('municipios')
            ->where('uf', 'MG')
            ->select(['id', 'nome', 'codigo_ibge', 'mesorregiao', 'microrregiao']);

        return $base()->whereRaw('LOWER(nome) = LOWER(?)', [$nome])->first()
            ?? $base()->where('nome', 'ilike', '%' . $nome . '%')->orderBy('nome')->first();
    }

    protected function municipioNaoEncontrado(string $nome): string
    {
        return json_encode([
            'encontrado' => false,
            'mensagem'   => "Municipio nao encontrado em MG: {$nome}",
        ]);
    }
}
