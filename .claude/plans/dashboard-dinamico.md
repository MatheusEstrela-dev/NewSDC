# Plano de Implementacao - Dashboard Dinamico

## Resumo
Implementar melhorias dinamicas no Dashboard inicial seguindo as diretrizes do task.md, utilizando Vue.js + Tailwind CSS + ApexCharts.

## Analise do Estado Atual

### O que ja existe (Dashboard.vue):
1. Cards com hover effects (translate-y, shadow, border-color)
2. Sparklines SVG com animacao de glow basica
3. ApexCharts integrado para graficos de barra e area
4. Donut chart customizado com CSS
5. Timeline com TransitionGroup
6. Indicadores rapidos com barras de progresso

### O que ja existe (NavItem.vue):
1. Barra vertical animada no hover (::before pseudo-element)
2. Transicoes de cor e padding
3. Estados ativos bem definidos

---

## Melhorias a Implementar

### 1. Animacoes de Entrada nos Cards (Staggered Animation)
**Arquivo:** `Dashboard.vue`
**Descricao:** Adicionar animacoes de entrada escalonadas quando o dashboard carrega

```vue
<!-- Adicionar v-for com delay dinamico -->
<div
  v-for="(metric, index) in metrics"
  :key="metric.title"
  :style="{ animationDelay: `${index * 100}ms` }"
  class="animate-fade-in-up"
>
```

**CSS a adicionar:**
```css
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fadeInUp 0.5s ease-out forwards;
  opacity: 0;
}
```

---

### 2. Aprimorar Sparklines com Animacao de "Corrida"
**Arquivo:** `Dashboard.vue`
**Descricao:** Adicionar efeito de stroke-dasharray animado nas linhas dos sparklines

```css
.trend-line-path {
  stroke-dasharray: 200;
  stroke-dashoffset: 200;
  animation: drawLine 1.5s ease-out forwards;
}

@keyframes drawLine {
  to {
    stroke-dashoffset: 0;
  }
}

.group:hover .trend-line-path {
  animation: line-glow 1.5s infinite alternate, drawLine 1.5s ease-out forwards;
}
```

---

### 3. Donut Chart com Efeito de "Explosao" nas Fatias
**Arquivo:** `Dashboard.vue`
**Descricao:** Ao hover em uma fatia da legenda, a fatia correspondente "pula" para fora

```vue
<!-- Adicionar interatividade na legenda -->
<div
  v-for="(mod, index) in moduleDistribution"
  @mouseenter="hoveredSegment = index"
  @mouseleave="hoveredSegment = null"
  :class="{ 'scale-105': hoveredSegment === index }"
>
```

**CSS:**
```css
.donut-segment {
  transition: transform 0.3s ease, filter 0.3s ease;
  transform-origin: center;
}

.donut-segment.is-hovered {
  transform: scale(1.08);
  filter: brightness(1.2) drop-shadow(0 0 10px currentColor);
}
```

---

### 4. Crosshair Dinamico no Grafico de Tendencias (ApexCharts)
**Arquivo:** `Dashboard.vue`
**Descricao:** Adicionar crosshair e tooltips dinamicos ao grafico de tendencias

```javascript
const trendChartOptions = computed(() => ({
  chart: {
    // ... existente
  },
  tooltip: {
    enabled: true,
    shared: true,
    intersect: false,
    followCursor: true,
    theme: 'dark',
    x: { show: true },
    marker: { show: true }
  },
  crosshairs: {
    show: true,
    width: 1,
    stroke: { color: '#94a3b8', width: 1, dashArray: 4 }
  }
}));
```

---

### 5. Barras de Progresso Animadas (Quick Stats)
**Arquivo:** `Dashboard.vue`
**Descricao:** Animar as barras de progresso ao entrar na viewport

```vue
<script setup>
import { useIntersectionObserver } from '@vueuse/core';

const quickStatsRef = ref(null);
const isQuickStatsVisible = ref(false);

useIntersectionObserver(quickStatsRef, ([{ isIntersecting }]) => {
  if (isIntersecting) isQuickStatsVisible.value = true;
});
</script>

<template>
  <div ref="quickStatsRef">
    <div
      class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-cyan-400"
      :style="{ width: isQuickStatsVisible ? '16.6%' : '0%' }"
      :class="{ 'transition-all duration-1000': isQuickStatsVisible }"
    />
  </div>
</template>
```

---

### 6. Timeline com Efeito de "Pulse" no Indicador Tempo Real
**Arquivo:** `Dashboard.vue`
**Descricao:** Melhorar o indicador de "Tempo real" com pulse mais visivel

```css
.realtime-indicator {
  position: relative;
}

.realtime-indicator::before {
  content: '';
  position: absolute;
  width: 10px;
  height: 10px;
  background: #10b981;
  border-radius: 50%;
  animation: pulse-ring 1.5s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
}

@keyframes pulse-ring {
  0% {
    transform: scale(0.8);
    opacity: 1;
  }
  100% {
    transform: scale(2.5);
    opacity: 0;
  }
}
```

---

### 7. Sidebar - Aprimorar Feedback Visual
**Arquivo:** `NavItem.vue`
**Descricao:** Adicionar efeito de ripple ao clicar e melhorar brilho da barra

**Ja implementado:** A barra vertical animada ja existe (`::before` com `width: 0 -> 4px`)

**Melhoria adicional - Ripple effect:**
```vue
<script setup>
function handleClick(event) {
  createRipple(event);
  if (onNavItemClick) onNavItemClick();
}

function createRipple(event) {
  const button = event.currentTarget;
  const circle = document.createElement('span');
  const diameter = Math.max(button.clientWidth, button.clientHeight);
  circle.style.width = circle.style.height = `${diameter}px`;
  circle.style.left = `${event.clientX - button.offsetLeft - diameter / 2}px`;
  circle.style.top = `${event.clientY - button.offsetTop - diameter / 2}px`;
  circle.classList.add('ripple');
  button.appendChild(circle);
  setTimeout(() => circle.remove(), 600);
}
</script>

<style>
.ripple {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: scale(0);
  animation: ripple-animation 0.6s linear;
  pointer-events: none;
}

@keyframes ripple-animation {
  to {
    transform: scale(4);
    opacity: 0;
  }
}
</style>
```

---

## Ordem de Implementacao

1. **Animacoes de entrada nos cards** (impacto visual imediato)
2. **Sparklines animados** (melhora percepcao de dinamismo)
3. **Donut interativo** (feedback visual na distribuicao)
4. **Barras de progresso animadas** (scroll reveal)
5. **Timeline pulse** (reforco visual tempo real)
6. **Crosshair ApexCharts** (interatividade nos graficos)
7. **Sidebar ripple** (feedback tactil)

---

## Dependencias Necessarias

Verificar se `@vueuse/core` esta instalado para usar `useIntersectionObserver`:
```bash
npm install @vueuse/core
```

---

## Arquivos a Modificar

| Arquivo | Tipo de Mudanca |
|---------|-----------------|
| `resources/js/Pages/Dashboard.vue` | Adicionar CSS animations e logica de scroll reveal |
| `resources/js/Components/NavItem.vue` | Adicionar ripple effect (opcional) |

---

## Estimativa

- Implementacao completa: ~2-3 horas
- Impacto: Alto (UX/UI)
- Risco: Baixo (apenas CSS e logica de animacao)
