# Exemplo Prático: Como Corrigir a Injeção do Inertia

## 🔍 Diagnóstico Atual

### ❌ **Problema Identificado:**

As telas **NÃO estão recebendo dados do backend via Inertia**. Os dados estão mockados no frontend.

### ✅ **Solução:**

Implementar a injeção correta de props do Laravel para o Vue.

---

## 📝 Exemplo 1: Corrigir Login.vue

### **Backend (Controller) - JÁ ESTÁ CORRETO**

```php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php
public function create(): Response
{
    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
}
```

### **Frontend (Vue) - PRECISA AJUSTAR**

**Antes (Atual):**
```vue
<script setup>
// ❌ Não recebe props do Inertia
const { ... } = useLogin();
</script>
```

**Depois (Correto):**
```vue
<script setup>
import { usePage } from '@inertiajs/vue3';
import { useLogin } from '../../composables/useLogin';

// ✅ Recebe props do Inertia
const props = defineProps({
  canResetPassword: {
    type: Boolean,
    default: false,
  },
  status: {
    type: String,
    default: null,
  },
});

// Usa composable normalmente
const {
  cpf,
  password,
  // ...
} = useLogin();

// Exibe status se houver
if (props.status) {
  // Exibir mensagem de status
}
</script>

<template>
  <!-- Usa props do Inertia -->
  <Link
    v-if="canResetPassword"
    :href="route('password.request')"
    class="forgot-password"
  >
    Esqueceu a senha?
  </Link>
</template>
```

---

## 📝 Exemplo 2: Corrigir Dashboard.vue

### **Backend (Controller) - PRECISA CRIAR**

**Criar Controller:**
```php
// app/Http/Controllers/DashboardController.php
<?php

namespace App\Http\Controllers;

use App\Models\Pmda;
use App\Models\Historico;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'metrics' => $this->getMetrics(),
            'pmdaEmAnalise' => $this->getPmdaEmAnalise(),
            'historico' => $this->getHistorico(),
        ]);
    }

    private function getMetrics(): array
    {
        return [
            'emEdicao' => [
                'val' => Pmda::where('status', 'em_edicao')->count(),
                'label' => 'Em Edição',
                'color' => 'bg-blue-600',
                'icon' => 'pencil',
                'desc' => 'Planos sendo editados pelos municípios.',
            ],
            'emAnalise' => [
                'val' => Pmda::where('status', 'em_analise')->count(),
                'label' => 'Em Análise',
                'color' => 'bg-amber-500',
                'icon' => 'clock',
                'desc' => 'Aguardando parecer técnico da CEDEC.',
            ],
            'aprovados' => [
                'val' => Pmda::where('status', 'aprovado')->count(),
                'label' => 'Aprovados',
                'color' => 'bg-emerald-600',
                'icon' => 'check',
                'desc' => 'Planos homologados e vigentes.',
            ],
            'atendidos' => [
                'val' => Pmda::where('status', 'atendido')->count(),
                'label' => 'Atendidos',
                'color' => 'bg-indigo-600',
                'icon' => 'check-badge',
                'desc' => 'Recursos liberados ou ação concluída.',
            ],
        ];
    }

    private function getPmdaEmAnalise(): array
    {
        return Pmda::where('status', 'em_analise')
            ->with(['municipio', 'responsavel'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'protocolo' => $item->protocolo,
                'status' => $item->status,
                'data' => $item->created_at->format('d/m/Y'),
                'municipio' => $item->municipio->nome ?? 'N/A',
                'responsavel' => $item->responsavel->nome ?? 'Pendente',
            ])
            ->toArray();
    }

    private function getHistorico(): array
    {
        return Historico::latest()
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'protocolo' => $item->protocolo,
                'municipio' => $item->municipio,
                'data' => $item->created_at->diffForHumans(),
                'acao' => $item->acao,
            ])
            ->toArray();
    }
}
```

**Atualizar Rota:**
```php
// routes/web.php
use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
```

### **Frontend (Vue) - PRECISA AJUSTAR**

