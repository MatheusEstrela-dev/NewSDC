<template>
  <div class="responsive-table-container">
    <!-- Desktop: Tabela tradicional -->
    <div class="table-desktop hidden md:block">
      <slot name="table"></slot>
    </div>

    <!-- Mobile: Cards -->
    <div class="table-mobile block md:hidden">
      <div v-if="items && items.length > 0" class="mobile-cards-list">
        <TableMobileCard
          v-for="(item, index) in items"
          :key="getItemKey(item, index)"
          :title="getItemTitle(item)"
          :subtitle="getItemSubtitle(item)"
          :data="item"
          :fields="mobileFields"
          :variant="getItemVariant ? getItemVariant(item) : 'default'"
          class="mobile-card-item"
        >
          <!-- Passar slots dinâmicos para campos customizados -->
          <template v-for="field in mobileFields" #[`field-${field.key}`]="{ value }">
            <slot :name="`mobile-${field.key}`" :item="item" :value="value">
              {{ value }}
            </slot>
          </template>

          <!-- Slot de ações -->
          <template v-if="$slots['mobile-actions']" #actions>
            <slot name="mobile-actions" :item="item"></slot>
          </template>

          <!-- Slot de footer -->
          <template v-if="$slots['mobile-footer']" #footer>
            <slot name="mobile-footer" :item="item"></slot>
          </template>
        </TableMobileCard>
      </div>

      <!-- Empty State Mobile -->
      <div v-else-if="showEmptyState" class="mobile-empty-state">
        <slot name="empty">
          <div class="empty-state-content">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
              />
            </svg>
            <p class="empty-text">{{ emptyMessage }}</p>
          </div>
        </slot>
      </div>

      <!-- Loading State Mobile -->
      <div v-else-if="loading" class="mobile-loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Carregando...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import TableMobileCard from '@/Components/Molecules/Table/TableMobileCard.vue';

const props = defineProps({
  /**
   * Array de itens a serem exibidos
   */
  items: {
    type: Array,
    default: () => [],
  },
  /**
   * Campos a serem exibidos nos cards mobile
   * Formato: [{ key: 'id', label: 'ID', fullWidth: false }]
   */
  mobileFields: {
    type: Array,
    required: true,
  },
  /**
   * Função para obter o título de cada card
   */
  getItemTitle: {
    type: Function,
    default: (item) => item.title || item.name || '',
  },
  /**
   * Função para obter o subtítulo de cada card
   */
  getItemSubtitle: {
    type: Function,
    default: (item) => item.subtitle || item.description || '',
  },
  /**
   * Função para obter a chave única de cada item
   */
  getItemKey: {
    type: Function,
    default: (item, index) => item.id || index,
  },
  /**
   * Função opcional para obter a variante de cor do card
   */
  getItemVariant: {
    type: Function,
    default: null,
  },
  /**
   * Mensagem quando não há dados
   */
  emptyMessage: {
    type: String,
    default: 'Nenhum registro encontrado',
  },
  /**
   * Mostrar estado vazio
   */
  showEmptyState: {
    type: Boolean,
    default: true,
  },
  /**
   * Estado de carregamento
   */
  loading: {
    type: Boolean,
    default: false,
  },
});
</script>

<style scoped>
.responsive-table-container {
  width: 100%;
}

/* Mobile Cards List */
.mobile-cards-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.mobile-card-item {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Empty State */
.mobile-empty-state {
  padding: 3rem 1.5rem;
  text-align: center;
}

.empty-state-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.empty-icon {
  width: 64px;
  height: 64px;
  color: #cbd5e1;
}

@media (prefers-color-scheme: dark) {
  .empty-icon {
    color: #475569;
  }
}

.empty-text {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

@media (prefers-color-scheme: dark) {
  .empty-text {
    color: #94a3b8;
  }
}

/* Loading State */
.mobile-loading-state {
  padding: 3rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@media (prefers-color-scheme: dark) {
  .loading-spinner {
    border-color: #334155;
    border-top-color: #60a5fa;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

@media (prefers-color-scheme: dark) {
  .loading-text {
    color: #94a3b8;
  }
}

/* Responsive adjustments */
@media (min-width: 640px) and (max-width: 767px) {
  .mobile-cards-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }
}
</style>
