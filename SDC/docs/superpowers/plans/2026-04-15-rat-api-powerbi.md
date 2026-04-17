# RAT API PowerBI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar `GET /api/v1/rat/protocolos` (listagem paginada + export PowerBI via `?format=powerbi`) e `POST /api/v1/rat/protocolos` (recebimento externo com rate limiting), seguindo o padrão Decretações.

**Architecture:** Controller fino `RatApiController` orquestra dois services especializados: `RatExportBiService` (GET — lista paginada e flat PowerBI via `RatRepositoryInterface`) e `RatReceiveBiService` (POST — persiste via `RatWriteService::createWithData()`). Auth dual (Sanctum + token PowerBI) reutiliza middleware `decretacoes.api.auth` existente.

**Tech Stack:** Laravel 11, PHP 8.3, `app/Modules/Rat` (módulo existente), `EloquentRatRepository`, `RatWriteService`, `RatFilterService`, `RatFilterDTO`, `RatListResource`, `RatResource`, `ApiRateLimiter`, `DecretacoesApiAuth`.

---

## Mapa de arquivos

| Ação | Arquivo |
|------|---------|
| **Criar** | `app/Modules/Rat/Http/Requests/ReceiveRatBiRequest.php` |
| **Criar** | `app/Modules/Rat/DTOs/RatReceiveBiDTO.php` |
| **Criar** | `app/Modules/Rat/Services/RatExportBiService.php` |
| **Criar** | `app/Modules/Rat/Services/RatReceiveBiService.php` |
| **Substituir** | `app/Http/Controllers/Api/V1/Rat/ProtocoloController.php` → `RatApiController.php` |
| **Modificar** | `app/Modules/Rat/RatServiceProvider.php` |
| **Modificar** | `routes/api.php` |

**Intocados:** `RatFilterService`, `RatFilterDTO`, `RatResource`, `RatListResource`, `RatWriteService`, `RatProtocoloService`, `RatService`, `EloquentRatRepository`.

---

## Ordem de execução (ondas paralelas)

```
Onda 1 (paralela): Task 1 + Task 2 + Task 3
Onda 2 (paralela, após Onda 1): Task 4 + Task 5
Onda 3 (sequencial, após Onda 2): Task 6
```

---

## Task 1: ReceiveRatBiRequest + RatReceiveBiDTO

**Agente pode executar esta task em paralelo com Task 2 e Task 3.**

**Files:**
- Create: `app/Modules/Rat/Http/Requests/ReceiveRatBiRequest.php`
- Create: `app/Modules/Rat/DTOs/RatReceiveBiDTO.php`

