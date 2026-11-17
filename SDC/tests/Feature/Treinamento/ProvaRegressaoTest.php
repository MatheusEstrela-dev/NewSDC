<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaAlternativa;
use App\Modules\Treinamento\Models\ProvaQuestao;
use App\Modules\Treinamento\Services\ProvaService;
use Illuminate\Support\Str;

/**
 * Issue #86, bloco 4.7: regressao do fluxo de prova existente (ProvaService) -
 * sorteio, resposta e correcao pelo gabarito (`correta`) continuam
 * funcionando depois das mudancas do modulo nesta issue (nenhum arquivo de
 * ProvaService foi tocado pelos blocos 1-3, mas o schema e os enums do
 * Treinamento mudaram e Status/TipoTreinamento ganharam um caso novo).
 *
 * `iniciar()`/`enviar()` disparam ItemDaTrilhaConcluido (ShouldDispatchAfterCommit):
 * dentro de DatabaseTransactions a transacao nunca comita de verdade, entao o
 * listener (emissao de certificado etc.) nunca roda aqui - seguro testar o
 * caminho "aprovado" sem efeito colateral externo.
 */
class ProvaRegressaoTest extends TreinamentoGestorTestCase
{
    private function montarProva(int $notaMinima = 60): array
    {
        $curso = $this->criarCurso(['tipo' => TipoTreinamento::EAD->value]);
        $modulo = Modulo::create(['treinamento_id' => $curso->id, 'titulo' => 'Pasta 1', 'ordem' => 1, 'carga_horaria' => 1]);
        $item = ModuloItem::create([
            'modulo_id' => $modulo->id,
            'tipo' => TipoItem::PROVA->value,
            'titulo' => 'Prova da pasta',
            'ordem' => 1,
            'obrigatorio' => true,
        ]);
        $prova = Prova::create(['modulo_item_id' => $item->id, 'nota_minima' => $notaMinima, 'max_tentativas' => 3]);

        $questao1 = ProvaQuestao::create(['prova_id' => $prova->id, 'enunciado' => 'Questao 1', 'ordem' => 1]);
        $certaQ1 = ProvaAlternativa::create(['prova_questao_id' => $questao1->id, 'texto' => 'Certa', 'correta' => true, 'ordem' => 1]);
        ProvaAlternativa::create(['prova_questao_id' => $questao1->id, 'texto' => 'Errada', 'correta' => false, 'ordem' => 2]);

        $questao2 = ProvaQuestao::create(['prova_id' => $prova->id, 'enunciado' => 'Questao 2', 'ordem' => 2]);
        $certaQ2 = ProvaAlternativa::create(['prova_questao_id' => $questao2->id, 'texto' => 'Certa', 'correta' => true, 'ordem' => 1]);
        $erradaQ2 = ProvaAlternativa::create(['prova_questao_id' => $questao2->id, 'texto' => 'Errada', 'correta' => false, 'ordem' => 2]);

        $aluno = User::factory()->create();
        $inscricao = Inscricao::create([
            'treinamento_id' => $curso->id,
            'inscrito_type' => User::class,
            'inscrito_id' => $aluno->id,
            'status' => StatusInscricao::APROVADA->value,
            'data_inscricao' => now(),
            'data_aprovacao' => now(),
            'qr_code_token' => (string) Str::uuid(),
        ]);

        return compact('curso', 'item', 'prova', 'questao1', 'certaQ1', 'questao2', 'certaQ2', 'erradaQ2', 'inscricao');
    }

    public function test_sorteio_congela_todas_as_questoes_com_alternativa(): void
    {
        $ctx = $this->montarProva();
        $tentativa = app(ProvaService::class)->iniciar($ctx['inscricao'], $ctx['item']);

        $this->assertCount(2, $tentativa->ordem);
        $this->assertNotNull($tentativa->iniciada_em);
        $this->assertNull($tentativa->finalizada_em);
    }

