<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\DTO\RedecDTO;
use App\Modules\Decretacoes\Models\Redec;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Catalogo das Regioes de Defesa Civil (REDEC) de Minas Gerais.
 *
 * FLUXO: dec_redecs -> Model Redec -> RedecDTO -> aqui -> props Inertia -> Vue
 *
 * Ponto unico de leitura do catalogo. Substitui o enum
 * App\Modules\Decretacoes\Enums\Redec, que trazia as 19 regionais e as sedes
 * escritas no proprio codigo: mantem os mesmos nomes de metodo (ids,
 * toSelectOptions, labelFor) para que as chamadas nao mudassem de forma, mas
 * agora cadastrar ou corrigir uma regional e um UPDATE, sem deploy.
 *
 * Metodos estaticos de proposito: as chamadas ficam em contexto estatico
 * (Resources, Requests, os metodos estaticos de ProcessoFilter), mesmo padrao
 * que ProcessoFilter usa para as demais listas de referencia.
 */
class RedecService
{
    /** Bump ao mudar o formato das linhas cacheadas (nao ao mudar os dados). */
    private const CACHE_KEY = 'decretacoes.redecs.v1';

    /** Catalogo de referencia: muda no ritmo de decreto, nao de requisicao. */
    private const CACHE_TTL = 86400;

    /**
     * Memo por requisicao: uma listagem chama labelFor() uma vez por linha, e
     * nao vale ir ao cache 50 vezes para responder a mesma pergunta.
     *
     * Estatico, portanto SOBREVIVE a requisicao sob Octane/Swoole - por isso
     * DecretacoesServiceProvider zera em RequestReceived. Sem esse reset, o
     * worker continuaria servindo o catalogo antigo depois de cadastrar uma
     * REDEC nova, que e justamente o que sair do enum veio permitir.
     *
     * @var array<int, RedecDTO>|null
     */
    private static ?array $memo = null;

    /**
     * Todas as REDECs ativas, indexadas pelo id, em ordem de numero.
     *
     * @return array<int, RedecDTO>
     */
    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        $linhas = self::linhas();

        $dtos = [];
        foreach ($linhas as $linha) {
            $dto = RedecDTO::fromArray($linha);
            $dtos[$dto->id] = $dto;
        }

        return self::$memo = $dtos;
    }

    /**
     * Uma REDEC pelo id cru (o que vem do request/banco). Null quando
     * desconhecida - mesmo contrato do antigo Redec::tryFrom().
     */
    public static function find(mixed $id): ?RedecDTO
    {
        if (! is_numeric($id)) {
            return null;
        }

        return self::all()[(int) $id] ?? null;
    }

    /**
     * Ids validos, para as regras de validacao acompanharem o catalogo.
     *
     * @return array<int, int>
     */
    public static function ids(): array
    {
        return array_keys(self::all());
    }

    /**
     * Opcoes para <select> (contrato id/label usado pelos FormSelect).
     *
     * @return array<int, array{id: int, label: string, sigla: string, sede: string, rpm: string}>
     */
    public static function toSelectOptions(): array
    {
        return array_values(array_map(
            fn (RedecDTO $redec) => $redec->toSelectOption(),
            self::all()
        ));
    }

    /** Rotulo a partir de um id cru (null quando desconhecido). */
    public static function labelFor(mixed $id): ?string
    {
        return self::find($id)?->label();
    }

    /**
     * Regra de validacao do campo `redec_id`.
     *
     * Devolve `null` (nenhuma restricao de lista) quando o catalogo esta
     * indisponivel - tabela ainda nao migrada, por exemplo. Sem isso um catalogo
     * vazio viraria `Rule::in([])`, que rejeita QUALQUER REDEC e travaria o
     * cadastro inteiro; o `integer` do chamador continua valendo.
     */
    public static function regraDeLista(): ?object
    {
        $ids = self::ids();

        return $ids !== [] ? Rule::in($ids) : null;
    }

    /**
     * Regras do campo `redec_id`, prontas para espalhar nas rules do Request.
     *
     * @return array<int, mixed>
     */
    public static function regrasDoCampo(): array
    {
        return array_values(array_filter([
            'nullable',
            'integer',
            self::regraDeLista(),
        ]));
    }

    /** Invalida o catalogo - chamar depois de alterar `dec_redecs`. */
    public static function clearCache(): void
    {
        self::flushMemo();
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Descarta apenas o memo de processo, preservando o cache compartilhado.
     *
     * Chamado a cada requisicao sob Octane (ver DecretacoesServiceProvider):
     * um worker vive por centenas de requisicoes e um Cache::forget feito por
     * OUTRO worker nao alcanca este memo.
     */
    public static function flushMemo(): void
    {
        self::$memo = null;
    }

    /**
     * Linhas cruas de `dec_redecs`, com cache.
     *
     * Resultado vazio NAO e cacheado: durante uma janela de deploy (tabela
     * ausente) um cache de 24h de lista vazia apagaria as REDECs de todas as
     * telas mesmo depois da migration rodar. O custo e um hasTable por
     * requisicao nesse cenario - o memo estatico limita a um.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function linhas(): array
    {
        $cacheadas = Cache::get(self::CACHE_KEY);

        if (is_array($cacheadas) && $cacheadas !== []) {
            return $cacheadas;
        }

        // hasTable ANTES de consultar, e nao try/catch em volta do SELECT: no
        // Postgres um comando que falha aborta a transacao inteira, e capturar a
        // excecao nao desfaz isso - todo INSERT seguinte morre com 25P02. Com a
        // migration pendente, um SELECT em tabela inexistente dentro de uma
        // transacao derrubaria o request todo, nao apenas a lista de REDECs.
        // hasTable le o catalogo do banco e nunca falha por tabela ausente.
        if (! Schema::hasTable((new Redec())->getTable())) {
            return [];
        }

        $linhas = Redec::query()
            ->ativas()
            ->emOrdem()
            ->get()
            ->map(fn (Redec $redec) => $redec->getAttributes())
            ->all();

        if ($linhas !== []) {
            Cache::put(self::CACHE_KEY, $linhas, self::CACHE_TTL);
        }

        return $linhas;
    }
}
