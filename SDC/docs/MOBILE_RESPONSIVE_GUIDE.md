# Guia de Responsividade Mobile - SDC Sistema

## 📱 Visão Geral

Este documento descreve a implementação completa de responsividade mobile para o sistema SDC, garantindo uma experiência otimizada em todos os dispositivos, desde smartphones (320px) até desktops widescreen (1920px+).

## 🎯 Objetivos Alcançados

✅ **Navegação Mobile**: Sidebar como drawer lateral com overlay
✅ **TopBar Responsivo**: Botão hamburger, ícones otimizados e search adaptativo
✅ **Cards Responsivos**: Layouts adaptativos para diferentes tamanhos de tela
✅ **Tabelas Mobile**: Conversão automática de tabelas em cards em telas pequenas
✅ **Touch-Friendly**: Botões e áreas de toque com tamanho mínimo de 44x44px
✅ **Breakpoints Customizados**: Sistema de breakpoints estendido

---

## 📐 Breakpoints

### Breakpoints Padrão

```javascript
// tailwind.config.js
{
  xs: '475px',   // Smartphones grandes
  sm: '640px',   // Tablets pequenos (portrait)
  md: '768px',   // Tablets (portrait)
  lg: '1024px',  // Tablets (landscape) / Desktop pequeno
  xl: '1280px',  // Desktop
  2xl: '1536px', // Desktop grande
  3xl: '1920px', // Widescreen
}
```

### Faixas de Dispositivos

- **Mobile Small**: 320px - 474px (iPhone SE, etc.)
- **Mobile**: 475px - 639px (iPhone 12/13/14)
- **Tablet Portrait**: 640px - 767px (iPad mini)
- **Tablet**: 768px - 1023px (iPad, tablets Android)
- **Desktop**: 1024px+ (Laptops e desktops)

---

## 🧩 Componentes Criados

### 1. HamburgerButton

Botão animado para abrir/fechar o menu mobile.

**Localização**: `Components/Atoms/Button/HamburgerButton.vue`

**Uso**:
```vue
<HamburgerButton
  :is-open="isSidebarOpen"
  @click="toggleSidebar"
  class="text-slate-700"
/>
```

**Props**:
- `isOpen` (Boolean): Estado do menu (aberto/fechado)

**Eventos**:
- `@click`: Emitido ao clicar no botão

---

### 2. TableMobileCard

Card para exibir dados de tabela em formato mobile.

**Localização**: `Components/Molecules/Table/TableMobileCard.vue`

**Uso**:
```vue
<TableMobileCard
  title="RAT #1234"
  subtitle="Município: Belo Horizonte"
  :data="item"
  :fields="[
    { key: 'protocolo', label: 'Protocolo' },
    { key: 'data', label: 'Data' },
    { key: 'status', label: 'Status', fullWidth: true }
  ]"
  variant="primary"
>
  <!-- Slot para campo customizado -->
  <template #field-status="{ value }">
    <Badge :variant="getStatusVariant(value)">
      {{ value }}
    </Badge>
  </template>

  <!-- Slot de ações -->
  <template #actions>
    <button>Ver</button>
  </template>
</TableMobileCard>
```

**Props**:
- `title` (String): Título principal do card
- `subtitle` (String): Subtítulo ou informação secundária
- `data` (Object): Objeto com os dados
- `fields` (Array): Array de campos `[{ key, label, fullWidth?, valueClass? }]`
- `variant` (String): Variante de cor (`default`, `primary`, `success`, `warning`, `danger`)

**Slots**:
- `field-{key}`: Customizar exibição de um campo específico
- `actions`: Ações no header do card
- `footer`: Conteúdo do footer

---

### 3. ResponsiveTable

Wrapper que alterna automaticamente entre tabela (desktop) e cards (mobile).

**Localização**: `Components/Organisms/Table/ResponsiveTable.vue`

**Uso**:
```vue
<ResponsiveTable
  :items="rats"
  :mobile-fields="[
    { key: 'protocolo', label: 'Protocolo' },
    { key: 'municipio', label: 'Município' },
    { key: 'data', label: 'Data' },
    { key: 'status', label: 'Status', fullWidth: true }
  ]"
  :get-item-title="(item) => `RAT #${item.protocolo}`"
  :get-item-subtitle="(item) => item.municipio"
  :loading="isLoading"
  empty-message="Nenhum RAT encontrado"
