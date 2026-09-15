<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CombustivelGauge from '@/Components/Atoms/Plantao/CombustivelGauge.vue';
import HodometroBadge from '@/Components/Atoms/Plantao/HodometroBadge.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

// Nomeado porque acoesDe() le as permissoes fora do template.
const props = defineProps({
  viaturas: {
    type: Array,
    default: () => [],
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  canMovimentar: {
    type: Boolean,
    default: false,
  },
  // Emitir a etiqueta do chaveiro define qual token abre a chave daquela
  // viatura: fica sob `plantao.reservas.manage`, nao sob `viaturas.view`.
  canQrCode: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['edit', 'delete', 'movimentacao', 'qrcode']);

// Cor por status literal no .vue: Tailwind nao escaneia app/**/*.php, entao o
// backend so manda o valor cru (status_valor) e o mapa fica aqui.
const CORES_STATUS = {
  DISPONIVEL: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
  EM_TRANSITO: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
  MANUTENCAO: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  CEDIDA: 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',
  INDISPONIVEL: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
  // Nao existe no enum PHP: estado de EXIBICAO, derivado de "DISPONIVEL com
  // reserva agendada". Ambar -- nem livre, nem avariada: comprometida.
  RESERVADA: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
};

const getStatusClasses = (statusValor) => CORES_STATUS[statusValor] ?? CORES_STATUS.INDISPONIVEL;

/**
 * Acoes do card. Fora do template, igual ao ViaturasTable e ao
 * BeneficiariosTable do Cisternas.
 */
const acoesDe = (item) => [
  {
    action: item.movimentacao_aberta_id ? 'finalize' : 'assign',
    aliasOverride: 'movimentar',
    label: item.movimentacao_aberta_id ? 'Registrar retorno' : 'Registrar saida',
    handler: () => emit('movimentacao', item.id),
    allowed: props.canMovimentar,
  },
  {
    // Menu suspenso com rotulo, no padrao do Cisternas: um icone solto na barra
    // nao diz que dali sai a etiqueta da chave.
    action: 'qrcode',
    placement: 'menu',
    // Slug consultado: plantao.reservas.manage. A etiqueta pertence ao ciclo da
    // chave, nao ao cadastro da viatura.
    resource: 'reservas',
    aliasOverride: 'manage',
    label: 'Etiqueta da chave',
    handler: () => emit('qrcode', item.id),
    allowed: props.canQrCode,
  },
  { action: 'edit', handler: () => emit('edit', item.id), allowed: props.canEdit },
  { action: 'delete', handler: () => emit('delete', item.id), allowed: props.canDelete },
];
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div
      v-for="item in viaturas"
      :key="item.id"
      class="bg-white dark:bg-slate-800/60 rounded-xl p-4 border border-slate-200 dark:border-slate-700/50 hover:border-slate-300 dark:hover:border-slate-600 transition-all shadow-sm hover:shadow-md"
    >
      <div class="flex items-start justify-between mb-3">
        <div>
          <p class="text-sm font-bold text-slate-900 dark:text-white">
            {{ item.prefixo }}
            <span class="ml-1 font-mono text-xs font-normal text-slate-500">{{ item.placa }}</span>
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1" :title="item.modelo">
            {{ item.modelo }}<span v-if="item.marca"> ({{ item.marca }})</span>
          </p>
        </div>
        <!--
          status_exibicao: mostra RESERVADA quando a viatura esta DISPONIVEL com
          reserva agendada. Reservada nao pode aparecer como livre.
        -->
        <span class="shrink-0 text-right">
          <span :class="getStatusClasses(item.status_exibicao_valor)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">
            {{ item.status_exibicao }}
          </span>
          <span
            v-if="item.reservada"
            class="mt-1 block text-[10px] leading-tight text-slate-500 dark:text-slate-400"
          >
            {{ item.reserva_agente_nome }}
          </span>
        </span>
      </div>

      <div class="flex items-center justify-between gap-3 mb-4 p-2 rounded-lg bg-slate-50 dark:bg-slate-700/30">
        <div class="flex flex-col gap-1 text-xs">
          <span class="text-slate-500 dark:text-slate-400">Localizacao</span>
          <span class="font-medium text-slate-700 dark:text-slate-200">{{ item.localizacao }}</span>
          <span class="text-slate-500 dark:text-slate-400 mt-2">Hodometro</span>
          <HodometroBadge :valor="item.hodometro" />
          <span class="text-slate-500 dark:text-slate-400 mt-2">Ultimo condutor</span>
          <span class="font-medium text-slate-700 dark:text-slate-200">{{ item.ultimo_condutor_nome ?? '--' }}</span>
        </div>

        <CombustivelGauge
          :percentual="item.combustivel_percentual"
          :label="item.combustivel_label ?? ''"
        />
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700/50">
        <ActionButton
          module="plantao"
          resource="viaturas"
          :actions="acoesDe(item)"
        />
      </div>
    </div>

    <div v-if="!viaturas || viaturas.length === 0" class="col-span-full">
      <ListEmptyState
        title="Nenhuma viatura encontrada"
        helper="Ajuste os filtros ou cadastre uma nova viatura."
      />
    </div>
  </div>
</template>
