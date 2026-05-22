<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Models\PaeFormAnexo;
use App\Modules\Pae\Models\PaeFormApontamento;
use App\Modules\Pae\Models\PaeFormConclusaoItem;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaeFormularioControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'pae.empreendimentos.view',
        'pae.empreendimentos.create',
        'pae.empreendimentos.edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function actingAsAnalista(): static
    {
        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo(self::PERMISSIONS);

        return $this->actingAs($user);
    }

    public function test_store_persiste_municipio_id(): void
    {
        $municipio = Municipio::create([
            'codigo_ibge' => '3106200',
            'nome' => 'Belo Horizonte',
            'uf' => 'MG',
        ]);

        $this->actingAsAnalista()
            ->post('/pae/formulario', [
                'barragem'     => 'Barragem Teste',
                'municipio_id' => $municipio->id,
            ]);

        $this->assertDatabaseHas('pae_forms', [
            'barragem_nome' => 'Barragem Teste',
            'municipio_id'  => $municipio->id,
        ]);
    }

    public function test_store_com_protocolo_mantem_url_canonica_do_protocolo(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::create([
            'num_protocolo' => '13.05.2026.999',
            'status'        => 'novo',
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'dt_entrada'    => now()->toDateString(),
            'arquivado'     => false,
        ]);

        $this->actingAsAnalista()
            ->post('/pae/formulario', [
                'barragem' => 'Barragem com protocolo',
                'pae_protocolo_id' => $protocolo->id,
            ])
            ->assertRedirect(route('pae.index', ['protocolo_id' => $protocolo->id]));

        $this->assertDatabaseHas('pae_forms', [
            'barragem_nome' => 'Barragem com protocolo',
            'pae_protocolo_id' => $protocolo->id,
        ]);
    }

    public function test_store_com_mesmo_protocolo_atualiza_formulario_existente(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::create([
            'num_protocolo' => '13.05.2026.998',
            'status'        => 'novo',
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'dt_entrada'    => now()->toDateString(),
            'arquivado'     => false,
        ]);

        $existing = PaeForm::factory()->create([
            'pae_protocolo_id' => $protocolo->id,
            'barragem_nome' => 'Valor antigo',
        ]);

        $this->actingAsAnalista()
            ->post('/pae/formulario', [
                'barragem' => 'Valor atualizado',
                'pae_protocolo_id' => $protocolo->id,
            ])
            ->assertRedirect(route('pae.index', ['protocolo_id' => $protocolo->id]));

        $this->assertSame(1, PaeForm::where('pae_protocolo_id', $protocolo->id)->count());
        $this->assertDatabaseHas('pae_forms', [
            'id' => $existing->id,
            'barragem_nome' => 'Valor atualizado',
            'pae_protocolo_id' => $protocolo->id,
        ]);
    }

    public function test_show_com_formulario_de_protocolo_redireciona_para_protocolo(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::create([
            'num_protocolo' => '13.05.2026.997',
            'status'        => 'novo',
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'dt_entrada'    => now()->toDateString(),
            'arquivado'     => false,
        ]);
        $form = PaeForm::factory()->create(['pae_protocolo_id' => $protocolo->id]);

        $this->actingAsAnalista()
            ->get("/pae/protocolo?formulario_id={$form->id}")
            ->assertRedirect(route('pae.index', ['protocolo_id' => $protocolo->id]));
    }

    public function test_update_apontamentos_usa_tabela_dedicada(): void
    {
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->put("/pae/formulario/{$form->id}/aptecnico", [
                'apontamentos' => [
                    ['text' => 'Item principal', 'children' => [
                        ['text' => 'Sub-item 1.1'],
                    ]],
                ],
            ]);

        $this->assertDatabaseHas('pae_form_apontamentos', [
            'pae_form_id' => $form->id,
            'conteudo'    => 'Item principal',
            'parent_id'   => null,
        ]);

        $this->assertDatabaseHas('pae_form_apontamentos', [
            'pae_form_id' => $form->id,
            'conteudo'    => 'Sub-item 1.1',
        ]);

        $this->assertDatabaseMissing('pae_form_conclusao', ['pae_form_id' => $form->id]);
    }

    public function test_update_conclusao_usa_tabela_dedicada(): void
    {
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->put("/pae/formulario/{$form->id}/conclusao", [
                'conclusao' => [
                    ['text' => 'Conclusao 1', 'children' => []],
                ],
            ]);

        $this->assertDatabaseHas('pae_form_conclusao', [
            'pae_form_id' => $form->id,
            'conteudo'    => 'Conclusao 1',
        ]);

        $this->assertDatabaseMissing('pae_form_apontamentos', ['pae_form_id' => $form->id]);
    }

    public function test_upload_pdf_valido_grava_anexo_e_arquivo(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->post("/pae/formulario/{$form->id}/anexos", [
                'arquivo' => UploadedFile::fake()->create('relatorio.pdf', 100, 'application/pdf'),
                'descricao' => 'Relatorio principal',
            ])
            ->assertRedirect();

        $anexo = PaeFormAnexo::query()->where('pae_form_id', $form->id)->first();

        $this->assertNotNull($anexo);
        $this->assertSame('relatorio.pdf', $anexo->nome_original);
        $this->assertSame('pae', $anexo->disk);
        $this->assertStringStartsWith('formularios/' . $form->id . '/documentos/anexos/', $anexo->path);
        Storage::disk('pae')->assertExists($anexo->path);
    }

    public function test_upload_por_protocolo_cria_formulario_e_salva_em_documentos_do_protocolo(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $numeroProtocolo = '22.05.2026.' . random_int(100000, 999999);
        $protocolo = PaeProtocolo::create([
            'num_protocolo' => $numeroProtocolo,
            'status'        => 'novo',
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'dt_entrada'    => now()->toDateString(),
            'arquivado'     => false,
        ]);

        $this->actingAsAnalista()
            ->post("/pae/protocolo/{$protocolo->id}/anexos", [
                'arquivo' => UploadedFile::fake()->image('vistoria.jpeg'),
                'descricao' => 'Imagem enviada pela aba Anexos',
            ])
            ->assertRedirect();

        $form = PaeForm::query()->where('pae_protocolo_id', $protocolo->id)->first();
        $this->assertNotNull($form);

        $anexo = PaeFormAnexo::query()->where('pae_form_id', $form->id)->first();
        $this->assertNotNull($anexo);
        $this->assertSame('vistoria.jpeg', $anexo->nome_original);
        $this->assertSame('pae', $anexo->disk);
        $this->assertStringStartsWith("protocolos/{$numeroProtocolo}/documentos/anexos/", $anexo->path);
        $this->assertDatabaseHas('pae_form_anexos', [
            'id' => $anexo->id,
            'pae_form_id' => $form->id,
            'descricao' => 'Imagem enviada pela aba Anexos',
        ]);
        Storage::disk('pae')->assertExists($anexo->path);
    }

    public function test_upload_pdf_ate_50mb_e_aceito(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->post("/pae/formulario/{$form->id}/anexos", [
                'arquivo' => UploadedFile::fake()->create('relatorio-40mb.pdf', 40960, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pae_form_anexos', [
            'pae_form_id' => $form->id,
            'nome_original' => 'relatorio-40mb.pdf',
        ]);
    }

    public function test_upload_imagens_validas_sao_aceitas(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->post("/pae/formulario/{$form->id}/anexos", [
                'arquivo' => UploadedFile::fake()->image('foto.jpg'),
            ])
            ->assertRedirect();

        $this->actingAsAnalista()
            ->post("/pae/formulario/{$form->id}/anexos", [
                'arquivo' => UploadedFile::fake()->image('mapa.png'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pae_form_anexos', [
            'pae_form_id' => $form->id,
            'nome_original' => 'foto.jpg',
        ]);

        $this->assertDatabaseHas('pae_form_anexos', [
            'pae_form_id' => $form->id,
            'nome_original' => 'mapa.png',
        ]);
    }

    public function test_upload_imagem_corrompida_e_rejeitado(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $jpegCorrompido = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxISEhUTEhMVFRUVFRUVFRUVFRUVFRUVFRUWFhUVFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGhAQGi0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAXAAEBAQEAAAAAAAAAAAAAAAAABQYH/8QAHBAAAgICAwAAAAAAAAAAAAAAAQIAAwQFERIh/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAWEQEBAQAAAAAAAAAAAAAAAAABABH/2gAMAwEAAhEDEQA/ANqJNRvVjzOEqK0tKp6g7P/Z',
            true
        );

        $this->actingAsAnalista()
            ->post("/pae/formulario/{$form->id}/anexos", [
                'arquivo' => UploadedFile::fake()->createWithContent('corrompida.jpeg', $jpegCorrompido),
            ])
            ->assertSessionHasErrors('arquivo');

        $this->assertDatabaseMissing('pae_form_anexos', [
            'pae_form_id' => $form->id,
            'nome_original' => 'corrompida.jpeg',
        ]);
    }

    public function test_upload_extensao_invalida_e_rejeitado(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->post("/pae/formulario/{$form->id}/anexos", [
                'arquivo' => UploadedFile::fake()->create('script.exe', 1, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('arquivo');

        $this->assertDatabaseMissing('pae_form_anexos', [
            'pae_form_id' => $form->id,
            'nome_original' => 'script.exe',
        ]);
    }

    public function test_upload_maior_que_50mb_e_rejeitado(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->post("/pae/formulario/{$form->id}/anexos", [
                'arquivo' => UploadedFile::fake()->create('grande.pdf', 51201, 'application/pdf'),
            ])
            ->assertSessionHasErrors('arquivo');
    }

    public function test_delete_anexo_remove_registro_e_arquivo(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);
        Storage::disk('pae')->put('formularios/' . $form->id . '/arquivo.pdf', 'conteudo');
        $anexo = PaeFormAnexo::create([
            'pae_form_id' => $form->id,
            'nome_original' => 'arquivo.pdf',
            'nome_arquivo' => 'arquivo.pdf',
            'mime_type' => 'application/pdf',
            'tamanho_bytes' => 8,
            'path' => 'formularios/' . $form->id . '/arquivo.pdf',
            'disk' => 'pae',
        ]);

        $this->actingAsAnalista()
            ->delete("/pae/formulario/{$form->id}/anexos/{$anexo->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('pae_form_anexos', ['id' => $anexo->id]);
        Storage::disk('pae')->assertMissing($anexo->path);
    }

    public function test_download_anexo_retorna_arquivo(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);
        Storage::disk('pae')->put('formularios/' . $form->id . '/arquivo.pdf', 'conteudo');
        $anexo = PaeFormAnexo::create([
            'pae_form_id' => $form->id,
            'nome_original' => 'arquivo.pdf',
            'nome_arquivo' => 'arquivo.pdf',
            'mime_type' => 'application/pdf',
            'tamanho_bytes' => 8,
            'path' => 'formularios/' . $form->id . '/arquivo.pdf',
            'disk' => 'pae',
        ]);

        $this->actingAsAnalista()
            ->get("/pae/formulario/{$form->id}/anexos/{$anexo->id}/download")
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_visualizar_anexo_retorna_arquivo_inline(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);
        Storage::disk('pae')->put('formularios/' . $form->id . '/imagem.jpeg', 'conteudo');
        $anexo = PaeFormAnexo::create([
            'pae_form_id' => $form->id,
            'nome_original' => 'imagem.jpeg',
            'nome_arquivo' => 'imagem.jpeg',
            'mime_type' => 'image/jpeg',
            'tamanho_bytes' => 8,
            'path' => 'formularios/' . $form->id . '/imagem.jpeg',
            'disk' => 'pae',
        ]);

        $this->actingAsAnalista()
            ->get("/pae/formulario/{$form->id}/anexos/{$anexo->id}/visualizar")
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=imagem.jpeg');
    }

    public function test_download_e_delete_de_anexo_de_outro_formulario_retorna_404(): void
    {
        Storage::fake('pae');
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);
        $outroForm = PaeForm::factory()->create(['status' => 'RASCUNHO']);
        Storage::disk('pae')->put('formularios/' . $outroForm->id . '/arquivo.pdf', 'conteudo');
        $anexo = PaeFormAnexo::create([
            'pae_form_id' => $outroForm->id,
            'nome_original' => 'arquivo.pdf',
            'nome_arquivo' => 'arquivo.pdf',
            'mime_type' => 'application/pdf',
            'tamanho_bytes' => 8,
            'path' => 'formularios/' . $outroForm->id . '/arquivo.pdf',
            'disk' => 'pae',
        ]);

        $this->actingAsAnalista()
            ->get("/pae/formulario/{$form->id}/anexos/{$anexo->id}/download")
            ->assertNotFound();

        $this->actingAsAnalista()
            ->delete("/pae/formulario/{$form->id}/anexos/{$anexo->id}")
            ->assertNotFound();
    }

    public function test_show_com_protocolo_id_retorna_prop_protocolo(): void
    {
        $perm = Permission::firstOrCreate(
            ['name' => 'pae.empreendimentos.view', 'guard_name' => 'web']
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($perm);

        $protocolo = \App\Modules\Pae\Models\PaeProtocolo::create([
            'num_protocolo' => '13.04.2026.001',
            'status'        => 'novo',
            'user_id'       => $user->id,
            'created_by'    => $user->id,
            'dt_entrada'    => now()->toDateString(),
            'arquivado'     => false,
        ]);

        $response = $this->actingAs($user)
            ->get("/pae/protocolo?protocolo_id={$protocolo->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Pae')
            ->has('protocolo')
            ->where('protocolo.id', $protocolo->id)
            ->where('protocolo.num_protocolo', '13.04.2026.001')
            ->where('protocolo.status', 'novo')
        );
    }

    public function test_show_sem_protocolo_id_retorna_protocolo_null(): void
    {
        $perm = Permission::firstOrCreate(
            ['name' => 'pae.empreendimentos.view', 'guard_name' => 'web']
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($perm);

        $response = $this->actingAs($user)->get('/pae/protocolo');

        $response->assertInertia(fn ($page) => $page
            ->component('Pae')
            ->where('protocolo', null)
        );
    }
}
