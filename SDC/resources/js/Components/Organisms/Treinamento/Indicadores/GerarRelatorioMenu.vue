<template>
  <div ref="raiz" class="relative">
    <Button variant="primary" size="md" type="button" :icon="DownloadIcon" icon-position="left" :disabled="carregando" :aria-expanded="aberto" @click="aberto = !aberto">
      {{ carregando ? 'Gerando...' : 'Gerar relatório' }}
      <ChevronDownIcon class="h-4 w-4" />
    </Button>

    <!-- Ancorado a esquerda no celular: a direita o menu sairia da tela em 375px. -->
    <div
      v-if="aberto"
      class="absolute left-0 z-30 mt-2 w-56 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900 md:left-auto md:right-0"
    >
      <button type="button" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800" @click="escolher('pdf')">
        <DocumentTextIcon class="h-4 w-4 shrink-0 text-red-500" />
        <span>PDF<span class="block text-[11px] text-slate-400">Documento para imprimir ou salvar</span></span>
      </button>
      <button type="button" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800" @click="escolher('csv')">
        <DownloadIcon class="h-4 w-4 shrink-0 text-emerald-500" />
        <span>Planilha (CSV)<span class="block text-[11px] text-slate-400">Para abrir no Excel</span></span>
      </button>
    </div>
  </div>
</template>

<script setup>
/**
 * Botao "Gerar relatorio" do cabecalho: abre o menu PDF / CSV e so emite a
 * escolha. Quem busca o dado (prop lazy `relatorio`) e a Index.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ChevronDownIcon from '@/Components/Icons/ChevronDownIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';

defineProps({
  carregando: { type: Boolean, default: false },
});

const emit = defineEmits(['gerar']);

const raiz = ref(null);
const aberto = ref(false);

function escolher(formato) {
  aberto.value = false;
  emit('gerar', formato);
}

const fecharAoClicarFora = (e) => {
  if (raiz.value && !raiz.value.contains(e.target)) aberto.value = false;
};

onMounted(() => document.addEventListener('mousedown', fecharAoClicarFora));
onBeforeUnmount(() => document.removeEventListener('mousedown', fecharAoClicarFora));
</script>
