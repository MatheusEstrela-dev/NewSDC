<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Services;

use App\Models\Municipio;
use App\Modules\Cedec\DTOs\PrefeituraFiltroDTO;
use App\Modules\Compdec\DTOs\PrefeituraDTO;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as PaginadorConcreto;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Prefeituras vistas pela CEDEC estadual: as linhas de `municipios`, com ou sem
 * `compdec_prefeituras` correspondente.
 *
 * Opera por `municipio_id`, nao por orgao. O
 * App\Modules\Compdec\Services\PrefeituraService continua intacto para a aba
 * municipal do Compdec; os dois convergem na mesma linha via
 * updateOrCreate(['municipio_id' => ...]).
 */
final class CedecPrefeituraService
{
    /**
     * municipios LEFT JOIN compdec_prefeituras (municipio sem prefeitura tambem
     * aparece) LEFT JOIN cedec_municipio (REDEC, macrorregiao) LEFT JOIN dec_redecs
     * (rotulo da REDEC).
     *
     * A condicao de soft delete do lado direito vai DENTRO do join: num where() ela
     * viraria filtro sobre a tabela da direita e transformaria o LEFT JOIN em INNER,
     * sumindo com todo municipio sem prefeitura.
     */
    private function baseQuery(): Builder
    {
        return DB::table('municipios')
            ->leftJoin('compdec_prefeituras', function (JoinClause $join): void {
                $join->on('compdec_prefeituras.municipio_id', '=', 'municipios.id')
                    ->whereNull('compdec_prefeituras.deleted_at');
            })
            ->leftJoin('cedec_municipio', 'cedec_municipio.Codmundv', '=', 'municipios.codigo_ibge')
            ->leftJoin('dec_redecs', 'dec_redecs.id', '=', 'cedec_municipio.redec_id');
    }

    public function listar(PrefeituraFiltroDTO $filtro): LengthAwarePaginator
    {
        $query = $this->baseQuery()
            ->select([
                'municipios.id as municipio_id',
                'municipios.nome as municipio_nome',
                'municipios.codigo_ibge',
                'dec_redecs.sigla as redec',
                'compdec_prefeituras.id as prefeitura_id',
                'compdec_prefeituras.prefeito_nome',
                'compdec_prefeituras.email_prefeitura',
                'compdec_prefeituras.tel_prefeitura',
            ])
            ->orderBy('municipios.nome');

        if ($filtro->busca !== null && trim($filtro->busca) !== '') {
            $query->where('municipios.nome', 'ILIKE', '%' . trim($filtro->busca) . '%');
        }

        if ($filtro->redecId !== null) {
            $query->where('cedec_municipio.redec_id', $filtro->redecId);
        }

        if ($filtro->macrorregiao !== null && $filtro->macrorregiao !== '') {
            // Coluna real: `macroregiao`, com uma letra r. O dominio usa
            // `macrorregiao`, com duas. Errar aqui faz o filtro voltar vazio em
            // silencio, sem erro de SQL.
            $query->where('cedec_municipio.macroregiao', $filtro->macrorregiao);
        }

        $this->aplicarPendencia($query, $filtro->pendencia);

        $paginador = $query->paginate($filtro->perPage);

        $this->anexarTemFoto($paginador);

        return $paginador;
    }

    /** @return array{total: int, sem_email: int, sem_telefone: int, sem_foto: int} */
    public function estatisticas(): array
    {
        return [
            'total' => $this->baseQuery()->count('municipios.id'),
            'sem_email' => $this->aplicarPendencia($this->baseQuery(), 'sem_email')->count('municipios.id'),
            'sem_telefone' => $this->aplicarPendencia($this->baseQuery(), 'sem_telefone')->count('municipios.id'),
            'sem_foto' => $this->aplicarPendencia($this->baseQuery(), 'sem_foto')->count('municipios.id'),
        ];
    }

    public function obterPorMunicipio(int $municipioId): ?Prefeitura
    {
        return Prefeitura::query()->where('municipio_id', $municipioId)->first();
    }

