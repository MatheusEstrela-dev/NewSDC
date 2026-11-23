<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Modules\Treinamento\Enums\NotaAvaliacaoCurso;
use App\Modules\Treinamento\Enums\NotaRelevanciaCurso;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Enums\StatusCertificado;
use App\Modules\Treinamento\Enums\StatusFrequencia;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Events\ItemDaTrilhaConcluido;
use App\Modules\Treinamento\Listeners\AvaliarConclusaoDaInscricao;
use App\Modules\Treinamento\Models\AvaliacaoCurso;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Frequencia;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\ProgressoItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CertificadoService;
use App\Modules\Treinamento\Services\ConclusaoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Avaliacao do curso como etapa final obrigatoria (barra de satisfacao 1..5):
 * concluir = requisitos do criterio (trilha completa no EAD; curso concluido +
 * frequencia minima nos eventos) E avaliacao enviada. A avaliacao so abre com
 * os requisitos cumpridos; enviar conclui e emite o certificado pelo caminho
 * real. Inscricao ja concluida antes da regra (legado) segue concluida.
 */
class AvaliacaoCursoTest extends TreinamentoGestorTestCase
{
    // ─── EAD: a avaliacao so abre no final da trilha ────────────────────────

    public function test_portal_ead_recusa_avaliacao_com_trilha_incompleta(): void
    {
        $cidadao = $this->cidadao();
        [$curso] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao);

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas(4))
            ->assertSessionHasErrors('nota');

        $this->assertFalse(AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists());
        $this->assertNull($inscricao->fresh()->concluido_em);
    }

    public function test_ead_trilha_completa_sem_avaliacao_nao_conclui_nem_emite(): void
    {
        $cidadao = $this->cidadao();
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao);
        $this->completarTrilha($inscricao, $item);

        $this->assertTrue(app(ConclusaoService::class)->requisitosCumpridos($inscricao->fresh()));
        $this->assertNull(app(CertificadoService::class)->emitirSeElegivel($inscricao->fresh()));

        $this->assertNull($inscricao->fresh()->concluido_em);
        $this->assertFalse(Certificado::query()->where('inscricao_id', $inscricao->id)->exists());
    }

    public function test_listener_do_ultimo_item_nao_conclui_sem_avaliacao(): void
    {
        $aluno = $this->usuarioComPermissoes(['treinamento.cursos.view']);
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::SERVIDORES);
        $inscricao = $this->inscrever($curso, $aluno);
        $this->completarTrilha($inscricao, $item);

        app(AvaliarConclusaoDaInscricao::class)->handle(new ItemDaTrilhaConcluido($inscricao->id, $item->id));

        $this->assertNull($inscricao->fresh()->concluido_em);
        $this->assertFalse(Certificado::query()->where('inscricao_id', $inscricao->id)->exists());
    }

    public function test_comando_de_reavaliacao_so_conclui_com_avaliacao(): void
    {
        $aluno = $this->usuarioComPermissoes(['treinamento.cursos.view']);
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::SERVIDORES);
        $inscricao = $this->inscrever($curso, $aluno);
        $this->completarTrilha($inscricao, $item);

        $this->artisan('treinamento:avaliar-conclusoes', ['treinamento' => $curso->id])->assertSuccessful();
        $this->assertNull($inscricao->fresh()->concluido_em, 'Sem avaliacao o comando nao conclui.');

        // Avaliacao gravada (ex.: a conclusao falhou depois do envio): o comando recupera.
        AvaliacaoCurso::create(['inscricao_id' => $inscricao->id, 'treinamento_id' => $curso->id, 'nota' => NotaAvaliacaoCurso::BOM]);
        $this->artisan('treinamento:avaliar-conclusoes', ['treinamento' => $curso->id])->assertSuccessful();

        $this->assertNotNull($inscricao->fresh()->concluido_em);
        $this->assertSame(StatusCertificado::GERADO, Certificado::query()->where('inscricao_id', $inscricao->id)->sole()->status);
    }

    public function test_portal_ead_avaliar_conclui_e_emite_o_certificado(): void
    {
        $cidadao = $this->cidadao();
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao);
        $this->completarTrilha($inscricao, $item);

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), [
                'nota' => 5,
                'nota_relevancia' => 4,
                'comentario' => '  Muito bom!  ',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $avaliacao = AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->sole();
        $this->assertSame(NotaAvaliacaoCurso::EXCELENTE, $avaliacao->nota);
        $this->assertSame(NotaRelevanciaCurso::RELEVANTE, $avaliacao->nota_relevancia);
        $this->assertSame('Muito bom!', $avaliacao->comentario);
        $this->assertSame($curso->id, $avaliacao->treinamento_id);

        $this->assertNotNull($inscricao->fresh()->concluido_em);
        $certificado = Certificado::query()->where('inscricao_id', $inscricao->id)->sole();
        $this->assertSame(StatusCertificado::GERADO, $certificado->status);
        $this->assertNotNull($certificado->codigo_verificacao);

        $this->actingAs($cidadao, 'cidadao')
            ->get(route('portal.treinamento.certificados.imprimir', $certificado))
            ->assertOk();
    }

    public function test_servidor_ead_avaliar_conclui_e_emite_o_certificado(): void
    {
        $aluno = $this->usuarioComPermissoes(['treinamento.cursos.view']);
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::SERVIDORES);
        $inscricao = $this->inscrever($curso, $aluno);
        $this->completarTrilha($inscricao, $item);
        $this->logarComo($aluno);

        $this->get(route('treinamentos.aluno.index', ['categoria' => 'LIVRE']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('meus_cursos.0.avaliacao_pendente', true)
                ->where('meus_cursos.0.concluido', false)
                ->where('meus_cursos.0.percentual', 50) // 1 item + a avaliacao: falta a etapa final
                ->has('certificados', 0));

        $this->post(route('treinamentos.aluno.avaliacao', $curso), $this->respostas(4))->assertSessionHasNoErrors();

        $this->assertNotNull($inscricao->fresh()->concluido_em);
        $certificado = Certificado::query()->where('inscricao_id', $inscricao->id)->sole();

        $this->get(route('treinamentos.aluno.index', ['categoria' => 'LIVRE']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('meus_cursos.0.avaliacao_pendente', false)
                ->where('meus_cursos.0.concluido', true)
                ->where('meus_cursos.0.percentual', 100)
                ->where('certificados.0.id', $certificado->id));

        $this->get(route('treinamentos.certificados.imprimir', $certificado))->assertOk();
    }

    // ─── Sala: a etapa final da trilha ──────────────────────────────────────

    public function test_portal_sala_mostra_a_etapa_final_bloqueada_liberada_e_enviada(): void
    {
        $cidadao = $this->cidadao();
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao);
        $sala = route('portal.treinamento.cursos.sala', $curso->link_publico_slug);

        $this->actingAs($cidadao, 'cidadao')->get($sala)->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Treinamento/Portal/SalaDeAula')
                ->where('avaliacao_final.situacao', 'BLOQUEADA')
                ->where('avaliacao_final.concluido', false)
                ->has('notas_avaliacao', 5)
                ->has('notas_relevancia', 5));

        $this->completarTrilha($inscricao, $item);

        $this->actingAs($cidadao, 'cidadao')->get($sala)->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('avaliacao_final.situacao', 'LIBERADA')
                ->where('avaliacao_final.concluido', false)
                ->where('avaliacao_final.certificado', null));

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas(3))
            ->assertSessionHasNoErrors();

        $this->actingAs($cidadao, 'cidadao')->get($sala)->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('avaliacao_final.situacao', 'ENVIADA')
                ->where('avaliacao_final.concluido', true)
                ->where('avaliacao_final.avaliacao.nota', 3)
                ->where('avaliacao_final.avaliacao.nota_relevancia', 5)
                ->where('avaliacao_final.avaliacao.nota_relevancia_label', 'Muito relevante')
                ->where('avaliacao_final.certificado.disponivel', true));
    }

    public function test_servidor_sala_libera_a_etapa_final_com_a_trilha_completa(): void
    {
        $aluno = $this->usuarioComPermissoes(['treinamento.cursos.view']);
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::SERVIDORES);
        $inscricao = $this->inscrever($curso, $aluno);
        $this->logarComo($aluno);

        $this->get(route('treinamentos.aluno.sala', $curso))->assertOk()
            ->assertInertia(fn ($page) => $page->where('avaliacao_final.situacao', 'BLOQUEADA'));

        $this->completarTrilha($inscricao, $item);

        // Abrir a sala reavalia a conclusao: sem avaliacao, continua so liberada.
        $this->get(route('treinamentos.aluno.sala', $curso))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('avaliacao_final.situacao', 'LIBERADA')
                ->where('avaliacao_final.concluido', false));
        $this->assertNull($inscricao->fresh()->concluido_em);
    }

    // ─── Eventos (presencial/online/semi): sem sala ─────────────────────────

    public function test_presencial_recusa_avaliacao_sem_frequencia_minima(): void
    {
        $cidadao = $this->cidadao();
        $curso = $this->eventoPublicado(PublicoAlvo::CIDADAOS);
        $inscricao = $this->eventoComFrequencia($curso, $cidadao, presente: false, concluirCurso: true);

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas(5))
            ->assertSessionHasErrors('nota');

        $this->assertFalse(AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists());
    }

    public function test_presencial_recusa_avaliacao_com_curso_ainda_nao_concluido(): void
    {
        $cidadao = $this->cidadao();
        $curso = $this->eventoPublicado(PublicoAlvo::CIDADAOS);
        $inscricao = $this->eventoComFrequencia($curso, $cidadao, presente: true, concluirCurso: false);

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas(5))
            ->assertSessionHasErrors('nota');

        $this->assertFalse(AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists());
    }

    public function test_portal_presencial_requisitos_cumpridos_aparece_em_minhas_inscricoes_e_avaliar_conclui(): void
    {
        $cidadao = $this->cidadao();
        $curso = $this->eventoPublicado(PublicoAlvo::CIDADAOS);
        $inscricao = $this->eventoComFrequencia($curso, $cidadao, presente: true, concluirCurso: true);

        // A finalizacao do curso (emissao em lote) nao conclui sem a avaliacao.
        $this->assertNull(app(CertificadoService::class)->emitirSeElegivel($inscricao->fresh()));
        $this->assertNull($inscricao->fresh()->concluido_em);

        $this->actingAs($cidadao, 'cidadao')
            ->get(route('portal.treinamento.inscricoes.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Treinamento/Portal/MinhasInscricoes')
                ->where("avaliacoes_pendentes.{$inscricao->id}.url_avaliar", route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug))
                ->has('notas_avaliacao', 5));

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas(2))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($inscricao->fresh()->concluido_em);
        $certificado = Certificado::query()->where('inscricao_id', $inscricao->id)->sole();
        $this->assertSame(StatusCertificado::GERADO, $certificado->status);

        $this->actingAs($cidadao, 'cidadao')
            ->get(route('portal.treinamento.inscricoes.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->missing("avaliacoes_pendentes.{$inscricao->id}")
                ->where('inscricoes.data.0.certificado.disponivel', true));
    }

    public function test_servidor_presencial_avalia_em_meus_cursos_e_conclui(): void
    {
        $servidor = $this->usuarioComPermissoes(['treinamento.cursos.view']);
        $curso = $this->criarCurso();
        $inscricao = $this->eventoComFrequencia($curso, $servidor, presente: true, concluirCurso: true);
        $this->logarComo($servidor);

        $this->get(route('treinamentos.aluno.index', ['categoria' => 'LIVRE']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('avaliacoes_pendentes.0.inscricao_id', $inscricao->id)
                ->where('avaliacoes_pendentes.0.url_avaliar', route('treinamentos.aluno.avaliacao', $curso->id)));

        $this->post(route('treinamentos.aluno.avaliacao', $curso), $this->respostas(1))->assertSessionHasNoErrors();

        $this->assertNotNull($inscricao->fresh()->concluido_em);
        $certificado = Certificado::query()->where('inscricao_id', $inscricao->id)->sole();

        // O certificado do evento aparece em "Meus certificados" e a pendencia some.
        $this->get(route('treinamentos.aluno.index', ['categoria' => 'LIVRE']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('avaliacoes_pendentes', 0)
                ->where('certificados.0.id', $certificado->id));

        $this->get(route('treinamentos.certificados.imprimir', $certificado))->assertOk();
    }

    // ─── Validacao e autorizacao ────────────────────────────────────────────

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function notasInvalidas(): array
    {
        return [
            'zero' => [0],
            'seis' => [6],
            'negativa' => [-1],
            'texto' => ['otimo'],
            'fracionada' => [3.5],
            'ausente' => [null],
        ];
    }

    #[DataProvider('notasInvalidas')]
    public function test_nota_fora_de_1_a_5_e_rejeitada(mixed $nota): void
    {
        $cidadao = $this->cidadao();
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao);
        $this->completarTrilha($inscricao, $item);

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas($nota))
            ->assertSessionHasErrors('nota');

        $this->assertFalse(AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists());
        $this->assertNull($inscricao->fresh()->concluido_em);
    }

    #[DataProvider('notasInvalidas')]
    public function test_relevancia_fora_de_1_a_5_e_rejeitada(mixed $relevancia): void
    {
        $cidadao = $this->cidadao();
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao);
        $this->completarTrilha($inscricao, $item);

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), ['nota_relevancia' => $relevancia] + $this->respostas(4))
            ->assertSessionHasErrors('nota_relevancia');

        $this->assertFalse(AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists());
        $this->assertNull($inscricao->fresh()->concluido_em);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function comentariosInvalidos(): array
    {
        return [
            'ausente' => [null],
            'vazio' => [''],
            'so espacos' => ['   '],
            'longo demais' => [str_repeat('a', 2001)],
        ];
    }

    #[DataProvider('comentariosInvalidos')]
    public function test_sugestao_e_obrigatoria_e_limitada(mixed $comentario): void
    {
        $cidadao = $this->cidadao();
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao);
        $this->completarTrilha($inscricao, $item);

        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), ['comentario' => $comentario] + $this->respostas(4))
            ->assertSessionHasErrors('comentario');

        $this->assertFalse(AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists());
        $this->assertNull($inscricao->fresh()->concluido_em);
    }

    public function test_segundo_envio_e_rejeitado_e_nao_altera_a_nota(): void
    {
        $cidadao = $this->cidadao();
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao);
        $this->completarTrilha($inscricao, $item);
        $url = route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug);

        $this->actingAs($cidadao, 'cidadao')->post($url, $this->respostas(2))->assertSessionHasNoErrors();
        $this->actingAs($cidadao, 'cidadao')->post($url, $this->respostas(5))->assertSessionHasErrors('nota');

        $avaliacoes = AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->get();
        $this->assertCount(1, $avaliacoes);
        $this->assertSame(NotaAvaliacaoCurso::RUIM, $avaliacoes->first()->nota);
        $this->assertSame(1, Certificado::query()->where('inscricao_id', $inscricao->id)->count());
    }

    public function test_outro_cidadao_nao_avalia_nem_ve_o_certificado_alheio(): void
    {
        $dono = $this->cidadao();
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $dono);
        $this->completarTrilha($inscricao, $item);
        $intruso = $this->cidadao();

        // A rota resolve a inscricao do proprio usuario logado: sem inscricao, 403.
        $this->actingAs($intruso, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas(1))
            ->assertForbidden();

        $this->assertFalse(AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists());
        $this->assertNull($inscricao->fresh()->concluido_em);

        $this->actingAs($dono, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas(5))
            ->assertSessionHasNoErrors();
        $certificado = Certificado::query()->where('inscricao_id', $inscricao->id)->sole();

        $this->actingAs($intruso, 'cidadao')
            ->get(route('portal.treinamento.certificados.imprimir', $certificado))
            ->assertForbidden();
    }

    public function test_servidor_nao_avalia_inscricao_de_outro_servidor(): void
    {
        $dono = $this->usuarioComPermissoes(['treinamento.cursos.view']);
        [$curso, $item] = $this->cursoEadComTrilha(PublicoAlvo::SERVIDORES);
        $inscricao = $this->inscrever($curso, $dono);
        $this->completarTrilha($inscricao, $item);

        $this->logarComo($this->usuarioComPermissoes(['treinamento.cursos.view']));
        $this->post(route('treinamentos.aluno.avaliacao', $curso), $this->respostas(5))->assertForbidden();

        $this->assertFalse(AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists());
        $this->assertNull($inscricao->fresh()->concluido_em);
    }

    // ─── Legado: concluiu antes da regra ────────────────────────────────────

    public function test_legado_concluido_sem_avaliacao_segue_concluido_e_com_certificado(): void
    {
        $cidadao = $this->cidadao();
        [$curso] = $this->cursoEadComTrilha(PublicoAlvo::CIDADAOS);
        $inscricao = $this->inscrever($curso, $cidadao, concluida: true);
        $concluidoEm = $inscricao->concluido_em;

        // Emissao (job, comando de pendentes) continua valendo para quem ja concluiu.
        $certificado = app(CertificadoService::class)->emitirSeElegivel($inscricao->fresh());
        $this->assertNotNull($certificado);
        $this->assertSame(StatusCertificado::GERADO, $certificado->status);
        $this->assertEquals($concluidoEm, $inscricao->fresh()->concluido_em);

        $this->actingAs($cidadao, 'cidadao')
            ->get(route('portal.treinamento.certificados.imprimir', $certificado))
            ->assertOk();

        $this->actingAs($cidadao, 'cidadao')
            ->get(route('portal.treinamento.cursos.sala', $curso->link_publico_slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('avaliacao_final.situacao', 'LIBERADA')
                ->where('avaliacao_final.concluido', true)
                ->where('avaliacao_final.certificado.disponivel', true));

        // Avaliar depois e opcional e nao mexe na conclusao nem no certificado.
        $this->actingAs($cidadao, 'cidadao')
            ->post(route('portal.treinamento.cursos.avaliacao', $curso->link_publico_slug), $this->respostas(4))
            ->assertSessionHasNoErrors();

        $this->assertEquals($concluidoEm, $inscricao->fresh()->concluido_em);
        $this->assertSame(1, Certificado::query()->where('inscricao_id', $inscricao->id)->count());
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * As tres respostas do formulario (todas obrigatorias): nota geral,
     * relevancia do conteudo e sugestao/comentario.
     *
     * @return array<string, mixed>
     */
    private function respostas(mixed $nota, mixed $relevancia = 5, string $comentario = 'Gostaria de ver mais sobre gestao de risco.'): array
    {
        return ['nota' => $nota, 'nota_relevancia' => $relevancia, 'comentario' => $comentario];
    }

    /**
     * Curso EAD publicado com uma pasta e um PDF obrigatorio de 1 pagina.
     *
     * @return array{0: Treinamento, 1: ModuloItem}
     */
    private function cursoEadComTrilha(PublicoAlvo $publico): array
    {
        $curso = $this->criarCursoEad(['publico_alvo' => $publico->value]);
        $curso->publicarNoPortal();

        $modulo = Modulo::create(['treinamento_id' => $curso->id, 'titulo' => 'Pasta 1', 'ordem' => 1, 'carga_horaria' => 1]);
        $item = ModuloItem::create([
            'modulo_id' => $modulo->id,
            'tipo' => TipoItem::PDF->value,
            'titulo' => 'Leitura obrigatoria',
            'ordem' => 1,
            'obrigatorio' => true,
            'total_paginas' => 1,
        ]);

        return [$curso->refresh(), $item];
    }

    private function completarTrilha(Inscricao $inscricao, ModuloItem $item): void
    {
        ProgressoItem::create([
            'inscricao_id' => $inscricao->id,
            'modulo_item_id' => $item->id,
            'status' => StatusProgresso::CONCLUIDO->value,
            'posicao' => 1,
            'concluido_em' => now(),
        ]);
    }

    private function eventoPublicado(PublicoAlvo $publico): Treinamento
    {
        $curso = $this->criarCurso(['publico_alvo' => $publico->value]);
        $curso->publicarNoPortal();

        return $curso->refresh();
    }

    /**
     * Evento com uma aula: presenca (100%) ou falta (0%), e o curso concluido
     * ou ainda em andamento.
     */
    private function eventoComFrequencia(Treinamento $curso, Model $inscrito, bool $presente, bool $concluirCurso): Inscricao
    {
        $modulo = Modulo::create(['treinamento_id' => $curso->id, 'titulo' => 'Aula 1', 'ordem' => 1, 'carga_horaria' => 1]);
        $inscricao = $this->inscrever($curso, $inscrito);

        Frequencia::create([
            'modulo_id' => $modulo->id,
            'inscricao_id' => $inscricao->id,
            'status' => ($presente ? StatusFrequencia::PRESENTE : StatusFrequencia::AUSENTE)->value,
            'data_aula' => now()->toDateString(),
        ]);

        Treinamento::query()->whereKey($curso->id)->update($concluirCurso
            ? ['status' => StatusTreinamento::CONCLUIDO->value, 'finalizado_em' => now()]
            : ['status' => StatusTreinamento::EM_ANDAMENTO->value]);

        return $inscricao->refresh();
    }

    private function cidadao(): Cidadao
    {
        $sufixo = Str::lower(Str::random(10));

        $cidadao = Cidadao::create([
            'name' => 'Cidadao '.$sufixo,
            'email' => "cidadao.{$sufixo}@example.test",
            'cpf' => str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT),
            'password' => 'senha-de-teste-123',
            'aceite_lgpd_em' => now(),
            'ativo' => true,
        ]);
        $cidadao->marcarEmailVerificado();

        return $cidadao;
    }

    private function inscrever(Treinamento $curso, Model $inscrito, bool $concluida = false): Inscricao
    {
        $inscricao = Inscricao::create([
            'treinamento_id' => $curso->id,
            'inscrito_type' => $inscrito->getMorphClass(),
            'inscrito_id' => $inscrito->getKey(),
            'status' => StatusInscricao::APROVADA->value,
            'data_inscricao' => now(),
            'data_aprovacao' => now(),
            'qr_code_token' => (string) Str::uuid(),
        ]);

        if ($concluida) {
            // Conclusao congelada (fora do $fillable de proposito, ver Inscricao).
            Inscricao::query()->whereKey($inscricao->id)->update(['concluido_em' => now()->subMonth(), 'aproveitamento' => 100]);
            $inscricao->refresh();
        }

        return $inscricao;
    }
}
