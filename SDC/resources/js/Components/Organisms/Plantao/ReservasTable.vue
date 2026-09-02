<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import ReservaStatusBadge from '@/Components/Molecules/Plantao/ReservaStatusBadge.vue';

const props = defineProps({
  reservas: {
    type: Array,
    default: () => [],
  },
  // Id do usuario da sessao: separa "minha reserva" de "reserva de outra
  // pessoa" na coluna de agente e decide se o cancelar aparece.
  agenteAtualId: {
    type: Number,
    default: null,
  },
  canManage: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['cancelar']);

const formatarInstante = (iso) => {
  if (!iso) return '--';

  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Cancelar exige ser dono da reserva ou ter `manage`. A mesma regra roda no
// ReservaCancelarController e responde 403 -- esconder o botao aqui e
// conveniencia de tela, nao a guarda.
const podeCancelar = (reserva) =>
  reserva.pode_cancelar && (props.canManage || reserva.agente_id === props.agenteAtualId);
</script>

<template>
  <ListContainer title="Reservas de viatura" :icon="CalendarIcon">
    <table class="w-full text-sm text-left">
      <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Viatura</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Agente</th>
          <!--
            No celular sobram Viatura, Agente, Inicio, Status e Acoes. `Fim` e
            `Destino` saem porque a tabela rola lateralmente e o que importa na
            tela pequena e "de quem e, e a partir de quando" -- o resto se le no
            desktop. Esconder colunas e melhor que rolagem de 7 colunas.
          -->
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Inicio</th>
          <th class="hidden md:table-cell px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Fim</th>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-center">Status</th>
          <th class="hidden lg:table-cell px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Destino</th>
          <th class="table-actions-head px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right w-28 min-w-28">Acoes</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <tr
          v-for="item in reservas"
          :key="item.id"
          class="hover:bg-slate-50 dark:hover:bg-slate-800/40"
        >
          <td class="px-4 py-3">
            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ item.viatura_prefixo }}</span>
            <span class="ml-1 text-xs text-slate-500 dark:text-slate-400">{{ item.viatura_placa }}</span>
          </td>

          <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
            {{ item.agente_nome }}
            <span
              v-if="item.agente_id === agenteAtualId"
              class="ml-1 text-xs font-medium text-blue-600 dark:text-blue-400"
            >(voce)</span>
          </td>

          <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs">
            {{ formatarInstante(item.inicio_previsto) }}
            <!-- Coluna Fim escondida no celular: o fim vira segunda linha aqui,
                 para a informacao nao desaparecer junto com a coluna. -->
            <span class="block md:hidden text-[10px] text-slate-400">
              ate {{ formatarInstante(item.fim_previsto) }}
            </span>
          </td>
          <td class="hidden md:table-cell px-4 py-3 text-slate-600 dark:text-slate-400 text-xs">{{ formatarInstante(item.fim_previsto) }}</td>

          <td class="px-4 py-3 text-center">
            <ReservaStatusBadge :status="item.status_valor" :label="item.status" size="sm" />
          </td>

          <td class="hidden lg:table-cell px-4 py-3 text-slate-500 dark:text-slate-400 text-xs">{{ item.destino ?? '--' }}</td>

          <td class="table-actions-cell px-4 py-3 text-right w-28 min-w-28">
            <div class="flex justify-end">
              <ActionButton
                module="plantao"
                resource="reservas"
                :actions="[
                  {
                    action: 'delete',
                    // O slug consultado e `plantao.reservas.create`, o mesmo da
                    // rota: cancelar a propria reserva e operacao diaria de
                    // quem reserva. Derrubar a de outra pessoa depende de
                    // `manage`, e quem decide isso e o `allowed` abaixo (e, de
                    // novo, o controller).
                    aliasOverride: 'create',
                    label: 'Cancelar reserva',
                    handler: () => emit('cancelar', item.id),
                    allowed: podeCancelar(item),
                  },
                ]"
              />
            </div>
          </td>
        </tr>

        <tr v-if="!reservas || reservas.length === 0">
          <td colspan="7" class="p-0">
            <ListEmptyState
              title="Nenhuma reserva encontrada"
              helper="Reserve uma viatura para poder retirar a chave pelo QR Code."
            />
          </td>
        </tr>
      </tbody>
    </table>
  </ListContainer>
</template>