    /**
     * Indicadores read-only vindos de `municipios` e `cedec_municipio`.
     *
     * A coluna do banco chama `macroregiao`, com uma unica letra r, que e o nome
     * gravado na migration do espelho do legado. A chave devolvida aqui,
     * `macrorregiao`, segue a grafia correta usada no resto do dominio.
     *
     * @return array{populacao: ?float, pop_rural: ?int, area: ?string,
     *   macrorregiao: ?string, territorio_desenv: ?string, distancia_bh: ?float,
     *   qtd_pipa: ?int, latitude: ?string, longitude: ?string, origem: string}
     */
    public function indicadoresMunicipais(int $municipioId): array
    {
        $municipio = Municipio::query()->findOrFail($municipioId);

        $linha = DB::table('cedec_municipio')
            ->where('Codmundv', $municipio->codigo_ibge)
            ->first();

        return [
            'populacao' => $linha?->populacao !== null ? (float) $linha->populacao : null,
            'pop_rural' => $linha?->pop_rural !== null ? (int) $linha->pop_rural : null,
            'area' => $linha?->area,
            'macrorregiao' => $linha?->macroregiao,
            'territorio_desenv' => $linha?->territorio_desenv,
            'distancia_bh' => $linha?->distancia_bh !== null ? (float) $linha->distancia_bh : null,
            'qtd_pipa' => $linha?->qtd_pipa !== null ? (int) $linha->qtd_pipa : null,
            'latitude' => $linha?->latitude,
            'longitude' => $linha?->longitude,
            'origem' => 'cedec_municipio (espelho do legado)',
        ];
    }

    /**
     * De onde veio o dado desta prefeitura. Quem le o cadastro precisa saber se o
     * numero e da carga do legado ou se ja passou por alguem -- sem isso, corrigir o
     * dado errado no lugar errado e questao de tempo.
     *
     * @return array{legacy_id: ?int, veio_do_legado: bool, ultima_acao_etl: ?string,
     *   ultima_carga_em: ?string, criado_em: ?string, atualizado_em: ?string}
     */
    public function rastreabilidade(int $municipioId): array
    {
        $prefeitura = $this->obterPorMunicipio($municipioId);

        if ($prefeitura === null) {
            return [
                'legacy_id' => null,
                'veio_do_legado' => false,
                'ultima_acao_etl' => null,
                'ultima_carga_em' => null,
                'criado_em' => null,
                'atualizado_em' => null,
            ];
        }

        // compdec_etl_log e compartilhada por todos os ETLs do Compdec: filtrar por
        // recurso e obrigatorio, senao pega a linha de outro modulo com o mesmo
        // legacy_id -- ja aconteceu com o recurso 'equipes'.
        $ultimo = $prefeitura->legacy_id === null ? null : DB::table('compdec_etl_log')
            ->where('recurso', 'prefeituras')
            ->where('legacy_id', $prefeitura->legacy_id)
            ->orderByDesc('id')
            ->first(['acao', 'created_at']);

        return [
            'legacy_id' => $prefeitura->legacy_id,
            'veio_do_legado' => $prefeitura->legacy_id !== null,
            'ultima_acao_etl' => $ultimo?->acao,
            'ultima_carga_em' => $ultimo?->created_at,
            'criado_em' => $prefeitura->created_at?->toIso8601String(),
            'atualizado_em' => $prefeitura->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Orgao COMPDEC do municipio, quando existe.
     *
     * A aba de prefeitura do Compdec e esta tela editam a MESMA linha de
     * compdec_prefeituras. Mostrar o vinculo e o que evita duas pessoas alterarem o
     * mesmo dado por portas diferentes sem saber uma da outra.
     *
     * @return array{id: int, nome: string, codigo: ?string}|null
     */
    public function orgaoCompdec(int $municipioId): ?array
    {
        $orgao = Orgao::query()
            ->where('municipio_id', $municipioId)
            ->orderBy('id')
            ->first(['id', 'nome', 'codigo']);

        return $orgao === null ? null : [
            'id' => (int) $orgao->id,
            'nome' => (string) $orgao->nome,
            'codigo' => $orgao->codigo,
        ];
    }

    /** updateOrCreate por municipio_id, em transacao. */
    public function upsertPorMunicipio(int $municipioId, PrefeituraDTO $dto): Prefeitura
    {
        return DB::transaction(function () use ($municipioId, $dto): Prefeitura {
            $payload = $dto->toArray();
            $payload['municipio_id'] = $municipioId;

            // legacy_id e a ponte com o registro de origem do ETL e tem indice
            // proprio. O formulario da CEDEC nao envia esse campo -- o
            // UpdatePrefeituraRequest nao o valida -- entao o DTO chega com null e o
            // toArray() emite a chave assim mesmo. Deixar passar faria o
            // updateOrCreate gravar null por cima da rastreabilidade, em silencio, no
            // primeiro save de um municipio ja migrado. Tirando a chave do payload, a
            // coluna nao entra no UPDATE e o valor existente sobrevive; quem passa um
            // legacyId de verdade continua escrevendo normalmente.
            if ($dto->legacyId === null) {
                unset($payload['legacy_id']);
            }

            return Prefeitura::query()->updateOrCreate(['municipio_id' => $municipioId], $payload);
        });
    }

    /**
     * Cria a linha de compdec_prefeituras se ainda nao existir: a CEDEC navega pelos
     * municipios, nao pelas prefeituras, entao pode chegar aqui antes de qualquer
     * outro dado ter sido preenchido.
     */
    public function uploadFoto(int $municipioId, UploadedFile $arquivo): Media
    {
        $prefeitura = Prefeitura::query()->firstOrCreate(['municipio_id' => $municipioId]);

        return $prefeitura
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO, config('compdec.disk', 'compdec'));
    }

    public function removerFoto(int $municipioId): bool
    {
        $prefeitura = Prefeitura::query()->where('municipio_id', $municipioId)->first();

        if ($prefeitura === null) {
            return false;
        }

        $prefeitura->clearMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO);

        return true;
    }

