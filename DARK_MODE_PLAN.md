# Plano de Implementação: Dark Mode Completo

## Resumo Executivo

O sistema já tem infraestrutura parcial de Dark Mode:
- ✅ Tailwind configurado com `darkMode: 'class'`
- ✅ TopBar.vue tem toggle funcional (localStorage + system preference)
- ⚠️ **PROBLEMA**: 40% do CSS é hardcoded em `<style>`, não usa Tailwind
- ⚠️ App é **dark-first** - falta suporte completo a light mode

**Objetivo**: Adicionar suporte completo a Light Mode sem quebrar nada.

---

## Fase 1: Infraestrutura (1 dia)

### 1.1 Criar Composable `useTheme.js`

**Arquivo**: `/SDC/resources/js/composables/useTheme.js` (CRIAR NOVO)

Extrair lógica de tema do TopBar.vue para composable reutilizável:

```javascript
import { ref, watch, onMounted } from 'vue';

const isDarkMode = ref(true); // Default dark (app atual)
const isInitialized = ref(false);

export function useTheme() {
  function initTheme() {
    if (isInitialized.value) return;

    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    isDarkMode.value = savedTheme ? savedTheme === 'dark' : prefersDark;
    applyTheme();
    isInitialized.value = true;
  }

  function applyTheme() {
    if (isDarkMode.value) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }

  function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light');
    applyTheme();
  }

  watch(isDarkMode, applyTheme);
  onMounted(initTheme);

  return { isDarkMode, toggleTheme, initTheme };
}
```

**Por quê?**
- Fonte única de verdade para tema
- Reutilizável em qualquer componente
- Segue padrão existente (usePageLoading usa module-level refs)

---

## Fase 2: Layouts (2 dias) - CRÍTICO

### 2.1 Sidebar.vue - Refatoração Completa

**Arquivo**: `/SDC/resources/js/Components/Sidebar.vue`

**Problema**: 200+ linhas de CSS hardcoded:
- `background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%)`
- `rgba(255, 255, 255, 0.1)` para bordas/backgrounds

**Solução**: Converter tudo para classes Tailwind com `dark:` variants

**Mapeamento de Cores**:

| CSS Hardcoded | Dark Mode | Light Mode |
|---------------|-----------|------------|
| `linear-gradient(#1e293b, #0f172a)` | `bg-gradient-to-b from-slate-800 to-slate-900` | `bg-gradient-to-b from-white to-gray-50 border-r border-gray-200` |
| `rgba(255,255,255,0.8)` | `text-white/80` | `text-slate-700` |
| `rgba(255,255,255,0.1)` (bg) | `bg-white/10` | `bg-slate-100` |
| `rgba(255,255,255,0.05)` (hover) | `hover:bg-white/5` | `hover:bg-slate-50` |

**Exemplo de Mudança**:

```vue
<!-- ANTES -->
<style scoped>
.sidebar {
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
}
</style>

<!-- DEPOIS -->
<aside class="bg-gradient-to-b from-slate-800 to-slate-900
              dark:from-slate-800 dark:to-slate-900
              light:from-white light:to-gray-50 light:border-r light:border-gray-200">
```

---

### 2.2 TopBar.vue - Refatoração + Integração useTheme

**Arquivo**: `/SDC/resources/js/Components/TopBar.vue`

**Mudanças**:

1. **Importar composable**:
```vue
<script setup>
import { useTheme } from '@/composables/useTheme';
const { isDarkMode, toggleTheme } = useTheme();
// REMOVER: local isDarkMode ref e funções de tema
</script>
```

2. **Converter CSS hardcoded**:

| CSS Hardcoded | Dark Mode | Light Mode |
|---------------|-----------|------------|
| `background: #ffffff` | `bg-slate-900` | `bg-white` |
| `color: #1e293b` | `text-slate-200` | `text-slate-900` |
| `border: #e2e8f0` | `border-slate-700` | `border-slate-200` |
| Search `bg: #ffffff` | `bg-slate-800 text-slate-200` | `bg-white text-slate-900` |

