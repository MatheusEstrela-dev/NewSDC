# Exemplo Prático: Implementação de API REST entre Módulos

## Estrutura de Arquivos Proposta

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── V1/
│   │   │       ├── Pae/
│   │   │       │   ├── EmpreendimentoController.php
│   │   │       │   ├── DocumentoController.php
│   │   │       │   ├── HistoricoController.php
│   │   │       │   └── ComiteController.php
│   │   │       ├── Rat/
│   │   │       │   └── ProtocoloController.php
│   │   │       ├── Tdap/
│   │   │       │   └── ProcessoController.php
│   │   │       └── Integracao/
│   │   │           └── IntegracaoController.php
│   │   └── ...
│   ├── Resources/
│   │   └── Api/
│   │       └── V1/
│   │           ├── Pae/
│   │           │   ├── EmpreendimentoResource.php
│   │           │   ├── DocumentoResource.php
│   │           │   └── ComiteResource.php
│   │           └── Rat/
│   │               └── ProtocoloResource.php
│   └── Requests/
│       └── Api/
│           └── V1/
│               └── Pae/
│                   ├── StoreEmpreendimentoRequest.php
│                   └── UpdateEmpreendimentoRequest.php
routes/
├── api.php
└── api/
    └── v1.php
```

## Exemplo 1: Controller PAE Completo

```php
<?php
// app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php

namespace App\Http\Controllers\Api\V1\Pae;

use App\Http\Controllers\Controller;
use App\Models\Pae\Empreendimento;
use App\Http\Resources\Api\V1\Pae\EmpreendimentoResource;
use App\Http\Requests\Api\V1\Pae\StoreEmpreendimentoRequest;
use App\Http\Requests\Api\V1\Pae\UpdateEmpreendimentoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmpreendimentoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $empreendimentos = Empreendimento::with(['municipio', 'documentos', 'comite'])
            ->paginate(15);
            
        return EmpreendimentoResource::collection($empreendimentos);
    }

    public function show(Empreendimento $empreendimento): JsonResponse
    {
        $empreendimento->load(['municipio', 'documentos', 'comite.membros', 'historico']);
        
        return response()->json([
            'data' => new EmpreendimentoResource($empreendimento)
        ]);
    }

    public function store(StoreEmpreendimentoRequest $request): JsonResponse
    {
        $empreendimento = Empreendimento::create($request->validated());
        
        return response()->json([
            'data' => new EmpreendimentoResource($empreendimento),
            'message' => 'Empreendimento criado com sucesso'
        ], 201);
    }

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

    public function destroy(Empreendimento $empreendimento): JsonResponse
    {
        $empreendimento->delete();
        
        return response()->json([
            'message' => 'Empreendimento removido com sucesso'
        ], 204);
    }
}
```

## Exemplo 2: Form Request para Validação

```php
<?php
// app/Http/Requests/Api/V1/Pae/StoreEmpreendimentoRequest.php

namespace App\Http\Requests\Api\V1\Pae;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpreendimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Empreendimento::class);
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'tipo' => 'required|string|in:Barragem de Rejeitos,Barragem de Água,Outro',
            'municipio_id' => 'required|exists:municipios,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'nivel_emergencia' => 'required|integer|between:1,3',
            'status' => 'required|string|in:aprovado,em_analise,pendente,vencido',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do empreendimento é obrigatório.',
            'municipio_id.exists' => 'O município selecionado não existe.',
            'latitude.between' => 'A latitude deve estar entre -90 e 90.',
            'longitude.between' => 'A longitude deve estar entre -180 e 180.',
        ];
    }
}
```

## Exemplo 3: Resource para Formatação

```php
<?php
// app/Http/Resources/Api/V1/Pae/EmpreendimentoResource.php

