<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Support;

use Illuminate\Support\Facades\DB;

/**
 * Ponte com_comdec.id_municipio -> municipios.id.
 *
 * O legado nao guarda o codigo IBGE nas tabelas com_*: guarda o id da
 * `cedec_municipio` (ex.: 7221, 2420). A traducao oficial do projeto passa
 * por `cedec_municipio.Codmundv` = `municipios.codigo_ibge`, o mesmo caminho
 * usado pelo import do RAT e por Cisterna\Domain\Etl\PonteMunicipio.
 *
 * A diferenca para a PonteMunicipio do Cisterna e a chave de entrada: la o
 * legado ja trazia o Codmundv, aqui o que existe e o id da cedec_municipio.
 * Por isso o mapa e carregado de uma vez (854 linhas) em vez de resolver
 * codigo a codigo.
 */
final class PonteMunicipioLegado
{
    /**
     * @var array<int, array{municipio_id: int|null, nome: string|null, codigo_ibge: string|null}>|null
     */
    private ?array $mapa = null;

    /**
     * Resolve o id novo do municipio. Retorna null quando o municipio legado
     * nao existe em `municipios` (caso do 7221 "MUNICIPIO TESTE").
     */
    public function resolver(?int $idMunicipioLegado): ?int
    {
        return $this->linha($idMunicipioLegado)['municipio_id'] ?? null;
    }

    /**
     * Nome oficial do municipio em `municipios`, usado para nomear o orgao:
     * `com_comdec` nao tem coluna de nome.
     */
    public function nome(?int $idMunicipioLegado): ?string
    {
        return $this->linha($idMunicipioLegado)['nome'] ?? null;
    }

    /**
     * Codigo IBGE, usado para derivar o `codigo` unico do orgao.
     */
    public function codigoIbge(?int $idMunicipioLegado): ?string
    {
        return $this->linha($idMunicipioLegado)['codigo_ibge'] ?? null;
    }

    /**
     * @return array{municipio_id: int|null, nome: string|null, codigo_ibge: string|null}
     */
    private function linha(?int $idMunicipioLegado): array
    {
        if ($idMunicipioLegado === null) {
            return ['municipio_id' => null, 'nome' => null, 'codigo_ibge' => null];
        }

        return $this->mapa()[$idMunicipioLegado]
            ?? ['municipio_id' => null, 'nome' => null, 'codigo_ibge' => null];
    }

    /**
     * @return array<int, array{municipio_id: int|null, nome: string|null, codigo_ibge: string|null}>
     */
    private function mapa(): array
    {
        if ($this->mapa !== null) {
            return $this->mapa;
        }

        $linhas = DB::table('cedec_municipio as cm')
            ->leftJoin('municipios as m', 'm.codigo_ibge', '=', 'cm.Codmundv')
            ->select([
                'cm.id as legado_id',
                'm.id as municipio_id',
                'm.nome as municipio_nome',
                'cm.Codmundv as codigo_ibge',
            ])
            ->get();

        $mapa = [];

        foreach ($linhas as $linha) {
            $mapa[(int) $linha->legado_id] = [
                'municipio_id' => $linha->municipio_id === null ? null : (int) $linha->municipio_id,
                'nome' => $linha->municipio_nome,
                'codigo_ibge' => $linha->codigo_ibge,
            ];
        }

        return $this->mapa = $mapa;
    }
}