- [ ] **Step 1.1: Criar ReceiveRatBiRequest**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveRatBiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Dados Gerais
            'dados_gerais'                               => ['nullable', 'array'],
            'dados_gerais.data_fato'                     => ['nullable', 'string'],
            'dados_gerais.data_inicio_atividade'         => ['nullable', 'string'],
            'dados_gerais.data_termino_atividade'        => ['nullable', 'string'],
            'dados_gerais.nat_cobrade_id'                => ['nullable'],
            'dados_gerais.nat_nome_operacao'             => ['nullable', 'string', 'max:255'],
            'dados_gerais.tem_vistoria'                  => ['nullable', 'boolean'],

            // Comunicacao
            'comunicacao'                                => ['nullable', 'array'],
            'comunicacao.tipo_solicitacao'               => ['nullable', 'string', 'in:telefone,radio,pessoal,sistema,email,outro'],
            'comunicacao.data_comunicacao'               => ['nullable', 'string'],
            'comunicacao.telefone_contato'               => ['nullable', 'string', 'max:20'],
            'comunicacao.nome_solicitante'               => ['nullable', 'string', 'max:255'],

            // Local
            'local'                                      => ['nullable', 'array'],
            'local.pais_id'                              => ['nullable', 'integer'],
            'local.uf'                                   => ['nullable', 'string', 'size:2'],
            'local.municipio_id'                         => ['nullable'],

            // Endereco
            'endereco'                                   => ['nullable', 'array'],
            'endereco.cep'                               => ['nullable', 'string', 'max:10'],
            'endereco.logradouro'                        => ['nullable', 'string', 'max:255'],
            'endereco.numero'                            => ['nullable', 'string', 'max:20'],
            'endereco.complemento'                       => ['nullable', 'string', 'max:255'],
            'endereco.bairro'                            => ['nullable', 'string', 'max:150'],
            'endereco.km'                                => ['nullable', 'string', 'max:20'],
            'endereco.cruzamento'                        => ['nullable', 'string'],
            'endereco.ponto_referencia'                  => ['nullable', 'string'],
            'endereco.tipo_localizacao'                  => ['nullable', 'string', 'in:urbana,rural,rodovia,estrada,mata,montanha,rio,lago,outros'],
            'endereco.latitude'                          => ['nullable', 'numeric', 'between:-90,90'],
            'endereco.longitude'                         => ['nullable', 'numeric', 'between:-180,180'],

            // Recursos (array de objetos)
            'recursos'                                   => ['nullable', 'array'],
            'recursos.*.tipo_recurso'                    => ['nullable', 'string'],
            'recursos.*.categoria'                       => ['nullable', 'string'],
            'recursos.*.orgao_responsavel'               => ['nullable', 'string'],
            'recursos.*.identificacao'                   => ['nullable', 'string'],
            'recursos.*.condutor'                        => ['nullable', 'string'],
            'recursos.*.descricao'                       => ['nullable', 'string'],
            'recursos.*.data_saida'                      => ['nullable', 'string'],
            'recursos.*.data_chegada'                    => ['nullable', 'string'],
            'recursos.*.km_percorrido'                   => ['nullable', 'numeric'],
            'recursos.*.local_origem'                    => ['nullable', 'string'],
            'recursos.*.local_destino'                   => ['nullable', 'string'],

            // Envolvidos (array de objetos)
            'envolvidos'                                 => ['nullable', 'array'],
            'envolvidos.*.tipo_pessoa'                   => ['nullable', 'string'],
            'envolvidos.*.cpf'                           => ['nullable', 'string'],
            'envolvidos.*.nome'                          => ['nullable', 'string'],
            'envolvidos.*.nome_social'                   => ['nullable', 'string'],
            'envolvidos.*.data_nascimento'               => ['nullable', 'date'],
            'envolvidos.*.idade_aparente'                => ['nullable', 'integer'],
            'envolvidos.*.sexo'                          => ['nullable', 'string'],
            'envolvidos.*.nome_mae'                      => ['nullable', 'string'],
            'envolvidos.*.nome_pai'                      => ['nullable', 'string'],
            'envolvidos.*.ocupacao'                      => ['nullable', 'string'],
            'envolvidos.*.escolaridade'                  => ['nullable', 'string'],
            'envolvidos.*.cep'                           => ['nullable', 'string'],
            'envolvidos.*.uf'                            => ['nullable', 'string'],
            'envolvidos.*.municipio'                     => ['nullable', 'string'],
            'envolvidos.*.logradouro'                    => ['nullable', 'string'],
            'envolvidos.*.bairro'                        => ['nullable', 'string'],
            'envolvidos.*.numero'                        => ['nullable', 'string'],
            'envolvidos.*.complemento'                   => ['nullable', 'string'],

            // Vistoria (objeto)
            'vistoria'                                   => ['nullable', 'array'],
            'vistoria.solicitante'                       => ['nullable', 'array'],
            'vistoria.solicitante.nome'                  => ['nullable', 'string'],
            'vistoria.solicitante.cpf'                   => ['nullable', 'string'],
            'vistoria.solicitante.telefone'              => ['nullable', 'string'],
            'vistoria.solicitante.cep'                   => ['nullable', 'string'],
            'vistoria.solicitante.bairro'                => ['nullable', 'string'],
            'vistoria.solicitante.endereco'              => ['nullable', 'string'],
            'vistoria.imovel'                            => ['nullable', 'array'],
            'vistoria.imovel.endereco'                   => ['nullable', 'string'],
            'vistoria.imovel.bairro'                     => ['nullable', 'string'],
            'vistoria.imovel.municipio'                  => ['nullable', 'string'],
            'vistoria.imovel.cep'                        => ['nullable', 'string'],
            'vistoria.estrutura'                         => ['nullable', 'array'],
            'vistoria.estrutura.tipo_imovel'             => ['nullable', 'string'],
            'vistoria.estrutura.tipo_construcao'         => ['nullable', 'string'],
            'vistoria.estrutura.tipo_destinacao'         => ['nullable', 'string'],
            'vistoria.estrutura.tipo_edificacao'         => ['nullable', 'string'],
            'vistoria.estrutura.sistema_estrutural'      => ['nullable', 'string'],
            'vistoria.estrutura.estado_conservacao'      => ['nullable', 'string'],
            'vistoria.estrutura.regime_ocupacao'         => ['nullable', 'string'],
            'vistoria.estrutura.num_pavimentos'          => ['nullable', 'integer'],
            'vistoria.moradores'                         => ['nullable', 'array'],
            'vistoria.moradores.proprietario'            => ['nullable', 'string'],
            'vistoria.moradores.telefone'                => ['nullable', 'string'],

            // Raiz
            'finalize'                                   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'comunicacao.tipo_solicitacao.in' => 'tipo_solicitacao deve ser: telefone, radio, pessoal, sistema, email ou outro.',
            'endereco.tipo_localizacao.in'    => 'tipo_localizacao deve ser: urbana, rural, rodovia, estrada, mata, montanha, rio, lago ou outros.',
            'endereco.latitude.between'       => 'latitude deve estar entre -90 e 90.',
            'endereco.longitude.between'      => 'longitude deve estar entre -180 e 180.',
        ];
    }
}
```

- [ ] **Step 1.2: Criar RatReceiveBiDTO**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Rat\Http\Requests\ReceiveRatBiRequest;

readonly class RatReceiveBiDTO
{
    public function __construct(
        public readonly array $dadosGerais,
        public readonly array $comunicacao,
        public readonly array $local,
        public readonly array $endereco,
        public readonly array $recursos,
        public readonly array $envolvidos,
        public readonly array $vistoria,
        public readonly bool  $finalize,
    ) {}

    public static function fromRequest(ReceiveRatBiRequest $request): self
    {
        return new self(
            dadosGerais: $request->input('dados_gerais', []),
            comunicacao: $request->input('comunicacao', []),
            local:       $request->input('local', []),
            endereco:    $request->input('endereco', []),
            recursos:    $request->input('recursos', []),
            envolvidos:  $request->input('envolvidos', []),
            vistoria:    $request->input('vistoria', []),
            finalize:    (bool) $request->input('finalize', false),
        );
    }

    public function toModelArray(): array
    {
        return [
            'dados_gerais' => $this->dadosGerais,
            'comunicacao'  => $this->comunicacao,
            'local'        => $this->local,
            'endereco'     => $this->endereco,
            'recursos'     => $this->recursos,
            'envolvidos'   => $this->envolvidos,
            'vistoria'     => $this->vistoria,
            'tem_vistoria' => !empty($this->vistoria),
        ];
    }
}
```

