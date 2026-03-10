<?php

declare(strict_types=1);

namespace Tests\Unit\Rat\Resources;

use App\Models\User;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Http\Resources\RatResource;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Models\Rat;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Testa os API Resources do módulo RAT.
 *
 * Verifica que RatResource e RatListResource produzem
 * os campos corretos no toArray().
 */
class RatResourceTest extends TestCase
{
    private function makeRat(array $overrides = []): Rat
    {
        $rat = new Rat();
        $rat->id           = 'uuid-abc-123';
        $rat->protocolo    = 'RAT-2026-00001';
        $rat->status       = Status::RASCUNHO->value;
        $rat->tem_vistoria = false;
        $rat->dados_gerais = ['data_fato' => '2026-03-01'];
        $rat->local        = ['municipio' => 'Curitiba', 'uf' => 'PR'];
        $rat->endereco     = ['logradouro' => 'Rua Teste'];
        $rat->comunicacao  = ['tipo_solicitacao' => 'telefone'];
        $rat->recursos     = [];
        $rat->envolvidos   = [];
        $rat->vistoria     = [];
        $rat->historico    = [];
        $rat->anexos       = [];
        $rat->created_at   = now();
        $rat->updated_at   = now();

        foreach ($overrides as $key => $value) {
            $rat->{$key} = $value;
        }

        return $rat;
    }

    // -------------------------------------------------------------------------
    // RatResource (detalhe completo)
    // -------------------------------------------------------------------------

    public function test_rat_resource_contem_campos_obrigatorios(): void
    {
        $rat      = $this->makeRat();
        $resource = (new RatResource($rat))->toArray(new Request());

        $camposEsperados = [
            'id', 'protocolo', 'status', 'status_label', 'tem_vistoria',
            'dados_gerais', 'local', 'endereco', 'comunicacao',
            'recursos', 'envolvidos', 'vistoria', 'historico', 'anexos',
            'criado_por', 'atualizado_por', 'created_at', 'updated_at',
        ];

        foreach ($camposEsperados as $campo) {
            $this->assertArrayHasKey($campo, $resource, "Campo '{$campo}' ausente no RatResource");
        }
    }

    public function test_rat_resource_status_label_e_calculado(): void
    {
        $rat = $this->makeRat(['status' => Status::EM_ANDAMENTO->value]);

        $resource = (new RatResource($rat))->toArray(new Request());

        $this->assertSame('Em Andamento', $resource['status_label']);
    }

    public function test_rat_resource_status_label_fallback_para_status_bruto(): void
    {
        $rat = $this->makeRat(['status' => 'status_desconhecido']);

        $resource = (new RatResource($rat))->toArray(new Request());

        $this->assertSame('status_desconhecido', $resource['status_label']);
    }

    public function test_rat_resource_arrays_json_nunca_sao_null(): void
    {
        $rat = $this->makeRat([
            'recursos'   => null,
            'envolvidos' => null,
            'vistoria'   => null,
            'historico'  => null,
            'anexos'     => null,
        ]);

        $resource = (new RatResource($rat))->toArray(new Request());

        $this->assertSame([], $resource['recursos']);
        $this->assertSame([], $resource['envolvidos']);
        $this->assertSame([], $resource['vistoria']);
        $this->assertSame([], $resource['historico']);
        $this->assertSame([], $resource['anexos']);
    }

    public function test_rat_resource_criado_por_fallback_sistema(): void
    {
        $rat          = $this->makeRat();
        $rat->creator = null; // sem relação carregada

        $resource = (new RatResource($rat))->toArray(new Request());

        $this->assertSame('Sistema', $resource['criado_por']);
    }

    // -------------------------------------------------------------------------
    // RatListResource (listagem leve)
    // -------------------------------------------------------------------------

    public function test_rat_list_resource_contem_campos_da_listagem(): void
    {
        $rat      = $this->makeRat();
        $resource = (new RatListResource($rat))->toArray(new Request());

        $camposEsperados = ['id', 'protocolo', 'status', 'status_label', 'local', 'data_fato', 'criado_por', 'created_at', 'updated_at'];

        foreach ($camposEsperados as $campo) {
            $this->assertArrayHasKey($campo, $resource, "Campo '{$campo}' ausente no RatListResource");
        }
    }

    public function test_rat_list_resource_nao_expoe_dados_sensiveis(): void
    {
        $rat      = $this->makeRat();
        $resource = (new RatListResource($rat))->toArray(new Request());

        // Campos pesados não devem estar no resource de listagem
        $this->assertArrayNotHasKey('envolvidos', $resource);
        $this->assertArrayNotHasKey('recursos', $resource);
        $this->assertArrayNotHasKey('vistoria', $resource);
        $this->assertArrayNotHasKey('historico', $resource);
        $this->assertArrayNotHasKey('anexos', $resource);
    }

    public function test_rat_list_resource_data_fato_vem_de_dados_gerais(): void
    {
        $rat = $this->makeRat(['dados_gerais' => ['data_fato' => '2026-01-15']]);

        $resource = (new RatListResource($rat))->toArray(new Request());

        $this->assertSame('2026-01-15', $resource['data_fato']);
    }

    public function test_rat_list_resource_data_fato_null_quando_ausente(): void
    {
        $rat = $this->makeRat(['dados_gerais' => []]);

        $resource = (new RatListResource($rat))->toArray(new Request());

        $this->assertNull($resource['data_fato']);
    }
}
