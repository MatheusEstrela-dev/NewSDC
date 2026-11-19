<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Models\User;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\ValidacaoDeCertificado;
use Database\Seeders\CapacitacoesDemoSeeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeder de dados ficticios das Capacitacoes: cobertura de estados, idempotencia,
 * limpeza segura e recusa em producao. Roda dentro da transacao do teste
 * (DatabaseTransactions), entao nada do que o seeder grava fica no banco.
 */
class CapacitacoesDemoSeederTest extends TreinamentoGestorTestCase
{
    private function semear(): void
    {
        app(CapacitacoesDemoSeeder::class)->run();
    }

    public function test_cobre_os_estados_do_modulo(): void
    {
        $this->semear();
        $c = CapacitacoesDemoSeeder::contagens();

        $this->assertGreaterThanOrEqual(12, $c['cursos']);
        $this->assertLessThanOrEqual(25, $c['cursos']);
        foreach (['EAD', 'PRESENCIAL', 'ONLINE', 'SEMI_PRESENCIAL'] as $tipo) {
            $this->assertArrayHasKey($tipo, $c['cursos_por_tipo']);
        }
        foreach (['PLANEJADO', 'EM_ANDAMENTO', 'CONCLUIDO', 'CANCELADO'] as $status) {
            $this->assertArrayHasKey($status, $c['cursos_por_status']);
        }
        foreach (['ATIVO', 'PAUSADO', 'DESATIVADO'] as $situacao) {
            $this->assertArrayHasKey($situacao, $c['cursos_por_situacao']);
        }
        foreach (PublicoAlvo::cases() as $publico) {
            $this->assertArrayHasKey($publico->value, $c['cursos_por_publico']);
        }
        $this->assertArrayHasKey('EVENTO', $c['cursos_por_categoria']);
        $this->assertArrayHasKey('CURSO', $c['cursos_por_categoria']);
        $this->assertGreaterThan(0, $c['cursos_publicados']);
        $this->assertLessThan($c['cursos'], $c['cursos_publicados'], 'Precisa haver curso nao publicado.');

        $this->assertCount(5, $c['cronograma_por_tipo']);
        $this->assertCount(17, $c['eventos_por_tipo'], 'Todos os TipoEventoCurso aparecem no historico.');
        $this->assertCount(4, $c['itens_por_tipo']);
        $this->assertCount(6, $c['videos_por_status']);
        $this->assertGreaterThan(0, $c['tentativas_abertas']);
        $this->assertCount(3, $c['entregas_por_status']);
        $this->assertCount(2, $c['progresso_itens_por_status']);
        $this->assertCount(3, $c['frequencias_por_status']);

        $this->assertCount(4, $c['inscricoes_por_status']);
        $this->assertGreaterThanOrEqual(40, $c['inscricoes']);
        $this->assertGreaterThan(0, $c['inscricoes_servidor']);
        $this->assertGreaterThan(0, $c['inscricoes_cidadao']);
        $this->assertGreaterThan(0, $c['inscricoes_concluidas']);

        $this->assertSame(['CANCELADO', 'GERADO', 'PENDENTE'], array_keys($c['certificados_por_status']));
        $this->assertGreaterThan(0, $c['concluidas_sem_avaliacao'], 'Precisa haver legado (concluiu antes da avaliacao obrigatoria).');
        $this->assertGreaterThanOrEqual(6, $c['avaliacoes_pendentes'], 'Precisa haver etapa final liberada (requisitos cumpridos, sem avaliar).');
        $this->assertGreaterThan(0, $c['certificados_reemitidos']);
        $this->assertSame([1, 2, 3, 4, 5], array_map('intval', array_keys($c['avaliacoes_por_nota'])));
        $this->assertGreaterThan(0, $c['avaliacoes_com_comentario']);
        $this->assertGreaterThan(0, $c['cidadaos_nao_verificados']);
        $this->assertGreaterThan(0, $c['cidadaos_inativos']);
        $this->assertGreaterThan(0, $c['credenciais_qr']);
    }

    public function test_conclusoes_tem_avaliacao_salvo_o_legado_e_codigo_valida(): void
    {
        $this->semear();
        $ids = Treinamento::query()->where('titulo', 'like', CapacitacoesDemoSeeder::PREFIXO.'%')->pluck('id');

        // Toda inscricao concluida tem certificado; sem avaliacao, so o legado (certificado GERADO, liberado).
        $concluidas = Inscricao::query()->whereIn('treinamento_id', $ids)->whereNotNull('concluido_em')->with(['avaliacao', 'certificado'])->get();
        $this->assertNotEmpty($concluidas);
        foreach ($concluidas as $inscricao) {
            $this->assertNotNull($inscricao->certificado, "Inscricao {$inscricao->id} concluida sem certificado.");
        }
        $semAvaliacao = $concluidas->whereNull('avaliacao');
        $this->assertNotEmpty($semAvaliacao);
        $this->assertTrue($semAvaliacao->every(fn (Inscricao $i) => $i->certificado->status->value === 'GERADO'));

        // Nenhuma inscricao com avaliacao ficou sem concluir (a avaliacao e o que conclui).
        $this->assertSame(0, Inscricao::query()->whereIn('treinamento_id', $ids)->whereNull('concluido_em')->whereHas('avaliacao')->count());

        $legado = $semAvaliacao->first()->certificado;

        // Validacao publica pelo codigo gerado pelo mecanismo do sistema (CertificadoService::garantirCodigo).
        $validacao = app(ValidacaoDeCertificado::class);
        $this->assertSame(ValidacaoDeCertificado::VALIDO, $validacao->buscar($legado->codigo_verificacao)['situacao']);
        $cancelado = Certificado::query()->whereIn('treinamento_id', $ids)->where('status', 'CANCELADO')->firstOrFail();
        $this->assertSame(ValidacaoDeCertificado::CANCELADO, $validacao->buscar($cancelado->codigo_verificacao)['situacao']);
        $reemitido = Certificado::query()->whereIn('treinamento_id', $ids)->whereNotNull('reemitido_em')->firstOrFail();
        $this->assertSame(ValidacaoDeCertificado::REEMITIDO, $validacao->buscar($reemitido->codigo_verificacao)['situacao']);
    }

