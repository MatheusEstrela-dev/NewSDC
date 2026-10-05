<template>
  <Head title="Catálogo de prêmios" />

  <div class="pb-6">
    <PageHeader
      title="Catálogo de prêmios"
      description="Serviços, adesões e bens que o município pode resgatar ao alcançar a faixa exigida."
      :icon="CheckBadgeIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-2">
          <Button v-if="pendentesTotal > 0" :href="route('resgate.catalogo.propostas')" variant="violet" :icon="LightBulbIcon" data-link-propostas>
            {{ numero(pendentesTotal) }} {{ pendentesTotal === 1 ? 'proposta aguardando' : 'propostas aguardando' }}
          </Button>
          <Button v-if="podePropor" :href="route('resgate.catalogo.propostas.nova')" variant="primary" :icon="PlusIcon" data-link-nova-proposta>Propor novo item</Button>
          <Button :href="route('resgate.pedidos')" variant="info" :icon="ClipboardDocumentListIcon" data-link-pedidos>Pedidos</Button>
          <Button :href="route('resgate.carteira')" variant="success" :icon="WalletIcon">Carteira</Button>
          <Button :href="route('ranking.index')" variant="warning" :icon="TrophyIcon">Placar</Button>
        </div>
      </template>
    </PageHeader>

    <p role="status" class="mb-6 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-500/30 dark:bg-sky-950/40 dark:text-sky-100">
      O resgate reserva os pontos e a unidade até a CEDEC aprovar ou recusar. Os valores são provisórios até o normativo da CEDEC; itens de demonstração só são resgatáveis em homologação, com pontos de demonstração.
      <template v-if="faixaFechada"> Faixa do seu município na temporada fechada: <strong>{{ rotuloFaixa(faixaFechada.faixa) }}</strong>.</template>
    </p>

    <div v-if="itens.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <ItemCatalogoCard v-for="item in itens" :key="item.codigo" :item="item" :pode-gerenciar="podePropor" :pode-solicitar="podeSolicitar" />
    </div>
    <p v-else class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
      Nenhum item publicado no catálogo.
    </p>
  </div>
</template>

<script setup>
/**
 * Vitrine do catalogo de premios (Fase 2). Propostas, nova proposta e cadastro
 * de unidade sao paginas proprias do SPA, nao modais.
 */
import { Head } from '@inertiajs/vue3';
import { ClipboardDocumentListIcon, LightBulbIcon, PlusIcon, TrophyIcon, WalletIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ItemCatalogoCard from '@/Components/Molecules/Resgate/ItemCatalogoCard.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { rotuloFaixa } from '@/Support/resgateCatalogo';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  itens: { type: Array, default: () => [] },
  pendentesTotal: { type: Number, default: 0 },
  faixaFechada: { type: Object, default: null },
  podePropor: { type: Boolean, default: false },
  podeAprovar: { type: Boolean, default: false },
  podeSolicitar: { type: Boolean, default: false },
});


const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
</script>
