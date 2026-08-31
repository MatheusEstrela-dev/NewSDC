<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Compdec\Support\MigracaoReport;
use App\Modules\Pmda\Models\Comunidade;
use Illuminate\Support\Facades\DB;

/**
 * ETL do catalogo de comunidades: pip_comunidade (legado MySQL) -> comunidades.
 *
 * E o catalogo mestre por municipio que alimenta o seletor "Adicionar
 * Comunidade" da aba de distribuicao do PMDA. Sem ele a tela abre vazia.
 *
 * NAO migra pip_pmda_comun (o vinculo comunidade<->plano): pmda_planos nao tem
 * coluna de referencia ao legado e seus registros sao todos novos, entao o
 * vinculo nao teria plano de destino. Isso depende de um ETL de pip_pmda que
 * ainda nao existe.
 *
 * Idempotente por legacy_id. Rodar duas vezes atualiza, nao duplica.
 */
class ComunidadeLegadoService
{
    /**
     * Colunas de pip_comunidade sem equivalente no schema novo, listadas aqui
     * para o proximo leitor nao procurar: `id_rota` nao tem conceito
     * correspondente; `id_user_validador` aponta para usuario legado sem
     * correspondencia garantida; `origem_cad`/`tipo_cad` viram apenas `ativo`,
     * ja que as 2.881 linhas do legado estao com tipo_cad='Ativo' e nao ha
     * fila de pre-cadastro pendente para virar pmda_comunidade_solicitacoes.
     */
    public function migrarLegado(int $chunk = 500, bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('comunidades');
        $report->dryRun = $dryRun;

        $mapaMunicipio = $this->mapaMunicipio();

        // Indices carregados de uma vez. Consultar comunidade a comunidade
        // dispararia ~2.9k selects e estoura o guard de N+1 do proprio sistema
        // ("Query budget exceeded"). Ambos sao atualizados durante o loop para
        // que uma duplicata do legado enxergue a linha que acabou de entrar.
        $porLegacy = $this->indicePorLegacyId();
        $porChave = $this->indicePorChave();

        DB::connection($this->conexaoLegado())
            ->table('pip_comunidade')
            ->select([
                'id', 'comunidade', 'municipio_id', 'latitude', 'longitude',
                'trecho_pav', 'trecho_n_pav', 'pop_atendida', 'id_ponto', 'tipo_cad',
            ])
            ->orderBy('id')
            ->chunk($chunk, function ($linhas) use (&$porLegacy, &$porChave, $mapaMunicipio, $report, $dryRun): void {
                foreach ($linhas as $linha) {
                    $this->migrarLinha($linha, $mapaMunicipio, $porLegacy, $porChave, $report, $dryRun);
                }
            });

        return $report;
    }

