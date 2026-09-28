<template>
  <section class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <nav class="flex border-b border-slate-200 dark:border-slate-700/50" role="tablist">
      <button v-for="aba in ABAS" :key="aba.id" type="button" role="tab" :aria-selected="isActive(aba.id)"
        class="px-5 py-3 text-sm font-medium" :class="isActive(aba.id) ? 'border-b-2 border-orange-500 text-orange-600 dark:text-orange-400' : 'text-slate-500'"
        @click="setActiveTab(aba.id)">
        {{ aba.label }} ({{ aba.id === 'comentarios' ? comentarios.length : historico.length }})
      </button>
    </nav>
    <div class="p-5">
      <div v-if="isActive('comentarios')" class="space-y-4">
        <ul class="space-y-3">
          <li v-for="c in comentarios" :key="c.id" class="rounded-xl border border-slate-200 p-4 dark:border-slate-700/50" :class="c.interno ? 'bg-amber-50 dark:bg-amber-900/10' : ''">
            <div class="flex flex-wrap justify-between gap-2 text-xs text-slate-500">
              <span class="font-semibold text-slate-700 dark:text-slate-200">{{ c.autor }}<span v-if="c.interno"> · interno</span></span>
              <time>{{ formatarDataHora(c.created_at) }}</time>
            </div>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ c.conteudo }}</p>
          </li>
          <li v-if="!comentarios.length" class="text-sm text-slate-500">Nenhum comentário ainda.</li>
        </ul>
        <DemandaComentarioForm :demanda-id="demandaId" :pode-interno="podeInterno" />
      </div>
      <ul v-else class="space-y-3">
        <DemandaHistoricoItem v-for="h in historico" :key="h.id" :item="h" />
      </ul>
    </div>
  </section>
</template>

<script setup>
import DemandaComentarioForm from '@/Components/Molecules/Demandas/DemandaComentarioForm.vue';
import DemandaHistoricoItem from '@/Components/Molecules/Demandas/DemandaHistoricoItem.vue';
import { formatarDataHora } from '@/Support/demandasFormat';
import { useTabs } from '@/Composables/core/useTabs';

defineProps({
  demandaId: { type: Number, required: true },
  comentarios: { type: Array, default: () => [] },
  historico: { type: Array, default: () => [] },
  podeInterno: { type: Boolean, default: false },
});

const ABAS = [{ id: 'comentarios', label: 'Comentários' }, { id: 'historico', label: 'Histórico' }];
const { isActive, setActiveTab } = useTabs('historico');
</script>
