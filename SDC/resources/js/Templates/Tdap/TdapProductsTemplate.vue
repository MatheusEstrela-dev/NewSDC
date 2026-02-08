<template>
  <div class="tdap-products-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Produtos TDAP"
      description="Gestão de produtos para ajuda humanitária"
      :icon="CubeIcon"
      variant="gradient"
    />

    <!-- Desktop: Tabela -->
    <CardBase v-if="!isMobile" variant="default" padding="lg">
      <Heading :level="4" color="default" class="mb-4">Lista de Produtos</Heading>

      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-slate-700 dark:border-slate-700 border-slate-200">
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Código</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Nome</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Tipo</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Grupo Risco</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Est. Mínimo</Text>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50 dark:divide-slate-700/50 divide-slate-200">
            <tr v-for="product in products" :key="product.id" class="hover:bg-slate-700/30 dark:hover:bg-slate-700/30 hover:bg-slate-100 transition-colors">
              <td class="px-4 py-4">
                <Text size="sm" color="default" weight="medium">{{ product.codigo }}</Text>
              </td>
              <td class="px-4 py-4">
                <Text size="sm" color="default">{{ product.nome }}</Text>
              </td>
              <td class="px-4 py-4">
                <ProductTypeBadge :type="product.tipo" />
              </td>
              <td class="px-4 py-4">
                <span :class="[
                  'px-2 py-1 rounded text-xs font-medium',
                  product.grupo_risco === 'ALIMENTO' ? 'bg-green-500/15 text-green-300 ring-1 ring-green-500/25' :
                  product.grupo_risco === 'QUIMICO' ? 'bg-yellow-500/15 text-yellow-300 ring-1 ring-yellow-500/25' :
                  'bg-gray-500/15 text-gray-300 ring-1 ring-gray-500/25'
                ]">
                  {{ product.grupo_risco }}
                </span>
              </td>
              <td class="px-4 py-4">
                <Text size="sm" color="muted">{{ product.estoque_minimo }}</Text>
              </td>
            </tr>
            <tr v-if="!products || products.length === 0">
              <td colspan="5" class="px-4 py-8 text-center">
                <Text size="sm" color="muted">Nenhum produto cadastrado</Text>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardBase>

    <!-- Mobile: Cards -->
    <div v-else class="grid grid-cols-1 gap-4">
      <TdapProductCard
        v-for="product in products"
        :key="product.id"
        :product="product"
      />
      <div v-if="!products || products.length === 0" class="text-center py-8">
        <Text size="sm" color="muted">Nenhum produto cadastrado</Text>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="(page) => $emit('page-change', page)"
      />
    </div>
  </div>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import ProductTypeBadge from '@/Components/Atoms/Tdap/ProductTypeBadge.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import TdapProductCard from '@/Components/Molecules/Tdap/TdapProductCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { useMobile } from '@/composables/useMobile';

// Detecção mobile
const { isMobile } = useMobile();

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({}),
  },
  pagination: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['page-change']);
</script>

<style scoped>
.tdap-products-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
