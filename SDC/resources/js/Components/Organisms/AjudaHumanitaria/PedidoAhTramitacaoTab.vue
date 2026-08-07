<template>
  <div class="space-y-6">
    <div v-if="canTramitar" class="flex flex-wrap items-end gap-3">
      <div class="min-w-56 flex-1">
        <FormSelect
          v-model="destinoEscolhido"
          label="Encaminhar para"
          :options="opcoesDestino"
          placeholder="Selecione a próxima etapa"
        />
      </div>
      <div class="min-w-64 flex-1">
        <FormField v-model="observacao" label="Observação" placeholder="Opcional, vai para o histórico" />
      </div>
      <Button variant="primary" size="md" :disabled="!destinoEscolhido" @click="tramitar">
        Tramitar
      </Button>
    </div>

    <p v-else-if="!destinos.length" class="text-sm text-slate-500 dark:text-slate-400">
      Este processo está encerrado e não admite novas movimentações.
    </p>

    <!-- RN-14: trilha completa -->
    <div>
      <p class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">Histórico</p>

      <ListEmptyState
        v-if="!tramites.length"
        title="Nenhuma movimentação"
        helper="O histórico aparece a partir do primeiro encaminhamento"
      />

      <ol v-else class="relative space-y-4 border-l border-slate-200 pl-5 dark:border-slate-700">
        <li v-for="t in tramitesRecentesPrimeiro" :key="t.id" class="relative">
          <span class="absolute -left-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-blue-500"></span>

          <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-slate-500 dark:text-slate-400">{{ t.de }}</span>
            <span class="text-slate-400">→</span>
            <PedidoAhStatusBadge :label="t.para" :cor="t.para_cor" />
          </div>

          <p v-if="t.observacao" class="mt-1 text-sm text-slate-700 dark:text-slate-200">
            {{ t.observacao }}
          </p>

          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            {{ formatarDataHora(t.quando) }}<span v-if="t.autor"> · {{ t.autor }}</span>
          </p>
        </li>
      </ol>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PedidoAhStatusBadge from '@/Components/Atoms/AjudaHumanitaria/PedidoAhStatusBadge.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

const props = defineProps({
  tramites: { type: Array, default: () => [] },
  destinos: { type: Array, default: () => [] },
  canTramitar: { type: Boolean, default: false },
});

const emit = defineEmits(['tramitar']);

const destinoEscolhido = ref('');
const observacao = ref('');

const opcoesDestino = computed(() => props.destinos);

const tramitesRecentesPrimeiro = computed(() => [...props.tramites].reverse());

function tramitar() {
  emit('tramitar', {
    status_alvo: Number(destinoEscolhido.value),
    observacao: observacao.value || null,
  });

  destinoEscolhido.value = '';
  observacao.value = '';
}

function formatarDataHora(valor) {
  if (!valor) return '—';

  return new Date(valor).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
