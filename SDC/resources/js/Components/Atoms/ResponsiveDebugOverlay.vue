<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useMobile } from '@/Composables/mobile/useMobile';

const props = defineProps({
  enabled: {
    type: Boolean,
    default: false,
  },
});

const { isMobile, isTablet, isDesktop, screenWidth } = useMobile();
const violations = ref([]);
const minimized = ref(false);

const breakpointLabel = computed(() => {
  if (isMobile.value) return 'mobile (<768)';
  if (isTablet.value) return 'tablet (768-1023)';
  if (isDesktop.value) return 'desktop (>=1024)';
  return 'unknown';
});

const breakpointColor = computed(() => {
  if (isMobile.value) return '#ef4444';
  if (isTablet.value) return '#f59e0b';
  if (isDesktop.value) return '#10b981';
  return '#6b7280';
});

let observer = null;

function scanViolations() {
  if (typeof document === 'undefined') return;
  const found = [];
  const bareTables = document.querySelectorAll('table');
  bareTables.forEach((t) => {
    const inResponsive = t.closest('[data-responsive="true"]');
    if (!inResponsive) {
      found.push({
        type: 'bare-table',
        element: tagPath(t),
        message: '<table> fora de <ResponsiveTable>',
      });
    }
  });
  const overflowOnly = document.querySelectorAll('.overflow-x-auto > table');
  overflowOnly.forEach((t) => {
    const inResponsive = t.closest('[data-responsive="true"]');
    if (!inResponsive) {
      found.push({
        type: 'overflow-only',
        element: tagPath(t),
        message: 'tabela usa apenas overflow-x-auto como estrategia mobile',
      });
    }
  });
  violations.value = found;
}

function tagPath(el) {
  const parts = [];
  let cur = el;
  let depth = 0;
  while (cur && depth < 5) {
    let part = cur.tagName.toLowerCase();
    if (cur.id) part += `#${cur.id}`;
    else if (cur.className && typeof cur.className === 'string') {
      const cls = cur.className.split(/\s+/).slice(0, 2).join('.');
      if (cls) part += `.${cls}`;
    }
    parts.unshift(part);
    cur = cur.parentElement;
    depth += 1;
  }
  return parts.join(' > ');
}

onMounted(() => {
  if (!props.enabled || typeof window === 'undefined') return;
  scanViolations();
  observer = new MutationObserver(() => {
    scanViolations();
  });
  observer.observe(document.body, { childList: true, subtree: true });
});

onUnmounted(() => {
  if (observer) {
    observer.disconnect();
    observer = null;
  }
});
</script>

<template>
  <div
    v-if="enabled"
    class="responsive-debug-overlay"
    :class="{ minimized }"
  >
    <button
      class="rdo-toggle"
      type="button"
      @click="minimized = !minimized"
      :title="minimized ? 'Expandir' : 'Minimizar'"
    >
      {{ minimized ? '+' : '-' }}
    </button>

    <div v-if="!minimized" class="rdo-content">
      <div class="rdo-row">
        <span class="rdo-label">Viewport:</span>
        <span class="rdo-value">{{ screenWidth }}px</span>
      </div>
      <div class="rdo-row">
        <span class="rdo-label">Breakpoint:</span>
        <span class="rdo-pill" :style="{ backgroundColor: breakpointColor }">
          {{ breakpointLabel }}
        </span>
      </div>
      <div class="rdo-row">
        <span class="rdo-label">Violacoes:</span>
        <span class="rdo-value" :class="{ 'rdo-error': violations.length > 0 }">
          {{ violations.length }}
        </span>
      </div>
      <ul v-if="violations.length > 0" class="rdo-violations">
        <li v-for="(v, i) in violations" :key="i" class="rdo-violation">
          <strong>{{ v.type }}</strong>
          <div class="rdo-violation-element">{{ v.element }}</div>
          <div class="rdo-violation-message">{{ v.message }}</div>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.responsive-debug-overlay {
  position: fixed;
  bottom: 16px;
  right: 16px;
  z-index: 9999;
  background: rgba(15, 23, 42, 0.95);
  color: #f1f5f9;
  font-family: ui-monospace, SFMono-Regular, monospace;
  font-size: 12px;
  padding: 12px 14px;
  border-radius: 8px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  max-width: 320px;
  max-height: 400px;
  overflow-y: auto;
}

.responsive-debug-overlay.minimized {
  padding: 4px 8px;
  max-width: 32px;
  max-height: 32px;
}

.rdo-toggle {
  position: absolute;
  top: 4px;
  right: 4px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: inherit;
  width: 22px;
  height: 22px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.rdo-content {
  margin-top: 6px;
  margin-right: 28px;
}

.rdo-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.rdo-label {
  color: #94a3b8;
  font-weight: 500;
  flex-shrink: 0;
}

.rdo-value {
  font-weight: 600;
}

.rdo-pill {
  padding: 2px 8px;
  border-radius: 12px;
  color: white;
  font-size: 11px;
  font-weight: 600;
}

.rdo-error {
  color: #ef4444;
}

.rdo-violations {
  margin: 6px 0 0;
  padding: 0;
  list-style: none;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 6px;
}

.rdo-violation {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 4px;
  padding: 6px 8px;
  margin-bottom: 4px;
}

.rdo-violation strong {
  color: #fca5a5;
  text-transform: uppercase;
  font-size: 10px;
}

.rdo-violation-element {
  margin-top: 2px;
  font-size: 10px;
  color: #cbd5e1;
  word-break: break-all;
}

.rdo-violation-message {
  margin-top: 2px;
  color: #f1f5f9;
}
</style>
