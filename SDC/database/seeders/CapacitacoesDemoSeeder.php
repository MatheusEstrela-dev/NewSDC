<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Database\QueryBudgetGuard;
use App\Models\User;
use App\Modules\Treinamento\Enums\TipoEventoCurso;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\CertificadoService;
use App\Modules\Treinamento\Services\ConclusaoService;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Dados FICTICIOS do modulo Capacitacoes/Treinamento para conferir as telas.
 *
 * Roda so sob demanda (nunca entra no DatabaseSeeder) e recusa produção:
 *
 *   php artisan db:seed --class=CapacitacoesDemoSeeder
 *   php artisan db:seed --class=CapacitacoesDemoLimpezaSeeder   (remove so os dados demo)
 *
 * Pre-requisitos: migrations de database/migrations/treinamento aplicadas na
 * conexao do Treinamento (a tabela curso_avaliacoes vive na migration principal
 * 2026_11_05_210000_create_treinamento_schemas_tables, metodo criarAvaliacaoDoCurso)
 * e roles/permissoes já criadas (RolesAndPermissionsSeeder / permissions:sincronizar).
 * Classe nova no container exige `composer dump-autoload -o` e `octane:reload`.
 *
 * COMO OS DADOS SAO MARCADOS (e por isso a limpeza não toca em nada seu)
 * - cursos e videos: titulo comeca com "[DEMO] ";
 * - servidores e cidadaos: e-mail @demo.test, CPF sintetico INVALIDO (prefixo 99,
 *   segundo digito verificador errado de proposito);
 * - tudo que depende deles (turmas, inscricoes, certificados...) sai por cascata
 *   ou por id.
 * Reexecutar apaga os cursos [DEMO] e recria tudo; as contas demo são mantidas
 * (mesmo id) e so tem a senha/papeis reafirmados. Nada alem do que e demo e apagado.
 *
 * LOGIN (senha única das contas demo: Demo@2026!). O login do SDC e por CPF.
 *   Servidor (SDC, tela de login normal):
 *     gestor         CPF 99000010102  demo.gestor@demo.test        cargo manager + todas as permissões do módulo
 *     professor 1    CPF 99000020167  demo.professor1@demo.test    (professor 2: 99000020248, 3: 99000020329)
 *     aluno          CPF 99000030120  demo.aluno@demo.test         (Fernanda; inscrita em vários cursos, em vários estados)
 *     servidores     demo.servidor01..19@demo.test, CPF 99000030201 (01) ... 99000032092 (19)
 *   Cidadão (Portal do Cidadão):
 *     CPF 99000100195  demo.cidadao@demo.test (verificado e ativo, inscrito em vários cursos)
 *     demo.cidadao01..13  CPF 99000100276 ... 99000101400  (verificados e ativos)
 *     demo.cidadao14/15   CPF 99000101591 / 99000101671    (e-mail NÃO verificado: login pede confirmação)
 *     demo.cidadao16/17   CPF 99000101752 / 99000101833    (conta inativa)
 *   Os CPFs são sintéticos e inválidos de propósito (prefixo 99, dígito verificador errado).
 *
 * AVALIACAO DO CURSO (etapa final obrigatoria: sem ela não há conclusão nem certificado)
 *   Toda inscrição concluída tem avaliação, exceto as "legado" (concluídas antes da regra).
 *   Requisitos cumpridos e avaliação pendente (etapa final liberada):
 *     aluno demo   EAD "Gestão de Abrigos Temporários" (sala) e evento "Seminário Estadual..." (Meus cursos)
 *     cidadão demo EAD "Gestão de Riscos... COBRADE" (sala) e semipresencial "Gestão Integrada..." (Minhas inscrições)
 *     outros servidores: servidor13 (EAD SINPDEC), servidor01 (SCI), servidor09 (Seminário), servidor02 (Roda de Conversa online)
 *   Trilha incompleta (etapa final com cadeado): aluno demo no EAD "Introdução ao SINPDEC";
 *     cidadão demo no EAD "Primeiros Socorros em Situações de Desastre".
 */
final class CapacitacoesDemoSeeder extends Seeder
{
    public const PREFIXO = '[DEMO] ';

    public const DOMINIO = 'demo.test';

    public const SENHA = 'Demo@2026!';

    private const COMENTARIOS = [
        'Conteúdo muito bom e aplicavel ao dia a dia da COMPDEC do meu município.',
        'Gostei dos exemplos da Operação Chuvas. Faltou um pouco mais de tempo para a prática.',
        'Material claro. Sugiro incluir mais estudos de caso de Minas Gerais.',
        'A plataforma travou algumas vezes, mas o curso em si e excelente.',
        'Carga horária adequada e professores atenciosos.',
        'Esperava mais profundidade no tema de S2iD.',
    ];

    /** @var array<string, int> */
    private array $mun = [];

    /** @var array<string, int> */
    private array $videos = [];

    private int $gestor = 0;

    /** @var list<int> */
    private array $prof = [];

    /** @var list<int> */
    private array $srv = [];

    /** @var list<int> */
    private array $cid = [];

    private int $contadorProva = 0;

    public function run(): void
    {
        self::recusarEmProducao();

        // Carga em massa deliberada: o número de queries cresce com o volume dos dados, não ha N+1.
        app(QueryBudgetGuard::class)->isentar();

        DB::transaction(function (): void {
            ConexaoTreinamento::transacao(fn () => self::limparConteudo());
            $this->criarPessoas();
            ConexaoTreinamento::transacao(function (): void {
                $this->carregarMunicipios();
                $this->criarVideos();
                foreach ($this->cursos() as $curso) {
                    $this->semear($curso);
                }
                $this->credenciais();
            });
        });

        $this->command?->info('Capacitacoes demo criadas. Senha das contas demo: '.self::SENHA);
        foreach (self::contagens() as $rotulo => $valor) {
            $this->command?->line(sprintf('  %-34s %s', $rotulo, is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor));
        }
        $this->command?->line('  Logins (CPF): gestor '.self::cpfFicticio(101).' | professor1 '.self::cpfFicticio(201).' | aluno '.self::cpfFicticio(301).' | cidadao '.self::cpfFicticio(1001));
    }

    // ─── Limpeza ────────────────────────────────────────────────────────────

    /**
     * Remove TODOS os dados demo (conteúdo e contas). Nada que não seja demo.
     *
     * @return array<string, mixed> contagens de antes da limpeza
     */
    public static function limpar(): array
    {
        self::recusarEmProducao();
        app(QueryBudgetGuard::class)->isentar();

        return DB::transaction(function (): array {
            $antes = self::contagens();
            ConexaoTreinamento::transacao(function (): void {
                self::limparConteudo();
                self::limparCidadaos();
            });
            self::limparServidores();

            return $antes;
        });
    }

    /** Cursos [DEMO] e tudo que pende deles ou das contas demo. */
    private static function limparConteudo(): void
    {
        $db = ConexaoTreinamento::conexao();
        $cursos = $db->table('treinamento.treinamentos')->where('titulo', 'like', self::PREFIXO.'%')->pluck('id')->all();
        $servidores = DB::table('users')->where('email', 'like', '%@'.self::DOMINIO)->pluck('id')->all();
        $cidadaos = $db->table('cadastro.cidadaos')->where('email', 'like', '%@'.self::DOMINIO)->pluck('id')->all();

        $inscricoes = $db->table('treinamento.inscricoes')
            ->where(function ($q) use ($cursos, $servidores, $cidadaos): void {
                $q->whereIn('treinamento_id', $cursos)
                    ->orWhere(fn ($q2) => $q2->where('inscrito_type', User::class)->whereIn('inscrito_id', $servidores))
                    ->orWhere(fn ($q2) => $q2->where('inscrito_type', Cidadao::class)->whereIn('inscrito_id', $cidadaos));
            })->pluck('id')->all();

        // certificados: FK RESTRICT para inscrição e curso, sai antes.
        $db->table('treinamento.certificados')
            ->where(fn ($q) => $q->whereIn('inscricao_id', $inscricoes)->orWhereIn('treinamento_id', $cursos))
            ->delete();
        // inscricoes levam frequencias, progresso, entregas, tentativas, liberacoes e avaliações por cascata
        // (e turma_id e RESTRICT: precisam sumir antes das turmas).
        $db->table('treinamento.inscricoes')->whereIn('id', $inscricoes)->delete();
        // curso leva modulos, itens, provas, turmas, cronograma, eventos e professores por cascata.
        $db->table('treinamento.treinamentos')->whereIn('id', $cursos)->delete();
        // modulo_itens.video_id e RESTRICT: videos so depois dos itens.
        $db->table('treinamento.videos')->where('titulo', 'like', self::PREFIXO.'%')->delete();
        $db->table('treinamento.credenciais_qr')->whereIn('user_id', $servidores)->delete();
        $db->table('treinamento.credencial_consultas')
            ->where(fn ($q) => $q->whereIn('user_id_consultado', $servidores)->orWhereIn('consultado_por', $servidores))
            ->delete();
    }

    private static function limparCidadaos(): void
    {
        ConexaoTreinamento::conexao()->table('cadastro.cidadaos')->where('email', 'like', '%@'.self::DOMINIO)->delete();
    }

