<template>
  <Head title="Carteira de resgate" />

  <div class="pb-6">
    <PageHeader
      title="Carteira de resgate"
      :description="ente?.nome ? `${ESCOPO_ROTULO[ente.escopo] ?? ''} ${ente.nome}` : 'Saldo de pontos do ente para resgate de serviços, adesões e bens.'"
      :icon="BoltIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-2">
          <Button :href="route('resgate.catalogo')" variant="sky" :icon="GiftIcon">Catálogo de prêmios</Button>
          <Button :href="route('ranking.index')" variant="warning" :icon="TrophyIcon">Voltar ao placar</Button>
        </div>
      </template>
    </PageHeader>

    <p role="status" class="mb-6 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-500/30 dark:bg-sky-950/40 dark:text-sky-100" data-fase-leitura>
      Visualização da carteira. O resgate será liberado depois do normativo da CEDEC que publica o catálogo, os custos e as regras.
      O resgate é sempre do {{ ESCOPO_ROTULO[filtros.escopo]?.toLowerCase() ?? 'ente' }}, nunca de uma pessoa.
    </p>

    <FilterSection v-if="podeEscolherEnte" title="Ente" :columns="3" :default-collapsed="false" class="mb-6">
      <FilterField label="Tipo de ente" type="select" :model-value="local.escopo" :options="escopos" @update:model-value="trocarEscopo" />
      <FilterField label="Ente" type="select" :model-value="local.ente" :options="entes" @update:model-value="local.ente = $event" />
      <div class="flex items-end justify-end pt-1">
        <FilterActions @search="aplicar" @clear="limpar" />
      </div>
    </FilterSection>

    <p v-if="!carteira" role="status" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100">
      {{ podeEscolherEnte ? 'Escolha um ente para ver a carteira.' : 'Seu usuário não está vinculado a um órgão com município; não há carteira para mostrar.' }}
    </p>

    <template v-else>
      <StatCardsGrid :colunas="4" class="mb-6">
        <StatCard title="Saldo resgatável" :value="carteira.saldo_resgatavel" :variant="carteira.saldo_resgatavel < 0 ? 'danger' : 'success'" :subtitle="carteira.saldo_resgatavel < 0 ? 'Negativo: novos resgates bloqueados' : 'Pontos maduros disponíveis'" :icon="BoltIcon" />
        <StatCard title="Em carência" :value="carteira.em_carencia" variant="warning" :subtitle="`Liberam após ${numero(carteira.carencia_dias)} dias`" :icon="ClockIcon" />
        <StatCard title="Faixa que libera prêmios" :value="faixaFechada ? FAIXAS[faixaFechada.faixa] : 'Sem faixa'" :format-number="false" variant="info" :subtitle="faixaFechada ? `Final do ${rotuloTrimestre(faixaFechada.chave)}` : 'Não pontuou na temporada fechada'" :icon="CheckBadgeIcon" />
        <StatCard title="Faixa na temporada atual" :value="FAIXAS[faixaAtual?.faixa] ?? '—'" :format-number="false" variant="info" :subtitle="`${numero(faixaAtual?.pontos)} pontos · referência, ainda não vale`" :icon="CheckBadgeIcon" />
      </StatCardsGrid>

      <CarteiraComposicao :carteira="carteira" class="mb-6" />
    </template>
  </div>
</template>

<script setup>
/**
 * Carteira de resgate do ente - Fase 1, somente leitura (plano 2026-09-25).
 * O saldo e derivado do ledger no backend; a faixa que libera premios e a da
 * temporada FECHADA (decisao D2), a atual aparece so como referencia.
 */
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { GiftIcon, TrophyIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import CarteiraComposicao from '@/Components/Organisms/Resgate/CarteiraComposicao.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { rotuloTrimestre } from '@/Support/rankingTemporada';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  carteira: { type: Object, default: null },
  faixaFechada: { type: Object, default: null },
  faixaAtual: { type: Object, default: null },
  ente: { type: Object, default: null },
  filtros: { type: Object, default: () => ({ escopo: 'municipio', ente: null }) },
  escopos: { type: Array, default: () => [] },
  entes: { type: Array, default: () => [] },
  podeEscolherEnte: { type: Boolean, default: false },
  faixas: { type: Object, default: null },
});

const ESCOPO_ROTULO = { municipio: 'Município', orgao: 'Órgão' };
const FAIXAS = { bronze: 'Bronze', prata: 'Prata', ouro: 'Ouro', diamante: 'Diamante' };

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));

const local = reactive({ escopo: props.filtros.escopo, ente: props.filtros.ente });

function navegar(parametros) {
  router.get(route('resgate.carteira'), parametros, { preserveScroll: true, replace: true });
}

// Trocar o tipo de ente recarrega a lista de entes daquele tipo.
function trocarEscopo(escopo) {
  local.escopo = escopo;
  local.ente = null;
  navegar({ escopo });
}

function aplicar() {
  navegar({ escopo: local.escopo, ...(local.ente ? { ente: local.ente } : {}) });
}

function limpar() {
  local.escopo = 'municipio';
  local.ente = null;
  navegar({});
}
</script>