- [ ] **Step 1.3: Commit**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
git add app/Modules/Rat/Http/Requests/ReceiveRatBiRequest.php
git add app/Modules/Rat/DTOs/RatReceiveBiDTO.php
git commit -m "feat(rat-api): ReceiveRatBiRequest + RatReceiveBiDTO"
```

---

## Task 2: RatExportBiService

**Agente pode executar esta task em paralelo com Task 1 e Task 3.**

**Files:**
- Create: `app/Modules/Rat/Services/RatExportBiService.php`

- [ ] **Step 2.1: Criar RatExportBiService**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Models\Rat;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Responsabilidade unica: fornecer dados do model Rat para a API de integracao.
 *
 * listForApi()      — paginado, usa RatListResource (consumo geral)
 * listForPowerBI()  — flat array desnormalizado por recurso x envolvido (consumo Power BI)
 */
class RatExportBiService
{
    public function __construct(
        private readonly RatRepositoryInterface $repository,
        private readonly RatFilterService       $filterService,
    ) {}

    /**
     * Lista paginada de RATs para consumo geral da API.
     */
    public function listForApi(RatFilterDTO $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    /**
     * Retorna array flat desnormalizado — uma linha por recurso x envolvido.
     * Quando nao ha recursos ou envolvidos, gera uma linha com nulls nos campos ausentes.
     */
    public function listForPowerBI(RatFilterDTO $filters): array
    {
        $allFilters = new RatFilterDTO(
            protocolo:  $filters->protocolo,
            status:     $filters->status,
            ano:        $filters->ano,
            municipio:  $filters->municipio,
            dataInicio: $filters->dataInicio,
            dataFim:    $filters->dataFim,
            perPage:    9999,
        );

        $rats = $this->repository->paginate($allFilters)->items();

        $rows = [];
        foreach ($rats as $rat) {
            $rows = array_merge($rows, $this->flattenRat($rat));
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function flattenRat(Rat $rat): array
    {
        $base      = $this->buildBaseRow($rat);
        $recursos  = $rat->recursos  ?? [];
        $envolvidos = $rat->envolvidos ?? [];

        if (empty($recursos) && empty($envolvidos)) {
            return [$this->mergeRow($base, null, null)];
        }

        if (empty($recursos)) {
            return array_map(fn($e) => $this->mergeRow($base, null, $e), $envolvidos);
        }

        if (empty($envolvidos)) {
            return array_map(fn($r) => $this->mergeRow($base, $r, null), $recursos);
        }

        $rows = [];
        foreach ($recursos as $recurso) {
            foreach ($envolvidos as $envolvido) {
                $rows[] = $this->mergeRow($base, $recurso, $envolvido);
            }
        }
        return $rows;
    }

    private function buildBaseRow(Rat $rat): array
    {
        $dg = $rat->dados_gerais ?? [];
        $lo = $rat->local        ?? [];
        $en = $rat->endereco     ?? [];
        $co = $rat->comunicacao  ?? [];

        return [
            'rat_id'                       => $rat->id,
            'protocolo'                    => $rat->protocolo,
            'status'                       => $rat->status,
            'tem_vistoria'                 => $rat->tem_vistoria,
            'created_at'                   => $rat->created_at?->toIso8601String(),
            'updated_at'                   => $rat->updated_at?->toIso8601String(),

            // dados_gerais
            'dados_gerais_data_fato'                => $dg['data_fato']              ?? null,
            'dados_gerais_data_inicio_atividade'    => $dg['data_inicio_atividade']  ?? null,
            'dados_gerais_data_termino_atividade'   => $dg['data_termino_atividade'] ?? null,
            'dados_gerais_nat_cobrade_id'           => $dg['nat_cobrade_id']         ?? null,
            'dados_gerais_nat_nome_operacao'        => $dg['nat_nome_operacao']      ?? null,

            // local
            'local_pais_id'    => $lo['pais_id']    ?? null,
            'local_uf'         => $lo['uf']         ?? null,
            'local_municipio'  => $lo['municipio']  ?? null,

            // endereco
            'endereco_cep'             => $en['cep']             ?? null,
            'endereco_logradouro'      => $en['logradouro']      ?? null,
            'endereco_numero'          => $en['numero']          ?? null,
            'endereco_bairro'          => $en['bairro']          ?? null,
            'endereco_complemento'     => $en['complemento']     ?? null,
            'endereco_tipo_localizacao'=> $en['tipo_localizacao']?? null,
            'endereco_latitude'        => $en['latitude']        ?? null,
            'endereco_longitude'       => $en['longitude']       ?? null,

            // comunicacao
            'comunicacao_tipo_solicitacao' => $co['tipo_solicitacao'] ?? null,
            'comunicacao_data'             => $co['data_comunicacao'] ?? null,
            'comunicacao_nome_solicitante' => $co['nome_solicitante'] ?? null,
        ];
    }

    private function mergeRow(array $base, ?array $recurso, ?array $envolvido): array
    {
        return array_merge(
            $base,
            $this->buildRecursoColumns($recurso),
            $this->buildEnvolvidoColumns($envolvido),
        );
    }

    private function buildRecursoColumns(?array $r): array
    {
        return [
            'recurso_tipo_recurso'      => $r['tipo_recurso']      ?? null,
            'recurso_categoria'         => $r['categoria']         ?? null,
            'recurso_orgao_responsavel' => $r['orgao_responsavel'] ?? null,
            'recurso_identificacao'     => $r['identificacao']     ?? null,
            'recurso_condutor'          => $r['condutor']          ?? null,
            'recurso_descricao'         => $r['descricao']         ?? null,
            'recurso_data_saida'        => $r['data_saida']        ?? null,
            'recurso_data_chegada'      => $r['data_chegada']      ?? null,
            'recurso_km_percorrido'     => $r['km_percorrido']     ?? null,
            'recurso_local_origem'      => $r['local_origem']      ?? null,
            'recurso_local_destino'     => $r['local_destino']     ?? null,
        ];
    }

    private function buildEnvolvidoColumns(?array $e): array
    {
        return [
            'envolvido_tipo_pessoa'     => $e['tipo_pessoa']     ?? null,
            'envolvido_cpf'             => $e['cpf']             ?? null,
            'envolvido_nome'            => $e['nome']            ?? null,
            'envolvido_nome_social'     => $e['nome_social']     ?? null,
            'envolvido_data_nascimento' => $e['data_nascimento'] ?? null,
            'envolvido_idade_aparente'  => $e['idade_aparente']  ?? null,
            'envolvido_sexo'            => $e['sexo']            ?? null,
            'envolvido_nome_mae'        => $e['nome_mae']        ?? null,
            'envolvido_nome_pai'        => $e['nome_pai']        ?? null,
            'envolvido_ocupacao'        => $e['ocupacao']        ?? null,
            'envolvido_escolaridade'    => $e['escolaridade']    ?? null,
            'envolvido_municipio'       => $e['municipio']       ?? null,
            'envolvido_uf'              => $e['uf']              ?? null,
        ];
    }
}
```

