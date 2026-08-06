<template>
  <div class="border border-slate-200 dark:border-slate-700/50 rounded-lg overflow-hidden">
    <!-- Header colapsavel -->
    <button
      type="button"
      class="w-full px-4 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors text-left"
      @click="isExpanded = !isExpanded"
    >
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <span class="font-semibold text-base text-slate-700 dark:text-slate-200 truncate">
          {{ desastre.titulo }}
        </span>
        <DesastreTotalBadge :desastre="localDesastre" />
      </div>
      <ChevronDownIcon
        :class="['w-4 h-4 text-slate-400 transition-transform shrink-0 ml-2', { 'rotate-180': isExpanded }]"
      />
    </button>

    <!-- Body -->
    <div v-show="isExpanded" class="p-4 border-t border-slate-200 dark:border-slate-700/50 space-y-4">
      <!-- Informacao -->
      <p v-if="desastre.informacao" class="text-sm text-slate-500 dark:text-slate-400 italic">
        {{ desastre.informacao }}
      </p>

      <!-- Tabela de itens -->
      <DesastreItemsTable
        :items="localDesastre.items"
        :municipio-id="municipioId"
        @update:campo="updateCampo"
      />

      <!-- Subsecoes (ex.: Danos Ambientais dentro de Danos Materiais) -->
      <div
        v-for="(subsecao, sIndex) in localSubsecoes"
        :key="subsecao.id"
        class="pt-4 border-t border-slate-200 dark:border-slate-700/50 space-y-3"
      >
        <div class="flex items-center gap-2">
          <div class="w-1 h-4 bg-emerald-500 rounded-full"></div>
          <h5 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
            {{ subsecao.titulo }}
          </h5>
          <span
            v-if="marcadosNaSubsecao(subsecao) > 0"
            class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300"
          >
            {{ marcadosNaSubsecao(subsecao) }} marcado(s)
          </span>
        </div>

        <p v-if="subsecao.informacao" class="text-sm text-slate-500 dark:text-slate-400 italic">
          {{ subsecao.informacao }}
        </p>

        <DesastreItemsTable
          :items="subsecao.items"
          :municipio-id="municipioId"
          @update:campo="(evento) => updateCampoSubsecao(sIndex, evento)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import DesastreTotalBadge from '@/Components/Molecules/Decretacoes/DesastreTotalBadge.vue';
import DesastreItemsTable from '@/Components/Molecules/Decretacoes/DesastreItemsTable.vue';
import { MARCADO, aplicaValorDoCampo } from '@/Composables/decretacoes/useDesastreRadio';

const props = defineProps({
  desastre: {
    type: Object,
    required: true,
  },
  municipioId: {
    type: Number,
    required: true,
  },
  // Blocos de danos exibidos dentro deste accordion em vez de accordion irmao.
  // Continuam sendo nos independentes da arvore: o pai grava cada atualizacao
  // na posicao original de `categoria.desastres`, entao o payload enviado ao
  // backend nao muda.
  subsecoes: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:desastre', 'update:subsecao']);

const isExpanded = ref(false);
const localDesastre = ref(clonar(props.desastre));
const localSubsecoes = ref(clonar(props.subsecoes));

watch(() => props.desastre, (val) => {
  localDesastre.value = clonar(val);
}, { deep: true });

watch(() => props.subsecoes, (val) => {
  localSubsecoes.value = clonar(val);
}, { deep: true });

function clonar(valor) {
  return JSON.parse(JSON.stringify(valor ?? null));
}

function updateCampo({ iIndex, fIndex, valor }) {
  aplicaValorDoCampo(localDesastre.value.items, iIndex, fIndex, valor);
  emit('update:desastre', semDescricao(localDesastre.value));
}

function updateCampoSubsecao(sIndex, { iIndex, fIndex, valor }) {
  const subsecao = localSubsecoes.value[sIndex];

  if (!subsecao) {
    return;
  }

  aplicaValorDoCampo(subsecao.items, iIndex, fIndex, valor);
  emit('update:subsecao', { index: sIndex, desastre: semDescricao(subsecao) });
}

/** Quantos itens da subsecao estao com a resposta marcada. */
function marcadosNaSubsecao(subsecao) {
  return (subsecao.items ?? []).filter((item) =>
    (item.campos ?? []).some((campo) => campo.tipo === 'radio'
      && String(campo.valor ?? '') === MARCADO
      && ehRespostaPositiva(campo.titulo)),
  ).length;
}

function ehRespostaPositiva(titulo) {
  return String(titulo ?? '').trim().toLowerCase() === 'sim';
}

function semDescricao(desastre) {
  const payload = clonar(desastre);

  // A descricao de areas/populacao afetada saiu do formulario: nao e enviada.
  delete payload.descricao;

  return payload;
}
</script>
