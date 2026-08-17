<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

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
        if ($nome === null || trim($nome) === '') {
            return null;
        }

        $ids = DB::table('municipios')
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim($nome))])
            ->pluck('id');

        return $ids->count() === 1 ? (int) $ids->first() : null;
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