    public function test_logins_demo_mostram_a_etapa_final_liberada_e_com_cadeado(): void
    {
        $this->semear();
        $curso = fn (string $titulo) => Treinamento::query()->where('titulo', CapacitacoesDemoSeeder::PREFIXO.$titulo)->sole();

        // Aluno demo (servidor): EAD com a trilha completa e sem avaliar, EAD incompleto e evento a avaliar.
        $this->logarComo(User::query()->where('cpf', CapacitacoesDemoSeeder::cpfFicticio(301))->sole());

        $this->get(route('treinamentos.aluno.sala', $curso('Gestão de Abrigos Temporários')))->assertOk()
            ->assertInertia(fn ($page) => $page->where('avaliacao_final.situacao', 'LIBERADA')->where('avaliacao_final.concluido', false));
        $this->get(route('treinamentos.aluno.sala', $curso('Introdução ao Sistema Nacional de Proteção e Defesa Civil (SINPDEC)')))->assertOk()
            ->assertInertia(fn ($page) => $page->where('avaliacao_final.situacao', 'BLOQUEADA'));

        $seminario = $curso('Seminário Estadual de Proteção e Defesa Civil 2026');
        $this->get(route('treinamentos.aluno.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('avaliacoes_pendentes.0.treinamento_id', $seminario->id));

        // Cidadao demo: o mesmo no portal (EAD na sala, semipresencial em "Minhas inscricoes").
        $cidadao = Cidadao::query()->where('cpf', CapacitacoesDemoSeeder::cpfFicticio(1001))->sole();

        $this->actingAs($cidadao, 'cidadao')
            ->get(route('portal.treinamento.cursos.sala', $curso('Gestão de Riscos e Desastres: Classificação pela COBRADE')->link_publico_slug))->assertOk()
            ->assertInertia(fn ($page) => $page->where('avaliacao_final.situacao', 'LIBERADA'));
        $this->actingAs($cidadao, 'cidadao')
            ->get(route('portal.treinamento.cursos.sala', $curso('Primeiros Socorros em Situações de Desastre')->link_publico_slug))->assertOk()
            ->assertInertia(fn ($page) => $page->where('avaliacao_final.situacao', 'BLOQUEADA'));

        $semi = Inscricao::query()->where('treinamento_id', $curso('Gestão Integrada de Riscos e Desastres')->id)
            ->where('inscrito_type', $cidadao->getMorphClass())->where('inscrito_id', $cidadao->id)->sole();
        $this->actingAs($cidadao, 'cidadao')
            ->get(route('portal.treinamento.inscricoes.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->has("avaliacoes_pendentes.{$semi->id}"));
    }

    public function test_reexecutar_nao_duplica_e_limpar_remove_so_o_demo(): void
    {
        $outro = $this->criarCurso(['titulo' => 'Curso real, nao e demo']);
        $dono = User::factory()->create();
        $inscricaoDoOutro = Inscricao::create([
            'treinamento_id' => $outro->id, 'inscrito_type' => User::class, 'inscrito_id' => $dono->id, 'status' => 'APROVADA', 'qr_code_token' => (string) Str::uuid(),
        ]);
        $usuariosAntes = User::query()->count();

        $this->semear();
        $primeira = CapacitacoesDemoSeeder::contagens();
        $this->semear();
        $segunda = CapacitacoesDemoSeeder::contagens();

        $this->assertSame($primeira, $segunda, 'Segunda execucao deve produzir exatamente as mesmas contagens.');
        $this->assertSame($usuariosAntes + $segunda['servidores'], User::query()->count(), 'Contas demo nao se duplicam.');
        $tokens = ConexaoTreinamento::conexao()->table('treinamento.inscricoes')->pluck('qr_code_token');
        $this->assertSame($tokens->count(), $tokens->unique()->count());

        $removido = CapacitacoesDemoSeeder::limpar();
        $this->assertSame($segunda['cursos'], $removido['cursos']);

        $depois = CapacitacoesDemoSeeder::contagens();
        $this->assertSame(0, $depois['cursos']);
        $this->assertSame(0, $depois['inscricoes']);
        $this->assertSame(0, $depois['cidadaos']);
        $this->assertSame(0, $depois['servidores']);

        // O que nao e demo permanece intacto.
        $this->assertNotNull(Treinamento::query()->find($outro->id));
        $this->assertNotNull(Inscricao::query()->find($inscricaoDoOutro->id));
        $this->assertSame($usuariosAntes, User::query()->count());
    }

    public function test_recusa_rodar_em_producao(): void
    {
        $original = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $this->expectException(RuntimeException::class);
            $this->semear();
        } finally {
            $this->app['env'] = $original;
        }
    }
}
