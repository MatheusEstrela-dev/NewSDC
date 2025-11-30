# ⚡ Skeleton Loading - Quick Start

Guia rápido de 5 minutos para implementar Skeleton Loading nas suas páginas.

---

## 🚀 Passo 1: Configurar Interceptadores (1x apenas)

**Edite `resources/js/app.js`**:

```javascript
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { setupInertiaLoadingInterceptors } from '@/composables/usePageLoading'

createInertiaApp({
  // ... suas configurações existentes

  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)

    // ✅ ADICIONAR ESTA LINHA
    setupInertiaLoadingInterceptors()

    app.mount(el)
  }
})
```

---

## 🎨 Passo 2: Usar em Páginas

### Dashboard

```vue
<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <!-- ✅ ADICIONAR LoadingWrapper -->
    <LoadingWrapper skeleton="dashboard">
      <!-- Seu conteúdo existente aqui -->
      <div class="min-h-screen bg-gray-100 p-8">
        <!-- ... -->
      </div>
    </LoadingWrapper>
  </AuthenticatedLayout>
</template>

<script setup>
// ✅ IMPORTAR
import LoadingWrapper from '@/Components/LoadingWrapper.vue'

// Resto do código permanece igual
</script>
```

### Página PAE

```vue
<template>
  <AuthenticatedLayout>
    <Head title="PAE" />

    <!-- ✅ ADICIONAR LoadingWrapper -->
    <LoadingWrapper skeleton="pae">
      <!-- Seu conteúdo existente -->
    </LoadingWrapper>
  </AuthenticatedLayout>
</template>

<script setup>
import LoadingWrapper from '@/Components/LoadingWrapper.vue'
</script>
```

### Tabela Simples

```vue
<LoadingWrapper skeleton="table">
  <MyTable :data="tableData" />
</LoadingWrapper>
```

### Card Simples

```vue
<LoadingWrapper skeleton="card">
  <MyCard />
</LoadingWrapper>
```

---

## 🎯 Tipos de Skeleton Disponíveis

| Tipo | Quando Usar |
|------|-------------|
| `skeleton="dashboard"` | Página com métricas + tabela + timeline |
| `skeleton="pae"` | Página com breadcrumb + tabs + formulário |
| `skeleton="table"` | Apenas tabela |
| `skeleton="card"` | Apenas card |

---

## 🎨 Tipos de Animação

```vue
<!-- Pulse (padrão - recomendado) -->
<LoadingWrapper skeleton="dashboard" animation="pulse">

<!-- Wave (mais chamativo) -->
<LoadingWrapper skeleton="dashboard" animation="wave">

<!-- Sem animação -->
<LoadingWrapper skeleton="dashboard" animation="none">
```

---

## ✅ Pronto!

Agora quando você navegar entre páginas, verá um skeleton animado ao invés de tela branca.

**Ver guia completo**: [SKELETON_LOADING_GUIDE.md](SKELETON_LOADING_GUIDE.md)
