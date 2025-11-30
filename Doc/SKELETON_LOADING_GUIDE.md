# 🎨 Guia Completo de Skeleton Loading

Sistema completo de Skeleton Screen implementado para melhorar a experiência do usuário durante carregamentos de página.

**Data**: 2025-01-21
**Versão**: 1.0.0

---

## 📋 Índice

- [O que é Skeleton Loading?](#-o-que-é-skeleton-loading)
- [Arquitetura](#-arquitetura)
- [Componentes Criados](#-componentes-criados)
- [Como Usar](#-como-usar)
- [Exemplos Práticos](#-exemplos-práticos)
- [Customização](#-customização)
- [Integração com Inertia](#-integração-com-inertia)
- [Boas Práticas](#-boas-práticas)

---

## 🎯 O que é Skeleton Loading?

**Skeleton Screen** (ou Skeleton Loading) é uma técnica de UX que exibe um placeholder animado enquanto o conteúdo real está sendo carregado.

### Benefícios

✅ **Percepção de Performance**: Usuário sente que a página é mais rápida
✅ **Reduz Ansiedade**: Menos frustração durante carregamentos
✅ **Evita Layout Shift**: Mantém estrutura visual consistente
✅ **Profissional**: Aparência moderna e polida
✅ **Melhor UX**: Usuário entende que algo está acontecendo

### Antes vs Depois

**❌ Sem Skeleton**:
```
[Tela branca por 2-3 segundos]
→ Conteúdo aparece de repente
→ Usuário acha que travou
```

**✅ Com Skeleton**:
```
[Skeleton animado aparece instantaneamente]
→ Usuário vê estrutura da página
→ Conteúdo carrega suavemente
→ Transição elegante
```

---

## 🏗️ Arquitetura

### Estrutura de Arquivos

```
resources/js/
├── Components/
│   └── Skeleton/
│       ├── SkeletonBase.vue          # Componente base (formas primitivas)
│       ├── SkeletonCard.vue          # Card genérico
│       ├── SkeletonTable.vue         # Tabela genérica
│       ├── SkeletonDashboard.vue     # Layout específico Dashboard
│       └── SkeletonPae.vue           # Layout específico PAE
│   └── LoadingWrapper.vue            # Wrapper inteligente
│
└── composables/
    └── usePageLoading.js              # Controle global de loading
```

### Hierarquia de Componentes

```
SkeletonBase (primitivo)
    ↓
SkeletonCard, SkeletonTable (compostos)
    ↓
SkeletonDashboard, SkeletonPae (específicos)
    ↓
LoadingWrapper (inteligente)
```

---

## 📦 Componentes Criados

### 1. SkeletonBase

**Propósito**: Componente primitivo para criar formas básicas

**Props**:
```vue
<SkeletonBase
  shape="rectangle|circle|line"      // Forma
  animation="pulse|wave|none"        // Tipo de animação
  size="xs|sm|md|lg|xl|custom"      // Tamanho predefinido
  width="100px"                      // Largura customizada
  height="50px"                      // Altura customizada
  rounded="none|sm|md|lg|full"      // Bordas arredondadas
  custom-class="my-class"            // Classes adicionais
/>
```

**Exemplos**:
```vue
<!-- Linha de texto -->
<SkeletonBase shape="line" width="200px" height="16px" />

<!-- Círculo (avatar) -->
<SkeletonBase shape="circle" width="48px" height="48px" />

<!-- Retângulo (botão) -->
<SkeletonBase shape="rectangle" width="120px" height="40px" rounded="md" />
```

---

### 2. SkeletonCard

**Propósito**: Card genérico com header, conteúdo e footer

**Props**:
```vue
<SkeletonCard
  :lines="3"                    // Número de linhas de texto
  :show-header="true"           // Exibir header
  header-width="60%"            // Largura do título
  :show-header-action="false"   // Exibir botão no header
  :show-image="false"           // Exibir área de imagem
  image-width="100%"            // Largura da imagem
  image-height="200px"          // Altura da imagem
  :show-footer="false"          // Exibir footer
  :footer-items="2"             // Número de items no footer
  animation="pulse"             // Tipo de animação
/>
```

**Exemplo**:
```vue
<!-- Card com header e 3 linhas -->
<SkeletonCard :lines="3" :show-header="true" />

<!-- Card com imagem -->
<SkeletonCard
  :lines="2"
  :show-image="true"
  image-height="300px"
  :show-footer="true"
/>
```

---

### 3. SkeletonTable

**Propósito**: Tabela com header, linhas e paginação

**Props**:
```vue
<SkeletonTable
  :columns="4"            // Número de colunas
  :rows="5"               // Número de linhas
  :show-pagination="true" // Exibir paginação
  animation="pulse"       // Tipo de animação
/>
```

**Exemplo**:
```vue
<!-- Tabela 5 colunas, 10 linhas -->
<SkeletonTable :columns="5" :rows="10" />

<!-- Tabela sem paginação -->
<SkeletonTable :columns="3" :rows="5" :show-pagination="false" />
```

---

### 4. SkeletonDashboard

**Propósito**: Layout completo do Dashboard

**Estrutura**:
- 4 cards de métricas no topo
- Tabela principal (2/3 width)
- Timeline lateral (1/3 width)

**Uso**:
```vue
<SkeletonDashboard animation="pulse" />
```

---

### 5. SkeletonPae

**Propósito**: Layout completo da página PAE

**Estrutura**:
- Breadcrumb
- Header com título e ações
- Tabs
- Grid de formulários
- Cards de ações

**Uso**:
```vue
<SkeletonPae animation="wave" />
```

---

### 6. LoadingWrapper

**Propósito**: Componente inteligente que alterna entre skeleton e conteúdo real

**Props**:
```vue
<LoadingWrapper
  skeleton="dashboard|pae|card|table|custom"  // Tipo de skeleton
  :custom-skeleton="MyCustomSkeleton"         // Skeleton customizado
  animation="pulse|wave"                       // Tipo de animação
  :force-loading="false"                      // Forçar loading
  :min-display-time="500"                     // Tempo mínimo (ms)
>
  <!-- Conteúdo real aqui -->
</LoadingWrapper>
```

---

## 🚀 Como Usar

### Passo 1: Configurar Interceptadores Globais

No seu `app.js`, configure os interceptadores do Inertia:

**[resources/js/app.js](SDC/resources/js/app.js)**:
```javascript
import { setupInertiaLoadingInterceptors } from '@/composables/usePageLoading'

// Após createApp()
setupInertiaLoadingInterceptors()
```

### Passo 2: Integrar em Páginas

#### Opção A: Com LoadingWrapper (Recomendado)

**Dashboard.vue**:
```vue
<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <LoadingWrapper skeleton="dashboard">
      <!-- Seu conteúdo original aqui -->
      <div class="min-h-screen bg-gray-100 p-8">
        <!-- Grid de métricas -->
        <div class="grid grid-cols-4 gap-6">
          <!-- ... -->
        </div>
      </div>
    </LoadingWrapper>
  </AuthenticatedLayout>
</template>

<script setup>
import LoadingWrapper from '@/Components/LoadingWrapper.vue'

// Seus composables e props
</script>
```

#### Opção B: Controle Manual

```vue
<template>
  <div>
    <!-- Skeleton -->
    <SkeletonDashboard v-if="isLoading" />

    <!-- Conteúdo Real -->
    <div v-else>
      <!-- Seu conteúdo -->
    </div>
  </div>
</template>

<script setup>
import { usePageLoading } from '@/composables/usePageLoading'
import SkeletonDashboard from '@/Components/Skeleton/SkeletonDashboard.vue'

const { isLoading } = usePageLoading()
</script>
```

---

## 💡 Exemplos Práticos

### Exemplo 1: Dashboard Completo

**[resources/js/Pages/Dashboard.vue](SDC/resources/js/Pages/Dashboard.vue)**:
```vue
<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <LoadingWrapper skeleton="dashboard" animation="pulse">
      <div class="min-h-screen bg-gray-100 p-8">
        <!-- Banner Ano Fiscal -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 text-white px-6 py-5 rounded-2xl shadow-lg mb-8">
          <h2 class="text-3xl font-bold">Exercício {{ currentYear }}</h2>
        </div>

        <!-- Grid de Métricas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
          <MetricsCard
            v-for="(metric, key) in metrics"
            :key="key"
            :metric="metric"
          />
        </div>

        <!-- Conteúdo Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <PmdaTable :data="pmdaData" />
          </div>
          <div>
            <Timeline :events="timelineEvents" />
          </div>
        </div>
      </div>
    </LoadingWrapper>
  </AuthenticatedLayout>
</template>

<script setup>
import LoadingWrapper from '@/Components/LoadingWrapper.vue'
import MetricsCard from '@/Components/Dashboard/MetricsCard.vue'
import PmdaTable from '@/Components/Dashboard/PmdaTable.vue'
import Timeline from '@/Components/Dashboard/Timeline.vue'

defineProps({
  metrics: Object,
  pmdaData: Array,
  timelineEvents: Array
})
</script>
```

---

### Exemplo 2: Página PAE

**[resources/js/Pages/Pae.vue](SDC/resources/js/Pages/Pae.vue)**:
```vue
<template>
  <AuthenticatedLayout>
    <Head title="Detalhes do PAE" />

    <LoadingWrapper skeleton="pae" animation="wave">
      <div class="space-y-6">
        <!-- Breadcrumb -->
        <PaeBreadcrumb :items="breadcrumbs" />

        <!-- Header -->
        <PaeHeader :pae="pae" />

        <!-- Tabs -->
        <PaeTabs v-model="activeTab">
          <template #content>
            <component :is="currentTabComponent" :pae="pae" />
          </template>
        </PaeTabs>
      </div>
    </LoadingWrapper>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import LoadingWrapper from '@/Components/LoadingWrapper.vue'
import PaeBreadcrumb from '@/Components/Pae/PaeBreadcrumb.vue'
import PaeHeader from '@/Components/Pae/PaeHeader.vue'
import PaeTabs from '@/Components/Pae/PaeTabs.vue'

const props = defineProps({
  pae: Object,
  breadcrumbs: Array
})

const activeTab = ref('dados-gerais')
</script>
```

---

### Exemplo 3: Loading em Operações Assíncronas

```vue
<template>
  <div>
    <LoadingWrapper
      skeleton="table"
      :force-loading="isSubmitting"
    >
      <form @submit.prevent="handleSubmit">
        <!-- Formulário -->
      </form>
    </LoadingWrapper>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAsyncLoading } from '@/composables/usePageLoading'
import LoadingWrapper from '@/Components/LoadingWrapper.vue'

const { withLoading } = useAsyncLoading()
const isSubmitting = ref(false)

const handleSubmit = async () => {
  isSubmitting.value = true

  await withLoading(async () => {
    // Sua chamada API
    await api.submitForm(formData)
  }, 'Salvando dados...')

  isSubmitting.value = false
}
</script>
```

---

### Exemplo 4: Skeleton Customizado

Crie seu próprio skeleton:

**CustomSkeleton.vue**:
```vue
<template>
  <div class="custom-skeleton space-y-4">
    <SkeletonBase shape="line" width="50%" height="32px" />
    <div class="grid grid-cols-2 gap-4">
      <SkeletonCard :lines="3" />
      <SkeletonCard :lines="3" />
    </div>
    <SkeletonTable :columns="3" :rows="5" />
  </div>
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue'
import SkeletonCard from './SkeletonCard.vue'
import SkeletonTable from './SkeletonTable.vue'
</script>
```

**Usar skeleton customizado**:
```vue
<LoadingWrapper
  skeleton="custom"
  :custom-skeleton="CustomSkeleton"
>
  <!-- Conteúdo -->
</LoadingWrapper>
```

---

## 🎨 Customização

### Tipos de Animação

#### 1. Pulse (Padrão)
```vue
<SkeletonBase animation="pulse" />
```
- Efeito de fade in/out suave
- Melhor para a maioria dos casos
- Menos distrativo

#### 2. Wave
```vue
<SkeletonBase animation="wave" />
```
- Efeito de onda deslizando
- Mais chamativo
- Bom para indicar processamento ativo

#### 3. None
```vue
<SkeletonBase animation="none" />
```
- Sem animação
- Útil para placeholders estáticos

---

### Cores e Temas

As cores são configuradas via Tailwind e suportam dark mode:

**Light Mode**:
```css
@apply bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200;
```

**Dark Mode**:
```css
@apply dark:from-gray-700 dark:via-gray-600 dark:to-gray-700;
```

**Customizar cores**:
```vue
<SkeletonBase
  custom-class="bg-gradient-to-r from-blue-200 to-blue-300"
/>
```

---

### Tamanhos Predefinidos

```vue
<!-- Extra Small -->
<SkeletonBase size="xs" />  <!-- h-8 -->

<!-- Small -->
<SkeletonBase size="sm" />  <!-- h-16 -->

<!-- Medium (Padrão) -->
<SkeletonBase size="md" />  <!-- h-24 -->

<!-- Large -->
<SkeletonBase size="lg" />  <!-- h-32 -->

<!-- Extra Large -->
<SkeletonBase size="xl" />  <!-- h-48 -->

<!-- Custom -->
<SkeletonBase size="custom" width="300px" height="150px" />
```

---

## 🔗 Integração com Inertia

### Configuração Global

**app.js**:
```javascript
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { setupInertiaLoadingInterceptors } from '@/composables/usePageLoading'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    return pages[`./Pages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)

    // ✅ Configurar interceptadores
    setupInertiaLoadingInterceptors()

    app.mount(el)
  },
})
```

### Eventos Capturados

O composable `usePageLoading` captura automaticamente:

| Evento | Ação |
|--------|------|
| `router.on('start')` | Inicia skeleton |
| `router.on('progress')` | Atualiza progresso |
| `router.on('finish')` | Remove skeleton |
| `router.on('error')` | Remove skeleton |
| `router.on('exception')` | Remove skeleton |

---

## ✅ Boas Práticas

### 1. **Sempre use LoadingWrapper**

❌ **Ruim**:
```vue
<div v-if="!loading">
  <MyContent />
</div>
```

✅ **Bom**:
```vue
<LoadingWrapper skeleton="dashboard">
  <MyContent />
</LoadingWrapper>
```

---

### 2. **Escolha skeleton apropriado**

Match o skeleton com a estrutura do conteúdo:

```vue
<!-- Dashboard com métricas e tabela -->
<LoadingWrapper skeleton="dashboard">

<!-- Formulário com tabs -->
<LoadingWrapper skeleton="pae">

<!-- Lista simples -->
<LoadingWrapper skeleton="table">

<!-- Card individual -->
<LoadingWrapper skeleton="card">
```

---

### 3. **Mantenha tempo mínimo de exibição**

Evite "flash" do skeleton:

```javascript
// Configurado globalmente em usePageLoading.js
const minLoadingTime = 500 // ms
```

Se carregamento for < 500ms, skeleton ainda será exibido por 500ms para evitar flash visual.

---

### 4. **Use animação consistente**

Escolha uma animação e use em todo o projeto:

```vue
<!-- Consistente -->
<LoadingWrapper skeleton="dashboard" animation="pulse">
<LoadingWrapper skeleton="pae" animation="pulse">
<LoadingWrapper skeleton="table" animation="pulse">
```

---

### 5. **Teste em conexões lentas**

No Chrome DevTools:

1. Abra DevTools (F12)
2. Network tab
3. Throttling → Fast 3G ou Slow 3G
4. Recarregue a página
5. Verifique se skeleton aparece suavemente

---

## 📊 Performance

### Métricas Esperadas

| Métrica | Antes | Depois |
|---------|-------|--------|
| **First Contentful Paint (FCP)** | 2.5s | 0.3s (skeleton) |
| **Largest Contentful Paint (LCP)** | 3.0s | 3.0s (mesmo) |
| **Cumulative Layout Shift (CLS)** | 0.15 | 0.02 ✅ |
| **Perceived Performance** | ⭐⭐ | ⭐⭐⭐⭐⭐ |

**Nota**: LCP não melhora, mas **percepção** do usuário melhora drasticamente.

---

## 🐛 Troubleshooting

### Problema 1: Skeleton não aparece

**Solução**: Verificar se `setupInertiaLoadingInterceptors()` foi chamado no `app.js`:

```javascript
// app.js
import { setupInertiaLoadingInterceptors } from '@/composables/usePageLoading'

createInertiaApp({
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)

    // ✅ Adicionar esta linha
    setupInertiaLoadingInterceptors()

    app.mount(el)
  }
})
```

---

### Problema 2: Skeleton "pisca" muito rápido

**Causa**: Carregamento é muito rápido (< 500ms)

**Solução**: Já está configurado! O `minLoadingTime` garante exibição mínima.

Se quiser ajustar:

```javascript
// usePageLoading.js
const minLoadingTime = 800 // Aumentar para 800ms
```

---

### Problema 3: Animação não funciona

**Solução**: Verificar se Tailwind está processando as classes:

```javascript
// tailwind.config.js
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  // ...
}
```

---

### Problema 4: Dark mode não funciona

**Solução**: Habilitar dark mode no Tailwind:

```javascript
// tailwind.config.js
module.exports = {
  darkMode: 'class', // ou 'media'
  // ...
}
```

---

## 📚 Referências e Inspirações

- [Material Design - Skeleton Screen](https://material.io/design/communication/loading.html)
- [Facebook Loading Skeleton](https://www.facebook.com)
- [LinkedIn Pulse Animation](https://www.linkedin.com)
- [Inertia.js Documentation](https://inertiajs.com)

---

## 🎉 Conclusão

Skeleton Loading implementado com sucesso!

### Checklist de Implementação

- [x] Componentes base criados
- [x] Skeletons específicos (Dashboard, PAE)
- [x] LoadingWrapper inteligente
- [x] Composable de controle global
- [x] Integração com Inertia
- [x] Documentação completa
- [ ] Testar em todas as páginas (seu próximo passo!)
- [ ] Ajustar animações conforme feedback
- [ ] Deploy em produção

### Próximos Passos

1. **Integrar em todas as páginas principais**
2. **Testar UX com usuários reais**
3. **Medir métricas de performance**
4. **Ajustar skeletons conforme feedback**

---

**Criado em**: 2025-01-21
**Versão**: 1.0.0
**Autor**: SDC DevOps Team