---

### 2.3 AuthenticatedLayout.vue

**Arquivo**: `/SDC/resources/js/Layouts/AuthenticatedLayout.vue`

**Mudanças**:

```vue
<!-- ANTES -->
<style scoped>
.layout-container { background: #f8fafc; }
.content-wrapper { background: #0f172a; }
</style>

<!-- DEPOIS -->
<div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
  <main class="bg-slate-50 dark:bg-slate-950">
    <slot />
  </main>
  <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
    <!-- ... -->
  </footer>
</div>
```

---

### 2.4 NavItem.vue

**Arquivo**: `/SDC/resources/js/Components/NavItem.vue`

**Adicionar variants light**:

```vue
<Link :class="[
  'flex items-center gap-3 px-5 py-3 transition-all',
  'dark:text-white/80 dark:hover:bg-white/5',
  'text-slate-700 hover:bg-slate-50 hover:text-slate-900',
  {
    'dark:bg-blue-500/15 dark:text-blue-400': active,
    'bg-blue-50 text-blue-600': active,
  }
]">
```

---

## Fase 3: Atomic Design (2 dias)

### 3.1 Tipografia

**Arquivos**:
- `/SDC/resources/js/Components/Atoms/Typography/Heading.vue`
- `/SDC/resources/js/Components/Atoms/Typography/Text.vue`

**Heading.vue**:
```javascript
const colorClasses = {
  default: 'text-slate-200 dark:text-slate-200 light:text-slate-900',
  primary: 'text-blue-400 dark:text-blue-400 light:text-blue-600',
  muted: 'text-slate-400 dark:text-slate-400 light:text-slate-600',
};
```

**Text.vue**:
```javascript
const colorClasses = {
  default: 'text-slate-300 dark:text-slate-300 light:text-slate-700',
  muted: 'text-slate-500 dark:text-slate-500 light:text-slate-500',
  primary: 'text-blue-400 dark:text-blue-400 light:text-blue-600',
  success: 'text-emerald-400 dark:text-emerald-400 light:text-emerald-600',
  warning: 'text-amber-400 dark:text-amber-400 light:text-amber-600',
  danger: 'text-red-400 dark:text-red-400 light:text-red-600',
};
```

---

### 3.2 Cards

**Arquivos**:
- `/SDC/resources/js/Components/Atoms/Card/CardBase.vue`
- `/SDC/resources/js/Components/Molecules/Statistics/StatCard.vue`

**CardBase.vue**:
```javascript
const variantClasses = {
  default: 'bg-slate-800/80 border-slate-700/50 dark:bg-slate-800/80 dark:border-slate-700/50 light:bg-white light:border-slate-200',
  info: 'bg-cyan-500/10 border-cyan-500/30 dark:bg-cyan-500/10 dark:border-cyan-500/30 light:bg-cyan-50 light:border-cyan-200',
  success: 'bg-emerald-500/10 border-emerald-500/30 light:bg-emerald-50 light:border-emerald-200',
  warning: 'bg-amber-500/10 border-amber-500/30 light:bg-amber-50 light:border-amber-200',
  danger: 'bg-red-500/10 border-red-500/30 light:bg-red-50 light:border-red-200',
};
```

**StatCard.vue**:
```javascript
const cardClasses = computed(() => {
  return 'bg-slate-900/60 dark:bg-slate-900/60 light:bg-white border ...';
});

const variantBorderClasses = {
  info: 'border-cyan-500/25 dark:border-cyan-500/25 light:border-cyan-200',
  success: 'border-emerald-500/25 light:border-emerald-200',
  warning: 'border-amber-500/25 light:border-amber-200',
  danger: 'border-red-500/25 light:border-red-200',
};
```

---

### 3.3 Badges

**Arquivo**: `/SDC/resources/js/Components/Atoms/Badge/Badge.vue`

