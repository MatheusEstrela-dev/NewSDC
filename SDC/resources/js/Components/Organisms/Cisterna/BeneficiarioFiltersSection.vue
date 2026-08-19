<template>
  <CollapsibleSection
    namespace="cisterna"
    section-id="filtros"
    title="Filtros de pesquisa"
    :subtitle="resumoAtivos"
    :icon="FunnelIcon"
    tom="neutro"
    :status-text="resumoAtivos"
  >
    <div class="mb-3 flex justify-end">
      <button
        type="button"
        class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
        @click="limpar"
      >
        Limpar
      </button>
    </div>

    <form class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="aplicar">
      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Nome</span>
        <input
          v-model="local.search"
          type="text"
          placeholder="Nome ou parte do nome"
          :class="INPUT"
        >
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">CPF</span>
        <input v-model="local.cpf" type="text" inputmode="numeric" placeholder="Somente digitos" :class="INPUT">
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Nº de instalacao</span>
        <input v-model="local.numero_instalacao" type="number" placeholder="Ex.: 834" :class="INPUT">
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Municipio</span>
        <select v-model="local.municipio_id" :class="INPUT" :disabled="semMunicipios">
          <option value="">Todos</option>
          <option v-for="m in municipios" :key="m.id" :value="m.id">{{ m.nome }}</option>
        </select>
        <!--
          Aviso deliberado: `cedec_municipio` chegou vazia da migracao, e o
          scope `Municipio::habilitadosCisterna()` faz join nela. Sem o aviso, o
          select vazio parece bug de tela quando na verdade falta rodar
          `legado:importar-cedec-municipio`.
        -->
        <span v-if="semMunicipios" class="mt-1 block text-xs text-amber-600 dark:text-amber-400">
          Nenhum municipio habilitado no programa.
        </span>
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Situacao da analise</span>
        <select v-model="local.situacao_analise" multiple size="4" :class="INPUT">
          <option v-for="o in situacoesAnalise" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Situacao da obra</span>
        <select v-model="local.situacao_obra" multiple size="4" :class="INPUT">
          <option v-for="o in situacoesObra" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Etapa concluida</span>
        <select v-model="local.etapa_concluida" :class="INPUT">
          <option value="">Qualquer</option>
          <option v-for="o in etapasVistoria" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Atendido por pipa</span>
        <select v-model="local.atendido_por_pipa" :class="INPUT">
          <option value="">Indiferente</option>
          <option value="1">Sim</option>
          <option value="0">Nao</option>
        </select>
      </label>

      <div class="flex items-end gap-4 sm:col-span-2 lg:col-span-4">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
          <input v-model="local.ranqueamento" type="checkbox" class="rounded border-slate-300 dark:border-slate-600">
          <!--
            Ordenacao, nao calculo. O legado tinha este filtro mas nenhuma rotina
            de ranqueamento -- a rota de calculo estava quebrada. A coluna e
            importada e apenas ordenavel.
          -->
          <span>Ordenar por ranqueamento</span>
        </label>

        <button type="submit" class="ml-auto rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
          Pesquisar
        </button>
      </div>
    </form>
  </CollapsibleSection>
</template>

<script setup>
import { reactive, computed, watch } from 'vue';
import { FunnelIcon } from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';

/**
 * Organismo de filtros. Nao navega: emite `apply` com o objeto de filtros e a
 * pagina decide como pedir ao servidor.
 */
const props = defineProps({
  filtros: { type: Object, default: () => ({}) },
  municipios: { type: Array, default: () => [] },
  situacoesAnalise: { type: Array, default: () => [] },
  situacoesObra: { type: Array, default: () => [] },
  etapasVistoria: { type: Array, default: () => [] },
});

const emit = defineEmits(['apply', 'clear']);

const INPUT = 'w-full rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:disabled:bg-slate-900';

const VAZIO = {
  search: '',
  cpf: '',
  numero_instalacao: '',
  municipio_id: '',
  situacao_analise: [],
  situacao_obra: [],
  etapa_concluida: '',
  atendido_por_pipa: '',
  ranqueamento: false,
};

const local = reactive({ ...VAZIO, ...normalizar(props.filtros) });

const semMunicipios = computed(() => (props.municipios?.length ?? 0) === 0);

/**
 * Quantos filtros estao valendo. Aparece no cabecalho da secao recolhida: sem
 * isso, a lista filtrada parece incompleta e o usuario nao ve onde mexer.
 */
const resumoAtivos = computed(() => {
  const ativos = Object.entries(local).filter(([, valor]) => {
    if (Array.isArray(valor)) return valor.length > 0;

    return valor !== '' && valor !== false && valor !== null && valor !== undefined;
  }).length;

  if (ativos === 0) return 'Nenhum filtro aplicado';

  return ativos === 1 ? '1 filtro aplicado' : `${ativos} filtros aplicados`;
});

/**
 * O servidor devolve os filtros que aplicou. Ressincronizar e o que mantem o
 * formulario coerente quando o filtro veio de um stat card, e nao daqui.
 */
watch(
  () => props.filtros,
  (novos) => Object.assign(local, VAZIO, normalizar(novos)),
  { deep: true },
);

function normalizar(filtros) {
  const f = filtros ?? {};

  return {
    ...f,
    // Os multiples precisam ser array mesmo quando o servidor manda um valor so.
    situacao_analise: aArray(f.situacao_analise),
    situacao_obra: aArray(f.situacao_obra),
    // Booleano trafega como '1'/'0' na query string.
    atendido_por_pipa: f.atendido_por_pipa === undefined || f.atendido_por_pipa === null
      ? ''
      : String(Number(f.atendido_por_pipa)),
    ranqueamento: Boolean(f.ranqueamento),
  };
}

function aArray(valor) {
  if (Array.isArray(valor)) return valor;

  return valor === undefined || valor === null || valor === '' ? [] : [valor];
}

function aplicar() {
  emit('apply', { ...local });
}

function limpar() {
  Object.assign(local, VAZIO);
  emit('clear');
}
</script>