>
  <!-- Slot para tabela desktop -->
  <template #table>
    <table class="w-full">
      <thead>
        <!-- Cabeçalho da tabela -->
      </thead>
      <tbody>
        <tr v-for="item in rats" :key="item.id">
          <!-- Linhas da tabela -->
        </tr>
      </tbody>
    </table>
  </template>

  <!-- Slot para customizar campo mobile -->
  <template #mobile-status="{ item, value }">
    <Badge :variant="getStatusVariant(value)">
      {{ value }}
    </Badge>
  </template>

  <!-- Slot para ações mobile -->
  <template #mobile-actions="{ item }">
    <Link :href="route('rat.show', item.id)">
      <EyeIcon class="w-5 h-5" />
    </Link>
  </template>
</ResponsiveTable>
```

**Props**:
- `items` (Array): Array de itens a serem exibidos
- `mobileFields` (Array): Campos para os cards mobile
- `getItemTitle` (Function): Função para obter título do card
- `getItemSubtitle` (Function): Função para obter subtítulo
- `getItemKey` (Function): Função para obter chave única
- `getItemVariant` (Function): Função para obter variante de cor
- `emptyMessage` (String): Mensagem quando não há dados
- `showEmptyState` (Boolean): Mostrar estado vazio
- `loading` (Boolean): Estado de carregamento

**Slots**:
- `table`: Conteúdo da tabela desktop
- `mobile-{fieldKey}`: Customizar campo específico no mobile
- `mobile-actions`: Ações para cada card mobile
- `mobile-footer`: Footer para cada card mobile
- `empty`: Estado vazio customizado

---

## 🎨 Composables

### useMobile

Detecta o tipo de dispositivo e fornece helpers de responsividade.

**Localização**: `composables/useMobile.js`

**Uso**:
```vue
<script setup>
import { useMobile } from '@/composables/useMobile';

const { isMobile, isTablet, isDesktop } = useMobile();
</script>

<template>
  <div>
    <p v-if="isMobile">Visualização Mobile</p>
    <p v-if="isTablet">Visualização Tablet</p>
    <p v-if="isDesktop">Visualização Desktop</p>
  </div>
</template>
```

**Retorno**:
- `isMobile` (Ref<Boolean>): Tela < 768px
- `isTablet` (Ref<Boolean>): Tela entre 768px e 1024px
- `isDesktop` (Ref<Boolean>): Tela >= 1024px

---

### useSidebarMobile

Gerencia o estado da sidebar mobile.

**Uso**:
```vue
<script setup>
import { useSidebarMobile } from '@/composables/useMobile';

