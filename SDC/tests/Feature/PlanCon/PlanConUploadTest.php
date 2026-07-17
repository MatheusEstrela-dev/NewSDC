<?php

declare(strict_types=1);

namespace Tests\Feature\PlanCon;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\PlanCon\Models\PlanoContingencia;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PlanConUploadTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'plancon.upload',
        'plancon.download',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function actingAsGestor(): static
    {
        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo(self::PERMISSIONS);

        return $this->actingAs($user);
    }

    // Codigos IBGE ficticios: o banco de dev/teste ja tem os 853 municipios
    // reais seedados e codigo_ibge e unique.
    private function criarMunicipio(string $codigoIbge = '9999991', string $nome = 'Municipio Teste A'): Municipio
    {
        return Municipio::firstOrCreate(
            ['codigo_ibge' => $codigoIbge],
            ['nome' => $nome, 'uf' => 'MG']
        );
    }

    public function test_upload_em_massa_resolve_municipio_pelo_prefixo_ibge(): void
    {
        Storage::fake('plancon');
        $municipioA = $this->criarMunicipio('9999991', 'Municipio Teste A');
        $municipioB = $this->criarMunicipio('9999992', 'Municipio Teste B');

        $this->actingAsGestor()
            ->post('/plancon/planos', [
                'files' => [
                    UploadedFile::fake()->create('9999991_plano.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('9999992_plano.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('plancon.index'));

        foreach ([$municipioA, $municipioB] as $municipio) {
            $plano = PlanoContingencia::where('municipio_id', $municipio->id)->first();
            $this->assertNotNull($plano);
            Storage::disk('plancon')->assertExists($plano->arquivo_url);
        }
    }

    public function test_upload_com_municipio_id_sem_prefixo_ibge(): void
    {
        Storage::fake('plancon');
        $municipio = $this->criarMunicipio();

        $this->actingAsGestor()
            ->post('/plancon/planos', [
                'files' => [UploadedFile::fake()->create('plano-municipal.pdf', 100, 'application/pdf')],
                'municipio_id' => $municipio->id,
            ])
            ->assertRedirect(route('plancon.index'));

        $this->assertDatabaseHas('planos_contingencia', [
            'municipio_id' => $municipio->id,
            'nome' => 'plano-municipal.pdf',
        ]);
    }

    public function test_reupload_atualiza_plano_existente_e_remove_arquivo_antigo(): void
    {
        Storage::fake('plancon');
        $municipio = $this->criarMunicipio();

        $this->actingAsGestor()->post('/plancon/planos', [
            'files' => [UploadedFile::fake()->create('9999991_v1.pdf', 100, 'application/pdf')],
        ]);

        $pathAntigo = PlanoContingencia::where('municipio_id', $municipio->id)->value('arquivo_url');

        $this->actingAsGestor()->post('/plancon/planos', [
            'files' => [UploadedFile::fake()->create('9999991_v2.pdf', 100, 'application/pdf')],
        ]);

        $this->assertSame(1, PlanoContingencia::where('municipio_id', $municipio->id)->count());
        $plano = PlanoContingencia::where('municipio_id', $municipio->id)->first();
        $this->assertSame('9999991_v2.pdf', $plano->nome);
        Storage::disk('plancon')->assertExists($plano->arquivo_url);
        Storage::disk('plancon')->assertMissing($pathAntigo);
    }

    public function test_arquivo_nao_pdf_e_rejeitado(): void
    {
        Storage::fake('plancon');
        $municipio = $this->criarMunicipio();

        $this->actingAsGestor()
            ->post('/plancon/planos', [
                'files' => [UploadedFile::fake()->create('9999991_plano.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')],
            ])
            ->assertSessionHasErrors('files.0');

        $this->assertDatabaseMissing('planos_contingencia', ['municipio_id' => $municipio->id]);
    }

    public function test_upload_sem_permissao_retorna_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/plancon/planos', [
                'files' => [UploadedFile::fake()->create('9999991_plano.pdf', 100, 'application/pdf')],
            ])
            ->assertForbidden();
    }

    public function test_download_retorna_arquivo(): void
    {
        Storage::fake('plancon');
        $municipio = $this->criarMunicipio();
        Storage::disk('plancon')->put($municipio->id . '/plano.pdf', 'conteudo');
        $plano = PlanoContingencia::create([
            'municipio_id' => $municipio->id,
            'nome' => 'plano.pdf',
            'arquivo_url' => $municipio->id . '/plano.pdf',
            'situacao' => 'regular',
        ]);

        $this->actingAsGestor()
            ->get("/plancon/planos/{$plano->id}/download")
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_download_de_plano_sem_arquivo_retorna_404(): void
    {
        Storage::fake('plancon');
        $municipio = $this->criarMunicipio();
        $plano = PlanoContingencia::create([
            'municipio_id' => $municipio->id,
            'nome' => 'plano.pdf',
            'arquivo_url' => null,
            'situacao' => 'regular',
        ]);

        $this->actingAsGestor()
            ->get("/plancon/planos/{$plano->id}/download")
            ->assertNotFound();
    }
}
