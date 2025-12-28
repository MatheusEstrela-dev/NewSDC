<template>
  <div class="tdap-products-container">
    <TdapProductsPageHeader />

    <CardBase variant="default" padding="lg">
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
  </div>
</template>

<script setup>
import TdapProductsPageHeader from '@/Components/Organisms/Tdap/Header/TdapProductsPageHeader.vue';
import ProductTypeBadge from '@/Components/Atoms/Tdap/ProductTypeBadge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({}),
  },
});
</script>

<style scoped>
.tdap-products-container {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  padding: 1.5rem;
}

@media (min-width: 640px) {
  .tdap-products-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .tdap-products-container {
    padding: 2rem 2.5rem;
  }
}

@media (min-width: 1280px) {
  .tdap-products-container {
    padding: 2rem 3rem;
  }
}
</style>
