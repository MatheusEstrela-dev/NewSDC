<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\Prestador;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * #62, "Validar os metodos de atuacao, create, edit, delete em cada Aba":
 * o ciclo completo pelas rotas web de Prestadores, Frota, Atas e Lotes --
 * tela de cadastro, gravacao, ficha, tela de edicao, alteracao e exclusao.
 */
class TdapAbasCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $user = User::factory()->create();
        $user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($user->fresh());
    }

    /** CNPJ valido (digitos verificadores) e unico por teste. */
    private function cnpj(): string
    {
        $base = '9' . str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
        foreach ([[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $pesos) {
            $soma = 0;
            foreach ($pesos as $i => $p) {
                $soma += (int) $base[$i] * $p;
            }
            $resto = $soma % 11;
            $base .= $resto < 2 ? '0' : (string) (11 - $resto);
        }

        return $base;
    }

    private function cicloDeTelas(string $modulo, int $id): void
    {
        $this->get(route("tdap.{$modulo}.show", $id))->assertOk();
        $this->get(route("tdap.{$modulo}.edit", $id))->assertOk();
    }

    public function test_crud_completo_das_abas_prestadores_frota_atas_e_lotes(): void
    {
        // Prestador
        $this->get(route('tdap.prestadores.index'))->assertOk();
        $this->get(route('tdap.prestadores.create'))->assertOk();
        $cnpj = $this->cnpj();
        $this->post(route('tdap.prestadores.store'), ['cnpj' => $cnpj, 'nome' => 'Prestador CRUD', 'email' => 'crud@teste.local', 'ativo' => true])
            ->assertSessionHasNoErrors()->assertRedirect();
        $prestador = Prestador::where('cnpj', $cnpj)->firstOrFail();
        $this->cicloDeTelas('prestadores', $prestador->id);
        $this->put(route('tdap.prestadores.update', $prestador->id), ['cnpj' => $cnpj, 'nome' => 'Prestador Editado', 'email' => 'crud@teste.local', 'ativo' => false])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Prestador Editado', $prestador->fresh()->nome);
        $this->assertFalse((bool) $prestador->fresh()->ativo);

        // Caminhao (aba Frota)
        $this->get(route('tdap.frota.index'))->assertOk();
        $this->get(route('tdap.frota.create'))->assertOk();
        $this->post(route('tdap.frota.store'), ['prestador_id' => $prestador->id, 'placa' => 'CRD1A23', 'capacidade_m3' => 16])
            ->assertSessionHasNoErrors()->assertRedirect();
        $caminhao = Caminhao::where('placa', 'CRD1A23')->firstOrFail();
        $this->cicloDeTelas('frota', $caminhao->id);
        $this->put(route('tdap.frota.update', $caminhao->id), ['prestador_id' => $prestador->id, 'placa' => 'CRD1A23', 'capacidade_m3' => 20.5, 'marca' => 'Volvo'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(20.5, (float) $caminhao->fresh()->capacidade_m3);

        // Ata
        $this->get(route('tdap.atas.index'))->assertOk();
        $this->get(route('tdap.atas.create'))->assertOk();
        $numeroAta = 'CRUD-' . random_int(1000, 9999);
        $this->post(route('tdap.atas.store'), ['numero' => $numeroAta, 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $ata = Ata::where('numero', $numeroAta)->firstOrFail();
        $this->cicloDeTelas('atas', $ata->id);
        $this->put(route('tdap.atas.update', $ata->id), ['numero' => $numeroAta, 'dt_inicio' => '2026-01-01', 'dt_final' => '2027-06-30'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2027-06-30', $ata->fresh()->dt_final->toDateString());

        // Lote
        $municipio = Municipio::factory()->create();
        $this->get(route('tdap.lotes.index'))->assertOk();
        $this->get(route('tdap.lotes.create'))->assertOk();
        $loteBase = ['ata_id' => $ata->id, 'prestador_id' => $prestador->id, 'municipio_ids' => [$municipio->id], 'numero' => 'L-CRUD', 'qtd_agua_m3' => 1000, 'valor_m3' => 12.5];
        $this->post(route('tdap.lotes.store'), $loteBase)->assertSessionHasNoErrors()->assertRedirect();
        $lote = Lote::where('ata_id', $ata->id)->where('numero', 'L-CRUD')->firstOrFail();
        $this->cicloDeTelas('lotes', $lote->id);
        $this->put(route('tdap.lotes.update', $lote->id), ['valor_m3' => 15] + $loteBase)
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(15.0, (float) $lote->fresh()->valor_m3);

        // Exclusao, de dependente para dependencia.
        $this->delete(route('tdap.lotes.destroy', $lote->id))->assertRedirect();
        $this->assertSoftDeleted('tdap_lotes', ['id' => $lote->id]);
        $this->delete(route('tdap.frota.destroy', $caminhao->id))->assertRedirect();
        $this->assertSoftDeleted('tdap_caminhoes', ['id' => $caminhao->id]);
        $this->delete(route('tdap.atas.destroy', $ata->id))->assertRedirect();
        $this->assertSoftDeleted('tdap_atas', ['id' => $ata->id]);
        $this->delete(route('tdap.prestadores.destroy', $prestador->id))->assertRedirect();
        $this->assertSoftDeleted('tdap_prestadores', ['id' => $prestador->id]);
    }
}