    private function aplicarPendencia(Builder $query, ?string $pendencia): Builder
    {
        return match ($pendencia) {
            'sem_email' => $query->where(function (Builder $sub): void {
                $sub->whereNull('compdec_prefeituras.email_prefeitura')
                    ->orWhere('compdec_prefeituras.email_prefeitura', '');
            }),
            'sem_telefone' => $query->where(function (Builder $sub): void {
                $sub->whereNull('compdec_prefeituras.tel_prefeitura')
                    ->orWhere('compdec_prefeituras.tel_prefeitura', '');
            }),
            'sem_foto' => $query->whereRaw(
                'NOT EXISTS (SELECT 1 FROM media WHERE media.model_type = ? AND media.model_id = compdec_prefeituras.id AND media.collection_name = ?)',
                [Prefeitura::class, Prefeitura::MEDIA_FOTO_PREFEITO],
            ),
            default => $query,
        };
    }

    /**
     * Uma segunda query em `media` limitada aos ids da PAGINA atual, nunca um EXISTS
     * por linha na projecao, que rodaria ate 100 vezes por listagem.
     *
     * Recebe a classe CONCRETA: getCollection() e transform() nao existem no contrato
     * Illuminate\Contracts\Pagination\LengthAwarePaginator, so na implementacao que o
     * paginate() devolve.
     */
    private function anexarTemFoto(PaginadorConcreto $paginador): void
    {
        $idsDaPagina = array_values(array_filter($paginador->getCollection()->pluck('prefeitura_id')->all()));

        $idsComFoto = $idsDaPagina === [] ? [] : Media::query()
            ->where('model_type', Prefeitura::class)
            ->where('collection_name', Prefeitura::MEDIA_FOTO_PREFEITO)
            ->whereIn('model_id', $idsDaPagina)
            ->pluck('model_id')
            ->all();

        $paginador->getCollection()->transform(function (object $linha) use ($idsComFoto): object {
            $linha->tem_foto = $linha->prefeitura_id !== null && in_array($linha->prefeitura_id, $idsComFoto, true);

            return $linha;
        });
    }
}
