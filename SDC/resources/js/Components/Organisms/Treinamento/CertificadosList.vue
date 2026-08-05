<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  certificados: {
    type: Array,
    default: () => [],
  },
  canReemitir: { type: Boolean, default: false },
});

const emit = defineEmits(['reemitir']);
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
            <div class="flex items-center justify-end gap-3">
              <a
                v-if="certificado.disponivel"
                :href="route('treinamentos.certificados.imprimir', certificado.id)"
                target="_blank"
                class="text-xs font-medium text-blue-600 hover:underline"
              >
                Ver / Imprimir
              </a>
              <button
                v-if="canReemitir"
                type="button"
                class="text-xs font-medium text-amber-600 hover:underline"
                @click="emit('reemitir', certificado)"
              >
                Reemitir
              </button>
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