namespace App\Http\Resources\Api\V1\Pae;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpreendimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'municipio' => [
                'id' => $this->municipio->id ?? null,
                'nome' => $this->municipio->nome ?? null,
                'uf' => $this->municipio->uf ?? null,
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

## Exemplo 4: Rotas API Organizadas

```php
<?php
// routes/api/v1.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Pae\EmpreendimentoController;
use App\Http\Controllers\Api\V1\Pae\DocumentoController;
use App\Http\Controllers\Api\V1\Pae\HistoricoController;
use App\Http\Controllers\Api\V1\Pae\ComiteController;
use App\Http\Controllers\Api\V1\Rat\ProtocoloController;
use App\Http\Controllers\Api\V1\Integracao\IntegracaoController;

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Módulo PAE
    Route::prefix('pae')->name('api.v1.pae.')->group(function () {
        // Empreendimentos
        Route::apiResource('empreendimentos', EmpreendimentoController::class);
        
        // Documentos
        Route::get('empreendimentos/{empreendimento}/documentos', 
            [DocumentoController::class, 'index'])->name('documentos.index');
        Route::post('empreendimentos/{empreendimento}/documentos', 
            [DocumentoController::class, 'store'])->name('documentos.store');
        Route::delete('documentos/{documento}', 
            [DocumentoController::class, 'destroy'])->name('documentos.destroy');
        
        // Histórico
        Route::get('empreendimentos/{empreendimento}/historico', 
            [HistoricoController::class, 'index'])->name('historico.index');
        
        // Comitê
        Route::get('empreendimentos/{empreendimento}/comite', 
            [ComiteController::class, 'show'])->name('comite.show');
        Route::post('empreendimentos/{empreendimento}/comite/membros', 
            [ComiteController::class, 'addMember'])->name('comite.add-member');
    });
    
    // Módulo RAT
    Route::prefix('rat')->name('api.v1.rat.')->group(function () {
        Route::apiResource('protocolos', ProtocoloController::class);
    });
    
    // Integração entre Módulos
    Route::prefix('integracao')->name('api.v1.integracao.')->group(function () {
        Route::get('rat/{ratId}/pae', 
            [IntegracaoController::class, 'getPaeByRat'])->name('rat.pae');
        Route::get('pae/{paeId}/rat', 
            [IntegracaoController::class, 'getRatByPae'])->name('pae.rat');
    });
});
```

## Exemplo 5: Uso no Frontend (Vue.js)

```javascript
// resources/js/services/api/pae.js

import api from '../api';

export const paeService = {
    /**
     * Lista todos os empreendimentos
     */
    async listEmpreendimentos(params = {}) {
        const response = await api.get('/pae/empreendimentos', { params });
        return response.data;
    },

    /**
     * Busca um empreendimento específico
     */
    async getEmpreendimento(id) {
        const response = await api.get(`/pae/empreendimentos/${id}`);
        return response.data.data;
    },

    /**
     * Cria um novo empreendimento
     */
    async createEmpreendimento(data) {
        const response = await api.post('/pae/empreendimentos', data);
        return response.data.data;
    },

    /**
     * Atualiza um empreendimento
     */
    async updateEmpreendimento(id, data) {
        const response = await api.put(`/pae/empreendimentos/${id}`, data);
        return response.data.data;
    },

    /**
     * Remove um empreendimento
     */
    async deleteEmpreendimento(id) {
        await api.delete(`/pae/empreendimentos/${id}`);
    },

    /**
     * Lista documentos de um empreendimento
     */
    async listDocumentos(empreendimentoId) {
        const response = await api.get(`/pae/empreendimentos/${empreendimentoId}/documentos`);
        return response.data.data;
    },

    /**
     * Faz upload de documento
     */
    async uploadDocumento(empreendimentoId, file) {
        const formData = new FormData();
        formData.append('arquivo', file);
        formData.append('tipo', file.type);
        
        const response = await api.post(
            `/pae/empreendimentos/${empreendimentoId}/documentos`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }
        );
        return response.data.data;
    },
};
```

## Exemplo 6: Composable Vue para PAE

```javascript
// resources/js/composables/usePaeApi.js

import { ref } from 'vue';
import { paeService } from '@/services/api/pae';

export function usePaeApi() {
    const loading = ref(false);
    const error = ref(null);
    const empreendimentos = ref([]);
    const empreendimento = ref(null);

    async function fetchEmpreendimentos(params = {}) {
        loading.value = true;
        error.value = null;
        
        try {
            const data = await paeService.listEmpreendimentos(params);
            empreendimentos.value = data.data;
            return data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao carregar empreendimentos';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchEmpreendimento(id) {
        loading.value = true;
        error.value = null;
        
        try {
            empreendimento.value = await paeService.getEmpreendimento(id);
            return empreendimento.value;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao carregar empreendimento';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createEmpreendimento(data) {
        loading.value = true;
        error.value = null;
        
        try {
            const novo = await paeService.createEmpreendimento(data);
            empreendimentos.value.push(novo);
            return novo;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao criar empreendimento';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateEmpreendimento(id, data) {
        loading.value = true;
        error.value = null;
        
        try {
            const atualizado = await paeService.updateEmpreendimento(id, data);
            const index = empreendimentos.value.findIndex(e => e.id === id);
            if (index !== -1) {
                empreendimentos.value[index] = atualizado;
            }
            if (empreendimento.value?.id === id) {
                empreendimento.value = atualizado;
            }
            return atualizado;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao atualizar empreendimento';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        error,
        empreendimentos,
        empreendimento,
        fetchEmpreendimentos,
        fetchEmpreendimento,
        createEmpreendimento,
        updateEmpreendimento,
    };
}
```

## Exemplo 7: Integração entre Módulos

```php
<?php
// app/Http/Controllers/Api/V1/Integracao/IntegracaoController.php

namespace App\Http\Controllers\Api\V1\Integracao;

use App\Http\Controllers\Controller;
use App\Models\Pae\Empreendimento;
use App\Models\Rat\Protocolo;
use App\Http\Resources\Api\V1\Pae\EmpreendimentoResource;
use App\Http\Resources\Api\V1\Rat\ProtocoloResource;
use Illuminate\Http\JsonResponse;

class IntegracaoController extends Controller
{
    /**
     * Busca empreendimento PAE relacionado a um protocolo RAT
     * GET /api/v1/integracao/rat/{ratId}/pae
     */
    public function getPaeByRat(int $ratId): JsonResponse
    {
        $protocolo = Protocolo::with('municipio')->findOrFail($ratId);
        
        // Busca empreendimento relacionado
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
                'rat' => new ProtocoloResource($protocolo),
                'pae' => new EmpreendimentoResource($empreendimento),
                'relacao' => [
                    'tipo' => 'municipio',
                    'municipio_id' => $protocolo->municipio_id,
                ]
            ]
        ]);
    }

    /**
     * Busca protocolos RAT relacionados a um empreendimento PAE
     * GET /api/v1/integracao/pae/{paeId}/rat
     */
    public function getRatByPae(int $paeId): JsonResponse
    {
        $empreendimento = Empreendimento::with('municipio')->findOrFail($paeId);
        
        $protocolos = Protocolo::where('municipio_id', $empreendimento->municipio_id)
            ->get();
            
        return response()->json([
            'data' => [
                'pae' => new EmpreendimentoResource($empreendimento),
                'protocolos_rat' => ProtocoloResource::collection($protocolos),
                'total' => $protocolos->count(),
            ]
        ]);
    }
}
```

## Fluxo Completo: Criar Empreendimento via API

### 1. Frontend (Vue.js)

```vue
<template>
  <form @submit.prevent="handleSubmit">
    <input v-model="form.nome" placeholder="Nome do Empreendimento" />
    <select v-model="form.municipio_id">
      <option v-for="municipio in municipios" :key="municipio.id" :value="municipio.id">
        {{ municipio.nome }}
      </option>
    </select>
    <button type="submit" :disabled="loading">Salvar</button>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { usePaeApi } from '@/composables/usePaeApi';

const { createEmpreendimento, loading, error } = usePaeApi();

const form = ref({
  nome: '',
  tipo: 'Barragem de Rejeitos',
  municipio_id: null,
  latitude: -20.2547,
  longitude: -43.8011,
  nivel_emergencia: 1,
});

async function handleSubmit() {
  try {
    const empreendimento = await createEmpreendimento(form.value);
    console.log('Empreendimento criado:', empreendimento);
    // Redirecionar ou atualizar lista
  } catch (err) {
    console.error('Erro:', err);
  }
}
</script>
```

### 2. Backend (Laravel)

```php
// Request chega em: POST /api/v1/pae/empreendimentos
// Controller valida → Cria → Retorna Resource
```

### 3. Resposta JSON

```json
{
    "data": {
        "id": 1,
        "nome": "Barragem Sul Superior",
        "tipo": "Barragem de Rejeitos",
        "municipio": {
            "id": 123,
            "nome": "Itabirito",
            "uf": "MG"
        },
        "protocolo": "2024.10.15.0081",
        "status": "aprovado",
        ...
    },
    "message": "Empreendimento criado com sucesso"
}
```

## Vantagens desta Abordagem

1. **Desacoplamento**: Módulos podem evoluir independentemente
2. **Reutilização**: APIs podem ser consumidas por diferentes clientes
3. **Testabilidade**: Fácil de testar endpoints isoladamente
4. **Escalabilidade**: Cada módulo pode ser escalado separadamente
5. **Padronização**: Respostas consistentes em todos os módulos
6. **Documentação**: APIs podem ser documentadas com Swagger
7. **Versionamento**: Fácil adicionar novas versões sem quebrar compatibilidade

