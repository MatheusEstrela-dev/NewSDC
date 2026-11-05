<?php

declare(strict_types=1);

namespace Tests\Unit\Dto;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Varredura estatica da camada DTO contra o contrato da secao 7 de
 * docs/plans/plano_dtos.md.
 *
 * O catalogo abaixo lista todo DTO da aplicacao com a sua funcao. DTO novo
 * sem catalogo derruba o primeiro teste: a ideia e obrigar quem cria o DTO a
 * dizer se ele e de escrita (e portanto precisa de teste de fluxo) ou nao.
 *
 * Categorias:
 *  - escrita     entrada HTTP/API que termina em Model (Request -> DTO -> Service)
 *  - filtro      entrada de consulta (query string)
 *  - saida       leitura montada de Model/consulta
 *  - integracao  normalizacao de feed externo ou resultado de integracao
 *  - dominio     contrato interno (regra, spec, objeto de valor)
 *  - fora        fora do escopo do plano (Core/Actions)
 */
final class ArquiteturaDaCamadaDtoTest extends TestCase
{
    public const CATALOGO = [
        // Acessos
        'App\Modules\Acessos\DTOs\CadastroAcessoDTO' => 'escrita',
        'App\Modules\Acessos\DTOs\ChecagemDiretorio' => 'integracao',
        'App\Modules\Acessos\DTOs\ContaDiretorio' => 'integracao',
        'App\Modules\Acessos\DTOs\ReferenciaConta' => 'integracao',
        'App\Modules\Acessos\DTOs\ResultadoOperacao' => 'integracao',
        // Ajuda Humanitaria
        'App\Modules\AjudaHumanitaria\DTOs\EntregaBeneficiarioDTO' => 'escrita',
        'App\Modules\AjudaHumanitaria\DTOs\ItemPedidoDTO' => 'escrita',
        'App\Modules\AjudaHumanitaria\DTOs\ParecerDTO' => 'escrita',
        'App\Modules\AjudaHumanitaria\DTOs\PedidoAhDTO' => 'escrita',
        'App\Modules\AjudaHumanitaria\DTOs\TransicaoPedidoDTO' => 'escrita',
        // Cedec
        'App\Modules\Cedec\DTOs\PrefeituraFiltroDTO' => 'filtro',
        // Cemaden
        'App\Modules\Cemaden\DTOs\EstacaoCemadenDTO' => 'integracao',
        'App\Modules\Cemaden\DTOs\LeituraCemadenDTO' => 'integracao',
        // Cisterna
        'App\Modules\Cisterna\DTOs\BeneficiarioDTO' => 'escrita',
        'App\Modules\Cisterna\DTOs\ComunidadeDTO' => 'escrita',
        'App\Modules\Cisterna\DTOs\ItemConferidoDTO' => 'escrita',
        'App\Modules\Cisterna\DTOs\LoteDTO' => 'escrita',
        'App\Modules\Cisterna\DTOs\NotificacaoDTO' => 'escrita',
        'App\Modules\Cisterna\DTOs\OrdemServicoDTO' => 'escrita',
        'App\Modules\Cisterna\DTOs\VistoriaDTO' => 'escrita',
        // Compdec
        'App\Modules\Compdec\DTOs\AnexoDTO' => 'escrita',
        'App\Modules\Compdec\DTOs\EquipeDTO' => 'escrita',
        'App\Modules\Compdec\DTOs\OrgaoDTO' => 'escrita',
        'App\Modules\Compdec\DTOs\PlanoContingenciaDTO' => 'escrita',
        'App\Modules\Compdec\DTOs\PrefeituraDTO' => 'escrita',
        // Core (fora do plano)
        'App\Core\Actions\DTOs\ActionConfigDTO' => 'fora',
        // Dashboard
        'App\Modules\Dashboard\DTOs\DashboardStatsDTO' => 'saida',
        // Decretacoes
        'App\Modules\Decretacoes\DTO\ProcessoRequestDTO' => 'escrita',
        'App\Modules\Decretacoes\DTO\CampoData' => 'escrita',
        'App\Modules\Decretacoes\DTO\ItemData' => 'escrita',
        'App\Modules\Decretacoes\DTO\DesastreData' => 'escrita',
        'App\Modules\Decretacoes\DTO\CategoriaData' => 'escrita',
        'App\Modules\Decretacoes\DTO\MunicipioData' => 'escrita',
        'App\Modules\Decretacoes\DTO\DesastreSubmissionDTO' => 'escrita',
        'App\Modules\Decretacoes\DTO\RedecDTO' => 'saida',
        // Demandas
        'App\Modules\Demandas\DTOs\AtualizarDemandaData' => 'escrita',
        'App\Modules\Demandas\DTOs\CriarDemandaData' => 'escrita',
        'App\Modules\Demandas\DTOs\FiltroDemanda' => 'filtro',
        'App\Modules\Demandas\DTOs\ResolucaoDemandaData' => 'escrita',
        // Estoque
        'App\Modules\Estoque\DTOs\EntradaEstoqueDTO' => 'escrita',
        'App\Modules\Estoque\DTOs\ProdutoDTO' => 'escrita',
        'App\Modules\Estoque\DTOs\SaidaEstoqueDTO' => 'escrita',
        // Geoespacial
        'App\Modules\Geoespacial\DTOs\CamadaGeoDTO' => 'integracao',
        'App\Modules\Geoespacial\DTOs\FeicaoKmlDTO' => 'integracao',
        'App\Modules\Geoespacial\DTOs\MetadadoCamadaDTO' => 'escrita',
        'App\Modules\Geoespacial\DTOs\ProcedenciaDTO' => 'integracao',
        // Inmet
        'App\Modules\Inmet\DTOs\EstacaoDTO' => 'integracao',
        'App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO' => 'integracao',
        // Inventario
        'App\Modules\Inventario\DTOs\EmprestimoAvulsoData' => 'escrita',
        'App\Modules\Inventario\DTOs\EquipamentoData' => 'escrita',
        'App\Modules\Inventario\DTOs\EstacaoData' => 'escrita',
        'App\Modules\Inventario\DTOs\PessoaRemanejadaData' => 'escrita',
        'App\Modules\Inventario\DTOs\RemanejamentoData' => 'escrita',
        // Medalhao
        'App\Modules\Medalhao\DTOs\PayloadBruto' => 'integracao',
        // Notificacoes
        'App\Modules\Notificacoes\DTO\NotificacaoSpec' => 'dominio',
        // Pae
        'App\Modules\Pae\DTOs\EmitirCcpaeDTO' => 'escrita',
        'App\Modules\Pae\DTOs\PaeFormAnexoDTO' => 'escrita',
        'App\Modules\Pae\DTOs\PaeFormInfoGeraisDTO' => 'escrita',
        'App\Modules\Pae\DTOs\PaeFormObjetivoDTO' => 'escrita',
        // PlanCon
        'App\Modules\PlanCon\DTOs\MunicipioDTO' => 'saida',
        'App\Modules\PlanCon\DTOs\PlanConStatsDTO' => 'saida',
        // Plantao
        'App\Modules\Plantao\DTOs\MovimentacaoDTO' => 'saida',
        'App\Modules\Plantao\DTOs\PlantaoDetailDTO' => 'saida',
        'App\Modules\Plantao\DTOs\PlantaoListDTO' => 'saida',
        'App\Modules\Plantao\DTOs\ReservaListDTO' => 'saida',
        'App\Modules\Plantao\DTOs\SnapshotDTO' => 'saida',
        'App\Modules\Plantao\DTOs\ViaturaListDTO' => 'saida',
        // Pmda
        'App\Modules\Pmda\DTOs\PmdaPlanoDTO' => 'escrita',
        // Ranking
        'App\Modules\Ranking\DTOs\ContextoInstitucional' => 'dominio',
        'App\Modules\Ranking\DTOs\FatoNormalizado' => 'dominio',
        'App\Modules\Ranking\DTOs\FiltroPlacar' => 'filtro',
        'App\Modules\Ranking\DTOs\RegraVigente' => 'dominio',
        'App\Modules\Ranking\DTOs\ScoreDecisionData' => 'dominio',
        'App\Modules\Ranking\DTOs\ScoreRuleData' => 'dominio',
        'App\Modules\Ranking\DTOs\ScoreTransactionData' => 'dominio',
        // Rat
        'App\Modules\Rat\DTOs\RatAgenteDTO' => 'escrita',
        'App\Modules\Rat\DTOs\RatBoDTO' => 'escrita',
        'App\Modules\Rat\DTOs\RatDadosGeraisDTO' => 'escrita',
        'App\Modules\Rat\DTOs\RatEnvolvidoDTO' => 'escrita',
        'App\Modules\Rat\DTOs\RatHistoricoDTO' => 'escrita',
        'App\Modules\Rat\DTOs\RatRecursoDTO' => 'escrita',
        'App\Modules\Rat\DTOs\RatRelatosDTO' => 'escrita',
        'App\Modules\Rat\DTOs\RatTermoInterdicaoDTO' => 'escrita',
        'App\Modules\Rat\DTOs\RatVistoriaDTO' => 'escrita',
        // Resgate
        'App\Modules\Resgate\DTOs\Carteira' => 'dominio',
        // Sismos
        'App\Modules\Sismos\DTOs\SismoDTO' => 'integracao',
        // Suporte
        'App\Modules\Suporte\DTOs\CreateTicketDTO' => 'escrita',
        // Tdap
        'App\Modules\Tdap\DTOs\AtaDTO' => 'escrita',
        'App\Modules\Tdap\DTOs\CaminhaoDTO' => 'escrita',
        'App\Modules\Tdap\DTOs\CronoCaminhaoDTO' => 'escrita',
        'App\Modules\Tdap\DTOs\CronoViagemDTO' => 'escrita',
        'App\Modules\Tdap\DTOs\CronogramaDTO' => 'escrita',
        'App\Modules\Tdap\DTOs\LoteDTO' => 'escrita',
        'App\Modules\Tdap\DTOs\PrestadorDTO' => 'escrita',
        'App\Modules\Tdap\DTOs\VinculoPmdaDTO' => 'integracao',
        'App\Modules\Tdap\DTOs\VistoriaDTO' => 'escrita',
        // Treinamento
        'App\Modules\Treinamento\DTOs\ResultadoAutenticacaoCidadao' => 'integracao',
    ];