**Antes (Atual):**
```vue
<script setup>
// ❌ Dados mockados no composable
const { metrics, pmdaEmAnalise, historico } = useDashboard();
</script>
```

**Depois (Correto):**
```vue
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDashboard } from '../../composables/useDashboard';

// ✅ Recebe props do Inertia
const props = defineProps({
  metrics: {
    type: Object,
    required: true,
  },
  pmdaEmAnalise: {
    type: Array,
    default: () => [],
  },
  historico: {
    type: Array,
    default: () => [],
  },
});

// Passa props para o composable
const { modal, openDetails } = useDashboard({
  initialMetrics: props.metrics,
  initialPmdaEmAnalise: props.pmdaEmAnalise,
  initialHistorico: props.historico,
});

// Acessa props compartilhadas
const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
  <div>
    <p>Bem-vindo, {{ user?.name }}!</p>
    <!-- Usa dados do Inertia -->
    <MetricsCard
      v-for="(metric, key) in metrics"
      :key="key"
      :metric="metric"
    />
  </div>
</template>
```

### **Composable - PRECISA AJUSTAR**

**Antes (Atual):**
```javascript
export function useDashboard() {
  // ❌ Dados hardcoded
  const metrics = ref({ /* dados mockados */ });
  const pmdaEmAnalise = ref([ /* dados mockados */ ]);
  const historico = ref([ /* dados mockados */ ]);
}
```

**Depois (Correto):**
```javascript
export function useDashboard(initialData = {}) {
  // ✅ Usa dados iniciais do Inertia ou fallback
  const metrics = ref(initialData.initialMetrics || {
    emEdicao: { val: 0, label: 'Em Edição', /* ... */ },
    // ... fallback
  });
  
  const pmdaEmAnalise = ref(initialData.initialPmdaEmAnalise || []);
  const historico = ref(initialData.initialHistorico || []);

  // Função para atualizar dados via Inertia
  function refreshData() {
    router.reload({
      only: ['metrics', 'pmdaEmAnalise', 'historico'],
    });
  }

  return {
    metrics,
    pmdaEmAnalise,
    historico,
    refreshData,
    // ...
  };
}
```

---

## 🔄 Fluxo Completo de Dados

### **1. Requisição HTTP**
```
GET /dashboard
```

### **2. Controller Processa**
```php
DashboardController::index()
    ↓
Busca dados do banco
    ↓
Inertia::render('Dashboard', ['metrics' => $data])
```

### **3. Middleware Adiciona Props Compartilhadas**
```php
HandleInertiaRequests::share()
    ↓
Adiciona: auth.user, ziggy, etc.
```

### **4. Frontend Recebe Props**
```vue
Dashboard.vue
    ↓
defineProps({ metrics, pmdaEmAnalise, historico })
    ↓
useDashboard({ initialMetrics: props.metrics, ... })
    ↓
Componentes usam dados reais
```

---

## ✅ Checklist de Implementação

### **Backend:**
- [ ] Criar DashboardController
- [ ] Implementar métodos para buscar dados do banco
- [ ] Passar dados via Inertia::render()
- [ ] Atualizar rotas

### **Frontend:**
- [ ] Adicionar defineProps() nos componentes
- [ ] Atualizar composables para receber dados iniciais
- [ ] Usar props do Inertia ao invés de dados mockados
- [ ] Implementar loading states
- [ ] Implementar error handling

### **Testes:**
- [ ] Verificar se props estão chegando no frontend
- [ ] Testar com dados reais do banco
- [ ] Verificar performance
- [ ] Testar atualização de dados

---

## 🎯 Benefícios da Correção

1. **Dados Reais**: Componentes recebem dados do banco de dados
2. **SSR Ready**: Preparado para Server-Side Rendering
3. **Type Safety**: Props tipadas no Vue
4. **Performance**: Dados carregados no servidor
5. **Manutenibilidade**: Fonte única de verdade (backend)

---

**Última atualização**: 2025-11-20

