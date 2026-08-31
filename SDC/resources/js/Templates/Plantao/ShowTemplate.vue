<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import ViaturaSnapshotCard from '@/Components/Molecules/Plantao/ViaturaSnapshotCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import RelatorioPassagemPanel from '@/Components/Organisms/Plantao/RelatorioPassagemPanel.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import {
  ArrowLeftIcon,
  ExclamationTriangleIcon,
  PencilSquareIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  plantao: {
    type: Object,
    required: true,
  },
  canRelatorio: {
    type: Boolean,
    default: false,
  },
});

// Cor de UI nunca vem de enum PHP (Tailwind nao escaneia app/**/*.php):
// o backend manda o valor cru (status_valor) e o mapeamento fica aqui.
const STATUS_CLASSES = {
  ATIVO: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/25',
  PENDENTE_ACEITE: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 ring-1 ring-amber-500/25',
  FINALIZADO: 'bg-slate-500/15 text-slate-600 dark:text-slate-400 ring-1 ring-slate-500/25',
  FINALIZADO_COM_DIVERGENCIA: 'bg-red-500/15 text-red-600 dark:text-red-400 ring-1 ring-red-500/25',
};

const statusClasses = computed(
  () => STATUS_CLASSES[props.plantao.status_valor] ?? STATUS_CLASSES.FINALIZADO,
);

const voltar = () => router.visit(route('plantao.index'));
const editar = () => router.visit(route('plantao.edit', props.plantao.id));
</script>

<template>
  <div class="plantao-show-container">
    <PageHeader
      :title="`Turno de ${plantao.data} (${plantao.periodo_label})`"
      :description="`Assumido por ${plantao.plantonista_nome}`"
      :icon="ClockIcon"
      :icon-image="moduleIcon('plantao')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <span
            :class="statusClasses"
            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
          >
            {{ plantao.status_label }}
          </span>

          <Button variant="secondary" size="md" :icon="ArrowLeftIcon" icon-position="left" @click="voltar">
            Voltar
          </Button>

          <Button
            v-if="plantao.pode_editar"
            variant="primary"
            size="md"
            :icon="PencilSquareIcon"
            icon-position="left"
            @click="editar"
          >
            Editar
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Cabecalho do turno -->
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40">
      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Assumido por</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ plantao.plantonista_nome }}</dd>
        </div>
        <div v-if="plantao.plantonista_saida_nome">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Saindo de serviço</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ plantao.plantonista_saida_nome }}</dd>
        </div>
        <div v-if="plantao.localizacao">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Localização</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ plantao.localizacao }}</dd>
        </div>

        <div v-if="plantao.encerrado_em">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Encerrado em</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">
            {{ plantao.encerrado_em }}
            <span v-if="plantao.encerrado_por_nome">
              por {{ plantao.encerrado_por_nome }}
              <span v-if="plantao.encerrado_por_terceiro" class="text-xs text-amber-600 dark:text-amber-400">
                (em nome de {{ plantao.plantonista_nome }})
              </span>
            </span>
          </dd>
        </div>

        <div v-if="plantao.aceito_em">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Aceito em</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">
            {{ plantao.aceito_em }}
            <span v-if="plantao.aceito_por_nome">por {{ plantao.aceito_por_nome }}</span>
          </dd>
        </div>

        <div v-if="plantao.observacoes" class="sm:col-span-2 lg:col-span-3">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Observações</dt>
          <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-900 dark:text-slate-100">{{ plantao.observacoes }}</dd>
        </div>
      </dl>

      <div
        v-if="plantao.divergencia"
        class="mt-4 flex items-start gap-2 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
      >
        <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
        <div>
          <p class="font-semibold">Divergência apontada no aceite</p>
          <p>{{ plantao.divergencia }}</p>
        </div>
      </div>
    </div>

    <!-- Viaturas do turno -->
    <CollapsibleSection
      namespace="plantao"
      section-id="show-viaturas"
      title="Viaturas do turno"
      subtitle="Snapshot declarado no encerramento"
      :icon="TruckIcon"
      class="mb-6"
    >
      <div v-if="plantao.snapshots.length" class="space-y-3">
        <ViaturaSnapshotCard
          v-for="snapshot in plantao.snapshots"
          :key="snapshot.id"
          :snapshot="snapshot"
        />
      </div>
      <ListEmptyState v-else title="Nenhuma viatura declarada" helper="O turno ainda não tem snapshot registrado." />
    </CollapsibleSection>

    <!-- Movimentacoes do turno -->
    <CollapsibleSection
      namespace="plantao"
      section-id="show-movimentacoes"
      title="Movimentações do turno"
      subtitle="Saídas e retornos registrados durante este turno"
      :icon="ClockIcon"
      class="mb-6"
    >
      <div v-if="plantao.movimentacoes.length" class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-slate-200 dark:border-slate-700">
            <tr class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
              <th class="px-3 py-2">Viatura</th>
              <th class="px-3 py-2">Condutor</th>
              <th class="px-3 py-2">Saída</th>
              <th class="px-3 py-2">Retorno</th>
              <th class="px-3 py-2">Destino / Motivo</th>
              <th class="px-3 py-2">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="mov in plantao.movimentacoes" :key="mov.id">
              <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">
                {{ mov.prefixo }} - {{ mov.placa }}
              </td>
              <td class="px-3 py-2 text-slate-600 dark:text-slate-300">{{ mov.condutor_nome || '-' }}</td>
              <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                {{ mov.saida_em || '-' }}
                <span v-if="mov.saida_hodometro" class="block text-xs text-slate-400">
                  {{ mov.saida_hodometro }} km · {{ mov.saida_combustivel_label }}
                </span>
              </td>
              <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                {{ mov.retorno_em || '-' }}
                <span v-if="mov.retorno_hodometro" class="block text-xs text-slate-400">
                  {{ mov.retorno_hodometro }} km · {{ mov.retorno_combustivel_label }}
                </span>
              </td>
              <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                <span>{{ mov.destino || '-' }}</span>
                <span v-if="mov.motivo" class="block text-xs text-slate-400">{{ mov.motivo }}</span>
                <span v-if="mov.alteracoes" class="block text-xs text-amber-600 dark:text-amber-400">{{ mov.alteracoes }}</span>
              </td>
              <td class="px-3 py-2 text-slate-600 dark:text-slate-300">{{ mov.status_label }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <ListEmptyState v-else title="Nenhuma movimentação neste turno" helper="Nenhuma viatura saiu ou retornou durante este turno." />
    </CollapsibleSection>

    <!-- Ocorrencias de destaque -->
    <CollapsibleSection
      namespace="plantao"
      section-id="show-ocorrencias"
      title="Ocorrências de destaque"
      :icon="ExclamationTriangleIcon"
      class="mb-6"
    >
      <p v-if="plantao.ocorrencias_destaque" class="whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">
        {{ plantao.ocorrencias_destaque }}
      </p>
      <ListEmptyState v-else title="Nenhuma ocorrência de destaque" helper="" />
    </CollapsibleSection>

    <!-- Relatorio de passagem de servico -->
    <RelatorioPassagemPanel v-if="canRelatorio" :plantao-id="plantao.id" />
  </div>
</template>

<style scoped>
.plantao-show-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