const {
  isSidebarOpen,
  openSidebar,
  closeSidebar,
  toggleSidebar
} = useSidebarMobile();
</script>
```

**Retorno**:
- `isSidebarOpen` (Ref<Boolean>): Estado da sidebar
- `openSidebar` (Function): Abre a sidebar
- `closeSidebar` (Function): Fecha a sidebar
- `toggleSidebar` (Function): Alterna estado da sidebar

---

## 🛠️ Utilitários CSS

### Classes Customizadas

Arquivo: `resources/css/utilities/mobile.css`

#### Touch-Friendly

```css
.touch-target    /* min: 44x44px */
.touch-target-lg /* min: 48x48px */
```

#### Container Responsivo

```css
.container-mobile /* Padding adaptativo: 1rem → 1.5rem → 2rem */
```

#### Safe Area (para dispositivos com notch)

```css
.safe-top
.safe-bottom
.safe-left
.safe-right
```

#### Altura de Viewport Mobile

```css
.h-screen-mobile      /* 100vh / 100dvh */
.min-h-screen-mobile  /* min-height: 100vh / 100dvh */
```

#### Texto Responsivo

```css
.text-responsive-xs   /* 0.75rem → 0.875rem */
.text-responsive-sm   /* 0.875rem → 1rem */
.text-responsive-base /* 1rem → 1.125rem */
```

#### Grid Responsivo

```css
.grid-auto-fit /* Auto-fit grid com mínimo de 250px */
```

#### Stack Responsivo

```css
.stack-responsive /* Vertical mobile, horizontal desktop */
```

#### Visibilidade

```css
.hide-mobile /* Ocultar em mobile, mostrar em desktop */
.show-mobile /* Mostrar apenas em mobile */
```

#### Card Mobile

```css
.card-mobile /* Padding adaptativo: 1rem → 1.5rem → 2rem */
```

#### Tabela Responsiva

```css
.table-responsive /* Scroll horizontal automático */
.table-desktop    /* Visível apenas em desktop */
.table-card-mobile /* Visível apenas em mobile */
```

#### Animações

```css
.touch-feedback /* Scale 0.95 ao tocar */
```

---

## 📦 Estrutura de Pastas

```
SDC/
├── resources/
│   ├── js/
│   │   ├── composables/
│   │   │   └── useMobile.js          ← Composable de detecção mobile
│   │   ├── Components/
│   │   │   ├── Atoms/
│   │   │   │   └── Button/
│   │   │   │       └── HamburgerButton.vue
│   │   │   ├── Molecules/
│   │   │   │   ├── Statistics/
│   │   │   │   │   └── StatCard.vue   ← Card de estatísticas (já otimizado)
│   │   │   │   └── Table/
│   │   │   │       └── TableMobileCard.vue
│   │   │   ├── Organisms/
│   │   │   │   └── Table/
│   │   │   │       └── ResponsiveTable.vue
│   │   │   ├── Sidebar.vue            ← Sidebar com drawer mobile
│   │   │   └── TopBar.vue             ← TopBar responsivo
│   │   └── Layouts/
│   │       └── AuthenticatedLayout.vue ← Layout com overlay mobile
│   └── css/
│       ├── app.css
│       └── utilities/
│           └── mobile.css             ← Utilitários mobile
├── tailwind.config.js                 ← Breakpoints customizados
└── docs/
    └── MOBILE_RESPONSIVE_GUIDE.md     ← Este documento
```

---

## 🚀 Como Adaptar uma Nova Página

### Passo 1: Adaptar Layout de Cards

Se sua página tem cards de estatísticas, eles já devem estar responsivos. Verifique se usam as classes Tailwind adequadas:

```vue
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <StatCard ... />
</div>
```

### Passo 2: Adaptar Tabelas

**Opção A: Usar ResponsiveTable (Recomendado)**

```vue
<ResponsiveTable
  :items="data"
  :mobile-fields="mobileFields"
  :get-item-title="(item) => item.nome"
>
  <template #table>
    <!-- Sua tabela desktop existente -->
  </template>
</ResponsiveTable>
```

**Opção B: Implementação Manual**

```vue
<!-- Desktop -->
<div class="hidden md:block">
  <table>...</table>
</div>

<!-- Mobile -->
<div class="block md:hidden">
  <TableMobileCard
    v-for="item in data"
    :key="item.id"
    :data="item"
    :fields="mobileFields"
  />
</div>
```

### Passo 3: Ajustar Padding/Spacing

Use classes responsivas do Tailwind:

```vue
<div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
  <!-- Conteúdo -->
</div>
```

### Passo 4: Testar em Diferentes Tamanhos

1. Abra o DevTools (F12)
2. Ative o modo responsivo (Ctrl+Shift+M)
3. Teste nos breakpoints: 375px, 640px, 768px, 1024px, 1440px

---

## ✅ Checklist de Implementação

Ao criar ou adaptar uma página, verifique:

- [ ] Layout se adapta de 320px até 1920px+
- [ ] Sidebar aparece como drawer em mobile (< 1024px)
- [ ] TopBar mostra botão hamburger em mobile
- [ ] Cards de estatísticas usam grid responsivo
- [ ] Tabelas se transformam em cards ou têm scroll horizontal
- [ ] Botões têm tamanho mínimo de 44x44px em mobile
- [ ] Textos são legíveis em todas as telas
- [ ] Padding/margins são adaptativos
- [ ] Imagens são responsivas
- [ ] Formulários são touch-friendly
- [ ] Hover effects são substituídos por active em touch devices

---

## 🎨 Boas Práticas

### 1. Mobile-First

Sempre comece o desenvolvimento pensando em mobile:

```vue
<!-- ✅ Bom -->
<div class="text-sm md:text-base lg:text-lg">

