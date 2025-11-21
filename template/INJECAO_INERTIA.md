# Como a Injeção via Inertia.js Está Funcionando

## 📋 Visão Geral

O Inertia.js permite que o Laravel passe dados diretamente para os componentes Vue sem precisar de uma API REST tradicional. Os dados são injetados automaticamente como props nos componentes Vue.

## 🔄 Fluxo de Dados

```
Laravel Controller
    ↓
Inertia::render('ComponentName', ['props' => $data])
    ↓
HandleInertiaRequests Middleware (adiciona props compartilhadas)
    ↓
Frontend (Vue Component)
    ↓
$page.props ou defineProps()
```

## 🏗️ Configuração Atual

### 1. **Configuração do Inertia no Frontend** (`resources/js/app.js`)

```javascript
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
});
```

**Como funciona:**
- `resolvePageComponent`: Resolve dinamicamente o componente Vue baseado no nome
- `props`: Props passadas do Laravel são automaticamente injetadas no componente
- `plugin`: Plugin do Inertia que fornece `$page`, `router`, etc.

### 2. **Middleware HandleInertiaRequests** (`app/Http/Middleware/HandleInertiaRequests.php`)

Este middleware adiciona props compartilhadas em todas as requisições:

```php
public function share(Request $request): array
{
    return [
        'auth' => [
            'user' => $request->user(),
        ],
        'ziggy' => fn () => [
            ...(new Ziggy)->toArray(),
            'location' => $request->url(),
        ],
    ];
}
```

**Props compartilhadas automaticamente:**
- `auth.user`: Usuário autenticado (se houver)
- `ziggy`: Rotas do Laravel disponíveis no frontend

### 3. **Controllers Laravel** (Backend → Frontend)

#### Exemplo: Login Controller

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

**Props passadas:**
- `canResetPassword`: Boolean
- `status`: String (status da sessão)

#### Exemplo: Dashboard Controller

```php
// routes/web.php
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
```

**Atualmente:** Nenhuma prop específica está sendo passada (apenas props compartilhadas)

## 📥 Como Receber Props no Vue

### Método 1: Usando `$page.props` (Template)

```vue
<template>
  <div>
    <!-- Acessa props compartilhadas -->
    <p>{{ $page.props.auth.user.name }}</p>
    
    <!-- Acessa props específicas do controller -->
    <p v-if="$page.props.canResetPassword">Pode resetar senha</p>
  </div>
</template>
```

### Método 2: Usando `usePage()` (Composition API)

```vue
<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();

// Props compartilhadas
const user = page.props.auth?.user;

// Props específicas
const canResetPassword = page.props.canResetPassword;
</script>
```

### Método 3: Usando `defineProps()` (Recomendado)

```vue
<script setup>
defineProps({
  canResetPassword: {
    type: Boolean,
    default: false,
  },
  status: {
    type: String,
    default: null,
  },
});
</script>
```

## 🔍 Estado Atual das Telas

### ✅ **Login.vue** - Funcionando Corretamente

**Controller:**
```php
return Inertia::render('Auth/Login', [
    'canResetPassword' => Route::has('password.request'),
    'status' => session('status'),
]);
```

**Componente Vue:**
```vue
<script setup>
// Atualmente NÃO está recebendo props do Inertia
// Os dados são gerenciados localmente via composable
const { ... } = useLogin();
</script>
```

**Status:** ✅ Funcional, mas **NÃO está usando props do Inertia** - dados são mockados no composable.

### ⚠️ **Dashboard.vue** - Precisa de Ajustes

**Controller:**
```php
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard'); // Sem props!
})->middleware(['auth', 'verified']);
```

**Componente Vue:**
```vue
<script setup>
// Atualmente NÃO está recebendo props do Inertia
// Os dados são mockados no composable
const { metrics, pmdaEmAnalise, historico } = useDashboard();
</script>
```

**Status:** ⚠️ Funcional, mas **NÃO está usando props do Inertia** - dados são mockados no composable.

## 🚨 Problema Identificado

**As telas estão funcionando, mas NÃO estão recebendo dados do backend via Inertia!**

### Problemas:

