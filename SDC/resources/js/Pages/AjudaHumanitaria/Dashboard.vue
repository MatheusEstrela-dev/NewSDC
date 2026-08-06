<template>
  <AuthenticatedLayout>
    <Head title="Ajuda Humanitária — Painel" />

    <PageHeader
      title="Ajuda Humanitária"
      description="Painel do processo de pedido de material"
      :icon="HeartIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('ajuda-humanitaria.pedidos.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Novo Pedido</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <StatCardsGrid class="mt-6" :colunas="5">
      <StatCard
        title="Total"
        :value="estatisticas.total || 0"
        variant="info"
        :icon="DocumentTextIcon"
      />
      <StatCard
        title="Em edição"
        :value="estatisticas.em_edicao || 0"
        variant="warning"
        :icon="PencilIcon"
        subtitle="Com o município"
      />
      <StatCard
        title="Em análise"
        :value="estatisticas.em_analise || 0"
        variant="info"
        :icon="ClipboardIcon"
        subtitle="DLOG e Diretor"
      />
      <StatCard
        title="Aguardando retirada"
        :value="estatisticas.aguardando_retirada || 0"
        variant="warning"
        :icon="TruckIcon"
        subtitle="Material a entregar"
      />
      <StatCard
        title="Em prestação"
        :value="estatisticas.em_prestacao || 0"
        variant="success"
        :icon="CheckCircleIcon"
        subtitle="Atendidos"
      />
    </StatCardsGrid>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
      <!-- Prestacoes vencendo: o aviso mais acionavel do painel -->
      <ListContainer
        title="Prestações de contas em risco"
        :icon="ExclamationTriangleIcon"
        icon-class="text-amber-500"
        :count="prestacoesEmRisco.length"
      >
        <template #header-actions>
          <Link :href="route('ajuda-humanitaria.pedidos.index', { status: 6 })">
            <Button variant="secondary" size="sm">Ver atendidos</Button>
          </Link>
        </template>

        <ListEmptyState
          v-if="!prestacoesEmRisco.length"
          title="Nenhuma prestação vencendo"
          helper="Nada exige atenção nos próximos dias"
        />

        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-800">
          <li v-for="p in prestacoesEmRisco" :key="p.id" class="px-4 py-3 sm:px-6">
            <Link :href="route('ajuda-humanitaria.pedidos.show', p.pedido_id)" class="block">
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Pedido {{ p.identificador }}
                    <span class="font-normal text-slate-500 dark:text-slate-400">
                      · {{ p.municipio ?? '—' }}
                    </span>
                  </p>
                  <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ p.status }} · prazo em {{ formatarData(p.data_limite) }}
                  </p>
                </div>
                <span
                  :class="[
                    'shrink-0 rounded-full px-2.5 py-1 text-xs font-medium',
                    p.vencida
                      ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200'
                      : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                  ]"
                >
                  {{ p.vencida ? 'Vencida' : 'A vencer' }}
                </span>
              </div>
            </Link>
          </li>
        </ul>
      </ListContainer>

      <!-- Pedidos parados na mesma etapa -->
      <ListContainer
        title="Aguardando ação"
        :icon="ClockIcon"
        icon-class="text-blue-500"
        :count="aguardandoAcao.length"
      >
        <template #header-actions>
          <Link :href="route('ajuda-humanitaria.pedidos.index')">
            <Button variant="secondary" size="sm">Ver todos</Button>
          </Link>
        </template>

        <ListEmptyState
          v-if="!aguardandoAcao.length"
          title="Nenhum pedido parado"
          helper="Todos os processos estão em movimento"
        />

        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-800">
          <li v-for="p in aguardandoAcao" :key="p.id" class="px-4 py-3 sm:px-6">
            <Link :href="route('ajuda-humanitaria.pedidos.show', p.id)" class="block">
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Pedido {{ p.identificador }}
                    <span class="font-normal text-slate-500 dark:text-slate-400">
                      · {{ p.municipio ?? '—' }}
                    </span>
                  </p>
                  <p class="mt-0.5 text-xs" :class="p.atrasado ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'">
                    Há {{ p.dias_parado ?? 0 }} dia(s) nesta etapa
                  </p>
                </div>
                <PedidoAhStatusBadge :label="p.status_label" :cor="p.status_cor" />
              </div>
            </Link>
          </li>
        </ul>
      </ListContainer>
    </div>

    <!-- Trilha recente -->
    <ListContainer
      class="mt-6"
      title="Últimas tramitações"
      :icon="ArrowsRightLeftIcon"
      :count="ultimasTramitacoes.length"
    >
      <ListEmptyState
        v-if="!ultimasTramitacoes.length"
        title="Nenhuma tramitação registrada"
        helper="As movimentações de processo aparecem aqui"
      />

      <ul v-else class="divide-y divide-slate-100 dark:divide-slate-800">
        <li v-for="t in ultimasTramitacoes" :key="t.id" class="px-4 py-3 sm:px-6">
          <Link :href="route('ajuda-humanitaria.pedidos.show', t.pedido_id)" class="block">
            <div class="flex flex-wrap items-center gap-2 text-sm">
              <span class="font-semibold text-slate-800 dark:text-slate-100">
                Pedido {{ t.identificador }}
              </span>
              <span class="text-slate-400">·</span>
              <span class="text-slate-500 dark:text-slate-400">{{ t.municipio ?? '—' }}</span>
              <span class="text-slate-400">·</span>
              <span class="text-slate-500 dark:text-slate-400">{{ t.de }}</span>
              <span class="text-slate-400">→</span>
              <PedidoAhStatusBadge :label="t.para" :cor="t.para_cor" />
            </div>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
              {{ formatarDataHora(t.quando) }}<span v-if="t.autor"> · {{ t.autor }}</span>
            </p>
          </Link>
        </li>
      </ul>
    </ListContainer>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PedidoAhStatusBadge from '@/Components/Atoms/AjudaHumanitaria/PedidoAhStatusBadge.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  estatisticas: { type: Object, default: () => ({}) },
  aguardandoAcao: { type: Array, default: () => [] },
  prestacoesEmRisco: { type: Array, default: () => [] },
  ultimasTramitacoes: { type: Array, default: () => [] },
  canCreate: { type: Boolean, default: false },
});

function formatarData(valor) {
  if (!valor) return '—';

  const [ano, mes, dia] = String(valor).split('-');

  return dia ? `${dia}/${mes}/${ano}` : valor;
}

function formatarDataHora(valor) {
  if (!valor) return '—';

  return new Date(valor).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
