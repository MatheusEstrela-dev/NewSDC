<?php

declare(strict_types=1);

namespace Tests\Feature\Suporte;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Modules\Suporte\Models\SupportTicket;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Anexo do chamado de suporte (#63, P0 do checklist_correcoes): era validado
 * e descartado; agora vai para a mensagem inicial e e baixavel pelo autor.
 */
class SupportTicketAnexoTest extends TestCase
{
    use DatabaseTransactions;

    private User $autor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Storage::fake('local');

        $this->autor = User::factory()->create();
    }

    private function abrirChamado(array $extra = []): SupportTicket
    {
        $this->actingAs($this->autor)
            ->postJson(route('suporte.store'), $extra + [
                'subject'     => 'Erro ao salvar RAT',
                'category'    => 'error',
                'priority'    => 'high',
                'description' => 'Ao salvar aparece erro.',
            ])
            ->assertOk();

        return SupportTicket::where('user_id', $this->autor->id)->latest('id')->firstOrFail();
    }

    public function test_anexo_e_gravado_e_associado_ao_chamado(): void
    {
        $ticket = $this->abrirChamado(['attachment' => UploadedFile::fake()->create('print.pdf', 120, 'application/pdf')]);

        $mensagem = $ticket->messages()->whereNotNull('attachment_path')->firstOrFail();
        Storage::disk('local')->assertExists($mensagem->attachment_path);
    }

    public function test_chamado_sem_anexo_nao_cria_mensagem(): void
    {
        $this->assertSame(0, $this->abrirChamado()->messages()->count());
    }

    public function test_autor_baixa_o_anexo_e_outro_usuario_nao(): void
    {
        $ticket = $this->abrirChamado(['attachment' => UploadedFile::fake()->create('print.pdf', 10, 'application/pdf')]);
        $mensagem = $ticket->messages()->firstOrFail();
        $url = route('suporte.anexo', ['ticket' => $ticket->id, 'mensagem' => $mensagem->id]);

        $this->actingAs($this->autor)->get($url)->assertOk();

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    public function test_listagem_expoe_o_link_do_anexo(): void
    {
        $ticket = $this->abrirChamado(['attachment' => UploadedFile::fake()->create('print.pdf', 10, 'application/pdf')]);

        $this->actingAs($this->autor)
            ->getJson(route('suporte.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $ticket->id)
            ->assertJsonPath('data.0.anexos.0.nome', basename($ticket->messages()->value('attachment_path')));
    }

    public function test_categoria_fora_do_enum_e_recusada_sem_erro_500(): void
    {
        $this->actingAs($this->autor)
            ->postJson(route('suporte.store'), [
                'subject'     => 'x',
                'category'    => 'qualquer',
                'priority'    => 'urgente',
                'description' => 'y',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category', 'priority']);
    }
}