    /** Traits compartilhados: infraestrutura, nao DTO. */
    private const TRAITS = [
        'App\Modules\Shared\DTOs\Traits\LeituraComPresenca',
        'App\Modules\Shared\DTOs\Traits\SafeCastingTrait',
    ];

    private static function raiz(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * Todo arquivo PHP em pasta DTO/DTOs da aplicacao.
     *
     * @return list<string>
     */
    private static function arquivosDto(): array
    {
        $arquivos = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::raiz() . '/app', \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $arquivo) {
            $caminho = str_replace('\\', '/', $arquivo->getPathname());
            if (str_ends_with($caminho, '.php') && preg_match('#/DTOs?/#', $caminho)) {
                $arquivos[] = $caminho;
            }
        }
        sort($arquivos);

        return $arquivos;
    }

    /**
     * Classes/traits declarados num arquivo (namespace + nome), via tokens.
     *
     * @return list<string>
     */
    private static function declaracoes(string $arquivo): array
    {
        $tokens = token_get_all((string) file_get_contents($arquivo));
        $namespace = '';
        $nomes = [];
        $n = count($tokens);
        for ($i = 0; $i < $n; $i++) {
            if (! is_array($tokens[$i])) {
                continue;
            }
            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $n && $tokens[$j] !== ';'; $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_NAME_QUALIFIED, T_STRING], true)) {
                        $namespace .= $tokens[$j][1];
                    }
                }
            }
            if (in_array($tokens[$i][0], [T_CLASS, T_TRAIT, T_ENUM, T_INTERFACE], true)) {
                // `Foo::class` nao e declaracao.
                $anterior = $i - 1;
                while ($anterior >= 0 && is_array($tokens[$anterior]) && $tokens[$anterior][0] === T_WHITESPACE) {
                    $anterior--;
                }
                if ($anterior >= 0 && is_array($tokens[$anterior]) && $tokens[$anterior][0] === T_DOUBLE_COLON) {
                    continue;
                }
                for ($j = $i + 1; $j < $n; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $nomes[] = $namespace . '\\' . $tokens[$j][1];
                        break;
                    }
                }
            }
        }

        return $nomes;
    }

    /** @return array<string, string> classe => arquivo */
    private static function descobertos(): array
    {
        $mapa = [];
        foreach (self::arquivosDto() as $arquivo) {
            foreach (self::declaracoes($arquivo) as $classe) {
                $mapa[$classe] = $arquivo;
            }
        }

        return $mapa;
    }

    /** @return iterable<string, array{string}> */
    public static function dtosDeEntrada(): iterable
    {
        foreach (self::CATALOGO as $classe => $categoria) {
            if (in_array($categoria, ['escrita', 'filtro'], true)) {
                yield $classe => [$classe];
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function dtosDeEscrita(): iterable
    {
        foreach (self::CATALOGO as $classe => $categoria) {
            if ($categoria === 'escrita') {
                yield $classe => [$classe];
            }
        }
    }

    // ── Catalogo ────────────────────────────────────────────────────────────

    public function test_todo_dto_da_aplicacao_esta_no_catalogo_e_vice_versa(): void
    {
        $descobertos = array_diff(array_keys(self::descobertos()), self::TRAITS);
        $catalogados = array_keys(self::CATALOGO);

        $semCatalogo = array_values(array_diff($descobertos, $catalogados));
        $inexistentes = array_values(array_diff($catalogados, $descobertos));

        $this->assertSame([], $semCatalogo, 'DTO novo sem categoria no catalogo (e sem teste de fluxo): ' . implode(', ', $semCatalogo));
        $this->assertSame([], $inexistentes, 'Catalogo aponta DTO que nao existe mais: ' . implode(', ', $inexistentes));
        $this->assertCount(97, $catalogados, 'Total esperado: 93 arquivos - 2 traits = 91, e DecretacoesDTO.php declara 7 classes');
    }

    public function test_todo_dto_do_catalogo_carrega_pelo_autoload(): void
    {
        $falhas = [];
        foreach (array_keys(self::CATALOGO) as $classe) {
            if (! class_exists($classe)) {
                $falhas[] = $classe;
            }
        }
        $this->assertSame([], $falhas, 'Classe nao carrega pelo autoload: ' . implode(', ', $falhas));
    }

    // ── Regras que valem para todo DTO ──────────────────────────────────────

    public function test_todo_arquivo_dto_declara_strict_types(): void
    {
        $falhas = [];
        foreach (self::arquivosDto() as $arquivo) {
            if (! preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)/', (string) file_get_contents($arquivo))) {
                $falhas[] = $this->relativo($arquivo);
            }
        }
        $this->assertSame([], $falhas);
    }

    public function test_nenhum_dto_importa_ou_recebe_request(): void
    {
        $falhas = [];
        foreach (self::arquivosDto() as $arquivo) {
            $codigo = (string) file_get_contents($arquivo);
            if (preg_match('/^use\s+(Illuminate\\\\Http\\\\Request|Illuminate\\\\Foundation\\\\Http\\\\FormRequest|App\\\\[\w\\\\]*\\\\Requests\\\\\w+)\s*;/m', $codigo, $m)) {
                $falhas[] = $this->relativo($arquivo) . ' importa ' . $m[1];
            }
        }

        foreach (array_keys(self::CATALOGO) as $classe) {
            foreach ((new ReflectionClass($classe))->getMethods() as $metodo) {
                if ($metodo->getDeclaringClass()->getName() !== $classe) {
                    continue;
                }
                foreach ($metodo->getParameters() as $p) {
                    $tipo = $p->getType();
                    if ($tipo instanceof ReflectionNamedType && ! $tipo->isBuiltin()
                        && is_a($tipo->getName(), \Illuminate\Http\Request::class, true)) {
                        $falhas[] = "{$classe}::{$metodo->getName()}() recebe {$tipo->getName()}";
                    }
                }
            }
        }

        $this->assertSame([], $falhas, implode("\n", $falhas));
    }

    // ── DTOs de entrada (escrita e filtro) ─────────────────────────────────

    #[DataProvider('dtosDeEntrada')]
    public function test_dto_de_entrada_e_imutavel(string $classe): void
    {
        $ref = new ReflectionClass($classe);
        $mutaveis = [];
        foreach ($ref->getProperties() as $prop) {
            if (! $prop->isStatic() && ! $prop->isReadOnly()) {
                $mutaveis[] = '$' . $prop->getName();
            }
        }

        $this->assertSame([], $mutaveis, "{$classe} tem propriedade mutavel: " . implode(', ', $mutaveis));
    }

    #[DataProvider('dtosDeEscrita')]
    public function test_dto_de_escrita_tem_factory_que_recebe_array(string $classe): void
    {
        $ref = new ReflectionClass($classe);
        $factories = [];
        foreach ($ref->getMethods(\ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC) as $m) {
            if (! $m->isPublic() || ! $m->isStatic() || $m->getDeclaringClass()->getName() !== $classe) {
                continue;
            }
            $retorno = $m->getReturnType();
            $retornaSiMesmo = $retorno instanceof ReflectionNamedType
                && in_array($retorno->getName(), ['self', 'static', $classe], true);
            $temParametroArray = false;
            foreach ($m->getParameters() as $p) {
                $t = $p->getType();
                if ($t instanceof ReflectionNamedType && $t->getName() === 'array') {
                    $temParametroArray = true;
                    break;
                }
            }
            if ($retornaSiMesmo && $temParametroArray) {
                $factories[] = $m->getName();
            }
        }

        $this->assertNotSame([], $factories, "{$classe} nao tem factory estatico que receba o array validado");
    }

    // ── Fronteiras entre camadas ────────────────────────────────────────────

    /**
     * Model nao conhece DTO de entrada: quem traduz DTO em atributos e o
     * Service. DTO de integracao/dominio pode entrar em metodo de transicao
     * do Model (OperacaoAd::confirmar(ResultadoOperacao)) -- isso e contrato
     * de dominio, nao fluxo de formulario.
     */
    public function test_model_nao_importa_dto_de_entrada(): void
    {
        $entrada = [];
        foreach (self::CATALOGO as $classe => $categoria) {
            if (in_array($categoria, ['escrita', 'filtro'], true)) {
                $entrada[$classe] = true;
            }
        }
        $falhas = [];
        $pastas = glob(self::raiz() . '/app/Modules/*/Models') ?: [];
        $pastas[] = self::raiz() . '/app/Models';
        foreach ($pastas as $pasta) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pasta, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $arquivo) {
                if (! str_ends_with($arquivo->getFilename(), '.php')) {
                    continue;
                }
                preg_match_all('/^use\s+(App\\\\[\w\\\\]*\\\\DTOs?\\\\\w+)\s*;/m', (string) file_get_contents($arquivo->getPathname()), $m);
                foreach ($m[1] as $importado) {
                    if (isset($entrada[$importado])) {
                        $falhas[] = $this->relativo($arquivo->getPathname()) . ': use ' . $importado;
                    }
                }
            }
        }
        $this->assertSame([], $falhas, implode("\n", $falhas));
    }

    /**
     * Contrato 1: o DTO nasce de `validated()`. Montar DTO de escrita com
     * all()/input()/only()/except() leva chave sem regra ate o Model.
     */
    public function test_dto_de_escrita_nao_nasce_de_entrada_nao_validada(): void
    {
        $curtos = [];
        foreach (self::CATALOGO as $classe => $categoria) {
            if ($categoria === 'escrita') {
                $curtos[] = preg_quote(substr($classe, strrpos($classe, '\\') + 1), '/');
            }
        }
        $padrao = '/\b(' . implode('|', $curtos) . ')::\w+\(\s*\$request->(all|input|only|except|post|json)\(/';

        $falhas = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::raiz() . '/app', \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $arquivo) {
            if (! str_ends_with($arquivo->getFilename(), '.php')) {
                continue;
            }
            foreach (file($arquivo->getPathname()) ?: [] as $n => $linha) {
                if (preg_match($padrao, $linha)) {
                    $falhas[] = $this->relativo($arquivo->getPathname()) . ':' . ($n + 1) . ' ' . trim($linha);
                }
            }
        }
        $this->assertSame([], $falhas, implode("\n", $falhas));
    }

    /**
     * Contrato 1: nenhum Service reconstroi DTO de entrada a partir de array.
     * O Service recebe o DTO pronto do Controller (ou de RatRelatosDTO).
     */
    public function test_service_nao_reconstroi_dto_de_escrita(): void
    {
        $curtos = [];
        foreach (self::CATALOGO as $classe => $categoria) {
            if ($categoria === 'escrita') {
                $curtos[] = preg_quote(substr($classe, strrpos($classe, '\\') + 1), '/');
            }
        }
        $padrao = '/\b(' . implode('|', $curtos) . ')::(fromArray|fromRequest|fromValidated|deValidados|deFormulario)\(/';

        $falhas = [];
        $pastas = glob(self::raiz() . '/app/Modules/*/Services') ?: [];
        if (is_dir(self::raiz() . '/app/Services')) {
            $pastas[] = self::raiz() . '/app/Services';
        }
        foreach ($pastas as $pasta) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pasta, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $arquivo) {
                if (! str_ends_with($arquivo->getFilename(), '.php')) {
                    continue;
                }
                foreach (file($arquivo->getPathname()) ?: [] as $n => $linha) {
                    if (preg_match($padrao, $linha)) {
                        $falhas[] = $this->relativo($arquivo->getPathname()) . ':' . ($n + 1) . ' ' . trim($linha);
                    }
                }
            }
        }
        $this->assertSame([], $falhas, implode("\n", $falhas));
    }

    private function relativo(string $caminho): string
    {
        return ltrim(str_replace([str_replace('\\', '/', self::raiz()), '\\'], ['', '/'], str_replace('\\', '/', $caminho)), '/');
    }
}
