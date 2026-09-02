<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CombustivelGauge from '@/Components/Atoms/Plantao/CombustivelGauge.vue';
import HodometroBadge from '@/Components/Atoms/Plantao/HodometroBadge.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
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
  // Nao existe no enum PHP: e estado de EXIBICAO, derivado de "DISPONIVEL com
  // reserva agendada". Ambar porque nao esta livre nem avariada -- esta
  // comprometida com alguem.
  RESERVADA: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
};

const getStatusClasses = (statusValor) => CORES_STATUS[statusValor] ?? CORES_STATUS.INDISPONIVEL;

/**
 * Acoes da linha. Fora do template no padrao do BeneficiariosTable do
 * Cisternas: alem de comportar comentario, um array literal dentro do atributo
 * quebra o parser assim que o comentario contem aspas duplas -- elas fecham o
 * atributo HTML antes da hora.
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
    // nao diz que dali sai a etiqueta da chave. O ActionButton ja conhece
    // 'qrcode' -- icone, rotulo e cor ciano vem dele.
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

const formatarJanela = (inicio, fim) => {
  if (!inicio) return '';

  const opcoes = { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' };
  const de = new Date(inicio).toLocaleString('pt-BR', opcoes);

  if (!fim) return de;

  // Mesmo dia: repetir a data nos dois lados so gasta espaco na celula.
  const mesmoDia = new Date(inicio).toDateString() === new Date(fim).toDateString();
  const ate = new Date(fim).toLocaleString(
    'pt-BR',
    mesmoDia ? { hour: '2-digit', minute: '2-digit' } : opcoes,
  );

  return `${de} - ${ate}`;
};
</script>

<template>
  <ListContainer
    title="Frota de Viaturas"
    :icon="TruckIcon"
  >
    <table class="w-full text-sm text-left">
      <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Prefixo</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Placa</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Modelo</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Localizacao</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-center">Status</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-center">Combustivel</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Hodometro</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Ultimo condutor</th>
          <th class="table-actions-head px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right w-36 min-w-36">Acoes</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
        <tr
          v-for="item in viaturas"
          :key="item.id"
          class="table-row-solid transition-colors"
        >
          <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-200">{{ item.prefixo }}</td>
          <td class="px-4 py-3 font-mono text-slate-700 dark:text-slate-300">{{ item.placa }}</td>
          <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
            {{ item.modelo }}
            <span v-if="item.marca" class="text-xs text-slate-400">({{ item.marca }})</span>
          </td>
          <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs">{{ item.localizacao }}</td>
          <td class="px-4 py-3 text-center">
            <!--
              status_exibicao, e nao status: o primeiro mostra RESERVADA quando a
              viatura esta DISPONIVEL com reserva agendada. Uma viatura reservada
              nao pode ser oferecida como livre nesta tela.
            -->
            <span :class="getStatusClasses(item.status_exibicao_valor)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
              {{ item.status_exibicao }}
            </span>
            <span
              v-if="item.reservada"
              class="mt-1 block text-[10px] leading-tight text-slate-500 dark:text-slate-400"
            >
              {{ item.reserva_agente_nome }}<br>{{ formatarJanela(item.reserva_inicio, item.reserva_fim) }}
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="flex justify-center">
              <CombustivelGauge
                :percentual="item.combustivel_percentual"
                :label="item.combustivel_label ?? ''"
                altura="h-10"
              />
            </div>
          </td>
          <td class="px-4 py-3">
            <HodometroBadge :valor="item.hodometro" />
          </td>
          <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs">{{ item.ultimo_condutor_nome ?? '--' }}</td>
          <td class="table-actions-cell px-4 py-3 text-right w-36 min-w-36">
            <div class="flex justify-end">
              <ActionButton
                module="plantao"
                resource="viaturas"
                :actions="acoesDe(item)"
              />
            </div>
          </td>
        </tr>
        <tr v-if="!viaturas || viaturas.length === 0">
          <td colspan="9" class="p-0">
            <ListEmptyState
              title="Nenhuma viatura encontrada"
              helper="Ajuste os filtros ou cadastre uma nova viatura."
            />
          </td>
        </tr>
      </tbody>
    </table>
  </ListContainer>
</template>
