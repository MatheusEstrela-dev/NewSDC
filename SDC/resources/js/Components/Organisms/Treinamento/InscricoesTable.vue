<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  inscricoes: {
    type: Array,
    default: () => [],
  },
  canAprovar: { type: Boolean, default: false },
  canReprovar: { type: Boolean, default: false },
  canRegistrarPresenca: { type: Boolean, default: false },
  presencaLiberada: { type: Boolean, default: false },
  moduloSelecionado: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['aprovar', 'reprovar', 'marcar-presenca']);
</script>

<template>
  <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
      <thead class="bg-slate-50 dark:bg-slate-800/60">
        <tr class="text-left text-slate-500 dark:text-slate-400">
          <th class="px-4 py-2 font-medium">Nome</th>
          <th class="px-4 py-2 font-medium">Tipo</th>
          <th class="px-4 py-2 font-medium">Status</th>
          <th class="px-4 py-2 font-medium">Frequência</th>
          <th class="px-4 py-2 text-right font-medium">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <tr v-for="inscricao in inscricoes" :key="inscricao.id" class="text-slate-700 dark:text-slate-300">
          <td class="px-4 py-2.5">
            <div class="font-medium">{{ inscricao.inscrito_nome || '—' }}</div>
            <div class="text-xs text-slate-400">{{ inscricao.inscrito_email }}</div>
          </td>
          <td class="px-4 py-2.5">
            <Badge :color="inscricao.inscrito_tipo === 'servidor' ? 'blue' : 'purple'">
              {{ inscricao.inscrito_tipo === 'servidor' ? 'Servidor' : 'Cidadão' }}
            </Badge>
          </td>
          <td class="px-4 py-2.5">
            <Badge :color="inscricao.status_color">{{ inscricao.status_label }}</Badge>
          </td>
          <td class="px-4 py-2.5">{{ inscricao.percentual_frequencia?.toFixed(0) ?? 0 }}%</td>
          <td class="px-4 py-2.5">
            <div class="flex items-center justify-end gap-2">
              <button
                v-if="canAprovar && inscricao.status === 'PENDENTE'"
                type="button"
                class="text-xs font-medium text-green-600 hover:underline"
                @click="emit('aprovar', inscricao)"
              >
                Aprovar
              </button>
              <button
                v-if="canReprovar && inscricao.status === 'PENDENTE'"
                type="button"
                class="text-xs font-medium text-red-600 hover:underline"
                @click="emit('reprovar', inscricao)"
              >
                Reprovar
              </button>
              <Button
                v-if="canRegistrarPresenca && presencaLiberada && inscricao.status === 'APROVADA'"
                variant="secondary"
                size="sm"
                :disabled="!moduloSelecionado"
                @click="emit('marcar-presenca', inscricao)"
              >
                Marcar Presença
              </Button>
            </div>
          </td>
        </tr>
        <tr v-if="inscricoes.length === 0">
          <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">Nenhuma inscrição encontrada.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
