<template>
  <div class="rat-card">
    <!-- Header -->
    <div class="rat-card-header">
      <div class="rat-card-title-section">
        <h3 class="rat-card-number">{{ rat.numero_bos || `RAT #${rat.id}` }}</h3>
        <p class="rat-card-subtitle">{{ rat.dados_gerais?.municipio || rat.local?.municipio || 'Sem município' }}</p>
      </div>
      <div class="rat-card-year">
        <span class="year-badge">{{ getYear(rat.created_at) }}</span>
      </div>
    </div>

    <!-- Body -->
    <div class="rat-card-body">
      <div class="rat-card-field">
        <span class="field-label">Data/Hora</span>
        <span class="field-value">{{ formatDateTime(rat.created_at) }}</span>
      </div>

      <div class="rat-card-field">
        <span class="field-label">Status</span>
        <span class="field-value">
          <StatusBadge :status="rat.status" />
        </span>
      </div>

      <div class="rat-card-field rat-card-field-full">
        <span class="field-label">Criado por</span>
        <span class="field-value">{{ rat.criado_por || 'Sistema' }}</span>
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="rat-card-footer">
<<<<<<< Updated upstream
      <ActionButton
        module="rat"
        resource="protocolos"
=======
      <TableActions
        :show-print="true"
        :show-edit="canEdit"
        :show-attachments="true"
        :show-delete="canDelete"
        attachments-label="Relacionar"
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        size="md"
        :actions="[
          { action: 'view',        handler: () => $emit('view', rat.id) },
          { action: 'print',       handler: () => $emit('print', rat.id) },
          { action: 'edit',        handler: () => $emit('edit', rat.id),        allowed: canEdit },
          { action: 'attachments', handler: () => $emit('attachments', rat.id), label: 'Relacionar' },
          { action: 'delete',      handler: () => $emit('delete', rat.id),      allowed: canDelete },
        ]"
      />
    </div>
  </div>
</template>

<script setup>
import StatusBadge from '@/Components/Atoms/Badge/StatusBadge.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';

const props = defineProps({
  rat: {
    type: Object,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['view', 'print', 'edit', 'attachments', 'delete']);


const formatDateTime = (dateTime) => {
  if (!dateTime) return '-';
  const date = new Date(dateTime);
  return date.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

function getYear(date) {
  if (!date) return new Date().getFullYear();
  return new Date(date).getFullYear();
}
</script>

<style scoped>
.rat-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.rat-card:active {
  transform: scale(0.98);
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .rat-card {
    background: #1e293b;
    border-color: #334155;
  }
}

/* Header */
.rat-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

@media (prefers-color-scheme: dark) {
  .rat-card-header {
    background: #0f172a;
    border-bottom-color: #334155;
  }
}

.rat-card-title-section {
  flex: 1;
  min-width: 0;
}

.rat-card-number {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (prefers-color-scheme: dark) {
  .rat-card-number {
    color: #f1f5f9;
  }
}

.rat-card-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.25rem 0 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (prefers-color-scheme: dark) {
  .rat-card-subtitle {
    color: #94a3b8;
  }
}

.rat-card-year {
  flex-shrink: 0;
}

.year-badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  background: #3b82f6;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 6px;
}

/* Body */
.rat-card-body {
  padding: 1rem;
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
}

@media (min-width: 375px) {
  .rat-card-body {
    grid-template-columns: 1fr 1fr;
  }
}

.rat-card-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.rat-card-field-full {
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
.rat-card-footer {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.5rem;
  flex-wrap: wrap;
}

@media (prefers-color-scheme: dark) {
  .rat-card-footer {
    background: #0f172a;
    border-top-color: #334155;
  }
}

</style>
