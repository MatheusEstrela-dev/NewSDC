<template>
  <section aria-label="Lista de notas técnicas" class="flex h-full min-h-0 flex-col">
    <header class="shrink-0 border-b border-slate-200 p-2 dark:border-slate-700/50">
      <SearchInput
        v-model="termo"
        placeholder="Buscar por código, título ou resumo"
        size="sm"
      />
    </header>

    <div class="min-h-0 flex-1 overflow-y-auto p-2">
      <ul v-if="itens.length" class="space-y-1">
        <NotaTecnicaListaItem
          v-for="nota in itens"
          :key="nota.id"
          :nota="nota"
          :ativa="nota.id === notaId"
          :versoes="nota.id === notaId ? versoes : []"
          :versao-id="versaoId"
          @select="$emit('select-nota', $event)"
          @select-versao="$emit('select-versao', $event)"
        />
      </ul>
      <ListEmptyState
        v-else
        :icon="DocumentMagnifyingGlassIcon"
        title="Nenhuma nota encontrada"
        helper="Escolha outra pasta ou ajuste a busca."
      />
    </div>

    <footer v-if="paginacao.last_page > 1" class="shrink-0 border-t border-slate-200 px-2 pb-2 dark:border-slate-700/50">
      <Pagination :pagination="paginacao" @page-change="$emit('page', $event)" />
    </footer>
  </section>
</template>

<script setup>
/**
 * Painel central-esquerdo: lista densa das notas da pasta escolhida, com as
 * revisoes da nota aberta agrupadas sob ela.
 *
 * A busca espera a pessoa parar de digitar antes de emitir: cada emissao vira
 * uma visita ao servidor.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { DocumentMagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import SearchInput from '@/Components/Atoms/Input/SearchInput.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import NotaTecnicaListaItem from '@/Components/Molecules/NotasTecnicas/NotaTecnicaListaItem.vue';

const ESPERA_BUSCA_MS = 400;

const props = defineProps({
  notas: { type: Object, default: () => ({ data: [] }) },
  busca: { type: String, default: '' },
  notaId: { type: Number, default: null },
  versaoId: { type: Number, default: null },
  versoes: { type: Array, default: () => [] },
});

const emit = defineEmits(['select-nota', 'select-versao', 'buscar', 'page']);

const itens = computed(() => props.notas?.data ?? []);

const paginacao = computed(() => ({
  current_page: props.notas?.current_page ?? 1,
  last_page: props.notas?.last_page ?? 1,
  per_page: props.notas?.per_page ?? 25,
  total: props.notas?.total ?? 0,
  from: props.notas?.from ?? null,
  to: props.notas?.to ?? null,
}));

const termo = ref(props.busca ?? '');
let temporizador = null;

watch(termo, (valor) => {
  clearTimeout(temporizador);
  // Igual ao que o servidor ja aplicou (ex.: sincronizado pelo watch abaixo): nada a pedir.
  if (valor.trim() === (props.busca ?? '')) return;
  temporizador = setTimeout(() => emit('buscar', valor.trim()), ESPERA_BUSCA_MS);
});

// Busca limpa por fora (card "Em vigor", troca de pasta) volta ao campo.
watch(() => props.busca, (valor) => {
  if ((valor ?? '') !== termo.value.trim()) termo.value = valor ?? '';
});

onBeforeUnmount(() => clearTimeout(temporizador));
</script>
