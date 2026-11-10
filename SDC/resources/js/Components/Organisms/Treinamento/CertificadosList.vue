<script setup>
import { markRaw } from 'vue';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';
import { route } from 'ziggy-js';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

const props = defineProps({
  certificados: {
    type: Array,
    default: () => [],
  },
  canReemitir: { type: Boolean, default: false },
});

const emit = defineEmits(['reemitir']);

const ReemitirIcon = markRaw(ArrowPathIcon);

// Permissao ja decidida pelo back (disponivel / canReemitir): `allowed` explicito
// sem module/resource, para o ActionButton nao montar um slug que nao existe.
const acoes = (certificado) => [
  {
    action: 'print',
    label: 'Ver / Imprimir certificado',
    allowed: Boolean(certificado.disponivel),
    handler: () => window.open(route('treinamentos.certificados.imprimir', certificado.id), '_blank', 'noopener'),
  },
  {
    action: 'edit',
    label: 'Reemitir certificado',
    icon: ReemitirIcon,
    allowed: props.canReemitir,
    handler: () => emit('reemitir', certificado),
  },
];
</script>

<template>
  <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
      <thead class="bg-slate-50 dark:bg-slate-800/60">
        <tr class="text-left text-slate-500 dark:text-slate-400">
          <th class="px-4 py-2 font-medium">Participante</th>
          <th class="px-4 py-2 font-medium">Status</th>
          <th class="px-4 py-2 font-medium">Emitido em</th>
          <th class="px-4 py-2 text-right font-medium">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <tr v-for="certificado in certificados" :key="certificado.id" class="text-slate-700 dark:text-slate-300">
          <td class="px-4 py-2.5 font-medium">{{ certificado.inscrito_nome || '—' }}</td>
          <td class="px-4 py-2.5"><Badge :color="certificado.status_color">{{ certificado.status_label }}</Badge></td>
          <td class="px-4 py-2.5">{{ certificado.emitido_em ? new Date(certificado.emitido_em).toLocaleDateString('pt-BR') : '—' }}</td>
          <td class="px-4 py-2.5">
            <div class="flex justify-end">
              <ActionButton size="sm" :actions="acoes(certificado)" />
            </div>
          </td>
        </tr>
        <tr v-if="certificados.length === 0">
          <td colspan="4" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">Nenhum certificado emitido ainda.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
