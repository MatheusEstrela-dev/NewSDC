<template>
  <div class="rat-card">
    <!-- Header -->
    <div class="rat-card-header">
      <div class="rat-card-title-section">
        <h3 class="rat-card-number">{{ rat.numero_rat }}</h3>
        <p class="rat-card-subtitle">{{ rat.municipio || 'Sem município' }}</p>
      </div>
      <div class="rat-card-year">
        <span class="year-badge">{{ rat.ano }}</span>
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
        <span class="field-value">{{ rat.usuario_nome || '-' }}</span>
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="rat-card-footer">
      <button
        type="button"
        class="rat-card-action rat-card-action-view"
        title="Visualizar"
        @click="$emit('view', rat.id)"
      >
        <EyeIcon class="w-6 h-6" />
        <span>Ver</span>
      </button>

      <button
        type="button"
        class="rat-card-action rat-card-action-print"
        title="Imprimir Boletim"
        @click="$emit('print', rat.id)"
      >
        <PrinterIcon class="w-6 h-6" />
        <span>Imprimir</span>
      </button>

      <button
        type="button"
        class="rat-card-action rat-card-action-edit"
        title="Editar"
        @click="$emit('edit', rat.id)"
      >
        <PencilIcon class="w-6 h-6" />
        <span>Editar</span>
      </button>

      <button
        v-if="rat.has_attachments"
        type="button"
        class="rat-card-action rat-card-action-attachments"
        title="Anexos"
        @click="$emit('attachments', rat.id)"
      >
        <PaperClipIcon class="w-6 h-6" />
        <span>Anexos</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import StatusBadge from '@/Components/Atoms/Badge/StatusBadge.vue';
import EyeIcon from '@/Components/Icons/EyeIcon.vue';
import PrinterIcon from '@/Components/Icons/PrinterIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';

const props = defineProps({
  rat: {
    type: Object,
    required: true,
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

.rat-card-action {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.875rem;
  border: none;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  min-height: 36px;
}

.rat-card-action-view {
  background: #dbeafe;
  color: #1e40af;
}

.rat-card-action-view:hover {
  background: #bfdbfe;
}

.rat-card-action-view:active {
  background: #93c5fd;
}

@media (prefers-color-scheme: dark) {
  .rat-card-action-view {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
  }

  .rat-card-action-view:hover {
    background: rgba(59, 130, 246, 0.25);
  }
}

.rat-card-action-print {
  background: #e0f2fe;
  color: #0369a1;
}

.rat-card-action-print:hover {
  background: #bae6fd;
}

.rat-card-action-print:active {
  background: #7dd3fc;
}

@media (prefers-color-scheme: dark) {
  .rat-card-action-print {
    background: rgba(14, 165, 233, 0.15);
    color: #38bdf8;
  }

  .rat-card-action-print:hover {
    background: rgba(14, 165, 233, 0.25);
  }
}

.rat-card-action-edit {
  background: #fef3c7;
  color: #92400e;
}

.rat-card-action-edit:hover {
  background: #fde68a;
}

.rat-card-action-edit:active {
  background: #fcd34d;
}

@media (prefers-color-scheme: dark) {
  .rat-card-action-edit {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
  }

  .rat-card-action-edit:hover {
    background: rgba(245, 158, 11, 0.25);
  }
}

.rat-card-action-attachments {
  background: #d1fae5;
  color: #065f46;
}

.rat-card-action-attachments:hover {
  background: #a7f3d0;
}

.rat-card-action-attachments:active {
  background: #6ee7b7;
}

@media (prefers-color-scheme: dark) {
  .rat-card-action-attachments {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
  }

  .rat-card-action-attachments:hover {
    background: rgba(16, 185, 129, 0.25);
  }
}
</style>
