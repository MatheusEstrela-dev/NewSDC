<?php

declare(strict_types=1);

namespace Tests\Feature\Rat;

use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Infrastructure\Persistence\EloquentRatRepository;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Services\RatFilterService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Testes de integração para EloquentRatRepository.
 *
 * Verifica que cada método do repositório interage corretamente
 * com o banco de dados real (revertido via DatabaseTransactions).
 */
class EloquentRatRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private function makeRepository(): EloquentRatRepository
    {
        return app(EloquentRatRepository::class);
    }

    private function baseData(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // create()
    // -----------------------------------------------------------------------

    public function test_create_persiste_rat_no_banco(): void
    {
        $repo = $this->makeRepository();

        $rat = $repo->create($this->baseData());

        $this->assertNotNull(Rat::find($rat->id));
    }

    public function test_create_retorna_instancia_de_rat(): void
    {
        $repo = $this->makeRepository();
        $rat  = $repo->create($this->baseData());

        $this->assertInstanceOf(Rat::class, $rat);
    }

    public function test_create_gera_uuid_automaticamente(): void
    {
        $repo = $this->makeRepository();
        $rat  = $repo->create($this->baseData());

        $this->assertTrue(Str::isUuid($rat->id));
    }

    // -----------------------------------------------------------------------
    // findById()
    // -----------------------------------------------------------------------

    public function test_find_by_id_retorna_rat_existente(): void
    {
        $repo     = $this->makeRepository();
        $criado   = $repo->create($this->baseData());
        $buscado  = $repo->findById($criado->id);

        $this->assertNotNull($buscado);
        $this->assertSame($criado->id, $buscado->id);
    }

    public function test_find_by_id_retorna_null_para_id_inexistente(): void
    {
        $repo    = $this->makeRepository();
        $buscado = $repo->findById('uuid-que-nao-existe-99999');

        $this->assertNull($buscado);
    }

    // -----------------------------------------------------------------------
    // update()
    // -----------------------------------------------------------------------

    public function test_update_persiste_campos_alterados(): void
    {
        $repo = $this->makeRepository();
        $rat  = $repo->create($this->baseData());

        // O repositório espera 'dadosGerais' (camelCase) igual ao payload do frontend
        $repo->update($rat->id, [
            'dadosGerais' => ['data_fato' => '2026-03-10'],
            'status'      => Status::EM_ANDAMENTO->value,
        ]);

        $fresco = $rat->fresh();
        $this->assertSame('2026-03-10', $fresco->dados_gerais['data_fato']);
        $this->assertSame(Status::EM_ANDAMENTO->value, $fresco->status);
    }

    public function test_update_retorna_rat_atualizado(): void
    {
        $repo   = $this->makeRepository();
        $rat    = $repo->create($this->baseData());
        $result = $repo->update($rat->id, ['status' => Status::EM_ANDAMENTO->value]);

        $this->assertInstanceOf(Rat::class, $result);
        $this->assertSame(Status::EM_ANDAMENTO->value, $result->status);
    }

    // -----------------------------------------------------------------------
    // updateStatus()
    // -----------------------------------------------------------------------

    public function test_update_status_muda_apenas_o_status(): void
    {
        $repo     = $this->makeRepository();
        $rat      = $repo->create($this->baseData(['protocolo' => 'RAT-2026-99999']));

        $repo->updateStatus($rat->id, Status::FINALIZADO->value);

        $fresco = $rat->fresh();
        $this->assertSame(Status::FINALIZADO->value, $fresco->status);
        $this->assertSame('RAT-2026-99999', $fresco->protocolo); // não alterado
    }

    // -----------------------------------------------------------------------
    // delete()
    // -----------------------------------------------------------------------

    public function test_delete_remove_rat_do_banco(): void
    {
        $repo = $this->makeRepository();
        $rat  = $repo->create($this->baseData());
        $id   = $rat->id;

        $repo->delete($id);

        $this->assertNull(Rat::find($id));
    }

    // -----------------------------------------------------------------------
    // paginate()
    // -----------------------------------------------------------------------

    public function test_paginate_retorna_paginador(): void
    {
        $repo    = $this->makeRepository();
        $filters = new RatFilterDTO();

        $resultado = $repo->paginate($filters);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $resultado);
    }

    public function test_paginate_filtro_status_retorna_apenas_aquele_status(): void
    {
        $repo = $this->makeRepository();
        $repo->create($this->baseData(['status' => Status::RASCUNHO->value]));
        $repo->create($this->baseData(['status' => Status::FINALIZADO->value]));

        $filters  = RatFilterDTO::fromArray(['status' => Status::FINALIZADO->value]);

        $resultado = $repo->paginate($filters);

        foreach ($resultado->items() as $item) {
            $this->assertSame(Status::FINALIZADO->value, $item->status);
        }
    }

    public function test_paginate_filtro_protocolo_encontra_por_trecho(): void
    {
        $repo = $this->makeRepository();
        $repo->create($this->baseData(['protocolo' => 'RAT-2026-88888']));
        $repo->create($this->baseData(['protocolo' => 'RAT-2026-77777']));

        $filters = RatFilterDTO::fromArray(['protocolo' => '88888']);

        $resultado = $repo->paginate($filters);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
        foreach ($resultado->items() as $item) {
            $this->assertStringContainsString('88888', $item->protocolo);
        }
    }

    // -----------------------------------------------------------------------
    // getLatestSequence()
    // -----------------------------------------------------------------------

    public function test_get_latest_sequence_retorna_zero_quando_sem_registros(): void
    {
        // Garantir que não existe nenhum RAT com esse protocolo específico
        $repo = $this->makeRepository();

        // Usa ano futuro improvável para isolar o teste
        $seq = $repo->getLatestSequence(9999);

        $this->assertSame(0, $seq);
    }

    public function test_get_latest_sequence_retorna_maior_sequencia(): void
    {
        $repo = $this->makeRepository();

        Rat::create($this->baseData(['protocolo' => 'RAT-8888-00010']));
        Rat::create($this->baseData(['protocolo' => 'RAT-8888-00050']));
        Rat::create($this->baseData(['protocolo' => 'RAT-8888-00030']));

        $seq = $repo->getLatestSequence(8888);

        $this->assertSame(50, $seq);
    }

    // -----------------------------------------------------------------------
    // getMunicipalities()
    // -----------------------------------------------------------------------

    public function test_get_municipalities_retorna_lista_unica(): void
    {
        $repo = $this->makeRepository();

        Rat::create($this->baseData(['local' => ['municipio' => 'Curitiba', 'uf' => 'PR']]));
        Rat::create($this->baseData(['local' => ['municipio' => 'Curitiba', 'uf' => 'PR']]));
        Rat::create($this->baseData(['local' => ['municipio' => 'Londrina', 'uf' => 'PR']]));

        $municipios = $repo->getMunicipalities();

        $this->assertContains('Curitiba', $municipios);
        $this->assertContains('Londrina', $municipios);
        $this->assertSame(count($municipios), count(array_unique($municipios)));
    }
}
