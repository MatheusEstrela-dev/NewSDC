# API REST entre Módulos - SDC

## Visão Geral

No sistema SDC, a comunicação entre módulos (PAE, RAT, TDAP, etc.) pode ser feita através de APIs REST, permitindo que cada módulo seja independente e se comunique de forma padronizada.

## Arquitetura Proposta

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Módulo    │     │   Módulo    │     │   Módulo    │
│     PAE     │────▶│     RAT     │────▶│    TDAP     │
└─────────────┘     └─────────────┘     └─────────────┘
      │                    │                    │
      └────────────────────┼────────────────────┘
                           │
                    ┌──────────────┐
                    │  API Gateway │
                    │  (Laravel)   │
                    └──────────────┘
                           │
                    ┌──────────────┐
                    │   Database   │
                    └──────────────┘
```

## Estrutura de Rotas API

### 1. Organização por Módulos

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    
    // Módulo PAE
    Route::prefix('pae')->middleware('auth:sanctum')->group(function () {
        Route::get('/empreendimentos', [PaeController::class, 'index']);
        Route::get('/empreendimentos/{id}', [PaeController::class, 'show']);
        Route::post('/empreendimentos', [PaeController::class, 'store']);
        Route::put('/empreendimentos/{id}', [PaeController::class, 'update']);
        Route::delete('/empreendimentos/{id}', [PaeController::class, 'destroy']);
        
        // Sub-recursos
        Route::get('/empreendimentos/{id}/documentos', [PaeDocumentoController::class, 'index']);
        Route::post('/empreendimentos/{id}/documentos', [PaeDocumentoController::class, 'store']);
        Route::get('/empreendimentos/{id}/historico', [PaeHistoricoController::class, 'index']);
        Route::get('/empreendimentos/{id}/comite', [PaeComiteController::class, 'show']);
    });
    
    // Módulo RAT
    Route::prefix('rat')->middleware('auth:sanctum')->group(function () {
        Route::get('/protocolos', [RatController::class, 'index']);
        Route::get('/protocolos/{id}', [RatController::class, 'show']);
        Route::post('/protocolos', [RatController::class, 'store']);
        Route::put('/protocolos/{id}', [RatController::class, 'update']);
    });
    
    // Módulo TDAP
    Route::prefix('tdap')->middleware('auth:sanctum')->group(function () {
        Route::get('/processos', [TdapController::class, 'index']);
        Route::get('/processos/{id}', [TdapController::class, 'show']);
        Route::post('/processos', [TdapController::class, 'store']);
    });
    
    // APIs de Integração entre Módulos
    Route::prefix('integracao')->middleware('auth:sanctum')->group(function () {
        // Buscar empreendimento do PAE relacionado a um protocolo RAT
        Route::get('/rat/{ratId}/pae', [IntegracaoController::class, 'getPaeByRat']);
        
        // Buscar protocolos RAT relacionados a um empreendimento PAE
        Route::get('/pae/{paeId}/rat', [IntegracaoController::class, 'getRatByPae']);
        
        // Sincronizar dados entre módulos
        Route::post('/sincronizar', [IntegracaoController::class, 'sincronizar']);
    });
});
```

## Exemplo de Controller API

### Controller PAE

```php
<?php

namespace App\Http\Controllers\Api\V1\Pae;

use App\Http\Controllers\Controller;
use App\Models\Pae\Empreendimento;
use App\Http\Resources\Pae\EmpreendimentoResource;
use App\Http\Requests\Api\Pae\StoreEmpreendimentoRequest;
use App\Http\Requests\Api\Pae\UpdateEmpreendimentoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmpreendimentoController extends Controller
{
    /**
     * Lista todos os empreendimentos
     * GET /api/v1/pae/empreendimentos
     */
    public function index(): AnonymousResourceCollection
    {
        $empreendimentos = Empreendimento::with(['municipio', 'documentos', 'comite'])
            ->paginate(15);
            
        return EmpreendimentoResource::collection($empreendimentos);
    }

    /**
     * Exibe um empreendimento específico
     * GET /api/v1/pae/empreendimentos/{id}
     */
    public function show(Empreendimento $empreendimento): JsonResponse
    {
        $empreendimento->load(['municipio', 'documentos', 'comite.membros', 'historico']);
        
        return response()->json([
            'data' => new EmpreendimentoResource($empreendimento)
        ]);
    }

    /**
     * Cria um novo empreendimento
     * POST /api/v1/pae/empreendimentos
     */
    public function store(StoreEmpreendimentoRequest $request): JsonResponse
    {
        $empreendimento = Empreendimento::create($request->validated());
        
        return response()->json([
            'data' => new EmpreendimentoResource($empreendimento),
            'message' => 'Empreendimento criado com sucesso'
        ], 201);
    }

    /**
     * Atualiza um empreendimento
     * PUT /api/v1/pae/empreendimentos/{id}
     */
    public function update(
        UpdateEmpreendimentoRequest $request,
        Empreendimento $empreendimento
    ): JsonResponse {
        $empreendimento->update($request->validated());
        
        return response()->json([
            'data' => new EmpreendimentoResource($empreendimento->fresh()),
            'message' => 'Empreendimento atualizado com sucesso'
        ]);
    }

    /**
     * Remove um empreendimento
     * DELETE /api/v1/pae/empreendimentos/{id}
     */
    public function destroy(Empreendimento $empreendimento): JsonResponse
    {
        $empreendimento->delete();
        
        return response()->json([
            'message' => 'Empreendimento removido com sucesso'
        ], 204);
    }
}
```