<!-- ❌ Evitar -->
<div class="text-lg lg:text-sm">
```

### 2. Touch-Friendly

Garanta áreas de toque adequadas:

```vue
<!-- ✅ Bom -->
<button class="min-w-[44px] min-h-[44px] p-3">

<!-- ❌ Evitar -->
<button class="p-1">
```

### 3. Conteúdo Adaptativo

Esconda informações secundárias em mobile:

```vue
<span class="hidden sm:inline">
  © 2025 Todos os direitos reservados
</span>
```

### 4. Imagens Responsivas

Use srcset ou classes Tailwind:

```vue
<img
  src="image.jpg"
  class="w-full h-auto"
  loading="lazy"
/>
```

### 5. Grids Responsivos

Use auto-fit quando possível:

```vue
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
```

---

## 🐛 Troubleshooting

### Problema: Sidebar não fecha ao clicar em um link mobile

**Solução**: Verifique se o `NavItem.vue` está chamando `handleClick` corretamente:

```vue
<Link @click="handleClick">
```

### Problema: Tabela não se transforma em cards no mobile

**Solução**: Certifique-se de usar as classes corretas:

```vue
<div class="table-desktop">  <!-- hidden md:block -->
<div class="table-mobile">   <!-- block md:hidden -->
```

### Problema: Overlay não aparece ao abrir sidebar mobile

**Solução**: Verifique se o `AuthenticatedLayout` tem o componente de overlay:

```vue
<Transition name="fade">
  <div
    v-if="isSidebarOpen && (isMobile || isTablet)"
    class="mobile-overlay"
    @click="closeSidebar"
  ></div>
</Transition>
```

### Problema: Botões muito pequenos em mobile

**Solução**: Use as classes de touch-target:

```vue
<button class="touch-target">
  <!-- ou -->
<button class="min-w-[44px] min-h-[44px]">
```

---

## 📊 Performance

### Lazy Loading

Use lazy loading para imagens e componentes pesados:

```vue
<img loading="lazy" />

const HeavyComponent = defineAsyncComponent(() =>
  import('./HeavyComponent.vue')
);
```

### Otimização de Renderização

Use `v-show` para alternar visibilidade frequentemente, `v-if` para renderização condicional:

```vue
<!-- Alternância frequente -->
<div v-show="isMobile">

<!-- Renderização condicional -->
<div v-if="shouldRenderExpensiveComponent">
```

---

## 📚 Recursos Adicionais

- [Tailwind CSS Responsive Design](https://tailwindcss.com/docs/responsive-design)
- [MDN - Mobile Web Best Practices](https://developer.mozilla.org/en-US/docs/Web/Guide/Mobile)
- [Vue.js Responsive Design Patterns](https://vuejs.org/guide/best-practices/performance.html)

---

## 🔄 Changelog

### v1.0.0 (2025-01-25)

**Adicionado**:
- ✅ Sidebar como drawer mobile com overlay
- ✅ TopBar responsivo com botão hamburger
- ✅ Componente HamburgerButton animado
- ✅ Composables useMobile e useSidebarMobile
- ✅ Componente TableMobileCard
- ✅ Componente ResponsiveTable
- ✅ Utilitários CSS mobile (mobile.css)
- ✅ Breakpoints customizados (xs, 3xl)
- ✅ Otimização de cards de estatísticas
- ✅ Dark mode support em todos os componentes
- ✅ Touch-friendly buttons (44x44px)
- ✅ Documentação completa

**Melhorado**:
- ✅ AuthenticatedLayout com suporte mobile
- ✅ Footer responsivo
- ✅ Padding/margins adaptativos
- ✅ StatCard com breakpoints responsivos

---

## 👥 Contribuindo

Ao contribuir com melhorias de responsividade:

1. Teste em múltiplos dispositivos e tamanhos
2. Siga as convenções de nomenclatura Tailwind
3. Documente novos componentes neste guia
4. Garanta compatibilidade com dark mode
5. Priorize performance (lazy loading, code splitting)

---

## 📞 Suporte

Para dúvidas ou problemas relacionados à implementação mobile, consulte:

1. Este documento
2. Código-fonte dos componentes mencionados
3. Tailwind CSS documentation

---

**Última atualização**: 25/01/2025
**Versão**: 1.0.0
**Autor**: Claude Code (Anthropic)