```javascript
const variantClasses = {
  info: 'bg-cyan-500/20 text-cyan-400 dark:bg-cyan-500/20 dark:text-cyan-400 light:bg-cyan-100 light:text-cyan-700',
  success: 'bg-emerald-500/20 text-emerald-400 light:bg-emerald-100 light:text-emerald-700',
  warning: 'bg-amber-500/20 text-amber-400 light:bg-amber-100 light:text-amber-700',
  danger: 'bg-red-500/20 text-red-400 light:bg-red-100 light:text-red-700',
};
```

---

### 3.4 Forms

**Arquivos**:
- `/SDC/resources/js/Components/Atoms/Input/TextInput.vue`
- `/SDC/resources/js/Components/Atoms/Input/SelectInput.vue`
- `/SDC/resources/js/Components/Atoms/Button/Button.vue`

**TextInput.vue**:
```javascript
const bgClass = props.readonly
  ? 'bg-slate-800/50 dark:bg-slate-800/50 light:bg-slate-50'
  : 'bg-slate-900/50 dark:bg-slate-900/50 light:bg-white';

const borderClass = 'border-slate-700 dark:border-slate-700 light:border-slate-300';
const textClass = 'text-slate-200 dark:text-slate-200 light:text-slate-900';
```

---

### 3.5 Tables

**Arquivos**:
- `/SDC/resources/js/Components/Atoms/Table/TableHeader.vue`
- `/SDC/resources/js/Components/Atoms/Table/TableRow.vue`
- `/SDC/resources/js/Components/Atoms/Table/TableCell.vue`

```vue
<!-- TableHeader.vue -->
<th class="bg-slate-800/50 text-slate-300 border-b border-slate-700
          dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700
          light:bg-slate-50 light:text-slate-700 light:border-slate-200">

<!-- TableRow.vue -->
<tr class="border-b border-slate-700/50 hover:bg-slate-800/30
          dark:border-slate-700/50 dark:hover:bg-slate-800/30
          light:border-slate-200 light:hover:bg-slate-50">
```

---

## Fase 4: Organisms (2 dias)

### 4.1 Filtros

**Arquivos**:
- `/SDC/resources/js/Components/Molecules/Filter/FilterSection.vue`
- `/SDC/resources/js/Components/Molecules/Filter/FilterActions.vue`

**Adicionar dark: variants** em todos os backgrounds, borders, text colors.

---

### 4.2 Tabelas

**Arquivos**:
- `/SDC/resources/js/Components/Organisms/Rat/Table/RatTable.vue`
- `/SDC/resources/js/Components/Organisms/Decretacoes/ProcessoTable.vue`

Garantir que usam componentes atômicos de Table (já terão dark mode).

---

## Fase 5: Páginas e Módulos (2 dias)

### 5.1 Dashboard

**Arquivo**: `/SDC/resources/js/Pages/Dashboard.vue`

```vue
<!-- ANTES -->
<div class="min-h-screen bg-gray-100">

<!-- DEPOIS -->
<div class="min-h-screen bg-slate-50 dark:bg-slate-950">
```

---

### 5.2 Módulos

Aplicar dark: variants em todos os templates:

**Decretações**:
- `/SDC/resources/js/Templates/Decretacoes/ProcessoIndexTemplate.vue`
- `/SDC/resources/js/Pages/Decretacoes/ProcessoShow.vue`
- Todos os componentes em `/SDC/resources/js/Components/Organisms/Decretacoes/`
- Todos os badges em `/SDC/resources/js/Components/Molecules/Decretacoes/`

**RAT**:
- `/SDC/resources/js/Templates/Rat/RatIndexTemplate.vue`
- `/SDC/resources/js/Pages/Rat.vue`
- `/SDC/resources/js/Components/Organisms/Rat/`

**PAE**:
- `/SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue`
- `/SDC/resources/js/Components/Organisms/Pae/`

**TDAP**:
- `/SDC/resources/js/Templates/Tdap/` (todos os templates)

**Demandas**:
- `/SDC/resources/js/Templates/Demandas/DemandasIndexTemplate.vue`
- `/SDC/resources/js/Components/Organisms/Demandas/`

**Admin**:
- `/SDC/resources/js/Pages/Admin/` (todos os índices)