## API Resources (Formatação de Respostas)

### EmpreendimentoResource

```php
<?php

namespace App\Http\Resources\Pae;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpreendimentoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'municipio' => [
                'id' => $this->municipio->id,
                'nome' => $this->municipio->nome,
                'uf' => $this->municipio->uf,
            ],
            'coordenadas' => [
                'lat' => $this->latitude,
                'lng' => $this->longitude,
            ],
            'protocolo' => $this->protocolo,
            'status' => $this->status,
            'nivel_emergencia' => $this->nivel_emergencia,
            'data_emissao' => $this->data_emissao?->format('Y-m-d'),
            'proximo_vencimento' => $this->proximo_vencimento?->format('Y-m-d'),
            'documentos' => DocumentoResource::collection($this->whenLoaded('documentos')),
            'comite' => new ComiteResource($this->whenLoaded('comite')),
            'historico' => HistoricoResource::collection($this->whenLoaded('historico')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

## Comunicação entre Módulos

### Exemplo: Integração PAE ↔ RAT

```php
<?php

namespace App\Http\Controllers\Api\V1\Integracao;

use App\Http\Controllers\Controller;
use App\Models\Pae\Empreendimento;
use App\Models\Rat\Protocolo;
use Illuminate\Http\JsonResponse;

class IntegracaoController extends Controller
{
    /**
     * Busca empreendimento PAE relacionado a um protocolo RAT
     * GET /api/v1/integracao/rat/{ratId}/pae
     */
    public function getPaeByRat(int $ratId): JsonResponse
    {
        $protocolo = Protocolo::findOrFail($ratId);
        
        // Busca empreendimento relacionado pelo município ou protocolo
        $empreendimento = Empreendimento::where('municipio_id', $protocolo->municipio_id)
            ->orWhere('protocolo', $protocolo->numero)
            ->first();
            
        if (!$empreendimento) {
            return response()->json([
                'message' => 'Nenhum empreendimento PAE encontrado para este protocolo RAT'
            ], 404);
        }
        
        return response()->json([
            'data' => [
                'rat' => [
                    'id' => $protocolo->id,
                    'numero' => $protocolo->numero,
                ],
                'pae' => new \App\Http\Resources\Pae\EmpreendimentoResource($empreendimento)
            ]
        ]);
    }

    /**
     * Busca protocolos RAT relacionados a um empreendimento PAE
     * GET /api/v1/integracao/pae/{paeId}/rat
     */
    public function getRatByPae(int $paeId): JsonResponse
    {
        $empreendimento = Empreendimento::findOrFail($paeId);
        
        $protocolos = Protocolo::where('municipio_id', $empreendimento->municipio_id)
            ->get();
            
        return response()->json([
            'data' => [
                'pae' => [
                    'id' => $empreendimento->id,
                    'nome' => $empreendimento->nome,
                ],
                'protocolos_rat' => \App\Http\Resources\Rat\ProtocoloResource::collection($protocolos)
            ]
        ]);
    }

