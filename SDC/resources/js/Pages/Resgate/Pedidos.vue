<template>
  <Head title="Pedidos de resgate" />

  <div class="pb-6">
    <PageHeader
      title="Pedidos de resgate"
      :description="visaoGlobal ? 'Todos os pedidos. Os reservados aguardam a decisão da CEDEC.' : 'Pedidos do seu município.'"
      :icon="CheckBadgeIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    >
      <template #actions>
        <Button :href="route('resgate.catalogo')" variant="sky" :icon="GiftIcon">Catálogo</Button>
      </template>
    </PageHeader>

    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filtrar por status">
      <Link v-for="opcao in FILTROS" :key="opcao.valor ?? 'todos'" :href="route('resgate.pedidos', opcao.valor ? { status: opcao.valor } : {})" preserve-scroll
        class="rounded-full border px-3 py-1 text-xs font-semibold transition"
        :class="(filtros.status ?? null) === opcao.valor ? 'border-blue-500 bg-blue-500 text-white' : 'border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700'"
      >{{ opcao.rotulo }}</Link>
    </nav>

    <ul v-if="pedidos.length" class="space-y-3">
      <li v-for="pedido in pedidos" :key="pedido.id" class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800" data-pedido>
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <Link :href="route('resgate.pedidos.show', pedido.id)" class="text-sm font-bold text-blue-700 hover:underline dark:text-blue-300">{{ pedido.protocolo }}</Link>
              <StatusPedidoBadge :status="pedido.status" />
              <Badge v-if="pedido.demonstracao" cor="violet" size="sm">Demonstração</Badge>
            </div>
            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ pedido.item_titulo }} <span class="font-normal text-slate-500">v{{ pedido.item_versao }}</span></p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              {{ entes[`${pedido.ente_escopo}:${pedido.ente_id}`] ?? `#${pedido.ente_id}` }} ·
              {{ pedido.custo_pontos > 0 ? `${numero(pedido.custo_pontos)} pontos` : 'só pela faixa' }}
              <template v-if="pedido.unidade_patrimonio"> · unidade {{ pedido.unidade_patrimonio }}</template>
              · por {{ usuarios[pedido.solicitado_por] ?? `#${pedido.solicitado_por}` }} em {{ dataHora(pedido.solicitado_em) }}
            </p>
          </div>
          <div class="w-full sm:w-auto sm:min-w-[18rem]">
            <DecisaoPedido :pedido="pedido" :pode-aprovar="podeAprovar" :usuario-id="usuarioId" />
          </div>
        </div>
      </li>
    </ul>
    <p v-else class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">Nenhum pedido neste filtro.</p>
  </div>
</template>

<script setup>
/** Pedidos de resgate - pagina propria do SPA; decisao no proprio card. */
import { Head, Link } from '@inertiajs/vue3';
import { GiftIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import StatusPedidoBadge from '@/Components/Atoms/Resgate/StatusPedidoBadge.vue';
import DecisaoPedido from '@/Components/Organisms/Resgate/DecisaoPedido.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  pedidos: { type: Array, default: () => [] },
  entes: { type: Object, default: () => ({}) },
  usuarios: { type: Object, default: () => ({}) },
  filtros: { type: Object, default: () => ({ status: null }) },
  podeAprovar: { type: Boolean, default: false },
  visaoGlobal: { type: Boolean, default: false },
  usuarioId: { type: Number, default: 0 },
});

const FILTROS = [
  { valor: null, rotulo: 'Todos' },
  { valor: 'reservado', rotulo: 'Reservados' },
  { valor: 'aprovado', rotulo: 'Aprovados' },
  { valor: 'termo_emitido', rotulo: 'Termo emitido' },
  { valor: 'termo_assinado', rotulo: 'Termo assinado' },
  { valor: 'entregue', rotulo: 'Entregues' },
  { valor: 'contestado', rotulo: 'Contestados' },
  { valor: 'concluido', rotulo: 'Concluídos' },
  { valor: 'recusado', rotulo: 'Recusados' },
  { valor: 'cancelado', rotulo: 'Cancelados' },
  { valor: 'expirado', rotulo: 'Expirados' },
  { valor: 'anulado', rotulo: 'Anulados' },
];

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
const dataHora = (valor) => (valor ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(String(valor).replace(' ', 'T').replace(/\+00$/, 'Z'))) : '');
</script>
