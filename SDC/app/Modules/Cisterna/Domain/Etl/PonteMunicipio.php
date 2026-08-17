<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use App\Modules\Cisterna\Support\NormalizaEntrada;
use Illuminate\Support\Facades\DB;

/**
 * Ponte Codmundv -> municipios.id.
 *
 * O legado guardava `codmundv` (codigo IBGE com digito verificador) como
 * varchar, e o nome do municipio duplicado como texto em quatro tabelas.
 * `cedec_municipio` e a ponte oficial do projeto:
 * cedec_municipio.Codmundv = municipios.codigo_ibge (ver
 * ImportCedecMunicipioCommand).
 *
 * Memoizado: uma carga de milhares de beneficiarios resolve os mesmos ~200
 * municipios repetidamente.
 */
final class PonteMunicipio
{
    /**
     * @var array<string, int|null>
     */
    private array $memo = [];

    /**
     * @var array<string, array<int, int>>|null
     */
    private ?array $indiceNome = null;

    public function resolver(?string $codmundv): ?int
    {
        $codigo = $this->normalizar($codmundv);

        if ($codigo === null) {
            return null;
        }

        if (array_key_exists($codigo, $this->memo)) {
            return $this->memo[$codigo];
        }

        $id = DB::table('municipios')->where('codigo_ibge', $codigo)->value('id');

        return $this->memo[$codigo] = $id === null ? null : (int) $id;
    }

    /**
     * Fallback por nome, para linhas do legado sem codmundv. Retorna null
     * quando o nome casa com mais de um municipio — nesse caso o refino
     * registra erro em vez de escolher no escuro.
     */
    public function resolverPorNome(?string $nome): ?int
    {
        $procurado = NormalizaEntrada::chaveTexto($nome);

        if ($procurado === null) {
            return null;
        }

        $encontrados = $this->indicePorNome()[$procurado] ?? [];

        // Mais de um municipio com o mesmo nome normalizado: nao escolhe no
        // escuro. O refino registra erro e a area decide.
        return count($encontrados) === 1 ? $encontrados[0] : null;
    }

    /**
     * Nome normalizado -> ids. Uma consulta por instancia (853 linhas).
     *
     * A comparacao ignora acento de proposito. O legado grava o nome em caixa
     * alta e SEM acento (`PINTOPOLIS`), enquanto `municipios.nome` tem a grafia
     * correta (`Pintopolis` com acento) -- um `LOWER(nome) = ?` falha em todo
     * municipio acentuado, que e a maioria. Foi assim que a comunidade
     * ALVORADA de Pintopolis caiu como orfa na primeira carga.
     *
     * Normalizado em PHP, e nao com `unaccent` no Postgres, para nao exigir
     * CREATE EXTENSION no banco.
     *
     * Le direto da tabela e nao de `Municipio::catalogo()`: o catalogo tem memo
     * de processo na frente de um cache de 24h, e uma carga de migracao nao pode
     * resolver municipio contra catalogo vencido.
     *
     * @return array<string, array<int, int>>
     */
    private function indicePorNome(): array
    {
        if ($this->indiceNome !== null) {
            return $this->indiceNome;
        }

        $indice = [];

        foreach (DB::table('municipios')->get(['id', 'nome']) as $municipio) {
            $chave = NormalizaEntrada::chaveTexto($municipio->nome);

            if ($chave === null) {
                continue;
            }

            $indice[$chave][] = (int) $municipio->id;
        }

        return $this->indiceNome = $indice;
    }

    private function normalizar(?string $codmundv): ?string
    {
        if ($codmundv === null) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $codmundv) ?? '';

        return strlen($digitos) === 7 ? $digitos : null;
    }
}