    /**
     * @param  array<int, int>  $mapaMunicipio  cedec_municipio.id => municipios.id
     * @param  array<int, int>  $porLegacy      legacy_id => comunidades.id
     * @param  array<string, int>  $porChave    "municipio:NOME" => comunidades.id
     */
    private function migrarLinha(
        object $linha,
        array $mapaMunicipio,
        array &$porLegacy,
        array &$porChave,
        MigracaoReport $report,
        bool $dryRun,
    ): void {
        $legacyId = (int) $linha->id;
        $nome = trim((string) $linha->comunidade);

        if ($nome === '') {
            $report->registrarErro($legacyId, 'comunidade sem nome');

            return;
        }

        $municipioId = $mapaMunicipio[(int) $linha->municipio_id] ?? null;

        if ($municipioId === null) {
            // Municipio legado sem par em `municipios` - na pratica so o 7221
            // ("MUNICIPIO TESTE", Codmundv = 0), com 7 comunidades de teste.
            $report->registrarSkip();

            return;
        }

        // O novo tem unique (municipio_id, nome) e o legado nao: sao 20 grupos
        // duplicados, todos o MESMO lugar cadastrado duas vezes (conferido: nos
        // dois casos com coordenada em ambas as linhas, as coordenadas sao
        // identicas). Vence a linha com coordenada; empate resolve pelo menor
        // id, que e a ordem do chunk. A perdedora entra como ignorada.
        $chave = $municipioId . ':' . mb_strtoupper($nome);
        $temCoordenada = $this->preenchido($linha->latitude);
        $idExistente = $porLegacy[$legacyId] ?? null;
        $idDaChave = $porChave[$chave] ?? null;

        // Duplicata sem coordenada perdendo para uma linha que ja ocupou a
        // chave: nao sobrescreve o registro bom com nulos.
        if ($idExistente === null && $idDaChave !== null && ! $temCoordenada) {
            $report->registrarSkip();

            return;
        }

        [$latitude, $longitude] = $this->coordenadas($linha->latitude, $linha->longitude);

        $atributos = [
            'municipio_id' => $municipioId,
            'nome' => $nome,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'trecho_pav' => $this->decimal($linha->trecho_pav),
            'trecho_n_pav' => $this->decimal($linha->trecho_n_pav),
            'pop_atendida' => $this->inteiro($linha->pop_atendida),
            'ponto_legacy_id' => $this->inteiro($linha->id_ponto),
            // tipo_cad no legado e a situacao do registro; qualquer coisa
            // diferente de "Ativo" entra desativada em vez de sumir.
            'ativo' => mb_strtolower(trim((string) $linha->tipo_cad)) === 'ativo',
        ];

        // A duplicata vencedora pode chegar depois da perdedora ja inserida:
        // nesse caso reaproveita a linha em vez de estourar o unique
        // (municipio_id, nome).
        $alvo = $idExistente ?? $idDaChave;

        if ($dryRun) {
            $alvo !== null ? $report->registrarAtualizacao() : $report->registrarInsercao();

            if ($alvo === null) {
                // Marca a chave como ocupada para o dry-run contar as duplicatas
                // seguintes como skip, igual faria na execucao real.
                $porChave[$chave] = 0;
                $porLegacy[$legacyId] = 0;
            }

            return;
        }

        try {
            if ($alvo !== null) {
                // Comunidade que alguem apagou no sistema novo NAO volta pelo
                // ETL. O legado nao sabe da exclusao, entao ressuscitar aqui
                // desfaria uma decisao manual - e, pior, a linha voltaria com
                // deleted_at preenchido, contada como "atualizada" mas invisivel
                // no seletor. Conta como ignorada e segue.
                if ($this->estaExcluida($alvo)) {
                    $report->registrarSkip();

                    return;
                }

                Comunidade::withTrashed()
                    ->whereKey($alvo)
                    ->update($atributos + ['legacy_id' => $legacyId, 'updated_at' => now()]);
                $report->registrarAtualizacao();
                $id = $alvo;
            } else {
                $id = (int) Comunidade::create($atributos + ['legacy_id' => $legacyId])->id;
                $report->registrarInsercao();
            }

            $porLegacy[$legacyId] = $id;
            $porChave[$chave] = $id;
        } catch (\Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
        }
    }

    /**
     * Ids com deleted_at preenchido, carregados uma vez para a checagem por
     * linha nao virar consulta.
     *
     * @var array<int, true>|null
     */
    private ?array $excluidos = null;

    private function estaExcluida(int $id): bool
    {
        if ($this->excluidos === null) {
            $this->excluidos = Comunidade::onlyTrashed()
                ->pluck('id')
                ->mapWithKeys(fn ($i) => [(int) $i => true])
                ->all();
        }

        return isset($this->excluidos[$id]);
    }

