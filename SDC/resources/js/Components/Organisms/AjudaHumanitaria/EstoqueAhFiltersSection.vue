<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Depósito"
      type="select"
      :model-value="local.deposito_id"
      :options="opcoesDeposito"
      placeholder="Todos os depósitos"
      @update:model-value="local.deposito_id = $event"
    />

    <FilterField
      class="md:col-span-2"
      label="Material"
      type="text"
      :model-value="local.busca"
      placeholder="Nome do material"
      @update:model-value="local.busca = $event"
    />

    <!-- Ocupa a quarta coluna da mesma linha dos campos, em vez de cair para
         uma linha propria: com apenas dois campos, o grid deixaria a coluna
         vazia e os botoes soltos abaixo. items-end alinha pela base dos
         campos, nao pelo topo do rotulo. -->
    <div class="flex items-end justify-start gap-2">
      <FilterActions @search="aplicar" @clear="limpar" />
    </div>
  </FilterSection>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  /** Apenas depositos que tem saldo, resolvidos pelo controller. */
  depositos: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

/**
 * O estado do formulario e local e so sobe no clique de Aplicar.
 *
 * FilterField emite a cada tecla digitada. Repassar isso adiante dispararia uma
 * navegacao por caractere no campo de material. Como a secao tem botao proprio,
 * a busca acontece quando o usuario decide, nao enquanto ele digita.
 */
const local = reactive({
  deposito_id: props.filters.deposito_id ?? '',
  busca: props.filters.busca ?? '',
});

// Realinha quando a navegacao volta com outros filtros na URL, sem o que o
// formulario passaria a mentir sobre o que esta filtrado.
watch(
  () => props.filters,
  (novos) => {
    local.deposito_id = novos.deposito_id ?? '';
    local.busca = novos.busca ?? '';
  },
);

const opcoesDeposito = computed(() => props.depositos.map((deposito) => ({
  value: deposito.id,
  label: `${deposito.sigla} — ${deposito.nome}`,
})));

function aplicar() {
  emit('filter-change', { ...local });
}

function limpar() {
  local.deposito_id = '';
  local.busca = '';
  emit('filter-reset');
}
</script>
