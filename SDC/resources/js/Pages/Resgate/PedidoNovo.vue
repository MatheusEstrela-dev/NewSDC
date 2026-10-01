<template>
  <Head :title="`Resgatar · ${item.titulo}`" />

  <div class="pb-6">
    <PageHeader
      :title="`Resgatar · ${item.titulo}`"
      description="Confira antes de confirmar. O pedido reserva os pontos e a unidade até a CEDEC aprovar ou recusar."
      :icon="CheckBadgeIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    />

    <div class="grid gap-6 lg:grid-cols-3">
      <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800 lg:col-span-2" data-pedido-resumo>
        <div class="flex flex-wrap items-center gap-2">
          <Badge :cor="TIPOS_ITEM[item.tipo]?.cor ?? 'slate'" size="sm">{{ TIPOS_ITEM[item.tipo]?.rotulo }}</Badge>
          <Badge v-if="item.demonstracao" cor="violet" size="sm">Demonstração</Badge>
          <Badge cor="slate" size="sm">{{ item.codigo }} · v{{ item.versao }}</Badge>
        </div>
        <p class="mt-3 text-sm text-slate-700 dark:text-slate-200">{{ item.descricao }}</p>

        <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Beneficiário</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ ente ? `${ente.escopo === 'municipio' ? 'Município' : 'Órgão'} ${ente.nome ?? `#${ente.id}`}` : '—' }}</dd></div>
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Instrumento</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ rotuloInstrumento(item.instrumento) }}</dd></div>
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Faixa exigida</dt><dd class="mt-1"><RankingFaixaBadge :faixa="item.faixa_minima" size="sm" /></dd></div>
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Faixa do ente (temporada fechada)</dt><dd class="mt-1"><RankingFaixaBadge v-if="avaliacao.faixa" :faixa="avaliacao.faixa.faixa" size="sm" /><span v-else class="font-semibold text-slate-900 dark:text-slate-100">Sem faixa</span></dd></div>
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Prazo da reserva</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ item.prazo_reserva_dias }} dias para a CEDEC decidir</dd></div>
          <div><dt class="text-xs text-slate-500 dark:text-slate-400">Entrega</dt><dd class="font-semibold text-slate-900 dark:text-slate-100">{{ item.unidade_responsavel }}</dd></div>
        </dl>

        <div v-if="item.documentos_exigidos?.length" class="mt-5">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Documentos que serão exigidos</p>
          <ul class="mt-2 list-inside list-disc text-sm text-slate-700 dark:text-slate-200">
            <li v-for="doc in item.documentos_exigidos" :key="doc">{{ doc }}</li>
          </ul>
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800" data-pedido-saldo>
        <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ avaliacao.demonstracao ? 'Saldo de demonstração' : 'Saldo resgatável' }}</h2>
        <dl class="mt-3 space-y-2 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Disponível</dt><dd class="font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ numero(avaliacao.saldo) }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Custo do item</dt><dd class="font-semibold tabular-nums text-slate-900 dark:text-slate-100">− {{ numero(avaliacao.custo) }}</dd></div>
          <div class="flex justify-between border-t border-slate-200 pt-2 dark:border-slate-700"><dt class="text-slate-500 dark:text-slate-400">Depois da reserva</dt><dd class="font-bold tabular-nums" :class="avaliacao.saldo - avaliacao.custo < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-300'">{{ numero(avaliacao.saldo - avaliacao.custo) }}</dd></div>
        </dl>

        <div v-if="avaliacao.impedimentos.length" role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-500/30 dark:bg-red-950/30 dark:text-red-200" data-pedido-impedimentos>
          <p class="font-bold">Não é possível resgatar agora:</p>
          <ul class="mt-1 list-inside list-disc">
            <li v-for="motivo in avaliacao.impedimentos" :key="motivo">{{ motivo }}</li>
          </ul>
        </div>

        <form v-else class="mt-4 space-y-3" @submit.prevent="confirmar">
          <label class="flex items-start gap-2 text-xs text-slate-700 dark:text-slate-200">
            <input v-model="form.ciente" type="checkbox" class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" data-pedido-ciente />
            Estou ciente de que o pedido é feito em nome do {{ ente?.escopo === 'orgao' ? 'órgão' : 'município' }}, que os pontos ficam reservados e que a CEDEC pode recusar.
          </label>
          <p v-if="form.errors.item || form.errors.ciente" role="alert" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.item || form.errors.ciente }}</p>
          <Button type="submit" variant="success" class="w-full" :disabled="!form.ciente" :loading="form.processing" data-pedido-confirmar>Confirmar resgate</Button>
        </form>

        <Link :href="route('resgate.catalogo')" class="mt-3 block text-center text-xs font-semibold text-slate-500 hover:underline dark:text-slate-400">Voltar ao catálogo</Link>
      </section>
    </div>
  </div>
</template>

<script setup>
/**
 * Confirmacao do pedido de resgate - pagina propria do SPA. Os impedimentos
 * vem da pre-avaliacao do backend; a solicitacao reavalia tudo com lock.
 * A chave de idempotencia vem do servidor: reenviar nao duplica o pedido.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { TIPOS_ITEM, rotuloInstrumento } from '@/Support/resgateCatalogo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  item: { type: Object, required: true },
  ente: { type: Object, default: null },
  avaliacao: { type: Object, required: true },
  chave: { type: String, required: true },
});

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));

const form = useForm({ item: props.item.codigo, chave: props.chave, ciente: false });

function confirmar() {
  form.post(route('resgate.pedidos.solicitar'), { preserveScroll: true });
}
</script>