- [ ] **Step 2.2: Commit**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
git add app/Modules/Rat/Services/RatExportBiService.php
git commit -m "feat(rat-api): RatExportBiService (listForApi + listForPowerBI)"
```

---

## Task 3: RatReceiveBiService

**Agente pode executar esta task em paralelo com Task 1 e Task 2.**

**Files:**
- Create: `app/Modules/Rat/Services/RatReceiveBiService.php`

- [ ] **Step 3.1: Criar RatReceiveBiService**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatReceiveBiDTO;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;

/**
 * Responsabilidade unica: receber dados externos via API e persistir como Rat.
 *
 * Delega criacao para RatWriteService — nunca acessa o repositorio diretamente.
 * Se finalize=true, finaliza o RAT apos criacao.
 */
class RatReceiveBiService
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    /**
     * Cria um novo RAT a partir de dados externos.
     * Se $dto->finalize === true, finaliza o RAT apos a criacao.
     */
    public function receive(RatReceiveBiDTO $dto): Rat
    {
        $rat = $this->writeService->createWithData($dto->toModelArray());

        if ($dto->finalize) {
            $rat = $this->writeService->finalize($rat->id);
        }

        return $rat;
    }
}
```

- [ ] **Step 3.2: Commit**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
git add app/Modules/Rat/Services/RatReceiveBiService.php
git commit -m "feat(rat-api): RatReceiveBiService"
```

---

## Task 4: RatApiController

**Aguarda Tasks 1, 2 e 3. Pode rodar em paralelo com Task 5.**

**Files:**
- Modify: `app/Http/Controllers/Api/V1/Rat/ProtocoloController.php`
  - Renomear classe para `RatApiController`, namespace permanece o mesmo
  - Substituir todo o conteúdo

- [ ] **Step 4.1: Substituir o stub ProtocoloController pelo RatApiController**

Arquivo: `app/Http/Controllers/Api/V1/Rat/ProtocoloController.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\DTOs\RatReceiveBiDTO;
use App\Modules\Rat\Http\Requests\ReceiveRatBiRequest;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Http\Resources\RatResource;
use App\Modules\Rat\Services\RatExportBiService;
use App\Modules\Rat\Services\RatReceiveBiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller para o modulo RAT.
 *
 * FLUXO: Request -> Controller -> Service -> JSON
 *
 * RESPONSABILIDADES:
 * - GET /api/v1/rat/protocolos              → listagem paginada
 * - GET /api/v1/rat/protocolos?format=powerbi → flat array para Power BI
 * - GET /api/v1/rat/protocolos/{id}         → detalhe completo
 * - POST /api/v1/rat/protocolos             → recebimento de dados externos
 *
 * @OA\Tag(
 *     name="RAT",
 *     description="Endpoints do modulo RAT (Registro de Atendimento Tecnico)"
 * )
 */