    /**
     * Sincroniza dados entre módulos
     * POST /api/v1/integracao/sincronizar
     */
    public function sincronizar(Request $request): JsonResponse
    {
        $request->validate([
            'modulo_origem' => 'required|in:pae,rat,tdap',
            'modulo_destino' => 'required|in:pae,rat,tdap',
            'dados' => 'required|array',
        ]);
        
        // Lógica de sincronização
        // Exemplo: atualizar dados do município em ambos os módulos
        
        return response()->json([
            'message' => 'Dados sincronizados com sucesso',
            'data' => $request->dados
        ]);
    }
}
```

## Autenticação API (Sanctum)

### Configuração

```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),
    
    'guard' => ['web'],
    
    'expiration' => null,
    
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
];
```

### Uso no Frontend (Vue.js)

```javascript
// resources/js/services/api.js
import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Interceptor para adicionar token
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('api_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Interceptor para tratar erros
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Redirecionar para login
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;
```

### Exemplo de Uso no Componente Vue

```vue
<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';

const empreendimentos = ref([]);
const loading = ref(false);
const error = ref(null);

async function fetchEmpreendimentos() {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await api.get('/pae/empreendimentos');
        empreendimentos.value = response.data.data;
    } catch (err) {
        error.value = err.response?.data?.message || 'Erro ao carregar empreendimentos';
    } finally {
        loading.value = false;
    }
}

async function createEmpreendimento(data) {
    try {
        const response = await api.post('/pae/empreendimentos', data);
        empreendimentos.value.push(response.data.data);
        return response.data;
    } catch (err) {
        throw new Error(err.response?.data?.message || 'Erro ao criar empreendimento');
    }
}

onMounted(() => {
    fetchEmpreendimentos();
});
</script>
```

## Padrões de Resposta

### Sucesso

```json
{
    "data": {
        "id": 1,
        "nome": "Barragem Sul Superior",
        "tipo": "Barragem de Rejeitos",
        ...
    },
    "message": "Operação realizada com sucesso"
}
```

### Lista Paginada

```json
{
    "data": [
        {
            "id": 1,
            "nome": "Barragem Sul Superior",
            ...
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "per_page": 15,
        "to": 15,
        "total": 75
    },
    "links": {
        "first": "/api/v1/pae/empreendimentos?page=1",
        "last": "/api/v1/pae/empreendimentos?page=5",
        "prev": null,
        "next": "/api/v1/pae/empreendimentos?page=2"
    }
}
```

### Erro

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "nome": [
            "O campo nome é obrigatório."
        ],
        "municipio_id": [
            "O campo município é obrigatório."
        ]
    }
}
```

## Versionamento de API

### Estrutura

```
routes/
├── api.php          # Versão atual (v1)
└── api/
    ├── v1.php       # Versão 1
    └── v2.php       # Versão 2 (futuro)
```

### Exemplo

```php
// routes/api.php
Route::prefix('v1')->group(base_path('routes/api/v1.php'));
Route::prefix('v2')->group(base_path('routes/api/v2.php'));
```

## Rate Limiting

```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Limite específico por módulo
RateLimiter::for('pae-api', function (Request $request) {
    return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
});
```

## Documentação (Swagger/OpenAPI)

### Instalação

```bash
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### Exemplo de Documentação

```php
/**
 * @OA\Get(
 *     path="/api/v1/pae/empreendimentos",
 *     summary="Lista todos os empreendimentos PAE",
 *     tags={"PAE"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número da página",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Lista de empreendimentos",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Empreendimento"))
 *         )
 *     )
 * )
 */
```

## Boas Práticas

1. **Sempre use Resources** para formatar respostas
2. **Valide dados** com Form Requests
3. **Use paginação** para listas grandes
4. **Implemente rate limiting** para prevenir abuso
5. **Documente suas APIs** com Swagger/OpenAPI
6. **Use versionamento** para manter compatibilidade
7. **Trate erros** de forma consistente
8. **Use HTTPS** em produção
9. **Implemente cache** quando apropriado
10. **Use testes** para garantir qualidade

## Exemplo Completo: Fluxo PAE → RAT

```php
// 1. Controller de Integração
public function criarProtocoloRatDoPae(int $paeId, Request $request)
{
    $empreendimento = Empreendimento::findOrFail($paeId);
    
    // Cria protocolo RAT baseado nos dados do PAE
    $protocolo = Protocolo::create([
        'municipio_id' => $empreendimento->municipio_id,
        'tipo' => 'PAE',
        'referencia_pae_id' => $empreendimento->id,
        'numero' => $this->gerarNumeroProtocolo(),
        'status' => 'em_analise',
    ]);
    
    return response()->json([
        'data' => new ProtocoloResource($protocolo),
        'message' => 'Protocolo RAT criado a partir do PAE'
    ], 201);
}
```

## Conclusão

A API REST permite que os módulos do SDC se comuniquem de forma padronizada, mantendo independência e facilitando integrações futuras. Cada módulo expõe seus recursos através de endpoints RESTful, permitindo que outros módulos ou sistemas externos consumam esses dados de forma consistente.