    public function test_iniciar_de_novo_retoma_a_tentativa_aberta_em_vez_de_sortear_outra(): void
    {
        $ctx = $this->montarProva();
        $primeira = app(ProvaService::class)->iniciar($ctx['inscricao'], $ctx['item']);
        $segunda = app(ProvaService::class)->iniciar($ctx['inscricao'], $ctx['item']);

        $this->assertSame($primeira->id, $segunda->id);
    }

    public function test_correcao_pelo_gabarito_aprova_quando_acerta_tudo(): void
    {
        $ctx = $this->montarProva(notaMinima: 60);
        $service = app(ProvaService::class);
        $tentativa = $service->iniciar($ctx['inscricao'], $ctx['item']);

        $service->responder($ctx['inscricao'], $ctx['item'], $tentativa->id, $ctx['questao1']->id, $ctx['certaQ1']->id);
        $service->responder($ctx['inscricao'], $ctx['item'], $tentativa->id, $ctx['questao2']->id, $ctx['certaQ2']->id);

        $corrigida = $service->enviar($ctx['inscricao'], $ctx['item'], $tentativa->id);

        $this->assertSame(100.0, $corrigida->nota);
        $this->assertTrue($corrigida->aprovada);
        $this->assertNotNull($corrigida->finalizada_em);
    }

    public function test_correcao_pelo_gabarito_reprova_abaixo_da_nota_minima(): void
    {
        $ctx = $this->montarProva(notaMinima: 60);
        $service = app(ProvaService::class);
        $tentativa = $service->iniciar($ctx['inscricao'], $ctx['item']);

        // Uma certa, uma errada: 50%, abaixo dos 60 exigidos.
        $service->responder($ctx['inscricao'], $ctx['item'], $tentativa->id, $ctx['questao1']->id, $ctx['certaQ1']->id);
        $service->responder($ctx['inscricao'], $ctx['item'], $tentativa->id, $ctx['questao2']->id, $ctx['erradaQ2']->id);

        $corrigida = $service->enviar($ctx['inscricao'], $ctx['item'], $tentativa->id);

        $this->assertSame(50.0, $corrigida->nota);
        $this->assertFalse($corrigida->aprovada);
    }

    public function test_questao_sem_resposta_conta_como_errada(): void
    {
        $ctx = $this->montarProva(notaMinima: 60);
        $service = app(ProvaService::class);
        $tentativa = $service->iniciar($ctx['inscricao'], $ctx['item']);

        // So responde a questao 1; a 2 fica sem resposta.
        $service->responder($ctx['inscricao'], $ctx['item'], $tentativa->id, $ctx['questao1']->id, $ctx['certaQ1']->id);

        $corrigida = $service->enviar($ctx['inscricao'], $ctx['item'], $tentativa->id);

        $this->assertSame(50.0, $corrigida->nota);
    }

    public function test_resposta_e_substituida_ao_trocar_de_alternativa_antes_do_envio(): void
    {
        $ctx = $this->montarProva();
        $service = app(ProvaService::class);
        $tentativa = $service->iniciar($ctx['inscricao'], $ctx['item']);

        $service->responder($ctx['inscricao'], $ctx['item'], $tentativa->id, $ctx['questao1']->id, $ctx['certaQ1']->id);
        // Autosave: troca de resposta antes de enviar (upsert pela chave unica
        // prova_tentativa_id + prova_questao_id).
        $errada = \App\Modules\Treinamento\Models\ProvaAlternativa::query()
            ->where('prova_questao_id', $ctx['questao1']->id)
            ->where('correta', false)
            ->first();
        $service->responder($ctx['inscricao'], $ctx['item'], $tentativa->id, $ctx['questao1']->id, $errada->id);

        $this->assertDatabaseHas('treinamento.prova_respostas', [
            'prova_tentativa_id' => $tentativa->id,
            'prova_questao_id' => $ctx['questao1']->id,
            'prova_alternativa_id' => $errada->id,
        ], $this->conexaoDoTreinamento());

        $this->assertDatabaseCount('treinamento.prova_respostas', 1, $this->conexaoDoTreinamento());
    }

    private function conexaoDoTreinamento(): string
    {
        return \App\Modules\Treinamento\Support\ConexaoTreinamento::nome();
    }
}
