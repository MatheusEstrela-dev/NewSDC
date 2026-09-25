<template>
  <Head :title="`Pedido ${pedido.protocolo}`" />

  <div class="pb-6">
    <PageHeader
      :title="`Pedido ${pedido.protocolo}`"
      :description="`${pedido.item_titulo} · ${entes[`${pedido.ente_escopo}:${pedido.ente_id}`] ?? `#${pedido.ente_id}`}`"
      :icon="CheckBadgeIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    />

    <div class="grid gap-6 lg:grid-cols-3">
      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800" data-pedido-dados>
        <div class="flex flex-wrap items-center gap-2">
          <StatusPedidoBadge :status="pedido.status" />
          <Badge v-if="pedido.demonstracao" cor="violet" size="sm">Demonstração</Badge>
        </div>
        <dl class="space-y-3 text-sm">
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Item (versão congelada)</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ pedido.item_codigo }} · v{{ pedido.item_versao }}</dd></div>
          <div v-if="pedido.unidade_patrimonio"><dt class="text-xs text-slate-500 dark:text-slate-400">Unidade reservada</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ pedido.unidade_patrimonio }}</dd></div>
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Pontos</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ pedido.custo_pontos > 0 ? numero(pedido.custo_pontos) : 'Só pela faixa' }}</dd></div>
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Faixa exigida / do ente</dt><dd class="mt-1 flex gap-2"><RankingFaixaBadge :faixa="pedido.faixa_exigida" size="sm" /><RankingFaixaBadge :faixa="pedido.faixa_do_ente" size="sm" /></dd></div>
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Solicitado por</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ usuarios[pedido.solicitado_por] ?? `#${pedido.solicitado_por}` }}</dd></div>
          <div v-if="pedido.status === 'reservado'"><dt class="text-xs text-slate-500 dark:text-slate-400">Reserva expira em</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ dataHora(pedido.expira_em) }}</dd></div>
          <div v-if="pedido.processo_sei"><dt class="text-xs text-slate-500 dark:text-slate-400">Processo SEI</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ pedido.processo_sei }} · termo {{ pedido.termo_documento_sei }}</dd></div>
          <div v-if="pedido.entregue_em"><dt class="text-xs text-slate-500 dark:text-slate-400">Entregue em</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ dataHora(pedido.entregue_em) }}</dd></div>
          <div v-if="pedido.concluido_em"><dt class="text-xs text-slate-500 dark:text-slate-400">Concluído em</dt><dd class="font-semibold text-emerald-700 dark:text-emerald-300">{{ dataHora(pedido.concluido_em) }}</dd></div>
        </dl>
        <DecisaoPedido :pedido="pedido" :pode-aprovar="podeAprovar" :usuario-id="usuarioId" />
      </section>

      <div class="space-y-6 lg:col-span-2">
        <ExecucaoPedido :pedido="pedido" :pode-aprovar="podeAprovar" :pode-entregar="podeEntregar" :age-pelo-ente="agePeloEnte" :usuario-id="usuarioId" />
        <LinhaDoTempoPedido :eventos="eventos" :usuarios="usuarios" :adulterado-em="adulterado_em" />
        <DocumentosPedido :documentos="documentos" :pedido-id="pedido.id" :usuarios="usuarios" />
        <ConsumoPedido :consumos="consumos" />
      </div>
    </div>
  </div>
</template>

<script setup>
/** Detalhe do pedido com a trilha auditavel - pagina propria do SPA. */
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';
import StatusPedidoBadge from '@/Components/Atoms/Resgate/StatusPedidoBadge.vue';
import DecisaoPedido from '@/Components/Organisms/Resgate/DecisaoPedido.vue';
import LinhaDoTempoPedido from '@/Components/Organisms/Resgate/LinhaDoTempoPedido.vue';
import ExecucaoPedido from '@/Components/Organisms/Resgate/ExecucaoPedido.vue';
import DocumentosPedido from '@/Components/Organisms/Resgate/DocumentosPedido.vue';
import ConsumoPedido from '@/Components/Organisms/Resgate/ConsumoPedido.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  pedido: { type: Object, required: true },
  eventos: { type: Array, default: () => [] },
  adulterado_em: { type: Number, default: null },
  entes: { type: Object, default: () => ({}) },
  usuarios: { type: Object, default: () => ({}) },
  podeAprovar: { type: Boolean, default: false },
  podeEntregar: { type: Boolean, default: false },
  agePeloEnte: { type: Boolean, default: false },
  documentos: { type: Array, default: () => [] },
  consumos: { type: Array, default: () => [] },
  usuarioId: { type: Number, default: 0 },
});

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
const dataHora = (valor) => (valor ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(String(valor).replace(' ', 'T').replace(/\+00$/, 'Z'))) : '');
</script>