class ProtocoloController extends Controller
{
    public function __construct(
        private readonly RatExportBiService  $exportService,
        private readonly RatReceiveBiService $receiveService,
    ) {}

    /**
     * Lista protocolos RAT ou exporta flat para Power BI.
     *
     * ?format=powerbi → retorna array flat desnormalizado (sem paginacao)
     * sem parametro   → retorna lista paginada com RatListResource
     *
     * @OA\Get(
     *     path="/api/v1/rat/protocolos",
     *     summary="Lista protocolos RAT / export Power BI",
     *     tags={"RAT"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="format", in="query", required=false,
     *         @OA\Schema(type="string", enum={"powerbi"})),
     *     @OA\Parameter(name="protocolo", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="municipio", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="ano", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="data_inicio", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="data_fim", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Sucesso"),
     *     @OA\Response(response=401, description="Nao autenticado"),
     *     @OA\Response(response=429, description="Rate limit excedido")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = RatFilterDTO::fromArray($request->only([
            'protocolo', 'status', 'municipio', 'ano',
            'data_inicio', 'data_fim', 'per_page',
        ]));

        if ($request->input('format') === 'powerbi') {
            $data = $this->exportService->listForPowerBI($filters);

            return response()->json([
                'success' => true,
                'data'    => $data,
                'meta'    => [
                    'total_registros' => count($data),
                    'gerado_em'       => now()->toIso8601String(),
                ],
            ]);
        }

        $paginator = $this->exportService->listForApi($filters);

        return response()->json([
            'success' => true,
            'data'    => RatListResource::collection($paginator)->response()->getData(true),
        ]);
    }

    /**
     * Detalhe completo de um RAT por UUID.
     *
     * @OA\Get(
     *     path="/api/v1/rat/protocolos/{id}",
     *     summary="Detalhe de um protocolo RAT",
     *     tags={"RAT"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Protocolo encontrado"),
     *     @OA\Response(response=404, description="Nao encontrado"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $rat = $this->exportService->findById($id);

        if (!$rat) {
            return response()->json([
                'success' => false,
                'message' => 'Protocolo RAT nao encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => (new RatResource($rat))->resolve(),
        ]);
    }

    /**
     * Recebe dados externos e cria um novo RAT.
     *
     * @OA\Post(
     *     path="/api/v1/rat/protocolos",
     *     summary="Recebe dados externos e cria protocolo RAT",
     *     tags={"RAT"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="dados_gerais", type="object"),
     *             @OA\Property(property="comunicacao", type="object"),
     *             @OA\Property(property="local", type="object"),
     *             @OA\Property(property="endereco", type="object"),
     *             @OA\Property(property="recursos", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="envolvidos", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="vistoria", type="object"),
     *             @OA\Property(property="finalize", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=201, description="RAT criado com sucesso"),
     *     @OA\Response(response=422, description="Dados invalidos"),
     *     @OA\Response(response=429, description="Rate limit excedido")
     * )
     */
    public function receive(ReceiveRatBiRequest $request): JsonResponse
    {
        $dto = RatReceiveBiDTO::fromRequest($request);
        $rat = $this->receiveService->receive($dto);

        return response()->json([
            'success' => true,
            'data'    => (new RatResource($rat))->resolve(),
        ], 201);
    }
}
```

- [ ] **Step 4.2: Adicionar findById ao RatExportBiService**

Abrir `app/Modules/Rat/Services/RatExportBiService.php` e adicionar o método após `listForPowerBI()`:

```php
    /**
     * Busca um RAT por UUID. Retorna null se nao encontrado.
     */
    public function findById(string $id): ?Rat
    {
        return $this->repository->findById($id);
    }
