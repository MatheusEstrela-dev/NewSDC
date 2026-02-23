<template>
  <div class="table-mobile-card">
    <!-- Header: Título principal e ações -->
    <div class="card-header">
      <div class="card-title-section">
        <h3 v-if="title" class="card-title">{{ title }}</h3>
        <p v-if="subtitle" class="card-subtitle">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="card-actions">
        <slot name="actions"></slot>
      </div>
    </div>

    <!-- Body: Lista de campos -->
    <div class="card-body">
      <div
        v-for="field in fields"
        :key="field.key"
        class="card-field"
        :class="{ 'card-field-full': field.fullWidth }"
      >
        <span class="field-label">{{ field.label }}</span>
        <span class="field-value" :class="field.valueClass">
          <slot :name="`field-${field.key}`" :value="getFieldValue(field.key)">
            {{ getFieldValue(field.key) }}
          </slot>
        </span>
      </div>
    </div>

    <!-- Footer: Ações secundárias -->
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  /**
   * Título principal do card
   */
  title: {
    type: String,
    default: '',
  },
  /**
   * Subtítulo ou informação secundária
   */
  subtitle: {
    type: String,
    default: '',
  },
  /**
   * Objeto com os dados a serem exibidos
   */
  data: {
    type: Object,
    required: true,
  },
  /**
   * Array de campos a serem exibidos
   * Formato: [{ key: 'id', label: 'ID', fullWidth: false, valueClass: '' }]
   */
  fields: {
    type: Array,
    required: true,
    validator: (fields) => {
      return fields.every(
        (field) => field.key && field.label
      );
    },
  },
  /**
   * Variante de cor do card
   */
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'primary', 'success', 'warning', 'danger'].includes(value),
  },
});

const getFieldValue = (key) => {
  return props.data[key] ?? '-';
};
</script>

<style scoped>
.table-mobile-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table-mobile-card:active {
  transform: scale(0.98);
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .table-mobile-card {
    background: #1e293b;
    border-color: #334155;
  }
}

/* Header */
.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

@media (prefers-color-scheme: dark) {
  .card-header {
    background: #0f172a;
    border-bottom-color: #334155;
  }
}

.card-title-section {
  flex: 1;
  min-width: 0;
}

.card-title {
  font-size: 1rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (prefers-color-scheme: dark) {
  .card-title {
    color: #f1f5f9;
  }
}

.card-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.25rem 0 0;
}

@media (prefers-color-scheme: dark) {
  .card-subtitle {
    color: #94a3b8;
  }
}

.card-actions {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Body */
.card-body {
  padding: 1rem;
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
}

@media (min-width: 375px) {
  .card-body {
    grid-template-columns: 1fr 1fr;
  }
}

.card-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.card-field-full {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

@media (prefers-color-scheme: dark) {
  .field-label {
    color: #94a3b8;
  }
}

.field-value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #0f172a;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (prefers-color-scheme: dark) {
  .field-value {
    color: #f1f5f9;
  }
}

/* Footer */
.card-footer {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
}

@media (prefers-color-scheme: dark) {
  .card-footer {
    background: #0f172a;
    border-top-color: #334155;
  }
}

/* Variantes */
.table-mobile-card[data-variant='primary'] {
  border-left: 4px solid #3b82f6;
}

.table-mobile-card[data-variant='success'] {
  border-left: 4px solid #10b981;
}

.table-mobile-card[data-variant='warning'] {
  border-left: 4px solid #f59e0b;
}

.table-mobile-card[data-variant='danger'] {
  border-left: 4px solid #ef4444;
}
</style>
