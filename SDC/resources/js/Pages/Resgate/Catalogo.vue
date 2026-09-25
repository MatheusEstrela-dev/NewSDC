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
          <Link v-if="pendentesTotal > 0" :href="route('resgate.catalogo.propostas')" :class="LINK_DESTAQUE" data-link-propostas>
            {{ numero(pendentesTotal) }} {{ pendentesTotal === 1 ? 'proposta aguardando' : 'propostas aguardando' }}
          </Link>
          <Link v-if="podePropor" :href="route('resgate.catalogo.propostas.nova')" :class="LINK" data-link-nova-proposta>Propor novo item</Link>
          <Link :href="route('resgate.carteira')" :class="LINK">Carteira</Link>
          <Link :href="route('ranking.index')" :class="LINK">Placar</Link>
        </div>
      </template>
    </PageHeader>

    <p role="status" class="mb-6 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-500/30 dark:bg-sky-950/40 dark:text-sky-100">
      Catálogo em construção. Os pedidos de resgate serão liberados depois do normativo da CEDEC; itens marcados como demonstração nunca são resgatáveis.
      <template v-if="faixaFechada"> Faixa do seu município na temporada fechada: <strong>{{ rotuloFaixa(faixaFechada.faixa) }}</strong>.</template>
    </p>

    <div v-if="itens.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <ItemCatalogoCard v-for="item in itens" :key="item.codigo" :item="item" :pode-gerenciar="podePropor" />
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
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
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
});

const LINK = 'inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700';
const LINK_DESTAQUE = 'inline-flex items-center rounded-lg bg-amber-500 px-3 py-2 text-sm font-bold text-slate-900 transition hover:bg-amber-400';

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
</script>