```

- [ ] **Step 4.3: Commit**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
git add app/Http/Controllers/Api/V1/Rat/ProtocoloController.php
git add app/Modules/Rat/Services/RatExportBiService.php
git commit -m "feat(rat-api): RatApiController (index/show/receive) + findById no ExportBiService"
```

---

## Task 5: RatServiceProvider — registrar novos services

**Aguarda Tasks 2 e 3. Pode rodar em paralelo com Task 4.**

**Files:**
- Modify: `app/Modules/Rat/RatServiceProvider.php`

- [ ] **Step 5.1: Adicionar singletons no método register()**

Abrir `app/Modules/Rat/RatServiceProvider.php`. Localizar o bloco existente de `singleton`:

```php
        $this->app->singleton(RatService::class);
        $this->app->singleton(RatProtocoloService::class);
```

Adicionar após essa linha (mantendo o bloco existente intacto):

```php
        $this->app->singleton(\App\Modules\Rat\Services\RatExportBiService::class);
        $this->app->singleton(\App\Modules\Rat\Services\RatReceiveBiService::class);
```

O bloco `use` no topo do arquivo **não precisa ser alterado** — os FQCNs completos resolvem o namespace.

- [ ] **Step 5.2: Commit**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
git add app/Modules/Rat/RatServiceProvider.php
git commit -m "feat(rat-api): registrar RatExportBiService e RatReceiveBiService no ServiceProvider"
```

---

## Task 6: Rotas — api.php

**Aguarda Tasks 4 e 5 (ambas concluídas).**

**Files:**
- Modify: `routes/api.php`

- [ ] **Step 6.1: Remover o apiResource stub**

Em `routes/api.php`, localizar e **remover** este bloco (dentro do grupo `auth:sanctum`):

```php
    // Módulo RAT — Protocolos legados
    Route::prefix('rat')->name('api.v1.rat.')->group(function () {
        Route::apiResource('protocolos', ProtocoloController::class);
    });