    /**
     * @return array<int, int>
     */
    private function indicePorLegacyId(): array
    {
        return Comunidade::withTrashed()
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function indicePorChave(): array
    {
        $indice = [];

        Comunidade::withTrashed()
            ->select(['id', 'municipio_id', 'nome'])
            ->cursor()
            ->each(function ($c) use (&$indice): void {
                $indice[$c->municipio_id . ':' . mb_strtoupper($c->nome)] = (int) $c->id;
            });

        return $indice;
    }

    /**
     * cedec_municipio.id (legado) -> municipios.id (novo).
     *
     * O join roda na conexao padrao porque `cedec_municipio` ja esta staged no
     * Postgres (854 linhas), mesmo caminho que Compdec\Support\
     * PonteMunicipioLegado usa. A traducao oficial e `Codmundv` = `codigo_ibge`.
     *
     * Carregado de uma vez: sao 854 linhas contra 2.881 comunidades, entao
     * resolver uma a uma seria pior.
     *
     * @return array<int, int>
     */
    private function mapaMunicipio(): array
    {
        // Os apelidos sao obrigatorios: as duas tabelas tem uma coluna `id` e,
        // sem renomear, pluck('m.id', 'cm.id') recebe do driver duas colunas de
        // mesmo nome, a segunda sobrescreve a primeira e o mapa vira
        // legado_id => legado_id. O ETL entao gravaria o id do legado em
        // municipio_id, e a FK so acusaria quando o numero por acaso NAO
        // existisse em `municipios` - ou seja, corromperia calado nos casos em
        // que existisse.
        return DB::table('cedec_municipio as cm')
            ->join('municipios as m', 'm.codigo_ibge', '=', 'cm.Codmundv')
            ->select(['cm.id as legado_id', 'm.id as novo_id'])
            ->pluck('novo_id', 'legado_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function conexaoLegado(): string
    {
        return (string) config('pmda.legacy_connection', config('compdec.legacy_connection', 'legacy'));
    }

    /**
     * Caixa envolvente de MG, com folga sobre os centroides dos 853 municipios
     * (lat -22.85 a -14.27, lon -50.69 a -39.94). A folga cobre o territorio
     * alem do centroide e comunidade rural encostada na divisa.
     */
    private const LAT_MIN = -23.6;
    private const LAT_MAX = -13.6;
    private const LON_MIN = -51.4;
    private const LON_MAX = -39.2;

    /** Coordenadas invertidas que o ETL corrigiu nesta execucao. */
    public int $coordenadasInvertidas = 0;

    /** Coordenadas impossiveis que o ETL anulou nesta execucao. */
    public int $coordenadasDescartadas = 0;

    /**
     * Saneia o par lat/long vindo do legado.
     *
     * O legado nao validava coordenada, entao ha tres tipos de sujeira:
     * par invertido (lat com valor de longitude), virgula deslocada
     * (-4.03 onde caberia -40.3) e lixo puro (lat -81, zeros).
     *
     * Inverter e seguro porque so acontece quando o par trocado cai dentro de
     * MG - e o caso das 5 comunidades de Jenipapo de Minas, que trocadas ficam
     * a ~12 km da sede. Ja "consertar" virgula deslocada multiplicando por 10
     * seria inventar precisao: o resultado fica verossimil, ninguem confere, e
     * o caminhao-pipa vai para o lugar errado. Coordenada impossivel vira NULL,
     * que a tela mostra como "sem coordenada" e alguem preenche direito.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function coordenadas(mixed $latBruta, mixed $lonBruta): array
    {
        $lat = $this->numero($latBruta);
        $lon = $this->numero($lonBruta);

        if ($lat === null || $lon === null) {
            return [null, null];
        }

        if ($this->dentroDeMg($lat, $lon)) {
            return [$this->texto($latBruta, 30), $this->texto($lonBruta, 30)];
        }

        if ($this->dentroDeMg($lon, $lat)) {
            $this->coordenadasInvertidas++;

            return [$this->texto($lonBruta, 30), $this->texto($latBruta, 30)];
        }

        $this->coordenadasDescartadas++;

        return [null, null];
    }

    private function dentroDeMg(float $lat, float $lon): bool
    {
        return $lat >= self::LAT_MIN && $lat <= self::LAT_MAX
            && $lon >= self::LON_MIN && $lon <= self::LON_MAX;
    }

    private function numero(mixed $valor): ?float
    {
        if (! $this->preenchido($valor)) {
            return null;
        }

        $texto = trim((string) $valor);

        return is_numeric($texto) ? (float) $texto : null;
    }

    private function preenchido(mixed $valor): bool
    {
        return $valor !== null && trim((string) $valor) !== '';
    }

    private function texto(mixed $valor, int $max): ?string
    {
        if (! $this->preenchido($valor)) {
            return null;
        }

        return mb_substr(trim((string) $valor), 0, $max);
    }

    private function decimal(mixed $valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }

    private function inteiro(mixed $valor): ?int
    {
        if ($valor === null || $valor === '' || (int) $valor === 0) {
            return null;
        }

        return (int) $valor;
    }
}