---

## Fase 6: Testes (1 dia)

### Checklist de Validação

**Theme Toggle**:
- [ ] Clicar no toggle alterna corretamente
- [ ] localStorage persiste preferência
- [ ] Refresh mantém tema
- [ ] System preference respeitada se não houver localStorage

**Layouts**:
- [ ] Sidebar renderiza corretamente nos 2 temas
- [ ] TopBar legível nos 2 temas
- [ ] Footer visível nos 2 temas

**Páginas (testar todas)**:
- [ ] Dashboard
- [ ] Decretações (grid/tabela)
- [ ] RAT (forms, tabelas)
- [ ] PAE (cards, modals)
- [ ] TDAP (dashboard, produtos)
- [ ] Demandas (lista, stats)
- [ ] Admin (users, roles)

**Componentes**:
- [ ] Forms: inputs, selects, date pickers legíveis
- [ ] Botões: todos os variants claros
- [ ] Badges: status badges legíveis
- [ ] Tabelas: headers, rows, hover funcionando
- [ ] Cards: variants (info/success/warning/danger) distintos
- [ ] Modais: overlays apropriados

**Mobile**:
- [ ] Light mode responsivo
- [ ] Dark mode responsivo
- [ ] Sidebar colapsada funciona

---

## Estratégia de Rollout

**RECOMENDADO: Rollout Atômico**

1. **Semana 1**: Fase 1-2 (Infra + Layouts) → Deploy staging
2. **Semana 2**: Fase 3 (Atoms) → Deploy staging
3. **Semana 3**: Fase 4-5 (Organisms + Páginas) → Deploy staging
4. **Semana 4**: Testes + Produção

**Vantagens**:
- Menor risco
- Validação incremental
- Feedback dos usuários por fase

**Tema Padrão**: **Dark Mode** (manter atual)
- Usuários já acostumados
- Professional para dashboards gov/enterprise
- Light mode como opt-in

---

## Mitigação de Riscos

### 1. Quebrar Layout
- **Solução**: Testar cada componente isoladamente antes de integrar
- Manter `<style scoped>` temporariamente, adicionar Tailwind, depois remover CSS

### 2. Performance
- **Solução**: Tailwind purge remove classes não-usadas automaticamente
- Monitorar bundle size com `npm run build`

### 3. Theme Inconsistente
- **Solução**: useTheme composable = fonte única de verdade
- Evitar inline styles com hex colors
- ESLint rule para detectar hardcoded colors

### 4. Third-party Components
- **Solução**: Identificar bibliotecas externas (charts, date pickers)
- Verificar suporte a dark mode
- Wrappear com CSS customizado se necessário

---

## Arquivos Críticos (Top 5)

1. **`/SDC/resources/js/composables/useTheme.js`** (CRIAR) - Infraestrutura central
2. **`/SDC/resources/js/Components/Sidebar.vue`** - Layout crítico (200+ linhas CSS)
3. **`/SDC/resources/js/Components/TopBar.vue`** - Toggle + integração useTheme
4. **`/SDC/resources/js/Layouts/AuthenticatedLayout.vue`** - Foundation de todos os layouts
5. **`/SDC/resources/js/Components/Atoms/Card/CardBase.vue`** - Usado em 72+ componentes

---

## Timeline

| Fase | Duração | Validação |
|------|---------|-----------|
| Fase 1 | 1 dia | Theme toggle global |
| Fase 2 | 2 dias | Layouts nos 2 temas |
| Fase 3 | 2 dias | Atoms nos 2 temas |
| Fase 4 | 2 dias | Organisms funcionais |
| Fase 5 | 2 dias | App completo nos 2 temas |
| Fase 6 | 1 dia | Testes + bug fixes |
| **TOTAL** | **10 dias** | Pronto para produção |

---

## Próximos Passos

1. Aprovar este plano
2. Criar branch `feature/dark-mode`
3. Iniciar Fase 1 (useTheme composable)
4. Testar toggle funciona globalmente
5. Avançar para Fase 2 (refatorar layouts)
