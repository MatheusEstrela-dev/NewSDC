<template>
  <div class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 overflow-hidden">
    <!-- Header do Municipio -->
    <button
      type="button"
      class="w-full px-4 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors text-left"
      @click="isExpanded = !isExpanded"
    >
      <div class="flex items-center gap-3">
        <!-- Icone em caixa colorida - mesmo padrao das secoes do modulo RAT -->
        <div class="rat-section-icon rat-section-icon-default">
          <MapPinIcon class="w-5 h-5" />
        </div>
        <span class="font-semibold text-slate-800 dark:text-slate-200">
          {{ municipio.nome || municipio.p_nome || `Municipio ${municipio.id}` }}
        </span>
        <span v-if="localMunicipio.n_protocolo_fide" class="text-xs text-slate-400 hidden sm:inline">
          ({{ localMunicipio.n_protocolo_fide }})
        </span>
      </div>
      <ChevronDownIcon
        :class="['w-5 h-5 text-slate-400 transition-transform shrink-0', { 'rotate-180': isExpanded }]"
      />
    </button>

    <!-- Conteudo do Municipio -->
    <div v-show="isExpanded" class="p-4 border-t border-slate-200 dark:border-slate-700/50 space-y-6">
      <!-- Protocolo FIDE -->
      <div class="max-w-sm">
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
          N. Protocolo FIDE
        </label>
        <ProtocoloFideInput
          :model-value="localMunicipio.n_protocolo_fide"
          @update:model-value="localMunicipio.n_protocolo_fide = $event; emitUpdate()"
        />
      </div>

      <!-- Categorias -->
      <div
        v-for="categoria in localMunicipio.categorias"
        :key="categoria.id"
        class="space-y-3"
      >
        <!-- Header da Categoria (nao colapsavel) -->
        <div class="flex items-center gap-2 pb-2 border-b border-slate-200 dark:border-slate-700/50">
          <div class="w-1 h-5 bg-primary-500 rounded-full"></div>
          <h4 class="text-sm font-semibold text-primary-600 dark:text-primary-400">
            {{ categoria.titulo }}
          </h4>
        </div>

        <!-- Desastres -->
        <div class="space-y-2 pl-3">
          <DesastreAccordion
            v-for="entrada in blocosVisiveis(categoria)"
            :key="entrada.desastre.id"
            :desastre="entrada.desastre"
            :subsecoes="entrada.subsecoes.map((s) => s.desastre)"
            :municipio-id="municipio.id"
            @update:desastre="updateDesastre(categoria.id, entrada.dIndex, $event)"
            @update:subsecao="({ index, desastre }) =>
              updateDesastre(categoria.id, entrada.subsecoes[index].dIndex, desastre)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { MapPinIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import ProtocoloFideInput from '@/Components/Atoms/Input/ProtocoloFideInput.vue';
import DesastreAccordion from '@/Components/Organisms/Decretacoes/DesastreAccordion.vue';

const props = defineProps({
  municipio: {
    type: Object,
    required: true,
  },
  mIndex: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['update:municipio']);

/**
 * Blocos de danos que aparecem dentro de outro bloco, em vez de accordion
 * irmao. No FIDE, Danos Humanos, Materiais e Ambientais pertencem ao mesmo
 * grupo (6) e vinham como tres accordions no mesmo nivel; Danos Ambientais
 * complementa a leitura dos danos materiais, entao entra como subsecao dele.
 *
 * Chaves e valores normalizados (minusculas, sem acento) porque os titulos vem
 * do cadastro do banco, acentuados e em caixa alta.
 */
const SUBSECOES_POR_BLOCO = {
  'danos materiais': ['danos ambientais'],
};

function normaliza(texto) {
  return String(texto ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase();
}

const isExpanded = ref(props.mIndex === 0);
const localMunicipio = ref(JSON.parse(JSON.stringify(props.municipio)));

watch(() => props.municipio, (val) => {
  localMunicipio.value = JSON.parse(JSON.stringify(val));
}, { deep: true });

/**
 * Monta a lista de accordions da categoria, movendo os blocos configurados em
 * SUBSECOES_POR_BLOCO para dentro do bloco pai.
 *
 * `dIndex` e sempre o indice ORIGINAL em `categoria.desastres` — e por ele que
 * as atualizacoes voltam para a arvore, mantendo o payload de gravacao
 * exatamente como o backend espera.
 */
function blocosVisiveis(categoria) {
  const desastres = categoria.desastres ?? [];
  const porTitulo = new Map();

  desastres.forEach((desastre, dIndex) => {
    porTitulo.set(normaliza(desastre.titulo), { desastre, dIndex });
  });

  const aninhados = new Set();

  desastres.forEach((desastre) => {
    (SUBSECOES_POR_BLOCO[normaliza(desastre.titulo)] ?? []).forEach((filho) => {
      if (porTitulo.has(filho)) {
        aninhados.add(filho);
      }
    });
  });

  return desastres
    .map((desastre, dIndex) => ({ desastre, dIndex }))
    .filter((entrada) => !aninhados.has(normaliza(entrada.desastre.titulo)))
    .map((entrada) => ({
      ...entrada,
      subsecoes: (SUBSECOES_POR_BLOCO[normaliza(entrada.desastre.titulo)] ?? [])
        .map((filho) => porTitulo.get(filho))
        .filter(Boolean),
    }));
}

function updateDesastre(categoriaId, dIndex, updatedDesastre) {
  const cat = localMunicipio.value.categorias.find((c) => c.id === categoriaId);
  if (cat) {
    cat.desastres[dIndex] = updatedDesastre;
    emitUpdate();
  }
}

function emitUpdate() {
  emit('update:municipio', JSON.parse(JSON.stringify(localMunicipio.value)));
}
</script>
