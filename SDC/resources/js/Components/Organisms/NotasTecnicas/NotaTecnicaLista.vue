<template>
  <section aria-label="Lista de notas técnicas" class="flex h-full min-h-0 flex-col">
    <header ref="cabecalho" class="shrink-0 border-b border-slate-200 p-2 dark:border-slate-700/50">
      <SearchInput
        v-model="termo"
        placeholder="Buscar no código, título, resumo ou texto do PDF"
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
          :pode-revogar="podeRevogar"
          @select="$emit('select-nota', $event)"
          @select-versao="$emit('select-versao', $event)"
          @revogar="$emit('revogar')"
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
  podeRevogar: { type: Boolean, default: false },
});

const emit = defineEmits(['select-nota', 'select-versao', 'buscar', 'page', 'revogar']);

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
const cabecalho = ref(null);
let temporizador = null;

watch(termo, (valor) => {
  clearTimeout(temporizador);
  temporizador = null;
  // Igual ao que o servidor ja aplicou (ex.: sincronizado pelo watch abaixo): nada a pedir.
  if (valor.trim() === (props.busca ?? '')) return;
  temporizador = setTimeout(() => {
    temporizador = null;
    emit('buscar', valor.trim());
  }, ESPERA_BUSCA_MS);
});

// Busca mudada por fora (card "Em vigor", troca de pasta, voltar do navegador)
// volta ao campo. Mas a resposta do servidor tambem chega por aqui: com busca
// pendente (temporizador ativo) ou digitacao em curso (foco no campo) o texto da
// pessoa e mais novo que o do servidor e nao pode ser sobrescrito.
watch(() => props.busca, (valor) => {
  const novo = valor ?? '';
  if (novo === termo.value.trim()) return;
  if (temporizador !== null) return;
  if (cabecalho.value?.contains(document.activeElement) && novo !== '') return;
  termo.value = novo;
});

onBeforeUnmount(() => clearTimeout(temporizador));
</script>
