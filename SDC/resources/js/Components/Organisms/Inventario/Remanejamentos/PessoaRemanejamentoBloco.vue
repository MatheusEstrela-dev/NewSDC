<template>
  <section
    :aria-labelledby="`pessoa-${bloco.chave}-titulo`"
    class="min-w-0 rounded-xl border bg-white p-4 shadow-sm sm:p-6 dark:bg-slate-900/60"
    :class="temErro ? 'border-red-300 dark:border-red-500/40' : 'border-slate-200 dark:border-slate-700/50'"
  >
    <header class="flex min-w-0 items-center justify-between gap-3">
      <h3 :id="`pessoa-${bloco.chave}-titulo`" class="text-sm font-semibold text-slate-900 dark:text-slate-100">Pessoa {{ indice + 1 }}</h3>
      <ButtonIcon v-if="podeRemover" :icon="TrashIcon" variant="danger" size="sm" title="Remover pessoa do lote" @click="$emit('remover', bloco.chave)" />
    </header>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      <FormSelect
        :id="`pessoa-${bloco.chave}-usuario`"
        :model-value="bloco.usuario_id"
        label="Pessoa"
        required
        :options="opcoes.usuarios"
        :error="erros.usuario_id ?? ''"
        @update:model-value="(valor) => $emit('selecionar-pessoa', bloco, valor)"
      />
      <FormField
        :id="`pessoa-${bloco.chave}-condicao`"
        :model-value="bloco.condicao_destino"
        label="Condição do destino"
        placeholder="VAZIA"
        maxlength="60"
        :error="erros.condicao_destino ?? ''"
        @update:model-value="(valor) => $emit('atualizar', bloco, 'condicao_destino', valor)"
      />
      <FormSelect
        :id="`pessoa-${bloco.chave}-origem`"
        :model-value="bloco.estacao_origem_id"
        label="Estação de origem"
        :options="opcoesEstacao"
        placeholder="Sem estação (estoque)"
        :error="erros.estacao_origem_id ?? ''"
        @update:model-value="(valor) => $emit('atualizar', bloco, 'estacao_origem_id', valor)"
      />
      <FormSelect
        :id="`pessoa-${bloco.chave}-destino`"
        :model-value="bloco.estacao_destino_id"
        label="Estação de destino"
        :options="opcoesEstacao"
        placeholder="Sem estação (estoque)"
        :error="erros.estacao_destino_id ?? ''"
        @update:model-value="(valor) => $emit('atualizar', bloco, 'estacao_destino_id', valor)"
      />
    </div>

    <div class="mt-5 min-w-0">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Equipamentos que vão junto</p>
      <ul class="mt-2 space-y-2">
        <li
          v-for="equipamento in listaVisivel"
          :key="equipamento.id"
          class="flex min-w-0 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700/50"
        >
          <input
            :id="`eq-${bloco.chave}-${equipamento.id}`"
            type="checkbox"
            class="h-4 w-4 shrink-0 rounded border-slate-300 text-orange-600 focus:ring-orange-500 dark:border-slate-600 dark:bg-slate-800"
            :checked="selecionado(equipamento)"
            :disabled="travado(equipamento)"
            @change="$emit('alternar-equipamento', bloco, equipamento.id)"
          />
          <label :for="`eq-${bloco.chave}-${equipamento.id}`" class="min-w-0 flex-1 text-sm text-slate-700 dark:text-slate-200">
            <span class="block truncate font-medium">{{ equipamento.nome }}</span>
            <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ equipamento.patrimonio ?? 'Sem patrimônio' }} · {{ equipamento.categoria ?? 'Sem categoria' }}</span>
          </label>
          <Badge v-if="equipamento.bloqueio" variant="warning" size="sm" class="shrink-0">{{ equipamento.bloqueio }}</Badge>
        </li>
        <li v-if="!listaVisivel.length" class="text-sm text-slate-500 dark:text-slate-400">
          {{ bloco.usuario_id ? 'Esta pessoa não tem equipamentos. Use a busca abaixo.' : 'Selecione a pessoa para ver os equipamentos dela.' }}
        </li>
      </ul>
      <p v-if="erros.equipamento_ids" role="alert" class="mt-2 break-words text-sm text-red-600 dark:text-red-400">{{ erros.equipamento_ids }}</p>
      <EquipamentoBusca
        class="mt-3"
        :equipamentos="opcoes.equipamentos"
        :ja-listados="idsVisiveis"
        @adicionar="(id) => $emit('alternar-equipamento', bloco, id)"
      />
    </div>

    <div
      v-if="liberados.length"
      role="status"
      class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
    >
      <p class="font-semibold">Serão liberados (ficam disponíveis, sem responsável):</p>
      <p class="mt-1 break-words">{{ liberados.map((e) => `${e.patrimonio ?? 'Sem patrimônio'} — ${e.nome}`).join(', ') }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { TrashIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import EquipamentoBusca from '@/Components/Molecules/Inventario/EquipamentoBusca.vue';

const props = defineProps({
  bloco: { type: Object, required: true },
  indice: { type: Number, required: true },
  opcoes: { type: Object, required: true },
  // Erros deste bloco por campo (usuario_id, equipamento_ids...), ja sem o prefixo pessoas.N.
  erros: { type: Object, default: () => ({}) },
  podeRemover: { type: Boolean, default: false },
  equipamentosDoUsuario: { type: Array, default: () => [] },
  equipamentosPorId: { type: Map, required: true },
  liberados: { type: Array, default: () => [] },
});
defineEmits(['selecionar-pessoa', 'atualizar', 'alternar-equipamento', 'remover']);

const temErro = computed(() => Object.keys(props.erros).length > 0);

const opcoesEstacao = computed(() => props.opcoes.estacoes.map((e) => ({
  value: e.value,
  label: e.ocupante ? `${e.label} (${e.ocupante})` : e.label,
})));

const selecionado = (equipamento) => props.bloco.equipamento_ids.includes(equipamento.id);

// Bloqueado nao entra; mas se ja estava marcado (lote em edicao), ainda pode sair.
const travado = (equipamento) => Boolean(equipamento.bloqueio) && !selecionado(equipamento);

// Item do lote em edicao que saiu das opcoes (baixado depois): continua
// visivel para poder ser desmarcado, senao seguiria no envio sem aparecer.
const indisponivel = (id) => ({
  id, nome: `Equipamento #${id}`, patrimonio: null, categoria: null, bloqueio: 'Indisponível',
});

// Os da pessoa + os que vieram de outra pessoa pela busca.
const listaVisivel = computed(() => {
  const proprios = props.equipamentosDoUsuario;
  const extras = props.bloco.equipamento_ids
    .filter((id) => !proprios.some((e) => e.id === id))
    .map((id) => props.equipamentosPorId.get(id) ?? indisponivel(id));
  return [...proprios, ...extras];
});
const idsVisiveis = computed(() => listaVisivel.value.map((e) => e.id));
</script>
