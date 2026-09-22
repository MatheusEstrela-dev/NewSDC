<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Console;

use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\Enums\FaixaRanking;
use Database\Seeders\RankingRegraSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Throwable;

/**
 * Auditoria de coerencia do catalogo de marcos do Ranking.
 *
 * O ranking nao tem coletor nem polling: um modulo so pontua quando emite
 * Domain Event E existe um ModuleAdapter que traduz aquele evento. O catalogo
 * (ranking.regras) e a outra metade do contrato. Quando as duas metades
 * divergem o sistema nao quebra - ele erra em silencio, e e esse silencio que
 * este comando existe para quebrar.
 *
 * SEVERIDADES, e por que a distincao importa:
 *
 *   PENDENCIA - marco catalogado cujo modulo ainda nao tem adaptador. E o
 *   estado NORMAL da maior parte do catalogo: os marcos nascem registrados e
 *   desabilitados, com motivo, justamente para que a lacuna fique visivel em
 *   vez de sumir. Nao reprova o gate.
 *
 *   ERRO - adaptador que emite rule_key inexistente no catalogo. O fato
 *   chegaria ao motor sem regra vigente e cairia em regra_nao_publicada: a
 *   entrega acontece, o usuario nao recebe o ponto e ninguem e avisado.
 *
 *   ERRO GRAVE - regra habilitada sem adaptador correspondente. E credito sem
 *   origem comprovada: a regra esta valendo, mas nenhum codigo consegue provar
 *   que o fato ocorreu.
 *
 *   ERRO - divergencia entre FaixaRanking (fonte de verdade) e
 *   config('ranking.faixas') (copia para UI e relatorio). A tela mostraria uma
 *   faixa e o saldo calcularia outra.
 *
 *   ERRO - modulo em app/Modules sem entrada no catalogo e sem entrada em
 *   config('ranking.modulos_sem_premio'). "Sem regra" precisa ser decisao
 *   documentada, nao esquecimento; um modulo novo tem de declarar a qual dos
 *   dois lados pertence.
 *
 *   AVISO (pendencia) - familia compartilhada entre modulos com pontos_base
 *   divergentes. A familia e a barreira de negocio contra premio duplo: o mesmo
 *   fato canonico chegando por dois modulos e UM premio, e quem chegar primeiro
 *   define quantos pontos valeu. Nao e bug automatico - pode ser decisao
 *   consciente -, mas nunca pode ficar implicito.
 *
 * Uso:
 *   php artisan ranking:verify-catalog
 *   php artisan ranking:verify-catalog --strict   # gate de CI
 */
class VerifyCatalogCommand extends Command
{
    protected $signature = 'ranking:verify-catalog
                            {--strict : Exit code 1 quando houver ERRO (pendencia nao reprova)}';

    protected $description = 'Audita a coerencia entre catalogo de regras, adaptadores registrados, faixas e cobertura de modulos';

    /**
     * Adaptadores da tag `ranking.adaptadores`. Default vazio para que o
     * container consiga instanciar o comando sem wiring; handle() resolve a tag
     * quando nada foi injetado.
     *
     * @var iterable<ModuleAdapter>
     */
    private iterable $adaptadores;

    /**
     * @param  iterable<ModuleAdapter>  $adaptadores
     */
    public function __construct(iterable $adaptadores = [])
    {
        parent::__construct();

        $this->adaptadores = $adaptadores;
    }

    public function handle(): int
    {
        $this->components->info('Ranking - auditoria do catalogo de marcos');
        $this->newLine();

        $adaptadores = $this->resolverAdaptadores();

        $relatorio = $this->analisar(
            $this->catalogo(),
            $this->ruleKeysPorModulo($adaptadores),
            (array) config('ranking.faixas', []),
            $this->modulosEmDisco(),
            (array) config('ranking.modulos_sem_premio', []),
        );

        $this->renderizar($relatorio);

        $estrito = (bool) $this->option('strict');

        if ($relatorio['total_erros'] === 0) {
            $this->components->info(sprintf(
                'Catalogo coerente. %d pendencia(s) de cobertura registrada(s).',
                $relatorio['total_pendencias'],
            ));
        } elseif ($estrito) {
            $this->components->error(sprintf(
                'Auditoria reprovada (--strict): %d erro(s).',
                $relatorio['total_erros'],
            ));
        } else {
            $this->components->warn(sprintf(
                'Auditoria concluida COM %d erro(s) e %d pendencia(s). Use --strict para reprovar.',
                $relatorio['total_erros'],
                $relatorio['total_pendencias'],
            ));
        }

        return $this->codigoDeSaida($relatorio, $estrito);
    }