    private static function limparServidores(): void
    {
        $ids = DB::table('users')->where('email', 'like', '%@'.self::DOMINIO)->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();
        DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();

        try {
            // Savepoint: uma FK de outro modulo não pode abortar a transacao inteira (Postgres).
            DB::transaction(fn () => DB::table('users')->whereIn('id', $ids)->delete());
        } catch (Throwable) {
            DB::table('users')->whereIn('id', $ids)->update(['active' => false, 'status' => 'inactive', 'deleted_at' => now()]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private static function recusarEmProducao(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('CapacitacoesDemoSeeder cria dados ficticios e não roda em produção.');
        }
    }

    // ─── Contagens (resumo e testes) ────────────────────────────────────────

    /** @return array<string, mixed> */
    public static function contagens(): array
    {
        $db = ConexaoTreinamento::conexao();
        $cursos = fn () => $db->table('treinamento.treinamentos')->where('titulo', 'like', self::PREFIXO.'%');
        $ids = $cursos()->pluck('id')->all();
        $por = fn (string $tabela, string $coluna, string $chave = 'treinamento_id') => $db->table($tabela)
            ->whereIn($chave, $ids)->selectRaw("$coluna AS k, COUNT(*) AS n")->groupBy($coluna)->orderBy($coluna)->pluck('n', 'k')->all();
        $moduloIds = $db->table('treinamento.modulos')->whereIn('treinamento_id', $ids)->pluck('id')->all();
        $itemIds = $db->table('treinamento.modulo_itens')->whereIn('modulo_id', $moduloIds)->pluck('id')->all();
        $inscricaoIds = $db->table('treinamento.inscricoes')->whereIn('treinamento_id', $ids)->pluck('id')->all();
        $contar = fn (string $tabela, string $col, array $in) => $db->table($tabela)->whereIn($col, $in)->count();

        return [
            'cursos' => count($ids),
            'cursos_por_tipo' => $por('treinamento.treinamentos', 'tipo', 'id'),
            'cursos_por_status' => $por('treinamento.treinamentos', 'status', 'id'),
            'cursos_por_situacao' => $por('treinamento.treinamentos', 'situacao', 'id'),
            'cursos_por_publico' => $por('treinamento.treinamentos', 'publico_alvo', 'id'),
            'cursos_por_categoria' => $por('treinamento.treinamentos', 'categoria', 'id'),
            'cursos_publicados' => $cursos()->whereNotNull('link_publico_slug')->whereNotNull('publicado_em')->count(),
            'turmas' => $db->table('treinamento.turmas')->whereIn('treinamento_id', $ids)->whereNull('deleted_at')->count(),
            'cronograma_por_tipo' => $por('treinamento.curso_cronogramas', 'tipo'),
            'eventos_por_tipo' => $por('treinamento.curso_eventos', 'tipo'),
            'modulos' => count($moduloIds),
            'itens_por_tipo' => $db->table('treinamento.modulo_itens')->whereIn('modulo_id', $moduloIds)->selectRaw('tipo AS k, COUNT(*) AS n')->groupBy('tipo')->orderBy('tipo')->pluck('n', 'k')->all(),
            'videos_por_status' => $db->table('treinamento.videos')->where('titulo', 'like', self::PREFIXO.'%')->selectRaw('status AS k, COUNT(*) AS n')->groupBy('status')->orderBy('status')->pluck('n', 'k')->all(),
            'provas' => $contar('treinamento.provas', 'modulo_item_id', $itemIds),
            'tentativas' => $contar('treinamento.prova_tentativas', 'inscricao_id', $inscricaoIds),
            'tentativas_abertas' => $db->table('treinamento.prova_tentativas')->whereIn('inscricao_id', $inscricaoIds)->whereNull('finalizada_em')->count(),
            'entregas_por_status' => $db->table('treinamento.projeto_entregas')->whereIn('inscricao_id', $inscricaoIds)->selectRaw('status AS k, COUNT(*) AS n')->groupBy('status')->orderBy('status')->pluck('n', 'k')->all(),
            'progresso_itens_por_status' => $db->table('treinamento.progresso_itens')->whereIn('inscricao_id', $inscricaoIds)->selectRaw('status AS k, COUNT(*) AS n')->groupBy('status')->orderBy('status')->pluck('n', 'k')->all(),
            'progresso_pastas' => $contar('treinamento.progresso_pastas', 'inscricao_id', $inscricaoIds),
            'frequencias_por_status' => $db->table('treinamento.frequencias')->whereIn('inscricao_id', $inscricaoIds)->selectRaw('status AS k, COUNT(*) AS n')->groupBy('status')->orderBy('status')->pluck('n', 'k')->all(),
            'professores_vinculados' => $db->table('treinamento.treinamento_professores')->whereIn('treinamento_id', $ids)->count(),
            'inscricoes' => count($inscricaoIds),
            'inscricoes_por_status' => $por('treinamento.inscricoes', 'status'),
            'inscricoes_servidor' => $db->table('treinamento.inscricoes')->whereIn('treinamento_id', $ids)->where('inscrito_type', User::class)->count(),
            'inscricoes_cidadao' => $db->table('treinamento.inscricoes')->whereIn('treinamento_id', $ids)->where('inscrito_type', Cidadao::class)->count(),
            'inscricoes_concluidas' => $db->table('treinamento.inscricoes')->whereIn('treinamento_id', $ids)->whereNotNull('concluido_em')->count(),
            'certificados_por_status' => $por('treinamento.certificados', 'status'),
            // Legado: concluiu antes da avaliacao obrigatoria (segue concluido e com certificado).
            'concluidas_sem_avaliacao' => $db->table('treinamento.inscricoes as i')->whereIn('i.treinamento_id', $ids)->whereNotNull('i.concluido_em')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('treinamento.curso_avaliacoes as a')->whereColumn('a.inscricao_id', 'i.id'))->count(),
            // Requisitos cumpridos e falta so avaliar (etapa final liberada), pela mesma regra do sistema.
            'avaliacoes_pendentes' => Inscricao::query()->whereIn('treinamento_id', $ids)->aprovadas()->whereNull('concluido_em')->whereDoesntHave('avaliacao')
                ->with('treinamento')->get()->filter(fn (Inscricao $i) => app(ConclusaoService::class)->requisitosCumpridos($i))->count(),
            'certificados_reemitidos' => $db->table('treinamento.certificados')->whereIn('treinamento_id', $ids)->whereNotNull('reemitido_em')->count(),
            'certificados_com_codigo' => $db->table('treinamento.certificados')->whereIn('treinamento_id', $ids)->whereNotNull('codigo_verificacao')->count(),
            'avaliacoes_por_nota' => $por('treinamento.curso_avaliacoes', 'nota'),
            'avaliacoes_com_comentario' => $db->table('treinamento.curso_avaliacoes')->whereIn('treinamento_id', $ids)->whereNotNull('comentario')->count(),
            'cidadaos' => $db->table('cadastro.cidadaos')->where('email', 'like', '%@'.self::DOMINIO)->count(),
            'cidadaos_nao_verificados' => $db->table('cadastro.cidadaos')->where('email', 'like', '%@'.self::DOMINIO)->whereNull('email_verified_at')->count(),
            'cidadaos_inativos' => $db->table('cadastro.cidadaos')->where('email', 'like', '%@'.self::DOMINIO)->where('ativo', false)->count(),
            'servidores' => DB::table('users')->where('email', 'like', '%@'.self::DOMINIO)->count(),
            'credenciais_qr' => $db->table('treinamento.credenciais_qr')->whereIn('user_id', DB::table('users')->where('email', 'like', '%@'.self::DOMINIO)->pluck('id')->all())->count(),
        ];
    }

    // ─── Pessoas ────────────────────────────────────────────────────────────

    private const PERMISSOES_GESTOR = [
        'treinamento.cursos.view', 'treinamento.cursos.create', 'treinamento.cursos.edit', 'treinamento.cursos.export',
        'treinamento.cursos.configurar', 'treinamento.cursos.cancelar', 'treinamento.cursos.pausar', 'treinamento.gestor.acessar',
        'treinamento.inscricoes.view', 'treinamento.inscricoes.aprovar', 'treinamento.inscricoes.reprovar', 'treinamento.inscricoes.manage',
        'treinamento.presencas.view', 'treinamento.presencas.registrar',
        'treinamento.certificados.view', 'treinamento.certificados.reemitir', 'treinamento.certificados.cancelar', 'treinamento.certificados.download',
        'treinamento.professor.acessar', 'treinamento.indicadores.view', 'treinamento.participantes.validar',
    ];

    private const PERMISSOES_PROFESSOR = [
        'treinamento.cursos.view', 'treinamento.professor.acessar', 'treinamento.inscricoes.view',
        'treinamento.presencas.view', 'treinamento.presencas.registrar', 'treinamento.certificados.view',
    ];

    private const PERMISSOES_ALUNO = ['treinamento.cursos.view', 'treinamento.certificados.view'];

    /** @var list<array{0:string,1:string}> nome, sexo */
    private const SERVIDORES = [
        ['Fernanda Lima Carvalho', 'F'], ['João Paulo Gomes Ribeiro', 'M'], ['Adriana Souza Nogueira', 'F'], ['Rodrigo Alves Meireles', 'M'],
        ['Luciana Martins Cardoso', 'F'], ['Thiago Resende Fonseca', 'M'], ['Camila Barbosa Teixeira', 'F'], ['Gustavo Henrique Pimenta', 'M'],
        ['Juliana Cristina Rezende', 'F'], ['Leandro Soares Vasconcelos', 'M'], ['Mariana Ávila Drummond', 'F'], ['Anderson Luiz Campos', 'M'],
        ['Cláudia Regina Santana', 'F'], ['Wellington Faria Magalhães', 'M'], ['Simone Aparecida Lopes', 'F'], ['Rafael Dutra Cunha', 'M'],
        ['Vanessa Pereira Coelho', 'F'], ['Márcio José Figueiredo', 'M'], ['Aline Monteiro Pacheco', 'F'], ['Fábio Henrique Tavares', ''],
    ];

    /** @var list<array{0:string,1:string}> */
    private const CIDADAOS = [
        ['Carlos Eduardo Teixeira Neves', 'M'], ['Maria das Graças Oliveira', 'F'], ['José Antônio Pereira da Silva', 'M'], ['Aparecida Rodrigues Faria', 'F'],
        ['Sebastião Lopes Moreira', 'M'], ['Luzia Maria Batista', 'F'], ['Geraldo Ferreira Campos', 'M'], ['Terezinha Vieira Santos', 'F'],
        ['Antônio Carlos Mendes', 'M'], ['Rosângela Pinto Araújo', 'F'], ['Wesley Gabriel Costa', 'M'], ['Eliane Cristina Rocha', 'F'],
        ['Valdemar Cunha Pires', 'M'], ['Joana Darc Matos', ''], ['Pedro Henrique Lacerda', 'M'], ['Larissa Gomes Azevedo', 'F'],
        ['Osvaldo Brandão Filho', 'M'], ['Benedita Souza Lima', 'F'],
    ];

    /** CPF sintetico: prefixo 99 + sequencial + 2o digito verificador errado (nunca e um CPF real valido). */
    public static function cpfFicticio(int $n): string
    {
        $base = '99'.str_pad((string) $n, 7, '0', STR_PAD_LEFT);
        $d1 = self::digito($base, 10);
        $d2 = (self::digito($base.$d1, 11) + 1) % 10;

        return $base.$d1.$d2;
    }

    private static function digito(string $s, int $peso): int
    {
        $soma = 0;
        foreach (str_split($s) as $c) {
            $soma += (int) $c * $peso--;
        }

        return (($soma * 10) % 11) % 10;
    }

    private function criarPessoas(): void
    {
        $hash = Hash::make(self::SENHA);
        $cargoManager = DB::table('roles')->where('slug', 'manager')->value('id');
        $cargoUser = DB::table('roles')->where('slug', 'user')->value('id');
        $orgao = null; // sem orgao: não vincula contas demo a orgaos reais

        foreach (array_merge(self::PERMISSOES_GESTOR, self::PERMISSOES_PROFESSOR, self::PERMISSOES_ALUNO) as $slug) {
            Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
        }

        $this->gestor = $this->servidor('demo.gestor@'.self::DOMINIO, 'Marcos Vinícius Andrade Pires', 101, $hash, $cargoManager, self::PERMISSOES_GESTOR, $orgao);
        $this->prof = [];
        foreach (['Renata Cristina Lacerda Moura', 'Eduardo Henrique Salgado Braga', 'Patrícia Helena Duarte Quintão'] as $i => $nome) {
            $this->prof[] = $this->servidor('demo.professor'.($i + 1).'@'.self::DOMINIO, $nome, 201 + $i, $hash, $cargoUser, self::PERMISSOES_PROFESSOR, $orgao);
        }
        $this->srv = [];
        foreach (self::SERVIDORES as $i => [$nome]) {
            $email = $i === 0 ? 'demo.aluno@'.self::DOMINIO : sprintf('demo.servidor%02d@%s', $i, self::DOMINIO);
            $this->srv[$i] = $this->servidor($email, $nome, 301 + $i, $hash, $cargoUser, self::PERMISSOES_ALUNO, $orgao);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $db = ConexaoTreinamento::conexao();
        $codigo = Hash::make('000000');
        $this->cid = [];
        foreach (self::CIDADAOS as $i => [$nome]) {
            $email = $i === 0 ? 'demo.cidadao@'.self::DOMINIO : sprintf('demo.cidadao%02d@%s', $i, self::DOMINIO);
            $verificado = ! in_array($i, [14, 15], true);
            $linha = [
                'name' => $nome, 'email' => $email, 'cpf' => self::cpfFicticio(1001 + $i), 'telefone' => sprintf('(31) 9%04d-%04d', 8000 + $i, 1000 + $i * 7),
                'password' => $hash, 'aceite_lgpd_em' => now()->subDays(40), 'ativo' => ! in_array($i, [16, 17], true),
                'email_verified_at' => $verificado ? now()->subDays(39) : null, 'last_login_at' => $verificado ? now()->subDays($i + 1) : null,
                'updated_at' => now(), 'deleted_at' => null,
            ];
            $id = $db->table('cadastro.cidadaos')->where('email', $email)->value('id');
            if ($id === null) {
                $id = $db->table('cadastro.cidadaos')->insertGetId($linha + ['created_at' => now()->subDays(40)]);
            } else {
                $db->table('cadastro.cidadaos')->where('id', $id)->update($linha);
            }
            $this->cid[$i] = (int) $id;

            $db->table('cadastro.cidadao_email_verificacoes')->where('cidadao_id', $id)->delete();
            if (! $verificado) {
                $db->table('cadastro.cidadao_email_verificacoes')->insert([
                    'cidadao_id' => $id, 'email' => $email, 'code_hash' => $codigo, 'code_attempts' => $i === 15 ? 3 : 0,
                    'expires_at' => $i === 14 ? now()->addHour() : now()->subDays(2), 'requested_ip' => '127.0.0.1', 'requested_user_agent' => 'seeder-demo',
                    'resend_count' => 1, 'last_resend_at' => now()->subMinutes(20), 'created_at' => now()->subHours(3), 'updated_at' => now()->subHours(3),
                ]);
            }
        }
    }

    /** @param  list<string>  $permissoes */
    private function servidor(string $email, string $nome, int $n, string $hash, mixed $cargo, array $permissoes, mixed $orgao): int
    {
        $linha = [
            'name' => $nome, 'email' => $email, 'cpf' => self::cpfFicticio($n), 'password' => $hash, 'active' => true, 'status' => 'active',
            'must_change_password' => false, 'welcome_tour_completed_at' => now(), 'email_verified_at' => now(), 'orgao_principal_id' => $orgao,
            'updated_at' => now(), 'deleted_at' => null,
        ];
        $id = DB::table('users')->where('email', $email)->value('id');
        if ($id === null) {
            $id = DB::table('users')->insertGetId($linha + ['created_at' => now()]);
        } else {
            DB::table('users')->where('id', $id)->update($linha);
        }

        DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $id)->delete();
        DB::table('model_has_permissions')->where('model_type', User::class)->where('model_id', $id)->delete();
        if ($cargo !== null) {
            DB::table('model_has_roles')->insert(['role_id' => $cargo, 'model_type' => User::class, 'model_id' => $id]);
        }
        $permIds = Permission::query()->where('guard_name', 'web')->whereIn('name', $permissoes)->pluck('id');
        DB::table('model_has_permissions')->insert($permIds->map(fn ($p) => ['permission_id' => $p, 'model_type' => User::class, 'model_id' => $id])->all());

        return (int) $id;
    }

    // ─── Apoio ──────────────────────────────────────────────────────────────

    private function t(string $tabela): Builder
    {
        return ConexaoTreinamento::conexao()->table('treinamento.'.$tabela);
    }

    private static function dia(int $dias, string $hora = '09:00'): CarbonImmutable
    {
        return CarbonImmutable::today()->addDays($dias)->setTimeFromTimeString($hora);
    }

    /** Nada acontece no futuro: o que já ocorreu fica no máximo "agora". */
    private static function passado(CarbonImmutable $d): CarbonImmutable
    {
        $max = CarbonImmutable::now()->subMinutes(10);

        return $d->greaterThan($max) ? $max : $d;
    }

    private function carregarMunicipios(): void
    {
        $this->mun = DB::table('municipios')->where('uf', 'MG')->pluck('id', 'nome')->map(fn ($id) => (int) $id)->all();
    }

    private function municipio(?string $nome): ?int
    {
        return $nome === null ? null : ($this->mun[$nome] ?? null);
    }

    // ─── Videos e provas ────────────────────────────────────────────────────

    private function criarVideos(): void
    {
        $p = $this->prof[0];
        $defs = [
            'v1' => ['Vídeo: Fundamentos do SINPDEC', 'PRONTO', 480], 'v2' => ['Vídeo: Preenchendo o FIDE passo a passo', 'PRONTO', 720],
            'v3' => ['Vídeo: Montando um abrigo temporário', 'PRONTO', 600], 'proc' => ['Vídeo: Operação Chuvas na prática', 'PROCESSANDO', null],
            'up' => ['Vídeo: Sala de Situação (upload em andamento)', 'UPLOAD_ABERTO', null], 'env' => ['Vídeo: Alertas e monitoramento', 'ENVIADO', null],
            'falha' => ['Vídeo: Simulado de evacuação', 'FALHOU', null], 'sem' => ['Vídeo: Rascunho sem arquivo', 'SEM_VIDEO', null],
        ];
        foreach ($defs as $k => [$titulo, $status, $dur]) {
            $id = $this->t('videos')->insertGetId([
                'titulo' => self::PREFIXO.$titulo, 'user_id' => $p, 'status' => $status,
                'original_path' => in_array($status, ['PROCESSANDO', 'ENVIADO', 'FALHOU', 'UPLOAD_ABERTO'], true) ? 'demo/videos/'.$k.'.mp4' : null,
                'tamanho_bytes' => $status === 'SEM_VIDEO' ? null : 83_000_000, 'hls_versao' => $status === 'PRONTO' ? 'demo-v1' : null,
                'duracao_segundos' => $dur, 'largura' => $status === 'PRONTO' ? 1280 : null, 'altura' => $status === 'PRONTO' ? 720 : null,
                'processado_em' => $status === 'PRONTO' ? now()->subDays(60) : null,
                'erro' => $status === 'FALHOU' ? 'Falha ao transcodificar: arquivo de origem corrompido (dado fictício).' : null,
                'tentativas' => $status === 'FALHOU' ? 3 : ($status === 'PROCESSANDO' ? 1 : 0), 'created_at' => now()->subDays(70), 'updated_at' => now()->subDays(5),
            ]);
            $this->videos[$k] = $id;

            if (in_array($status, ['UPLOAD_ABERTO', 'ENVIADO'], true)) {
                $this->t('video_uploads')->insert([
                    'id' => (string) Str::uuid(), 'video_id' => $id, 'user_id' => $p, 'nome_original' => $k.'.mp4', 'mime' => 'vídeo/mp4',
                    'tamanho_total' => 83_000_000, 'tamanho_parte' => 8_300_000, 'total_partes' => 10,
                    'partes_recebidas' => $status === 'ENVIADO' ? 10 : 4, 'tamanho_recebido' => $status === 'ENVIADO' ? 83_000_000 : 33_200_000,
                    'status' => $status === 'ENVIADO' ? 'COMPLETO' : 'ABERTO', 'expira_em' => now()->addDay(), 'created_at' => now()->subHours(5), 'updated_at' => now()->subHours(1),
                ]);
            }
        }
    }

    /** @return list<array{0:string,1:list<string>}> enunciado, alternativas (a 1a e a correta) */
    private function banco(): array
    {
        return [
            ['Qual lei instituiu a Política Nacional de Proteção e Defesa Civil (PNPDEC)?', ['Lei nº 12.608/2012', 'Lei nº 8.080/1990', 'Lei nº 9.433/1997', 'Lei nº 10.257/2001']],
            ['No S2iD, qual é a finalidade do Formulário de Informações do Desastre (FIDE)?', ['Registrar e caracterizar o desastre para o reconhecimento federal', 'Cadastrar servidores da COMPDEC', 'Solicitar diárias de deslocamento', 'Divulgar alertas a população']],
            ['O que a COBRADE classifica?', ['Os desastres, por categoria, grupo, subgrupo, tipo e subtipo', 'Os municípios por porte populacional', 'Os abrigos por capacidade', 'As rodovias por nível de risco']],
            ['Qual é uma ação típica da fase de preparação?', ['Elaboração e treinamento do Plano de Contingência', 'Reconstrução de pontes', 'Distribuição de cestas básicas', 'Reassentamento definitivo']],
            ['No SCI, quem tem a responsabilidade geral pelo gerenciamento do incidente?', ['Comandante do Incidente', 'Chefe de Logística', 'Oficial de Ligação', 'Chefe de Finanças']],
            ['Qual é a característica de um abrigo temporário bem gerido?', ['Cadastro das famílias, segurança, higiene e acolhimento', 'Acesso livre sem registro', 'Funcionamento sem coordenação', 'Permanência obrigatória de 90 dias']],
            ['O que são áreas de risco?', ['Áreas sujeitas a processos que podem causar danos a pessoas e bens', 'Áreas de preservacao sem ocupacao', 'Áreas exclusivamente rurais', 'Áreas com infraestrutura completa']],
            ['O que significa resiliência na gestão de riscos?', ['Capacidade de resistir, absorver e se recuperar dos efeitos de um desastre', 'Ausência total de ameaças', 'Evacuação permanente', 'Transferência de risco ao setor privado']],
            ['Qual a função do alerta de chuvas intensas emitido a população?', ['Permitir ações antecipadas de proteção', 'Substituir o Plano de Contingência', 'Registrar o desastre no S2iD', 'Autorizar a reconstrução']],
            ['Segundo a PNPDEC, a gestão de riscos é dever de:', ['União, Estados e Municípios, com participação da sociedade', 'Apenas do município', 'Apenas da União', 'Somente de entidades privadas']],
            ['Qual é a prioridade no atendimento inicial a uma vítima?', ['Garantir a segurança da cena e verificar consciência e respiração', 'Remover imediatamente a vítima', 'Oferecer água e alimento', 'Aguardar sem intervir']],
            ['O Plano de Contingência (PLANCON) deve ser:', ['Atualizado periodicamente e simulado com os atores envolvidos', 'Elaborado uma única vez e arquivado', 'Mantido em sigilo', 'Restrito à Defesa Civil estadual']],
        ];
    }

    /** @return array{id:int,min:float,max:int,questoes:list<array{id:int,alts:list<array{id:int,correta:bool}>}>} */
    private function criarProva(int $itemId, float $min, int $max, CarbonImmutable $quando): array
    {
        $provaId = $this->t('provas')->insertGetId(['modulo_item_id' => $itemId, 'nota_minima' => $min, 'max_tentativas' => $max, 'created_at' => $quando, 'updated_at' => $quando]);
        $banco = $this->banco();
        $questoes = [];
        for ($q = 0; $q < 4; $q++) {
            [$enunciado, $alts] = $banco[($this->contadorProva * 4 + $q) % count($banco)];
            $qid = $this->t('prova_questoes')->insertGetId(['prova_id' => $provaId, 'enunciado' => $enunciado, 'ordem' => $q + 1, 'created_at' => $quando, 'updated_at' => $quando]);
            $posCorreta = ($q + $this->contadorProva) % 4;
            $lista = $alts;
            $correta = array_shift($lista);
            array_splice($lista, $posCorreta, 0, [$correta]);
            $ar = [];
            foreach ($lista as $o => $texto) {
                $aid = $this->t('prova_alternativas')->insertGetId(['prova_questao_id' => $qid, 'texto' => $texto, 'correta' => $o === $posCorreta, 'ordem' => $o + 1, 'created_at' => $quando, 'updated_at' => $quando]);
                $ar[] = ['id' => $aid, 'correta' => $o === $posCorreta];
            }
            $questoes[] = ['id' => $qid, 'alts' => $ar];
        }
        $this->contadorProva++;

        return ['id' => $provaId, 'min' => $min, 'max' => $max, 'questoes' => $questoes];
    }

    /**
     * Tentativa enviada (acertos de 0 a 4) ou aberta (acertos = null: 2 respostas sem correcao).
     *
     * @param  array{id:int,min:float,max:int,questoes:list<array{id:int,alts:list<array{id:int,correta:bool}>}>}  $prova
     * @return float|null nota gravada (null se aberta)
     */
    private function tentativa(array $prova, int $inscricaoId, int $numero, ?int $acertos, CarbonImmutable $quando): ?float
    {
        $questoes = $prova['questoes'];
        $nota = $acertos === null ? null : round(100 * $acertos / count($questoes), 2);
        $ordem = array_map(fn ($q) => ['questao_id' => $q['id'], 'alternativas' => array_column($q['alts'], 'id')], $questoes);
        $quando = self::passado($quando);

        $id = $this->t('prova_tentativas')->insertGetId([
            'prova_id' => $prova['id'], 'inscricao_id' => $inscricaoId, 'numero' => $numero, 'ordem' => json_encode($ordem),
            'iniciada_em' => $quando, 'finalizada_em' => $acertos === null ? null : $quando->addMinutes(25), 'nota' => $nota,
            'aprovada' => $nota === null ? null : $nota >= $prova['min'], 'created_at' => $quando, 'updated_at' => $quando,
        ]);

        $respostas = [];
        foreach ($questoes as $i => $q) {
            if ($acertos === null && $i >= 2) {
                break;
            }
            $acerta = $acertos === null ? null : $i < $acertos;
            $alt = $acerta === false
                ? collect($q['alts'])->firstWhere('correta', false)
                : collect($q['alts'])->firstWhere('correta', true);
            $respostas[] = ['prova_tentativa_id' => $id, 'prova_questao_id' => $q['id'], 'prova_alternativa_id' => $alt['id'], 'correta' => $acerta, 'created_at' => $quando, 'updated_at' => $quando];
        }
        $this->t('prova_respostas')->insert($respostas);

        return $nota;
    }

    // ─── Cursos ─────────────────────────────────────────────────────────────

    /** @return array<string, array<string, mixed>> estrutura EAD: pastas e itens */
    private function estruturasEad(): array
    {
        $pdf = fn (string $t, int $pag, array $o = []) => ['PDF', $t, $o + ['pag' => $pag]];
        $vid = fn (string $t, string $v, array $o = []) => ['VIDEO', $t, $o + ['vídeo' => $v]];
        $proj = fn (string $t, string $d) => ['PROJETO', $t, ['desc' => $d]];
        $prova = fn (string $t, int $min = 70, int $max = 3) => ['PROVA', $t, ['min' => $min, 'max' => $max]];

        return [
            'E1' => [
                ['Fundamentos do Sistema Nacional de Proteção e Defesa Civil', [$vid('Apresentação do SINPDEC', 'v1'), $pdf('Lei 12.608/2012: leitura guiada', 12), $prova('Avaliação da pasta 1')]],
                ['Ameaças, vulnerabilidades e COBRADE', [$vid('Como classificar um desastre', 'v1'), $pdf('Tabela COBRADE comentada', 20), $proj('Classificação de desastres do seu município', 'Liste os três desastres mais frequentes no seu município e classifique-os pela COBRADE.'), $prova('Avaliação da pasta 2')]],
                ['Plano de Contingência e Operação Chuvas', [$pdf('Roteiro do PLANCON', 16), $proj('Esboço do Plano de Contingência', 'Envie o esboço do PLANCON do seu município com ao menos três cenários de risco.'), $prova('Prova final', 70, 2)]],
                ['Material complementar (em produção)', [
                    $vid('Operação Chuvas na prática', 'proc', ['opt' => true]), $vid('Sala de Situação', 'up', ['opt' => true]), $vid('Alertas e monitoramento', 'env', ['opt' => true]),
                    $vid('Simulado de evacuação', 'falha', ['opt' => true]), $vid('Vídeo a gravar', 'sem', ['opt' => true]), $pdf('Glossário de Defesa Civil (arquivo pendente)', 30, ['opt' => true, 'sem_arquivo' => true]),
                ]],
            ],
            'E2' => [
                ['Conceitos básicos de risco e desastre', [$vid('Risco, ameaça e vulnerabilidade', 'v1'), $pdf('Cartilha de gestão de riscos', 10), $prova('Avaliação de conceitos')]],
                ['Preparando-se em casa', [$pdf('Plano familiar de emergência', 8), $proj('Meu plano familiar de emergência', 'Monte o plano da sua família: pontos de encontro, contatos e kit de emergência.')]],
            ],
            'E3' => [['Prestação de contas no S2iD', [$vid('Preenchendo o FIDE', 'v2'), $pdf('Manual do S2iD (prestação de contas)', 14), $prova('Avaliação do S2iD', 70, 2)]]],
            'E4' => [['Estrutura do PLANCON', [$pdf('Modelo de PLANCON municipal', 16), $vid('Montando um abrigo temporário', 'v3'), $prova('Avaliação do PLANCON', 60, 3)]]],
            'E5' => [['Introdução', [$pdf('Defesa Civil: o que e e para que serve', 6)]], ['Pasta em construção', []]],
            'E6' => [['Primeiros socorros no desastre', [$vid('Segurança da cena e avaliação inicial', 'v1'), $pdf('Guia rapido de primeiros socorros', 9), $prova('Avaliação de primeiros socorros')]]],
            'E7' => [
                ['Instalação e gestão de abrigos', [$vid('Montando um abrigo temporário', 'v3'), $pdf('Manual de gestão de abrigos', 18), $proj('Planta e cadastro de um abrigo', 'Desenhe a planta de um abrigo para 80 pessoas e o modelo de cadastro das famílias.')]],
                ['Avaliação final', [$prova('Prova final de abrigos', 70, 3)]],
            ],
            'E8' => [['Mapeamento com drones', [$pdf('Introdução ao mapeamento aereo', 10), $proj('Plano de voo', 'Elabore o plano de voo para mapear uma encosta.')]]],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function cursos(): array
    {
        $sede = 'Auditório da CEDEC, Cidade Administrativa, Belo Horizonte';

        return [
            // ─── EAD ───────────────────────────────────────────────────────
            ['k' => 'E1', 'titulo' => 'Introdução ao Sistema Nacional de Proteção e Defesa Civil (SINPDEC)', 'tipo' => 'EAD', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'EM_ANDAMENTO', 'publicado' => true, 'sit' => 'ATIVO',
                'vagas' => null, 'ch' => 40, 'ini' => -90, 'auto' => true, 'prof' => [0, 1], 'ciclo' => true, 'ead' => 'E1', 'instrutor' => 'Renata Cristina Lacerda Moura',
                'desc' => 'Curso EAD de nivelamento sobre a estrutura do SINPDEC, a Lei 12.608/2012 e a classificação de desastres, para servidores e agentes de COMPDEC.',
                'cron' => [['OUTRO', 'Plantão de dúvidas com o professor (online)', 5, '19:00', 90], ['ENTREGA', 'Prazo: esboço do Plano de Contingência', 20, '23:59', 0], ['PROVA', 'Janela de aplicação da prova final', 25, '08:00', 600]],
                'insc' => [['s', 0, 'A', null, 'parcial'], ['s', 1, 'A', null, 'zero'], ['s', 2, 'A', null, 'quase'], ['s', 3, 'A', null, 'prova_reprovada:2'], ['s', 4, 'A', null, 'prova_andamento:2'],
                    ['s', 5, 'A', null, 'entrega_enviada:1'], ['s', 6, 'A', null, 'entrega_devolvida:1'], ['s', 7, 'A', null, 'concluido', 'n5c'], ['s', 8, 'A', null, 'concluido', 'n4'],
                    ['s', 9, 'A', null, 'concluido', 'n3'], ['s', 10, 'A', null, 'concluido', 'n2c'], ['s', 11, 'A', null, 'concluido', 'n1'], ['s', 12, 'A', null, 'concluido', 'legado'],
                    ['s', 13, 'A', null, 'concluido', 'avaliar'], ['s', 14, 'A', null, 'concluido', 'cancelado'], ['s', 18, 'A', null, 'concluido', 'pendente'],
                    ['s', 15, 'P'], ['s', 16, 'R'], ['s', 17, 'C']]],
            ['k' => 'E2', 'titulo' => 'Gestão de Riscos e Desastres: Classificação pela COBRADE', 'tipo' => 'EAD', 'cat' => 'CURSO', 'pub' => 'CIDADAOS', 'status' => 'EM_ANDAMENTO', 'publicado' => true, 'sit' => 'ATIVO',
                'vagas' => 8, 'ch' => 20, 'ini' => -60, 'auto' => false, 'prof' => [1], 'ead' => 'E2', 'instrutor' => 'Eduardo Henrique Salgado Braga',
                'desc' => 'Curso gratuito para a comunidade: entenda risco, ameaça e vulnerabilidade e monte o plano de emergência da sua família.',
                'cron' => [['ENTREGA', 'Entrega do plano familiar', 15, '23:59', 0]],
                'insc' => [['c', 0, 'A', null, 'concluido', 'avaliar'], ['c', 1, 'A', null, 'zero'], ['c', 2, 'A', null, 'quase'], ['c', 3, 'A', null, 'concluido', 'n5c'], ['c', 4, 'A', null, 'parcial'],
                    ['c', 5, 'A', null, 'concluido', 'legado'], ['c', 6, 'A', null, 'prova_andamento:1'], ['c', 7, 'P'], ['c', 8, 'C']]],
            ['k' => 'E3', 'titulo' => 'S2iD: Reconhecimento Federal e Prestação de Contas', 'tipo' => 'EAD', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'EM_ANDAMENTO', 'publicado' => true, 'sit' => 'PAUSADO',
                'vagas' => 30, 'ch' => 16, 'ini' => -70, 'auto' => true, 'prof' => [2], 'ead' => 'E3', 'instrutor' => 'Patrícia Helena Duarte Quintão', 'desc' => 'Passo a passo do FIDE e da prestação de contas no S2iD. Pausado temporariamente para atualização do conteúdo.',
                'insc' => [['s', 0, 'A', null, 'parcial'], ['s', 1, 'A', null, 'zero'], ['s', 2, 'A', null, 'concluido', 'n4'], ['s', 3, 'A', null, 'concluido', 'legado'], ['s', 4, 'P']]],
            ['k' => 'E4', 'titulo' => 'Plano de Contingência Municipal (PLANCON) para Chuvas', 'tipo' => 'EAD', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'EM_ANDAMENTO', 'publicado' => true, 'sit' => 'DESATIVADO',
                'vagas' => 25, 'ch' => 24, 'ini' => -120, 'auto' => false, 'prof' => [0], 'ead' => 'E4', 'instrutor' => 'Renata Cristina Lacerda Moura', 'desc' => 'Curso desativado: sai do catálogo, mas quem concluiu mantém o certificado.',
                'insc' => [['s', 5, 'A', null, 'parcial'], ['s', 6, 'A', null, 'concluido', 'legado'], ['s', 7, 'A', null, 'zero'], ['s', 8, 'P']]],
            ['k' => 'E5', 'titulo' => 'Defesa Civil na Comunidade: Primeiros Passos', 'tipo' => 'EAD', 'cat' => 'CURSO', 'pub' => 'CIDADAOS', 'status' => 'PLANEJADO', 'publicado' => false, 'sit' => 'ATIVO',
                'vagas' => null, 'ch' => 8, 'auto' => true, 'prof' => [1], 'ead' => 'E5', 'instrutor' => 'Eduardo Henrique Salgado Braga', 'desc' => 'Rascunho: ainda não publicado no portal, sem inscrições.', 'insc' => []],
            ['k' => 'E6', 'titulo' => 'Primeiros Socorros em Situações de Desastre', 'tipo' => 'EAD', 'cat' => 'CURSO', 'pub' => 'CIDADAOS', 'status' => 'PLANEJADO', 'publicado' => true, 'sit' => 'ATIVO',
                'vagas' => 3, 'ch' => 12, 'ini' => 10, 'fim' => 40, 'auto' => true, 'prof' => [2], 'ead' => 'E6', 'instrutor' => 'Patrícia Helena Duarte Quintão', 'desc' => 'Publicado, com inscrições abertas e início futuro. Vagas esgotadas: novos inscritos ficam pendentes.',
                'insc' => [['c', 0, 'A', null, 'zero'], ['c', 1, 'A', null, 'zero'], ['c', 2, 'A', null, 'zero'], ['c', 3, 'P'], ['c', 4, 'P']]],
            ['k' => 'E7', 'titulo' => 'Gestão de Abrigos Temporários', 'tipo' => 'EAD', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'CONCLUIDO', 'publicado' => true, 'sit' => 'ATIVO',
                'vagas' => 50, 'ch' => 30, 'ini' => -100, 'fim' => -20, 'fin' => -18, 'auto' => true, 'prof' => [0, 2], 'ead' => 'E7', 'instrutor' => 'Renata Cristina Lacerda Moura', 'desc' => 'Curso encerrado: período de inscrição fechado, concluintes com certificado.',
                'insc' => [['s', 0, 'A', null, 'concluido', 'avaliar'], ['s', 1, 'A', null, 'concluido', 'n5'], ['s', 2, 'A', null, 'concluido', 'n4c'], ['s', 3, 'A', null, 'quase'], ['s', 4, 'A', null, 'concluido', 'legado'],
                    ['s', 5, 'A', null, 'zero'], ['s', 6, 'R']]],
            ['k' => 'E8', 'titulo' => 'Mapeamento de Áreas de Risco com Drones', 'tipo' => 'EAD', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'CANCELADO', 'publicado' => true, 'sit' => 'DESATIVADO',
                'vagas' => 20, 'ch' => 20, 'ini' => -30, 'fim' => 30, 'auto' => false, 'prof' => [1], 'ead' => 'E8', 'instrutor' => 'Eduardo Henrique Salgado Braga', 'desc' => 'Cancelado por falta de instrutor habilitado.', 'motivo' => 'Cancelado por indisponibilidade do instrutor habilitado.',
                'insc' => [['s', 0, 'C'], ['s', 1, 'C'], ['s', 2, 'P']]],

            // ─── Presencial ────────────────────────────────────────────────
            ['k' => 'P1', 'titulo' => 'Operação Chuvas 2026/2027: Preparação das COMPDEC', 'tipo' => 'PRESENCIAL', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'EM_ANDAMENTO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 16, 'freq' => 80, 'auto' => false, 'liberada' => true, 'autoconf' => true, 'prof' => [0, 1], 'mun' => 'Belo Horizonte', 'local' => $sede, 'hora' => '08:30:00', 'instrutor' => 'Renata Cristina Lacerda Moura',
                'desc' => 'Capacitação presencial das COMPDEC para a Operação Chuvas: alerta, resposta, abrigos e prestação de contas.', 'removida' => true,
                'turmas' => [['Turma A - Belo Horizonte', -10, 20, 30, 'Belo Horizonte', $sede], ['Turma B - Montes Claros', -5, 25, 6, 'Montes Claros', 'Centro de Treinamento Regional, Montes Claros']],
                'aulas' => [['Módulo 1: Cenário de riscos e Operação Chuvas', -9, 4], ['Módulo 2: Alertas e Sala de Situação', -7, 4], ['Módulo 3: Gestão de abrigos', -5, 4], ['Módulo 4: S2iD e prestação de contas', 3, 2], ['Módulo 5: Simulado de resposta', 10, 2]],
                'cron' => [['ENCONTRO', 'Encontro de abertura da Operação Chuvas', -10, '08:30', 120], ['OUTRO', 'Visita técnica à Sala de Situação', 12, '14:00', 180], ['PROVA', 'Avaliação prática (simulado)', 16, '09:00', 240], ['ENTREGA', 'Entrega do plano de ação municipal', 24, '23:59', 0]],
                'insc' => [['s', 0, 'A', 0, 'alta'], ['s', 1, 'A', 0, 'alta'], ['s', 2, 'A', 0, 'just'], ['s', 3, 'A', 0, 'baixa'], ['s', 4, 'A', 0, 'nenhuma'], ['s', 5, 'P', 0], ['s', 6, 'R', 0], ['s', 14, 'C', 0],
                    ['s', 7, 'A', 1, 'alta'], ['s', 8, 'A', 1, 'baixa'], ['s', 9, 'A', 1, 'just'], ['s', 10, 'A', 1, 'alta'], ['s', 11, 'A', 1, 'alta'], ['s', 12, 'A', 1, 'nenhuma'], ['s', 13, 'P', 1], ['s', 15, 'C', 1]]],
            ['k' => 'P2', 'titulo' => 'Sistema de Comando em Incidentes (SCI): Nível Básico', 'tipo' => 'PRESENCIAL', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'CONCLUIDO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 16, 'freq' => 75, 'fin' => -28, 'auto' => false, 'prof' => [2], 'mun' => 'Belo Horizonte', 'local' => $sede, 'hora' => '08:00:00', 'instrutor' => 'Patrícia Helena Duarte Quintão', 'desc' => 'Fundamentos do SCI aplicados a incidentes de chuvas e inundações.',
                'turmas' => [['Turma 1 - Belo Horizonte', -60, -50, 30, 'Belo Horizonte', $sede], ['Turma 2 - Juiz de Fora', -40, -30, 25, 'Juiz de Fora', 'Base Regional de Defesa Civil, Juiz de Fora']],
                'aulas' => [['Módulo 1: Princípios do SCI', -58, 4], ['Módulo 2: Estrutura organizacional', -56, 4], ['Módulo 3: Planejamento do incidente', -38, 4], ['Módulo 4: Exercício simulado', -36, 4]],
                'cron' => [['ENCONTRO', 'Encontro de encerramento e entrega de certificados', -29, '15:00', 90], ['PROVA', 'Avaliação final do SCI', -37, '14:00', 120]],
                'insc' => [['s', 0, 'A', 0, 'alta', 'n4c'], ['s', 1, 'A', 0, 'alta', 'avaliar'], ['s', 2, 'A', 0, 'alta', 'legado'], ['s', 3, 'A', 0, 'alta', 'n3'], ['s', 4, 'A', 0, 'baixa'], ['s', 5, 'A', 0, 'alta', 'cancelado'],
                    ['s', 7, 'A', 1, 'alta', 'n5c'], ['s', 8, 'A', 1, 'just', 'n2'], ['s', 9, 'A', 1, 'baixa'], ['s', 10, 'R', 1], ['s', 11, 'C', 1], ['s', 12, 'A', 1, 'alta', 'reemitido']]],
            ['k' => 'P3', 'titulo' => 'Oficina de Elaboração de Planos de Contingência', 'tipo' => 'PRESENCIAL', 'cat' => 'CURSO', 'pub' => 'CIDADAOS', 'status' => 'PLANEJADO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 12, 'freq' => 75, 'auto' => false, 'prof' => [0], 'mun' => 'Contagem', 'local' => 'Centro Comunitário do Bairro Industrial, Contagem', 'instrutor' => 'Renata Cristina Lacerda Moura',
                'desc' => 'Oficina prática para lideranças e voluntários. Inscrições abertas; a Turma 1 já está com as vagas esgotadas.',
                'turmas' => [['Turma 1 - Contagem', 15, 30, 5, 'Contagem', 'Centro Comunitário do Bairro Industrial, Contagem'], ['Turma 2 - Betim', 45, 60, 20, 'Betim', 'Salão Paroquial, Betim']],
                'aulas' => [['Encontro 1: Riscos do seu bairro', 15, 4], ['Encontro 2: Montando o plano', 16, 4], ['Encontro 3: Simulado comunitário', 45, 4]],
                'cron' => [['ENCONTRO', 'Reunião de alinhamento com os voluntários', 12, '19:00', 90]],
                'insc' => [['c', 0, 'A', 0, 'nenhuma'], ['c', 1, 'A', 0, 'nenhuma'], ['c', 2, 'A', 0, 'nenhuma'], ['c', 3, 'A', 0, 'nenhuma'], ['c', 4, 'A', 0, 'nenhuma'], ['c', 5, 'P', 0],
                    ['c', 6, 'A', 1, 'nenhuma'], ['c', 7, 'A', 1, 'nenhuma'], ['c', 8, 'P', 1], ['c', 9, 'R', 1]]],
            ['k' => 'P4', 'titulo' => 'Seminário Estadual de Proteção e Defesa Civil 2026', 'tipo' => 'PRESENCIAL', 'cat' => 'EVENTO', 'pub' => 'SERVIDORES', 'status' => 'CONCLUIDO', 'publicado' => true, 'sit' => 'ATIVO',
                'vagas' => 200, 'ch' => 16, 'ini' => -46, 'fim' => -45, 'fin' => -44, 'freq' => 75, 'auto' => true, 'prof' => [], 'mun' => 'Belo Horizonte', 'local' => 'Centro de Convenções, Belo Horizonte', 'hora' => '08:00:00', 'instrutor' => 'Coordenadoria Estadual de Defesa Civil',
                'desc' => 'Evento legado, sem turmas: período e vagas valem pelo próprio curso.', 'aulas' => [['Dia 1: Abertura e palestras', -46, 8], ['Dia 2: Oficinas temáticas', -45, 8]],
                'insc' => [['s', 0, 'A', null, 'alta', 'avaliar'], ['s', 1, 'A', null, 'alta', 'n4'], ['s', 2, 'A', null, 'alta', 'n5'], ['s', 3, 'A', null, 'just', 'legado'], ['s', 4, 'A', null, 'alta', 'n3c'], ['s', 5, 'A', null, 'baixa'],
                    ['s', 6, 'A', null, 'alta', 'cancelado'], ['s', 7, 'A', null, 'alta', 'legado'], ['s', 8, 'A', null, 'alta', 'n5c'], ['s', 9, 'A', null, 'alta', 'avaliar'], ['s', 10, 'P'], ['s', 11, 'C']]],
            ['k' => 'P6', 'titulo' => 'Percepção de Risco para Lideranças Comunitárias', 'tipo' => 'PRESENCIAL', 'cat' => 'CURSO', 'pub' => 'CIDADAOS', 'status' => 'EM_ANDAMENTO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 12, 'freq' => 75, 'auto' => false, 'liberada' => true, 'prof' => [1], 'mun' => 'Teófilo Otoni', 'local' => 'Câmara Municipal de Teófilo Otoni', 'instrutor' => 'Eduardo Henrique Salgado Braga',
                'desc' => 'Período da turma encerrado, mas o curso ainda não foi finalizado (presença liberada).', 'turmas' => [['Turma única - Teófilo Otoni', -20, -2, 40, 'Teófilo Otoni', 'Câmara Municipal de Teófilo Otoni']],
                'aulas' => [['Aula 1: O que é risco', -19, 4], ['Aula 2: Mapas comunitários', -12, 4], ['Aula 3: Alertas e rotas de fuga', -5, 4]],
                'insc' => [['c', 0, 'A', 0, 'alta'], ['c', 1, 'A', 0, 'alta'], ['c', 2, 'A', 0, 'baixa'], ['c', 3, 'A', 0, 'just'], ['c', 4, 'P', 0], ['c', 5, 'R', 0], ['c', 16, 'A', 0, 'alta']]],

            // ─── Online ────────────────────────────────────────────────────
            ['k' => 'O1', 'titulo' => 'Webinário: Alertas e Monitoramento Hidrometeorológico', 'tipo' => 'ONLINE', 'cat' => 'EVENTO', 'pub' => 'CIDADAOS', 'status' => 'PLANEJADO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 2, 'freq' => 75, 'auto' => true, 'prof' => [2], 'local' => 'https://meet.demo.test/webinario-alertas', 'instrutor' => 'Patrícia Helena Duarte Quintão', 'desc' => 'Transmissão ao vivo com o Centro de Monitoramento.',
                'turmas' => [['Sessão única', 7, 7, 100, null, 'https://meet.demo.test/webinario-alertas']], 'aulas' => [['Webinário ao vivo', 7, 2]],
                'insc' => [['c', 0, 'A', 0, 'nenhuma'], ['c', 1, 'A', 0, 'nenhuma'], ['c', 2, 'P', 0]]],
            ['k' => 'O2', 'titulo' => 'Roda de Conversa Online: Balanço da Temporada de Chuvas', 'tipo' => 'ONLINE', 'cat' => 'EVENTO', 'pub' => 'SERVIDORES', 'status' => 'CONCLUIDO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 3, 'freq' => 75, 'fin' => -24, 'auto' => true, 'autoconf' => true, 'prof' => [0], 'local' => 'https://meet.demo.test/roda-chuvas', 'instrutor' => 'Renata Cristina Lacerda Moura', 'desc' => 'Lições aprendidas na última temporada de chuvas.',
                'turmas' => [['Sessão única', -25, -25, 60, null, 'https://meet.demo.test/roda-chuvas']], 'aulas' => [['Roda de conversa', -25, 3]],
                'insc' => [['s', 0, 'A', 0, 'alta', 'n5'], ['s', 1, 'A', 0, 'alta', 'n4c'], ['s', 2, 'A', 0, 'alta', 'avaliar'], ['s', 3, 'A', 0, 'alta', 'legado'], ['s', 4, 'A', 0, 'baixa'], ['s', 5, 'A', 0, 'alta', 'n1c'], ['s', 6, 'A', 0, 'alta', 'n3'], ['s', 7, 'P', 0]]],
            ['k' => 'O3', 'titulo' => 'Preenchimento do FIDE e do S2iD (ao vivo)', 'tipo' => 'ONLINE', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'EM_ANDAMENTO', 'publicado' => true, 'sit' => 'PAUSADO',
                'ch' => 6, 'freq' => 75, 'auto' => false, 'liberada' => true, 'autoconf' => true, 'prof' => [2], 'local' => 'https://meet.demo.test/fide-s2id', 'instrutor' => 'Patrícia Helena Duarte Quintão', 'desc' => 'Turma ao vivo pausada até a próxima atualização do sistema.',
                'turmas' => [['Turma Online 1', -3, 11, 40, null, 'https://meet.demo.test/fide-s2id']], 'aulas' => [['Aula 1: FIDE', -2, 2], ['Aula 2: Anexos e prestação de contas', 4, 2], ['Aula 3: Dúvidas e casos reais', 9, 2]],
                'insc' => [['s', 0, 'A', 0, 'alta'], ['s', 1, 'A', 0, 'alta'], ['s', 2, 'A', 0, 'baixa'], ['s', 3, 'P', 0], ['s', 4, 'A', 0, 'nenhuma']]],

            // ─── Semipresencial ────────────────────────────────────────────
            ['k' => 'S1', 'titulo' => 'Formação de Agentes Municipais de Proteção e Defesa Civil', 'tipo' => 'SEMI_PRESENCIAL', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'EM_ANDAMENTO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 60, 'freq' => 75, 'auto' => false, 'prof' => [0, 1, 2], 'mun' => 'Belo Horizonte', 'local' => $sede, 'hora' => '08:30:00', 'instrutor' => 'Renata Cristina Lacerda Moura', 'desc' => 'Formação em três meses: encontros presenciais e aulas online síncronas.',
                'turmas' => [['Turma 1 - Belo Horizonte', -20, 40, 25, 'Belo Horizonte', $sede], ['Turma 2 - Juiz de Fora (próxima)', 20, 80, 25, 'Juiz de Fora', 'Base Regional de Defesa Civil, Juiz de Fora']],
                'aulas' => [['Encontro presencial 1: Abertura', -18, 8], ['Aula online 1: Legislação', -11, 4], ['Encontro presencial 2: Resposta', -4, 8], ['Aula online 2: S2iD', 3, 4], ['Encontro presencial 3: Simulado', 10, 8], ['Aula online 3: Fechamento', 17, 4]],
                'cron' => [['ENCONTRO', 'Aula inaugural com o Coordenador Estadual', -18, '08:30', 120], ['PROVA', 'Avaliação parcial', 8, '14:00', 120], ['ENTREGA', 'Trabalho de conclusão', 35, '23:59', 0]],
                'insc' => [['s', 0, 'A', 0, 'alta'], ['s', 1, 'A', 0, 'alta'], ['s', 2, 'A', 0, 'just'], ['s', 3, 'A', 0, 'baixa'], ['s', 4, 'A', 0, 'alta'], ['s', 5, 'P', 0],
                    ['s', 10, 'A', 1, 'nenhuma'], ['s', 11, 'A', 1, 'nenhuma'], ['s', 12, 'P', 1], ['s', 13, 'R', 0], ['s', 14, 'C', 0]]],
            ['k' => 'S2', 'titulo' => 'Gestão Integrada de Riscos e Desastres', 'tipo' => 'SEMI_PRESENCIAL', 'cat' => 'CURSO', 'pub' => 'CIDADAOS', 'status' => 'CONCLUIDO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 40, 'freq' => 75, 'fin' => -58, 'auto' => false, 'prof' => [1], 'mun' => 'Divinópolis', 'local' => 'Escola Municipal Padre Libério, Divinópolis', 'instrutor' => 'Eduardo Henrique Salgado Braga', 'desc' => 'Turma concluída, com certificados em vários estados.',
                'turmas' => [['Turma única - Divinópolis', -90, -60, 30, 'Divinópolis', 'Escola Municipal Padre Libério, Divinópolis']],
                'aulas' => [['Encontro 1: Conceitos', -88, 8], ['Aula online 1: Mapeamento', -80, 4], ['Encontro 2: Planejamento', -72, 8], ['Aula online 2: Encerramento', -64, 4]],
                'insc' => [['c', 0, 'A', 0, 'alta', 'avaliar'], ['c', 1, 'A', 0, 'alta', 'n5'], ['c', 2, 'A', 0, 'alta', 'legado'], ['c', 3, 'A', 0, 'baixa'], ['c', 4, 'A', 0, 'alta', 'cancelado'], ['c', 5, 'R', 0],
                    ['c', 16, 'A', 0, 'alta', 'legado'], ['c', 17, 'A', 0, 'alta', 'n3']]],
            ['k' => 'S3', 'titulo' => 'Mobilização Comunitária e Formação de NUPDEC', 'tipo' => 'SEMI_PRESENCIAL', 'cat' => 'CURSO', 'pub' => 'CIDADAOS', 'status' => 'PLANEJADO', 'publicado' => false, 'sit' => 'ATIVO',
                'ch' => 30, 'freq' => 75, 'auto' => false, 'prof' => [1], 'mun' => 'Ouro Preto', 'local' => 'Casa de Cultura, Ouro Preto', 'instrutor' => 'Eduardo Henrique Salgado Braga', 'desc' => 'Rascunho: ainda não publicado, sem inscrições. Sem capa.',
                'turmas' => [['Turma 1 - Ouro Preto', 60, 90, 30, 'Ouro Preto', 'Casa de Cultura, Ouro Preto']], 'aulas' => [['Encontro 1', 60, 6], ['Aula online 1', 67, 3], ['Encontro 2', 75, 6]], 'insc' => []],
            ['k' => 'S4', 'titulo' => 'Cartografia Geotécnica de Áreas de Risco', 'tipo' => 'SEMI_PRESENCIAL', 'cat' => 'CURSO', 'pub' => 'SERVIDORES', 'status' => 'CANCELADO', 'publicado' => true, 'sit' => 'ATIVO',
                'ch' => 32, 'freq' => 75, 'auto' => false, 'prof' => [2], 'mun' => 'Governador Valadares', 'local' => 'Campus regional, Governador Valadares', 'instrutor' => 'Patrícia Helena Duarte Quintão', 'motivo' => 'Cancelado: parceria com a instituição de ensino não foi renovada.', 'desc' => 'Curso cancelado antes de começar.',
                'turmas' => [['Turma 1 - Governador Valadares', -10, 20, 20, 'Governador Valadares', 'Campus regional, Governador Valadares']], 'aulas' => [['Encontro 1', -9, 6], ['Aula online 1', 3, 3]],
                'insc' => [['s', 0, 'C', 0], ['s', 1, 'P', 0]]],
        ];
    }

    private const STATUS_INSC = ['A' => 'APROVADA', 'P' => 'PENDENTE', 'R' => 'REPROVADA', 'C' => 'CANCELADA'];

    /** @param  array<string, mixed>  $c */
    private function semear(array $c): void
    {
        $ead = isset($c['ead']);
        $turmasDef = $c['turmas'] ?? [];
        $criado = self::dia(min($c['ini'] ?? (empty($turmasDef) ? -60 : $turmasDef[0][1]), -20) - 40, '10:00');
        $publicadoEm = $c['publicado'] ? $criado->addDays(10) : null;
        $ini = $c['ini'] ?? (empty($turmasDef) ? null : min(array_column($turmasDef, 1)));
        $fim = $c['fim'] ?? (empty($turmasDef) ? null : max(array_column($turmasDef, 2)));
        $vagas = array_key_exists('vagas', $c) ? $c['vagas'] : (empty($turmasDef) ? null : array_sum(array_column($turmasDef, 3)));
        $titulo = self::PREFIXO.$c['titulo'];
        $concluido = $c['status'] === 'CONCLUIDO';
        $finalizado = $concluido ? self::dia($c['fin'] ?? -30, '17:00') : null;
        $sit = $c['sit'];

        $cursoId = $this->t('treinamentos')->insertGetId([
            'titulo' => $titulo, 'descricao' => $c['desc'], 'carga_horaria' => $c['ch'], 'tipo' => $c['tipo'], 'status' => $c['status'], 'instrutor' => $c['instrutor'] ?? null,
            'local' => $c['local'] ?? null, 'data_inicio' => $ini === null ? null : self::dia($ini)->toDateString(), 'data_fim' => $fim === null ? null : self::dia($fim)->toDateString(),
            'numero_vagas' => $vagas, 'percentual_frequencia_minimo' => $c['freq'] ?? 75, 'created_by' => $this->gestor, 'created_at' => $criado, 'updated_at' => self::dia(-1),
            'categoria' => $c['cat'], 'link_publico_slug' => $c['publicado'] ? 'demo-'.Str::slug($c['titulo']).'-'.substr(md5($c['titulo']), 0, 6) : null, 'publicado_em' => $publicadoEm,
            'presenca_liberada' => $c['liberada'] ?? false, 'presenca_liberada_em' => ($c['liberada'] ?? false) ? self::dia(-3, '08:00') : null, 'presenca_liberada_por' => ($c['liberada'] ?? false) ? $this->prof[0] : null,
            'finalizado_em' => $finalizado, 'hora_inicio' => $c['hora'] ?? null, 'presenca_autoconfirmavel' => $c['autoconf'] ?? false, 'publico_alvo' => $c['pub'],
            'inscricao_aprovacao_automatica' => $c['auto'], 'situacao' => $sit, 'situacao_alterada_em' => $sit === 'ATIVO' ? null : self::dia(-6, '11:00'), 'situacao_alterada_por' => $sit === 'ATIVO' ? null : $this->gestor,
            'capa_path' => null, 'municipio_id' => $this->municipio($c['mun'] ?? null),
        ]);

        foreach ($c['prof'] ?? [] as $p) {
            $this->t('treinamento_professores')->insert(['treinamento_id' => $cursoId, 'user_id' => $this->prof[$p], 'created_at' => $criado->addDay()]);
        }

        $turmaIds = [];
        foreach ($turmasDef as $i => [$nome, $ti, $tf, $tv, $mun, $local]) {
            $turmaIds[$i] = $this->t('turmas')->insertGetId([
                'treinamento_id' => $cursoId, 'nome' => $nome, 'data_inicio' => self::dia($ti)->toDateString(), 'data_fim' => self::dia($tf)->toDateString(), 'numero_vagas' => $tv,
                'local' => $local, 'municipio_id' => $this->municipio($mun), 'created_at' => $criado->addDays(2), 'updated_at' => $criado->addDays(2),
            ]);
        }
        $turmaRemovida = null;
        if ($c['removida'] ?? false) {
            $turmaRemovida = $this->t('turmas')->insertGetId([
                'treinamento_id' => $cursoId, 'nome' => 'Turma C - Uberlândia', 'data_inicio' => self::dia(30)->toDateString(), 'data_fim' => self::dia(40)->toDateString(), 'numero_vagas' => 20,
                'local' => 'Uberlândia', 'created_at' => $criado->addDays(2), 'updated_at' => $criado->addDays(8), 'deleted_at' => $criado->addDays(8),
            ]);
        }

        // Estrutura: pastas EAD ou aulas.
        $modulos = [];
        $ctxEad = null;
        if ($ead) {
            $ctxEad = $this->criarEstruturaEad($cursoId, $this->estruturasEad()[$c['ead']], $criado);
        } else {
            foreach ($c['aulas'] as $o => [$tituloAula, $dAula, $chAula]) {
                $id = $this->t('modulos')->insertGetId([
                    'treinamento_id' => $cursoId, 'titulo' => $tituloAula, 'descricao' => null, 'ordem' => $o + 1, 'carga_horaria' => $chAula,
                    'data_prevista' => self::dia($dAula)->toDateString(), 'created_at' => $criado->addDays(3), 'updated_at' => $criado->addDays(3),
                ]);
                $modulos[] = ['id' => $id, 'dia' => $dAula];
                $this->t('curso_cronogramas')->insert([
                    'treinamento_id' => $cursoId, 'turma_id' => null, 'titulo' => $tituloAula, 'descricao' => null, 'tipo' => 'AULA', 'inicio' => self::dia($dAula, $c['hora'] ?? '09:00'),
                    'fim' => self::dia($dAula, $c['hora'] ?? '09:00')->addHours($chAula), 'local' => $c['local'] ?? null, 'created_at' => $criado->addDays(3), 'updated_at' => $criado->addDays(3),
                ]);
            }
        }

        foreach ($c['cron'] ?? [] as [$tipo, $tCron, $dCron, $hCron, $durMin]) {
            $inicio = self::dia($dCron, $hCron);
            $this->t('curso_cronogramas')->insert([
                'treinamento_id' => $cursoId, 'turma_id' => null, 'titulo' => $tCron, 'descricao' => null, 'tipo' => $tipo, 'inicio' => $inicio, 'fim' => $durMin > 0 ? $inicio->addMinutes($durMin) : null,
                'local' => $c['local'] ?? null, 'created_at' => $criado->addDays(4), 'updated_at' => $criado->addDays(4),
            ]);
        }
        if ($turmaRemovida !== null) {
            $this->t('curso_cronogramas')->insert([
                'treinamento_id' => $cursoId, 'turma_id' => $turmaRemovida, 'titulo' => 'Aula extra (removida do cronograma)', 'descricao' => null, 'tipo' => 'AULA', 'inicio' => self::dia(31),
                'fim' => self::dia(31)->addHours(4), 'local' => null, 'created_at' => $criado->addDays(5), 'updated_at' => $criado->addDays(9), 'deleted_at' => $criado->addDays(9),
            ]);
        }

        // Inscrições.
        $resumo = ['concluidos' => 0];
        foreach ($c['insc'] as $i => $linha) {
            [$pool, $idx, $st, $turmaIdx, $perfil, $certChave] = array_pad($linha, 6, null);
            $this->inscrever($c, $cursoId, $i, $pool, (int) $idx, self::STATUS_INSC[$st], $turmaIdx === null ? null : $turmaIdx, $turmaIdx === null ? null : ($turmaIds[$turmaIdx] ?? null), $turmasDef, $perfil, $certChave, $modulos, $ctxEad, $finalizado);
        }

        $this->historico($cursoId, $c, $turmaIds, $turmasDef, $criado, $publicadoEm, $turmaRemovida);
    }

    /** @param  list<array{0:string,1:list<array<string,mixed>>}>  $pastas */
    private function criarEstruturaEad(int $cursoId, array $pastas, CarbonImmutable $criado): array
    {
        $ctx = ['obrigatorios' => [], 'pastas' => [], 'provas' => []];
        foreach ($pastas as $ordem => [$tituloPasta, $itens]) {
            $moduloId = $this->t('modulos')->insertGetId([
                'treinamento_id' => $cursoId, 'titulo' => $tituloPasta, 'descricao' => null, 'ordem' => $ordem + 1, 'carga_horaria' => 6, 'data_prevista' => null,
                'created_at' => $criado->addDays(3), 'updated_at' => $criado->addDays(3),
            ]);
            $pasta = ['id' => $moduloId, 'obrigatorios' => []];
            foreach ($itens as $o => [$tipo, $tituloItem, $opt]) {
                $vídeo = $opt['vídeo'] ?? null;
                $row = [
                    'modulo_id' => $moduloId, 'tipo' => $tipo, 'titulo' => $tituloItem, 'descricao' => $opt['desc'] ?? null, 'ordem' => $o + 1, 'obrigatorio' => ! ($opt['opt'] ?? false),
                    'arquivo_path' => null, 'arquivo_nome' => null, 'arquivo_mime' => null, 'arquivo_tamanho' => null, 'total_paginas' => null,
                    'created_at' => $criado->addDays(3), 'updated_at' => $criado->addDays(3), 'video_id' => $vídeo === null ? null : $this->videos[$vídeo],
                ];
                if ($tipo === 'PDF') {
                    $row['total_paginas'] = $opt['pag'];
                    if (! ($opt['sem_arquivo'] ?? false)) {
                        $slug = Str::slug($tituloItem);
                        $row['arquivo_path'] = 'demo/capacitacoes/'.$slug.'.pdf';
                        $row['arquivo_nome'] = $slug.'.pdf';
                        $row['arquivo_mime'] = 'application/pdf';
                        $row['arquivo_tamanho'] = 250_000 + $opt['pag'] * 12_000;
                    }
                }
                $itemId = $this->t('modulo_itens')->insertGetId($row);
                $item = ['id' => $itemId, 'tipo' => $tipo, 'dur' => $vídeo === null ? null : ($this->t('videos')->where('id', $this->videos[$vídeo])->value('duracao_segundos')), 'pag' => $opt['pag'] ?? null, 'prova' => null];
                if ($tipo === 'PROVA') {
                    $item['prova'] = $this->criarProva($itemId, (float) $opt['min'], (int) $opt['max'], $criado->addDays(3));
                }
                if ($row['obrigatorio']) {
                    $pasta['obrigatorios'][] = $item;
                    $ctx['obrigatorios'][] = $item;
                }
            }
            $ctx['pastas'][] = $pasta;
        }

        return $ctx;
    }

    // ─── Inscrições ─────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $c
     * @param  list<array{id:int,dia:int}>  $modulos
     */
    private function inscrever(array $c, int $cursoId, int $i, string $pool, int $idx, string $status, ?int $turmaIdx, ?int $turmaId, array $turmasDef, ?string $perfil, ?string $certChave, array $modulos, ?array $ctxEad, ?CarbonImmutable $finalizado): void
    {
        $servidor = $pool === 's';
        $inscritoId = $servidor ? $this->srv[$idx] : $this->cid[$idx];
        $sexo = trim($servidor ? self::SERVIDORES[$idx][1] : self::CIDADAOS[$idx][1]) ?: null;
        $inicioRel = $turmaIdx !== null ? $turmasDef[$turmaIdx][1] : ($c['ini'] ?? -30);
        $dInsc = self::dia(min(-1 - ($i % 6), $inicioRel - 5 - ($i % 4)), '10:30');
        $aprovada = $status === 'APROVADA';

        $inscId = $this->t('inscricoes')->insertGetId([
            'treinamento_id' => $cursoId, 'turma_id' => $turmaId, 'status' => $status, 'data_inscricao' => $dInsc, 'data_aprovacao' => $aprovada ? ($c['auto'] ? $dInsc : $dInsc->addDay()) : null,
            'aprovado_por_id' => $aprovada && ! $c['auto'] ? $this->gestor : null,
            'observacoes' => match ($status) {
                'REPROVADA' => 'Vínculo institucional não comprovado no ato da inscrição.',
                'CANCELADA' => 'Cancelada a pedido do participante.',
                'PENDENTE' => 'Aguardando analise do gestor.',
                default => null,
            },
            'created_at' => $dInsc, 'updated_at' => $dInsc, 'inscrito_type' => $servidor ? User::class : Cidadao::class, 'inscrito_id' => $inscritoId,
            'qr_code_token' => (string) Str::uuid(), 'sexo' => $sexo,
        ]);

        if (! $aprovada) {
            return;
        }

        $concluidoEm = null;
        $aproveitamento = null;

        if ($ctxEad !== null) {
            [$concluidoEm, $aproveitamento] = $this->progressoEad($inscId, $ctxEad, $perfil ?? 'zero', $dInsc->addDay(), $i);
        } elseif ($perfil !== null && $perfil !== 'nenhuma') {
            $pct = $this->frequencia($inscId, $modulos, $perfil, (float) ($c['freq'] ?? 75), $c['tipo'] === 'ONLINE' || ($c['autoconf'] ?? false));
            if ($c['status'] === 'CONCLUIDO' && $pct >= (float) ($c['freq'] ?? 75)) {
                $concluidoEm = $finalizado;
            }
        }

        // 'avaliar': requisitos cumpridos (trilha completa / frequência no evento concluído), mas a
        // avaliação do curso, etapa final obrigatória, ainda não foi enviada: não conclui nem emite.
        if ($concluidoEm !== null && $certChave !== 'avaliar') {
            $this->t('inscricoes')->where('id', $inscId)->update(['concluido_em' => $concluidoEm, 'aproveitamento' => $aproveitamento, 'updated_at' => $concluidoEm]);
            $this->certificado($inscId, $cursoId, $certChave ?? 'n4', $concluidoEm, $aproveitamento, $i);
        }
    }

    /**
     * Frequencia por perfil nas aulas já realizadas. Devolve o percentual sobre TODAS as aulas do curso
     * (mesma conta de Inscricao::calcularPercentualFrequencia).
     *
     * @param  list<array{id:int,dia:int}>  $modulos
     */
    private function frequencia(int $inscId, array $modulos, string $perfil, float $min, bool $autoconfirmavel): float
    {
        $passadas = array_values(array_filter($modulos, fn ($m) => $m['dia'] <= 0));
        $padraoJust = ['PRESENTE', 'JUSTIFICADA', 'PRESENTE', 'AUSENTE'];
        $linhas = [];
        $presencas = 0;
        foreach ($passadas as $n => $m) {
            $st = match ($perfil) {
                'alta' => 'PRESENTE',
                'just' => $padraoJust[$n % 4],
                'baixa' => $n === 0 ? 'PRESENTE' : 'AUSENTE',
                default => 'AUSENTE',
            };
            $presencas += $st === 'AUSENTE' ? 0 : 1;
            $linhas[] = [
                'modulo_id' => $m['id'], 'status' => $st, 'data_aula' => self::dia($m['dia'])->toDateString(), 'inscricao_id' => $inscId,
                'observacoes' => $st === 'JUSTIFICADA' ? 'Atestado médico apresentado.' : ($st === 'AUSENTE' ? 'Não compareceu.' : null),
                'registrado_por_id' => $this->prof[0], 'origem' => $autoconfirmavel ? 'autoconfirmacao' : (($n % 2 === 0) ? 'qr' : 'manual'),
                'created_at' => self::passado(self::dia($m['dia'], '12:30')), 'updated_at' => self::passado(self::dia($m['dia'], '12:30')),
            ];
        }
        if ($linhas !== []) {
            $this->t('frequencias')->insert($linhas);
        }

        return count($modulos) === 0 ? 0.0 : 100 * $presencas / count($modulos);
    }

    // ─── EAD: progresso ─────────────────────────────────────────────────────

    /**
     * @param  array{obrigatorios:list<array<string,mixed>>,pastas:list<array<string,mixed>>}  $ctx
     * @return array{0:?CarbonImmutable,1:?float} concluido_em e aproveitamento (so quando concluiu)
     */
    private function progressoEad(int $inscId, array $ctx, string $perfil, CarbonImmutable $inicio, int $seed): array
    {
        $ob = $ctx['obrigatorios'];
        $n = count($ob);
        [$nome, $param] = array_pad(explode(':', $perfil), 2, null);

        $indiceDoTipo = function (string $tipo, int $k) use ($ob): int {
            $achou = 0;
            foreach ($ob as $i => $item) {
                if ($item['tipo'] === $tipo && ++$achou === $k) {
                    return $i;
                }
            }

            return count($ob);
        };

        $feitos = match ($nome) {
            'zero' => 0,
            'parcial' => max(1, intdiv($n * 4, 10)),
            'concluido' => $n,
            'quase' => $n - 1,
            'prova_reprovada', 'prova_andamento' => $indiceDoTipo('PROVA', (int) $param),
            'entrega_enviada', 'entrega_devolvida' => $indiceDoTipo('PROJETO', (int) $param),
            default => 0,
        };

        $notas = [];
        $quando = $inicio;
        $feitosIds = [];
        for ($i = 0; $i < $feitos; $i++) {
            $quando = self::passado($inicio->addDays($i)->addHours(1 + $i % 5));
            $this->concluirItem($inscId, $ob[$i], $quando, $seed + $i, $notas);
            $feitosIds[$ob[$i]['id']] = true;
        }

        if ($feitos < $n && $nome !== 'zero') {
            $item = $ob[$feitos];
            $quando = self::passado($inicio->addDays($feitos)->addHours(2));
            $this->estadoDoItem($inscId, $item, $nome, $quando);
        }

        // progresso_pastas: pasta so grava se concluida e a anterior tambem (mesma regra do TrilhaService).
        foreach ($ctx['pastas'] as $pasta) {
            $todos = array_filter($pasta['obrigatorios'], fn ($it) => ! isset($feitosIds[$it['id']]));
            if ($todos !== []) {
                break;
            }
            $this->t('progresso_pastas')->insert(['inscricao_id' => $inscId, 'modulo_id' => $pasta['id'], 'concluida_em' => $quando]);
        }

        if ($nome !== 'concluido') {
            return [null, null];
        }

        return [$quando, $notas === [] ? null : round(array_sum($notas) / count($notas), 2)];
    }

    /** @param  list<float>  $notas  acumula a melhor nota de cada prova concluida */
    private function concluirItem(int $inscId, array $item, CarbonImmutable $quando, int $seed, array &$notas): void
    {
        $posicao = match ($item['tipo']) {
            'VIDEO' => $item['dur'] ?? 0,
            'PDF' => $item['pag'] ?? 1,
            default => 0,
        };

        if ($item['tipo'] === 'PROVA') {
            $prova = $item['prova'];
            $n = 1;
            if ($seed % 2 === 1) {
                $this->tentativa($prova, $inscId, $n++, 2, $quando->subHours(3));
            }
            $notas[] = (float) $this->tentativa($prova, $inscId, $n, $seed % 3 === 0 ? 4 : 3, $quando->subHour());
        } elseif ($item['tipo'] === 'PROJETO') {
            $this->entrega($inscId, $item['id'], 'APROVADA', $quando->subDay());
        }

        $this->t('progresso_itens')->insert([
            'inscricao_id' => $inscId, 'modulo_item_id' => $item['id'], 'status' => 'CONCLUIDO', 'posicao' => $posicao, 'concluido_em' => $quando, 'created_at' => $quando->subHour(), 'updated_at' => $quando,
        ]);
    }

    private function estadoDoItem(int $inscId, array $item, string $nome, CarbonImmutable $quando): void
    {
        switch ($nome) {
            case 'parcial':
                if (in_array($item['tipo'], ['VIDEO', 'PDF'], true)) {
                    $total = $item['dur'] ?? $item['pag'] ?? 2;
                    $this->t('progresso_itens')->insert(['inscricao_id' => $inscId, 'modulo_item_id' => $item['id'], 'status' => 'EM_ANDAMENTO', 'posicao' => intdiv((int) $total, 2), 'created_at' => $quando, 'updated_at' => $quando]);
                }
                break;
            case 'prova_reprovada':
                $prova = $item['prova'];
                foreach ([1 => 1, 2 => 2, 3 => 1] as $num => $acertos) {
                    if ($num > $prova['max']) {
                        break;
                    }
                    $this->tentativa($prova, $inscId, $num, $acertos, $quando->subDays($prova['max'] - $num + 1));
                }
                break;
            case 'prova_andamento':
                $this->tentativa($item['prova'], $inscId, 1, 2, $quando->subHours(5));
                $this->tentativa($item['prova'], $inscId, 2, null, $quando);
                break;
            case 'entrega_enviada':
                $this->entrega($inscId, $item['id'], 'ENVIADA', $quando);
                break;
            case 'entrega_devolvida':
                $this->entrega($inscId, $item['id'], 'DEVOLVIDA', $quando);
                break;
            case 'quase':
                if ($item['tipo'] === 'PROVA') {
                    $prova = $item['prova'];
                    for ($num = 1; $num <= $prova['max']; $num++) {
                        $this->tentativa($prova, $inscId, $num, 1 + $num % 2, $quando->subDays($prova['max'] - $num + 1));
                    }
                    $this->t('prova_liberacoes')->insert(['prova_id' => $prova['id'], 'inscricao_id' => $inscId, 'quantidade' => 1, 'liberado_por' => $this->prof[0], 'created_at' => $quando]);
                } elseif ($item['tipo'] === 'PROJETO') {
                    $this->entrega($inscId, $item['id'], 'DEVOLVIDA', $quando);
                }
                break;
        }
    }

    private function entrega(int $inscId, int $itemId, string $status, CarbonImmutable $quando): void
    {
        $quando = self::passado($quando);
        $corrigida = $status !== 'ENVIADA';
        $this->t('projeto_entregas')->insert([
            'inscricao_id' => $inscId, 'modulo_item_id' => $itemId, 'arquivo_path' => 'demo/entregas/'.$inscId.'-'.$itemId.'.pdf', 'arquivo_nome' => 'trabalho-final.pdf', 'arquivo_mime' => 'application/pdf',
            'arquivo_tamanho' => 410_000, 'comentario' => 'Segue o trabalho solicitado, conforme as orientacoes do curso.', 'status' => $status,
            'parecer' => match ($status) {
                'APROVADA' => 'Trabalho aprovado. Bom diagnóstico das áreas de risco do município.',
                'DEVOLVIDA' => 'Faltou identificar os pontos de apoio e as rotas de evacuacao. Revise e reenvie.',
                default => null,
            },
            'corrigido_por' => $corrigida ? $this->prof[0] : null, 'corrigido_em' => $corrigida ? $quando->addDay() : null, 'created_at' => $quando, 'updated_at' => $quando,
        ]);
    }

    // ─── Certificados ───────────────────────────────────────────────────────

    /**
     * Chaves: n1..n5 (avaliou com a nota; sufixo c = com comentario), cancelado, reemitido,
     * pendente (emissão na fila) e legado (concluiu antes da avaliação obrigatória: sem avaliação).
     * Fora o legado, toda conclusão tem avaliação: é a etapa final que conclui o curso.
     */
    private function certificado(int $inscId, int $cursoId, string $chave, CarbonImmutable $concluidoEm, ?float $aproveitamento, int $seed): void
    {
        $emitido = self::passado($concluidoEm->addMinutes(3));
        $status = match ($chave) {
            'cancelado' => 'CANCELADO',
            'pendente' => 'PENDENTE',
            default => 'GERADO',
        };
        $reemitido = $chave === 'reemitido';
        $cancelado = $chave === 'cancelado';

        $id = $this->t('certificados')->insertGetId([
            'inscricao_id' => $inscId, 'treinamento_id' => $cursoId, 'status' => $status, 'emitido_em' => $status === 'PENDENTE' ? null : $emitido, 'criado_por' => null,
            'created_at' => $emitido, 'updated_at' => $emitido, 'aproveitamento' => $status === 'PENDENTE' ? null : $aproveitamento,
            'reemitido_em' => $reemitido ? self::passado($emitido->addDays(5)) : null,
            'cancelado_em' => $cancelado ? self::passado($emitido->addDays(4)) : null, 'cancelado_por' => $cancelado ? $this->gestor : null,
            'cancelamento_motivo' => $cancelado ? 'Emitido em duplicidade: inscrição regularizada pela coordenação.' : null,
        ]);

        if ($status !== 'PENDENTE') {
            // Mesmo mecanismo do sistema (CertificadoService::garantirCodigo): UPDATE condicionado a codigo nulo.
            app(CertificadoService::class)->garantirCodigo(Certificado::query()->findOrFail($id));
        }

        $nota = null;
        $comentario = false;
        if (preg_match('/^n([1-5])(c?)$/', $chave, $m) === 1) {
            $nota = (int) $m[1];
            $comentario = $m[2] === 'c';
        } elseif ($chave !== 'legado') {
            $nota = match (true) {
                $reemitido => 5,
                $cancelado => 4,
                default => 3,
            };
        }
        if ($nota !== null) {
            $this->t('curso_avaliacoes')->insert([
                'inscricao_id' => $inscId, 'treinamento_id' => $cursoId, 'nota' => $nota,
                // Relevancia do conteudo (RN17): acompanha a nota geral com uma variacao de -1..+1 (faixa 1..5).
                'nota_relevancia' => max(1, min(5, $nota + ($seed % 3) - 1)),
                'comentario' => $comentario ? self::COMENTARIOS[$seed % count(self::COMENTARIOS)] : null,
                'created_at' => self::passado($concluidoEm->addHours(2)), 'updated_at' => self::passado($concluidoEm->addHours(2)),
            ]);
        }
    }

    // ─── Historico, credenciais ─────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $c
     * @param  array<int, int>  $turmaIds
     */
    private function historico(int $cursoId, array $c, array $turmaIds, array $turmasDef, CarbonImmutable $criado, ?CarbonImmutable $publicadoEm, ?int $turmaRemovida): void
    {
        $linhas = [];
        $t = $criado;
        $add = function (TipoEventoCurso $tipo, string $descricao, array $dados = [], ?int $turmaId = null, int $dias = 1) use (&$linhas, &$t, $cursoId): void {
            $t = self::passado($t->addDays($dias)->addMinutes(11));
            $linhas[] = ['treinamento_id' => $cursoId, 'turma_id' => $turmaId, 'tipo' => $tipo->value, 'descricao' => $descricao, 'dados' => json_encode($dados), 'autor_id' => $this->gestor, 'created_at' => $t];
        };

        $add(TipoEventoCurso::CURSO_CRIADO, 'Curso criado.', ['titulo' => self::PREFIXO.$c['titulo'], 'tipo' => $c['tipo']], null, 0);
        foreach ($turmasDef as $i => $def) {
            $add(TipoEventoCurso::TURMA_CRIADA, 'Turma "'.$def[0].'" criada.', ['nome' => $def[0], 'data_inicio' => self::dia($def[1])->toDateString(), 'data_fim' => self::dia($def[2])->toDateString(), 'numero_vagas' => $def[3]], $turmaIds[$i]);
        }
        foreach (array_slice($c['cron'] ?? [], 0, 2) as $cr) {
            $add(TipoEventoCurso::CRONOGRAMA_ADICIONADO, 'Item "'.$cr[1].'" adicionado ao cronograma.', ['titulo' => $cr[1], 'inicio' => self::dia($cr[2], $cr[3])->toDateTimeString(), 'tipo' => $cr[0]]);
        }
        $add(TipoEventoCurso::CURSO_ATUALIZADO, 'Dados do curso atualizados.', ['carga_horaria' => ['antes' => $c['ch'] - 2, 'depois' => $c['ch']]]);
        if (array_key_exists('vagas', $c) && $c['vagas'] !== null) {
            $add(TipoEventoCurso::VAGAS_ALTERADAS, 'Vagas alteradas de '.($c['vagas'] - 5).' para '.$c['vagas'].'.', ['antes' => $c['vagas'] - 5, 'depois' => $c['vagas']]);
        }
        if (isset($c['ini'], $c['fim'])) {
            $add(TipoEventoCurso::PERIODO_ALTERADO, 'Período do curso alterado.', ['antes' => ['data_inicio' => self::dia($c['ini'] - 7)->toDateString(), 'data_fim' => self::dia($c['fim'])->toDateString()], 'depois' => ['data_inicio' => self::dia($c['ini'])->toDateString(), 'data_fim' => self::dia($c['fim'])->toDateString()]]);
        }
        if ($publicadoEm !== null) {
            $add(TipoEventoCurso::CURSO_PUBLICADO, 'Curso publicado no catalogo.', ['publicado_em' => $publicadoEm->toIso8601String()]);
        }
        if (in_array($c['status'], ['EM_ANDAMENTO', 'CONCLUIDO'], true)) {
            $add(TipoEventoCurso::STATUS_ALTERADO, 'Status alterado de Planejado para Em andamento.', ['de' => 'PLANEJADO', 'para' => 'EM_ANDAMENTO'], null, 5);
        }
        if ($c['status'] === 'CONCLUIDO') {
            $add(TipoEventoCurso::STATUS_ALTERADO, 'Status alterado de Em andamento para Concluido.', ['de' => 'EM_ANDAMENTO', 'para' => 'CONCLUIDO'], null, 20);
        }
        if ($c['status'] === 'CANCELADO') {
            $add(TipoEventoCurso::CURSO_CANCELADO, 'Curso cancelado.', ['motivo' => $c['motivo'] ?? 'Cancelado pela coordenação.'], null, 8);
        }
        if ($c['ciclo'] ?? false) {
            $add(TipoEventoCurso::CURSO_PAUSADO, 'Curso pausado para revisao do conteúdo.', [], null, 20);
            $add(TipoEventoCurso::CURSO_RETOMADO, 'Curso retomado.', [], null, 2);
            $add(TipoEventoCurso::CURSO_DESATIVADO, 'Curso desativado.', [], null, 3);
            $add(TipoEventoCurso::CURSO_REATIVADO, 'Curso reativado.', [], null, 1);
        }
        if ($c['sit'] === 'PAUSADO') {
            $add(TipoEventoCurso::CURSO_PAUSADO, 'Curso pausado.', [], null, 30);
        }
        if ($c['sit'] === 'DESATIVADO') {
            $add(TipoEventoCurso::CURSO_DESATIVADO, 'Curso desativado.', [], null, 30);
        }
        if ($turmaRemovida !== null) {
            $add(TipoEventoCurso::TURMA_ATUALIZADA, 'Turma "'.$turmasDef[1][0].'" atualizada.', ['antes' => ['numero_vagas' => 20], 'depois' => ['numero_vagas' => $turmasDef[1][3]]], $turmaIds[1]);
            $add(TipoEventoCurso::CRONOGRAMA_ATUALIZADO, 'Item "'.$c['cron'][0][1].'" do cronograma atualizado.', ['titulo' => $c['cron'][0][1]]);
            $add(TipoEventoCurso::CRONOGRAMA_REMOVIDO, 'Item "Aula extra (removida do cronograma)" removido do cronograma.', ['titulo' => 'Aula extra (removida do cronograma)']);
            $add(TipoEventoCurso::TURMA_REMOVIDA, 'Turma "Turma C - Uberlândia" removida.', ['nome' => 'Turma C - Uberlândia']);
        }

        $this->t('curso_eventos')->insert($linhas);
    }

    private function credenciais(): void
    {
        foreach (array_merge([$this->gestor], $this->prof, array_slice($this->srv, 0, 6)) as $i => $userId) {
            $this->t('credenciais_qr')->insert(['user_id' => $userId, 'token' => Str::random(40), 'gerado_em' => now()->subDays(30 - $i), 'created_at' => now()->subDays(30), 'updated_at' => now()->subDays(30)]);
        }
        foreach ([[0, 1], [0, 3], [1, 1], [2, 2], [3, 1], [7, 6], [0, 9], [4, 0]] as $n => [$alvo, $dias]) {
            $this->t('credencial_consultas')->insert(['user_id_consultado' => $this->srv[$alvo], 'consultado_por' => $n % 2 === 0 ? $this->gestor : $this->prof[0], 'created_at' => now()->subDays($dias)->subMinutes($n * 13)]);
        }
    }
}
