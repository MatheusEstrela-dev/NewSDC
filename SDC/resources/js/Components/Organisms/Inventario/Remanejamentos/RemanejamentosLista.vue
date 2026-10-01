<template>
  <ListaResponsiva :itens="lotes" :colunas="COLUNAS" rotulo-acoes="Ações" acoes-fixas :vazio="VAZIO">
    <template #linha="{ item: lote, td, tdForte, totalColunas }">
      <tr class="table-row-solid transition-colors">
        <td :class="tdForte">{{ formatarDataHora(lote.criado_em) }}</td>
        <td :class="td"><span class="block max-w-[22rem] truncate" :title="lote.pessoas.join(', ')">{{ lote.pessoas.join(', ') }}</span></td>
        <td :class="td"><Badge variant="info" size="sm">{{ rotuloItens(lote) }}</Badge></td>
        <td :class="td"><StatusRemanejamentoBadge :status="lote.status" :label="lote.status_label" /></td>
        <td class="table-actions-cell px-3 py-2">
          <RemanejamentoAcoes
            :lote="lote"
            :pode="pode"
            :expandido="estaExpandido(lote.id)"
            :processando="processando"
            v-bind="repasse"
            @expandir="alternar(lote.id)"
          />
        </td>
      </tr>
      <tr v-if="estaExpandido(lote.id)">
        <td :colspan="totalColunas" class="border-t border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700/50 dark:bg-slate-900/40">
          <p v-if="lote.observacao" class="mb-3 text-sm text-slate-600 dark:text-slate-300">{{ lote.observacao }}</p>
          <RemanejamentoItensTabela :itens="lote.itens" />
        </td>
      </tr>
    </template>

    <template #cartao="{ item: lote }">
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatarDataHora(lote.criado_em) }}</p>
          <h3 class="break-words text-sm font-bold text-slate-900 dark:text-slate-100">{{ lote.pessoas.join(', ') }}</h3>
        </div>
        <StatusRemanejamentoBadge class="shrink-0" :status="lote.status" :label="lote.status_label" />
      </header>
      <div class="mt-2"><Badge variant="info" size="sm">{{ rotuloItens(lote) }}</Badge></div>
      <footer class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <RemanejamentoAcoes
          :lote="lote"
          :pode="pode"
          :expandido="estaExpandido(lote.id)"
          :processando="processando"
          v-bind="repasse"
          @expandir="alternar(lote.id)"
        />
      </footer>
      <div v-if="estaExpandido(lote.id)" class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <p v-if="lote.observacao" class="mb-3 break-words text-sm text-slate-600 dark:text-slate-300">{{ lote.observacao }}</p>
        <RemanejamentoItensTabela :itens="lote.itens" />
      </div>
    </template>
  </ListaResponsiva>
</template>

<script setup>
import { ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ListaResponsiva from '@/Components/Molecules/Inventario/ListaResponsiva.vue';
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';
import RemanejamentoAcoes from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentoAcoes.vue';
import RemanejamentoItensTabela from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentoItensTabela.vue';
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({
  lotes: { type: Array, required: true },
  pode: { type: Object, required: true },
  processando: { type: Boolean, default: false },
});
const emit = defineEmits(['seplag', 'planilha', 'editar', 'desfazer', 'chamado', 'abrir-chamado']);

const COLUNAS = ['Data / hora', 'Pessoas', 'Itens', 'Status'];
const VAZIO = { titulo: 'Nenhum remanejamento encontrado', ajuda: 'Ajuste os filtros ou use "Novo remanejamento".' };

// Repassa as acoes de RemanejamentoAcoes para o template sem reescrever cada uma.
const repasse = {
  onSeplag: (lote) => emit('seplag', lote),
  onPlanilha: (lote) => emit('planilha', lote),
  onEditar: (lote) => emit('editar', lote),
  onDesfazer: (lote) => emit('desfazer', lote),
  onChamado: (lote) => emit('chamado', lote),
  onAbrirChamado: (lote) => emit('abrir-chamado', lote),
};

// Guardado por id: o reload do tempo real reordena a lista sem fechar o que esta aberto.
const expandidos = ref([]);
const estaExpandido = (id) => expandidos.value.includes(id);
function alternar(id) {
  expandidos.value = estaExpandido(id) ? expandidos.value.filter((item) => item !== id) : [...expandidos.value, id];
}

const rotuloItens = (lote) => `${lote.itens_movidos} ${lote.itens_movidos === 1 ? 'item movido' : 'itens movidos'}`;
</script>