1. **Login.vue:**
   - ❌ Não recebe `canResetPassword` do controller
   - ❌ Não recebe `status` do controller
   - ✅ Funciona com dados locais

2. **Dashboard.vue:**
   - ❌ Não recebe dados do backend
   - ❌ Dados estão hardcoded no composable
   - ✅ Funciona com dados mockados

## ✅ Solução: Implementar Injeção Correta

### 1. **Atualizar Dashboard Controller**

```php
// routes/web.php ou criar DashboardController
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'metrics' => [
            'emEdicao' => 24,
            'emAnalise' => 5,
            'aprovados' => 77,
            'atendidos' => 12,
        ],
        'pmdaEmAnalise' => [
            // Dados reais do banco
        ],
        'historico' => [
            // Dados reais do banco
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');
```

### 2. **Atualizar Dashboard.vue para Receber Props**

```vue
<script setup>
import { usePage } from '@inertiajs/vue3';

// Recebe props do Inertia
const page = usePage();
const props = defineProps({
  metrics: {
    type: Object,
    default: () => ({}),
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

// Usa props do Inertia ou fallback para dados mockados
const { modal, openDetails } = useDashboard();
const metrics = computed(() => props.metrics || defaultMetrics);
</script>
```

### 3. **Atualizar useDashboard.js**

```javascript
export function useDashboard(initialData = {}) {
  // Usa dados iniciais do Inertia ou fallback
  const metrics = ref(initialData.metrics || defaultMetrics);
  const pmdaEmAnalise = ref(initialData.pmdaEmAnalise || []);
  const historico = ref(initialData.historico || []);
  
  // ...
}
```

## 📊 Comparação: Como Deveria Funcionar vs Como Está

### ❌ **Como Está (Atual)**

```
Controller → Inertia::render('Dashboard') [sem props]
    ↓
Dashboard.vue → useDashboard() [dados mockados]
    ↓
Componentes recebem dados mockados
```

### ✅ **Como Deveria Ser**

```
Controller → Inertia::render('Dashboard', ['metrics' => $data])
    ↓
HandleInertiaRequests → Adiciona props compartilhadas
    ↓
Dashboard.vue → defineProps({ metrics, ... })
    ↓
useDashboard(props) → Usa dados reais do backend
    ↓
Componentes recebem dados reais
```

## 🔧 Exemplo Completo de Implementação

### Backend (Controller)

```php
<?php

namespace App\Http\Controllers;

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
                'val' => 24,
                'label' => 'Em Edição',
                'color' => 'bg-blue-600',
                'icon' => 'pencil',
                'desc' => 'Planos sendo editados pelos municípios.',
            ],
            // ... outras métricas
        ];
    }

    private function getPmdaEmAnalise(): array
    {
        // Buscar do banco de dados
        return Pmda::where('status', 'em_analise')
            ->with('municipio', 'responsavel')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'protocolo' => $item->protocolo,
                'status' => $item->status,
                'data' => $item->created_at->format('d/m/Y'),
                'municipio' => $item->municipio->nome,
                'responsavel' => $item->responsavel->nome,
            ])
            ->toArray();
    }

    private function getHistorico(): array
    {
        // Buscar do banco de dados
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

### Frontend (Vue Component)

```vue
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDashboard } from '../../composables/useDashboard';

// Recebe props do Inertia
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

// Usa composable com dados do Inertia
const { modal, openDetails } = useDashboard({
  metrics: props.metrics,
  pmdaEmAnalise: props.pmdaEmAnalise,
  historico: props.historico,
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

## 📝 Resumo

### ✅ **O que está funcionando:**
- Inertia.js está configurado corretamente
- Props compartilhadas (`auth.user`) estão disponíveis
- Componentes Vue estão sendo renderizados

### ⚠️ **O que precisa ser ajustado:**
- Controllers não estão passando props específicas
- Componentes não estão recebendo props do Inertia
- Dados estão mockados no frontend ao invés de virem do backend

### 🎯 **Próximos Passos:**
1. Criar DashboardController com dados reais
2. Atualizar Dashboard.vue para receber props
3. Atualizar useDashboard.js para usar props iniciais
4. Implementar busca de dados do banco de dados
5. Adicionar loading states
6. Implementar error handling

---

**Última atualização**: 2025-11-20