```

- [ ] **Step 6.2: Adicionar os novos blocos com auth dual e rate limiting**

Logo abaixo da seção de `DECRETACOES API` (após a linha `Route::post('/receive', ...)`), adicionar:

```php
// ============================================================================
// RAT API — Autenticacao dupla (Sanctum + Power BI token)
// ============================================================================

// Rotas de leitura — limite alto (pro: 1000 creditos/min)
Route::prefix('v1/rat')
    ->name('api.v1.rat.')
    ->middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        'decretacoes.api.auth',
        'api-rate-limiter:pro',
    ])
    ->group(function () {
        Route::get('protocolos',      [\App\Http\Controllers\Api\V1\Rat\ProtocoloController::class, 'index'])->name('protocolos.index');
        Route::get('protocolos/{id}', [\App\Http\Controllers\Api\V1\Rat\ProtocoloController::class, 'show'])->name('protocolos.show');
    });

// Rota de escrita — limite restrito (default: 300 creditos/min)
Route::prefix('v1/rat')
    ->middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        'decretacoes.api.auth',
        'api-rate-limiter:default',
    ])
    ->group(function () {
        Route::post('protocolos', [\App\Http\Controllers\Api\V1\Rat\ProtocoloController::class, 'receive'])->name('api.v1.rat.protocolos.receive');
    });
```

- [ ] **Step 6.3: Verificar que as rotas foram registradas corretamente**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
php artisan route:list --path=api/v1/rat
```

Saída esperada (3 linhas):
```
GET  api/v1/rat/protocolos       api.v1.rat.protocolos.index
GET  api/v1/rat/protocolos/{id}  api.v1.rat.protocolos.show
POST api/v1/rat/protocolos       api.v1.rat.protocolos.receive
```

- [ ] **Step 6.4: Verificar que o container resolve sem erros**

```bash
php artisan config:clear && php artisan route:cache
```

Saída esperada: `Route cache cleared!` / `Routes cached successfully!`

- [ ] **Step 6.5: Commit final**

```bash
git add routes/api.php
git commit -m "feat(rat-api): rotas GET+POST /api/v1/rat/protocolos com auth dual e rate limiting"
```

---

## Self-Review

**Spec coverage:**

| Requisito do spec | Task que implementa |
|-------------------|---------------------|
| GET paginado | Task 2 (`listForApi`) + Task 4 (`index`) + Task 6 (rota) |
| GET `?format=powerbi` flat | Task 2 (`listForPowerBI`) + Task 4 (`index`) |
| GET por ID (`show`) | Task 2 (`findById`) + Task 4 (`show`) |
| POST receive externo | Task 1 (DTO+Request) + Task 3 (Service) + Task 4 (`receive`) |
| Rate limiting GET pro | Task 6 (`api-rate-limiter:pro`) |
| Rate limiting POST default | Task 6 (`api-rate-limiter:default`) |
| Auth dual (Sanctum + PowerBI token) | Task 6 (`decretacoes.api.auth`) |
| Registro no ServiceProvider | Task 5 |
| Campos de todas as abas no POST | Task 1 (`ReceiveRatBiRequest`) |
| Campos flat no PowerBI | Task 2 (`buildBaseRow` + `buildRecursoColumns` + `buildEnvolvidoColumns`) |

**Placeholder scan:** nenhum TBD, TODO ou "implement later" encontrado.

**Type consistency:**
- `RatReceiveBiDTO::fromRequest(ReceiveRatBiRequest $request)` — Task 1 define, Task 4 usa. Consistente.
- `RatExportBiService::findById(string $id): ?Rat` — adicionado em Task 4 Step 4.2, chamado em Task 4 Step 4.1. Consistente.
- `RatFilterDTO::fromArray(array $data)` — método existente, não criado neste plano. Verificado em `RatFilterDTO.php`.
- `RatReceiveBiService::receive(RatReceiveBiDTO $dto): Rat` — Task 3 define, Task 4 usa. Consistente.
