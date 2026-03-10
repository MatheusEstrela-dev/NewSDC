<?php

declare(strict_types=1);

namespace Tests\Feature\Rat;

use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Testes de Feature para RatAttachmentController (upload + remoção).
 */
class RatAttachmentControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function actingAsAdmin(): static
    {
        foreach (['rat.protocolos.view', 'rat.protocolos.edit'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        app()['cache']->forget('spatie.permission.cache');

        $user = User::factory()->create();
        $user->givePermissionTo(['rat.protocolos.view', 'rat.protocolos.edit']);

        return $this->actingAs($user);
    }

    private function makeRat(): Rat
    {
        return Rat::create([
            'protocolo'    => 'RAT-' . date('Y') . '-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'status'       => Status::RASCUNHO->value,
            'dados_gerais' => [],
            'local'        => [],
            'endereco'     => [],
            'comunicacao'  => [],
            'recursos'     => [],
            'envolvidos'   => [],
            'vistoria'     => [],
            'historico'    => [],
            'anexos'       => [],
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /rat/{id}/attachments
    // -----------------------------------------------------------------------

    public function test_upload_pdf_retorna_201_com_metadados(): void
    {
        Storage::fake('public');
        $rat  = $this->makeRat();
        $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        $response = $this->actingAsAdmin()
            ->postJson("/rat/{$rat->id}/attachments", ['file' => $file]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'nome', 'path', 'tipo', 'tamanho', 'url', 'data_upload']);
    }

    public function test_upload_imagem_jpg_retorna_201(): void
    {
        Storage::fake('public');
        $rat  = $this->makeRat();
        $file = UploadedFile::fake()->image('foto.jpg');

        $this->actingAsAdmin()
            ->postJson("/rat/{$rat->id}/attachments", ['file' => $file])
            ->assertStatus(201);
    }

    public function test_upload_persiste_anexo_no_banco(): void
    {
        Storage::fake('public');
        $rat  = $this->makeRat();
        $file = UploadedFile::fake()->create('relatorio.pdf', 200, 'application/pdf');

        $this->actingAsAdmin()->postJson("/rat/{$rat->id}/attachments", ['file' => $file]);

        $fresco = $rat->fresh();
        $this->assertCount(1, $fresco->anexos);
    }

    public function test_upload_rejeita_arquivo_acima_do_limite(): void
    {
        Storage::fake('public');
        $rat  = $this->makeRat();
        // 10MB + 1KB = excede o limite de 10MB
        $file = UploadedFile::fake()->create('gigante.pdf', (10 * 1024) + 1, 'application/pdf');

        $this->actingAsAdmin()
            ->postJson("/rat/{$rat->id}/attachments", ['file' => $file])
            ->assertStatus(422);
    }

    public function test_upload_rejeita_tipo_mime_nao_permitido(): void
    {
        Storage::fake('public');
        $rat  = $this->makeRat();
        $file = UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload');

        $this->actingAsAdmin()
            ->postJson("/rat/{$rat->id}/attachments", ['file' => $file])
            ->assertStatus(422);
    }

    public function test_upload_rejeita_sem_arquivo(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->postJson("/rat/{$rat->id}/attachments", [])
            ->assertStatus(422);
    }

    public function test_upload_retorna_404_para_rat_inexistente(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $this->actingAsAdmin()
            ->postJson('/rat/uuid-nao-existe/attachments', ['file' => $file])
            ->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // DELETE /rat/{id}/attachments/{anexoId}
    // -----------------------------------------------------------------------

    public function test_destroy_remove_anexo_do_banco(): void
    {
        Storage::fake('public');
        $rat  = $this->makeRat();
        $file = UploadedFile::fake()->create('para_deletar.pdf', 50, 'application/pdf');

        // Primeiro faz o upload para obter o id do anexo
        $uploadResponse = $this->actingAsAdmin()
            ->postJson("/rat/{$rat->id}/attachments", ['file' => $file])
            ->json();

        $anexoId = $uploadResponse['id'];

        // Agora deleta
        $this->actingAsAdmin()
            ->deleteJson("/rat/{$rat->id}/attachments/{$anexoId}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Anexo removido com sucesso.']);

        $fresco = $rat->fresh();
        $this->assertCount(0, $fresco->anexos);
    }

    public function test_destroy_nao_falha_com_anexo_id_inexistente(): void
    {
        Storage::fake('public');
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->deleteJson("/rat/{$rat->id}/attachments/uuid-nao-existe")
            ->assertStatus(200); // idempotente — remove o que encontra, ignora o resto
    }
}