    /**
     * Regra unica do gate de CI, isolada para poder ser exercitada: PENDENCIA
     * nunca reprova (a lacuna de cobertura e o estado esperado do catalogo);
     * ERRO so reprova sob --strict, para que rodar o comando a mao continue
     * mostrando tudo sem derrubar o shell.
     *
     * @param  array<string, mixed>  $relatorio
     */
    public function codigoDeSaida(array $relatorio, bool $estrito): int
    {
        return $estrito && (int) $relatorio['total_erros'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Nucleo puro da auditoria: recebe tudo por argumento e nao toca em banco,
     * config nem disco. E o que permite exercitar cada achado injetando um
     * catalogo ou um adaptador artificial, sem subir o framework.
     *
     * @param  list<array<string, mixed>>  $catalogo  regras vigentes (modulo, rule_key, familia, pontos_base, habilitada, ...)
     * @param  array<string, list<string>>  $ruleKeysPorModulo  modulo => rule_keys que os adaptadores emitem
     * @param  array<string, array{min?: int, max?: int|null}>  $faixasConfig
     * @param  list<string>  $modulosEmDisco
     * @param  array<string, string>  $modulosSemPremio
     * @return array<string, mixed>
     */
    public function analisar(
        array $catalogo,
        array $ruleKeysPorModulo,
        array $faixasConfig,
        array $modulosEmDisco,
        array $modulosSemPremio,
    ): array {
        $modulosComAdaptador = array_keys($ruleKeysPorModulo);

        $ruleKeysCatalogo = [];
        $modulosCatalogo = [];

        foreach ($catalogo as $regra) {
            $ruleKeysCatalogo[(string) $regra['rule_key']] = $regra;
            $modulosCatalogo[(string) $regra['modulo']] = true;
        }

        $marcosSemAdaptador = [];
        $habilitadaSemFonte = [];

        foreach ($catalogo as $regra) {
            $modulo = (string) $regra['modulo'];
            $ruleKey = (string) $regra['rule_key'];
            $temAdaptador = array_key_exists($modulo, $ruleKeysPorModulo);
            $emitida = $temAdaptador && in_array($ruleKey, $ruleKeysPorModulo[$modulo], true);
            $habilitada = (bool) ($regra['habilitada'] ?? false);

            // Regra valendo sem nada que consiga produzir o fato: credito sem
            // origem. Nao basta o modulo ter adaptador - a rule_key precisa
            // ser efetivamente emitida por ele.
            if ($habilitada && ! $emitida) {
                $habilitadaSemFonte[] = [
                    'modulo' => $modulo,
                    'rule_key' => $ruleKey,
                    'causa' => $temAdaptador
                        ? 'adaptador do modulo nao emite esta rule_key'
                        : 'modulo sem ModuleAdapter registrado',
                ];

                continue;
            }

            if (! $emitida) {
                $marcosSemAdaptador[] = [
                    'modulo' => $modulo,
                    'rule_key' => $ruleKey,
                    'motivo' => (string) ($regra['motivo_desabilitada'] ?? 'sem_motivo_declarado'),
                ];
            }
        }

        // O caminho inverso: o adaptador promete um marco que o catalogo nao
        // conhece. O fato chegaria ao motor e nao encontraria regra vigente.
        $adaptadorSemRegra = [];

        foreach ($ruleKeysPorModulo as $modulo => $ruleKeys) {
            foreach ($ruleKeys as $ruleKey) {
                if (! array_key_exists($ruleKey, $ruleKeysCatalogo)) {
                    $adaptadorSemRegra[] = [
                        'modulo' => (string) $modulo,
                        'rule_key' => $ruleKey,
                        'causa' => 'rule_key ausente do catalogo de regras',
                    ];

                    continue;
                }

                $moduloDaRegra = (string) $ruleKeysCatalogo[$ruleKey]['modulo'];

                if ($moduloDaRegra !== (string) $modulo) {
                    $adaptadorSemRegra[] = [
                        'modulo' => (string) $modulo,
                        'rule_key' => $ruleKey,
                        'causa' => "regra pertence ao modulo {$moduloDaRegra}",
                    ];
                }
            }
        }

        $cobertos = array_merge(array_keys($modulosCatalogo), array_keys($modulosSemPremio));

        $modulosSemCobertura = array_values(array_filter(
            $modulosEmDisco,
            static fn (string $modulo): bool => ! in_array($modulo, $cobertos, true),
        ));

        // Entrada que descreve modulo inexistente tambem e incoerencia: aponta
        // para diretorio renomeado ou removido sem ajuste do catalogo.
        $cobertosSemModulo = array_values(array_filter(
            array_unique($cobertos),
            static fn (string $modulo): bool => ! in_array($modulo, $modulosEmDisco, true),
        ));

        $familias = $this->analisarFamilias($catalogo);
        $faixasDivergentes = $this->compararFaixas($faixasConfig);

        $totalErros = count($adaptadorSemRegra)
            + count($habilitadaSemFonte)
            + count($faixasDivergentes)
            + count($modulosSemCobertura);

        $familiasDivergentes = array_values(array_filter(
            $familias,
            static fn (array $familia): bool => $familia['compartilhada'] && $familia['pontos_divergentes'],
        ));

        $totalPendencias = count($marcosSemAdaptador)
            + count($familiasDivergentes)
            + count($cobertosSemModulo);

        return [
            'resumo' => [
                'regras_no_catalogo' => count($catalogo),
                'modulos_no_catalogo' => count($modulosCatalogo),
                'adaptadores_registrados' => count($modulosComAdaptador),
                'rule_keys_emitidas' => array_sum(array_map('count', $ruleKeysPorModulo)),
                'modulos_em_disco' => count($modulosEmDisco),
                'modulos_sem_premio' => count($modulosSemPremio),
            ],
            'modulos_com_adaptador' => $modulosComAdaptador,
            'marcos_sem_adaptador' => $marcosSemAdaptador,
            'adaptador_sem_regra' => $adaptadorSemRegra,
            'habilitada_sem_fonte' => $habilitadaSemFonte,
            'faixas_divergentes' => $faixasDivergentes,
            'modulos_sem_cobertura' => $modulosSemCobertura,
            'cobertos_sem_modulo' => $cobertosSemModulo,
            'familias' => $familias,
            'familias_compartilhadas' => array_values(array_filter(
                $familias,
                static fn (array $familia): bool => $familia['compartilhada'],
            )),
            'familias_divergentes' => $familiasDivergentes,
            'total_erros' => $totalErros,
            'total_pendencias' => $totalPendencias,
        ];
    }

    /**
     * Agrupa o catalogo por familia. Familia compartilhada e a que aparece em
     * mais de um modulo - o mesmo fato canonico com duas portas de entrada.
     *
     * @param  list<array<string, mixed>>  $catalogo
     * @return list<array{familia: string, modulos: list<string>, rule_keys: list<string>, pontos: array<string, int>, compartilhada: bool, pontos_divergentes: bool}>
     */
    private function analisarFamilias(array $catalogo): array
    {
        $agrupado = [];

        foreach ($catalogo as $regra) {
            $familia = (string) $regra['familia'];
            $modulo = (string) $regra['modulo'];

            $agrupado[$familia]['modulos'][$modulo] = true;
            $agrupado[$familia]['rule_keys'][] = (string) $regra['rule_key'];
            $agrupado[$familia]['pontos'][$modulo.':'.$regra['rule_key']] = (int) $regra['pontos_base'];
        }

        ksort($agrupado);

        $familias = [];

        foreach ($agrupado as $familia => $dados) {
            $modulos = array_keys($dados['modulos']);
            $pontos = $dados['pontos'];

            $familias[] = [
                'familia' => $familia,
                'modulos' => $modulos,
                'rule_keys' => $dados['rule_keys'],
                'pontos' => $pontos,
                'compartilhada' => count($modulos) > 1,
                'pontos_divergentes' => count(array_unique(array_values($pontos))) > 1,
            ];
        }

        return $familias;
    }

    /**
     * FaixaRanking e a fonte de verdade; config('ranking.faixas') e copia. A
     * comparacao vai nos dois sentidos para pegar tambem faixa sobrando no
     * config.
     *
     * @param  array<string, array{min?: int, max?: int|null}>  $faixasConfig
     * @return list<array{faixa: string, campo: string, enum: string, config: string}>
     */
    private function compararFaixas(array $faixasConfig): array
    {
        $divergencias = [];

        foreach (FaixaRanking::cases() as $faixa) {
            if (! array_key_exists($faixa->value, $faixasConfig)) {
                $divergencias[] = [
                    'faixa' => $faixa->value,
                    'campo' => 'faixa',
                    'enum' => 'presente',
                    'config' => 'ausente',
                ];

                continue;
            }

            $config = $faixasConfig[$faixa->value];

            $comparacoes = [
                'min' => [$faixa->pontosMinimos(), $config['min'] ?? null],
                'max' => [$faixa->pontosMaximos(), $config['max'] ?? null],
            ];

            foreach ($comparacoes as $campo => [$doEnum, $doConfig]) {
                // Comparacao estrita de valor com null tratado a parte: no
                // topo da escala null e "sem teto", e 0 nao pode passar por
                // igual a null.
                $iguais = $doEnum === null
                    ? $doConfig === null
                    : ($doConfig !== null && (int) $doEnum === (int) $doConfig);

                if (! $iguais) {
                    $divergencias[] = [
                        'faixa' => $faixa->value,
                        'campo' => $campo,
                        'enum' => $doEnum === null ? 'null' : (string) $doEnum,
                        'config' => $doConfig === null ? 'null' : (string) $doConfig,
                    ];
                }
            }
        }

        $doEnum = array_map(static fn (FaixaRanking $f): string => $f->value, FaixaRanking::cases());

        foreach (array_keys($faixasConfig) as $chave) {
            if (! in_array((string) $chave, $doEnum, true)) {
                $divergencias[] = [
                    'faixa' => (string) $chave,
                    'campo' => 'faixa',
                    'enum' => 'ausente',
                    'config' => 'presente',
                ];
            }
        }

        return $divergencias;
    }

    /**
     * rule_keys que cada adaptador registrado promete emitir.
     *
     * O contrato ModuleAdapter nao declara as chaves (ele traduz evento a
     * evento), entao a extracao e por reflexao sobre as constantes do
     * adaptador, que e onde os dois adaptadores existentes mantem o mapa
     * evento => marco. Um adaptador pode declarar `ruleKeys(): array` publico
     * e a reflexao e dispensada.
     *
     * @param  list<ModuleAdapter>  $adaptadores
     * @return array<string, list<string>>
     */
    public function ruleKeysPorModulo(array $adaptadores): array
    {
        $mapa = [];

        foreach ($adaptadores as $adaptador) {
            $modulo = $adaptador->modulo();
            $mapa[$modulo] = array_values(array_unique(array_merge(
                $mapa[$modulo] ?? [],
                $this->ruleKeysDoAdaptador($adaptador),
            )));
            sort($mapa[$modulo]);
        }

        ksort($mapa);

        return $mapa;
    }

    /**
     * @return list<string>
     */
    public function ruleKeysDoAdaptador(ModuleAdapter $adaptador): array
    {
        if (method_exists($adaptador, 'ruleKeys')) {
            /** @var list<string> $declaradas */
            $declaradas = $adaptador->ruleKeys();

            return array_values(array_unique(array_map('strval', $declaradas)));
        }

        $encontradas = [];

        foreach ((new ReflectionClass($adaptador))->getConstants() as $valor) {
            $this->colherRuleKeys($valor, $encontradas);
        }

        return array_values(array_unique($encontradas));
    }

    /**
     * Varre o valor de uma constante atras de rule_keys.
     *
     * Duas fontes: a chave explicita `rule_key` do array de marcos e, como
     * rede de seguranca, qualquer string no formato canonico de rule_key -
     * UM ponto separando modulo e marco (`rat.registro_completo`). O formato
     * distingue rule_key de nome de evento (`rat.ocorrencia.registrada`, dois
     * pontos) e de familia (`rat_registro_completo`, nenhum).
     *
     * @param  list<string>  $encontradas
     */
    private function colherRuleKeys(mixed $valor, array &$encontradas): void
    {
        if (is_array($valor)) {
            foreach ($valor as $chave => $item) {
                if ($chave === 'rule_key' && is_string($item) && trim($item) !== '') {
                    $encontradas[] = trim($item);

                    continue;
                }

                $this->colherRuleKeys($item, $encontradas);
            }

            return;
        }

        if (is_string($valor) && preg_match('/^[a-z][a-z0-9_]*\.[a-z0-9_]+$/', $valor) === 1) {
            $encontradas[] = $valor;
        }
    }

    /**
     * Catalogo auditado: o que esta PUBLICADO na database de ranking. O seeder
     * so entra como fallback quando a tabela ainda nao existe ou esta vazia -
     * auditar o seeder quando o banco ja tem regras esconderia exatamente a
     * divergencia entre os dois.
     *
     * @return list<array<string, mixed>>
     */
    private function catalogo(): array
    {
        try {
            $linhas = DB::connection((string) config('ranking.conexao', 'ranking'))
                ->table('ranking.regras')
                ->whereNull('vigente_ate')
                ->orderBy('modulo')
                ->orderBy('rule_key')
                ->get()
                ->map(static fn (object $linha): array => (array) $linha)
                ->all();

            if ($linhas !== []) {
                $this->components->twoColumnDetail('Fonte do catalogo', 'ranking.regras (vigentes)');

                return $linhas;
            }

            $this->components->warn('ranking.regras vazia - auditando o catalogo do seeder.');
        } catch (Throwable $e) {
            $this->components->warn('ranking.regras indisponivel ('.$e->getMessage().') - auditando o catalogo do seeder.');
        }

        $this->components->twoColumnDetail('Fonte do catalogo', 'RankingRegraSeeder::catalogo()');

        return RankingRegraSeeder::catalogo();
    }

    /**
     * @return list<ModuleAdapter>
     */
    private function resolverAdaptadores(): array
    {
        $injetados = is_array($this->adaptadores)
            ? $this->adaptadores
            : iterator_to_array($this->adaptadores);

        if ($injetados !== []) {
            return array_values($injetados);
        }

        try {
            return array_values(iterator_to_array($this->getLaravel()->tagged('ranking.adaptadores')));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Diretorios reais de app/Modules. A comparacao e contra o disco, e nao
     * contra uma lista mantida a mao, porque a lista a mao e o proprio
     * esquecimento que esta auditoria procura.
     *
     * @return list<string>
     */
    private function modulosEmDisco(): array
    {
        $raiz = app_path('Modules');

        if (! is_dir($raiz)) {
            return [];
        }

        $modulos = [];

        foreach ((array) scandir($raiz) as $entrada) {
            if (! is_string($entrada) || $entrada === '.' || $entrada === '..') {
                continue;
            }

            if (is_dir($raiz.DIRECTORY_SEPARATOR.$entrada)) {
                $modulos[] = $entrada;
            }
        }

        sort($modulos);

        return $modulos;
    }

    /**
     * @param  array<string, mixed>  $relatorio
     */
    private function renderizar(array $relatorio): void
    {
        foreach ($relatorio['resumo'] as $rotulo => $valor) {
            $this->components->twoColumnDetail(str_replace('_', ' ', (string) $rotulo), (string) $valor);
        }

        $this->newLine();

        $this->secaoErro(
            'Adaptador emite rule_key fora do catalogo',
            $relatorio['adaptador_sem_regra'],
            ['Modulo', 'rule_key', 'Causa'],
            static fn (array $l): array => [$l['modulo'], $l['rule_key'], $l['causa']],
        );

        $this->secaoErro(
            'Regra habilitada sem fonte comprovada',
            $relatorio['habilitada_sem_fonte'],
            ['Modulo', 'rule_key', 'Causa'],
            static fn (array $l): array => [$l['modulo'], $l['rule_key'], $l['causa']],
        );

        $this->secaoErro(
            'Divergencia FaixaRanking x config(ranking.faixas)',
            $relatorio['faixas_divergentes'],
            ['Faixa', 'Campo', 'Enum', 'Config'],
            static fn (array $l): array => [$l['faixa'], $l['campo'], $l['enum'], $l['config']],
        );

        $this->secaoErro(
            'Modulos sem entrada no catalogo nem em modulos_sem_premio',
            array_map(static fn (string $m): array => ['modulo' => $m], $relatorio['modulos_sem_cobertura']),
            ['Modulo'],
            static fn (array $l): array => [$l['modulo']],
        );

        $this->secaoPendencia(
            'Marcos sem adaptador (lacuna de cobertura, nao erro)',
            $relatorio['marcos_sem_adaptador'],
            ['Modulo', 'rule_key', 'Motivo'],
            static fn (array $l): array => [$l['modulo'], $l['rule_key'], $l['motivo']],
        );

        $this->secaoPendencia(
            'Entradas de cobertura sem modulo correspondente em disco',
            array_map(static fn (string $m): array => ['modulo' => $m], $relatorio['cobertos_sem_modulo']),
            ['Modulo'],
            static fn (array $l): array => [$l['modulo']],
        );

        $compartilhadas = $relatorio['familias_compartilhadas'];

        $this->line('<fg=cyan>Familias compartilhadas entre modulos</>');

        if ($compartilhadas === []) {
            $this->components->bulletList(['nenhuma']);
        } else {
            $this->table(
                ['Familia', 'Modulos', 'pontos_base', 'Sinalizada'],
                array_map(static function (array $familia): array {
                    $pontos = [];

                    foreach ($familia['pontos'] as $origem => $valor) {
                        $pontos[] = $origem.'='.$valor;
                    }

                    return [
                        $familia['familia'],
                        implode(' + ', $familia['modulos']),
                        implode(' | ', $pontos),
                        $familia['pontos_divergentes'] ? 'DIVERGENTE' : 'coerente',
                    ];
                }, $compartilhadas),
            );
        }

        $this->newLine();
    }

    /**
     * @param  list<array<string, mixed>>  $linhas
     * @param  list<string>  $cabecalho
     * @param  callable(array<string, mixed>): list<string>  $mapa
     */
    private function secaoErro(string $titulo, array $linhas, array $cabecalho, callable $mapa): void
    {
        if ($linhas === []) {
            $this->components->task("OK - {$titulo}", static fn (): bool => true);

            return;
        }

        $this->components->task("ERRO - {$titulo}", static fn (): bool => false);
        $this->table($cabecalho, array_map($mapa, $linhas));
    }

    /**
     * @param  list<array<string, mixed>>  $linhas
     * @param  list<string>  $cabecalho
     * @param  callable(array<string, mixed>): list<string>  $mapa
     */
    private function secaoPendencia(string $titulo, array $linhas, array $cabecalho, callable $mapa): void
    {
        if ($linhas === []) {
            $this->components->task("OK - {$titulo}", static fn (): bool => true);

            return;
        }

        $this->line(sprintf('<fg=yellow>PENDENCIA</> - %s (%d)', $titulo, count($linhas)));
        $this->table($cabecalho, array_map($mapa, $linhas));
    }
}
