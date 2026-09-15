<template>
  <div v-if="!prestacao" class="text-sm text-slate-500 dark:text-slate-400">
    A prestação de contas é aberta automaticamente quando o pedido passa a
    Atendido, com os materiais que foram liberados.
  </div>

  <div v-else class="space-y-6">
    <!-- Cabecalho: prazo (RN-16) e situacao -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
      <div class="flex flex-wrap items-center gap-3">
        <span
          :class="[
            'rounded-full px-2.5 py-1 text-xs font-medium',
            prestacao.homologada
              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'
              : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
          ]"
        >
          {{ prestacao.status_label }}
        </span>

        <span class="text-sm text-slate-600 dark:text-slate-300">
          Prazo: {{ formatarData(prestacao.data_limite) }}
        </span>

        <span
          v-if="prestacao.vencida && !prestacao.homologada"
          class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-200"
        >
          Vencida
        </span>
      </div>

      <Button
        v-if="canHomologar && !prestacao.homologada"
        variant="success"
        size="md"
        :disabled="prestacao.saldo_total > 0"
        :title="prestacao.saldo_total > 0 ? 'Ainda há saldo pendente de entrega' : ''"
        @click="$emit('homologar')"
      >
        Homologar
      </Button>
    </div>

    <p v-if="prestacao.saldo_total > 0 && canHomologar" class="text-xs text-slate-500 dark:text-slate-400">
      A homologação só é liberada quando todos os materiais estiverem
      integralmente distribuídos. Faltam {{ prestacao.saldo_total }} unidade(s).
    </p>

    <!-- Um bloco por material -->
    <div
      v-for="item in prestacao.itens"
      :key="item.id"
      class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            {{ item.nome_material }}
          </p>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            {{ item.entregue }} de {{ item.qtd }} entregue(s) ·
            <span :class="item.saldo > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
              saldo {{ item.saldo }}
            </span>
          </p>
        </div>

        <Button
          v-if="canLancar && item.saldo > 0 && !prestacao.homologada"
          variant="primary"
          size="sm"
          @click="abrirModal(item)"
        >
          Lançar entrega
        </Button>
      </div>

      <!-- Barra de progresso do item -->
      <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
        <div
          class="h-full rounded-full bg-emerald-500 transition-all"
          :style="{ width: `${percentual(item)}%` }"
        ></div>
      </div>

      <ResponsiveTable
        v-if="item.entregas.length"
        :items="item.entregas"
        :mobile-fields="CAMPOS_MOBILE_ENTREGA"
        :get-item-title="(e) => e.nome_beneficiario"
        empty-message="Nenhuma entrega lançada para este material."
        class="mt-4"
      >
        <template #table>
      <table v-if="item.entregas.length" class="mt-4 min-w-full text-sm">
        <thead>
          <tr class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
            <th class="py-2">Beneficiário</th>
            <th class="py-2">RG</th>
            <th class="py-2">Comunidade</th>
            <th class="py-2 text-right">Qtd</th>
            <th class="py-2">Entrega</th>
            <th v-if="canLancar && !prestacao.homologada" class="py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="e in item.entregas"
            :key="e.id"
            class="border-t border-slate-100 dark:border-slate-800"
          >
            <td class="py-2 text-slate-700 dark:text-slate-200">{{ e.nome_beneficiario }}</td>
            <td class="py-2 text-slate-500 dark:text-slate-400">{{ e.rg || '—' }}</td>
            <td class="py-2 text-slate-500 dark:text-slate-400">{{ e.comunidade || '—' }}</td>
            <td class="py-2 text-right text-slate-700 dark:text-slate-200">{{ e.qtd }}</td>
            <td class="py-2 text-slate-500 dark:text-slate-400">{{ formatarData(e.data_entrega) }}</td>
            <td v-if="canLancar && !prestacao.homologada" class="py-2 text-right">
              <button
                type="button"
                class="text-xs font-medium text-red-600 hover:underline dark:text-red-400"
                @click="$emit('remover-entrega', e.id)"
              >
                Remover
              </button>
            </td>
          </tr>
        </tbody>
      </table>
        </template>

        <template #mobile-c-rg="{ item: e }">
          {{ e.rg || '—' }}
        </template>

        <template #mobile-c-comunidade="{ item: e }">
          {{ e.comunidade || '—' }}
        </template>

        <template #mobile-c-entrega="{ item: e }">
          {{ formatarData(e.data_entrega) }}
        </template>

        <template
          v-if="canLancar && !prestacao.homologada"
          #mobile-actions="{ item: e }"
        >
          <button
            type="button"
            class="text-xs font-medium text-red-600 hover:underline dark:text-red-400"
            @click="$emit('remover-entrega', e.id)"
          >
            Remover
          </button>
        </template>
      </ResponsiveTable>

      <p v-else class="mt-3 text-xs text-slate-500 dark:text-slate-400">
        Nenhuma entrega lançada para este material.
      </p>
    </div>

    <Modal :show="modalAberto" max-width="2xl" @close="fecharModal">
      <div class="p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
          Lançar entrega
        </h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          {{ itemEmEdicao?.nome_material }} · saldo {{ itemEmEdicao?.saldo }}
        </p>

        <div class="mt-4 space-y-4">
          <FormField
            v-model="formulario.nome_beneficiario"
            label="Nome do beneficiário"
            :required="true"
          />

          <div class="grid gap-4 sm:grid-cols-2">
            <FormField v-model="formulario.rg" label="RG" />
            <FormField v-model="formulario.comunidade" label="Comunidade" />
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <FormField
              v-model="formulario.qtd"
              label="Quantidade"
              type="number"
              :required="true"
            />
            <FormDateField
              v-model="formulario.data_entrega"
              label="Data da entrega"
              :required="true"
            />
          </div>

          <p v-if="excedeSaldo" class="text-xs text-red-600 dark:text-red-400">
            A quantidade excede o saldo disponível de {{ itemEmEdicao?.saldo }}.
          </p>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <Button variant="secondary" size="md" @click="fecharModal">Cancelar</Button>
          <Button variant="primary" size="md" :disabled="!podeSalvar" @click="salvar">
            Registrar
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Modal from '@/Components/Modal.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';

const props = defineProps({
  prestacao: { type: Object, default: null },
  canLancar: { type: Boolean, default: false },
  canHomologar: { type: Boolean, default: false },
});

const emit = defineEmits(['lancar-entrega', 'remover-entrega', 'homologar']);

const modalAberto = ref(false);
const itemEmEdicao = ref(null);

const formulario = reactive({
  nome_beneficiario: '',
  rg: '',
  comunidade: '',
  qtd: '',
  data_entrega: new Date().toISOString().slice(0, 10),
});

// RN-18 antecipada na interface. A regra continua sendo aplicada no servico:
// aqui ela so evita uma ida ao servidor para receber a recusa.
const excedeSaldo = computed(
  () => Number(formulario.qtd) > (itemEmEdicao.value?.saldo ?? 0),
);

const podeSalvar = computed(
  () =>
    formulario.nome_beneficiario.trim() !== '' &&
    Number(formulario.qtd) > 0 &&
    !excedeSaldo.value,
);

function percentual(item) {
  if (!item.qtd) return 0;

  return Math.min(100, Math.round((item.entregue / item.qtd) * 100));
}

function abrirModal(item) {
  itemEmEdicao.value = item;
  formulario.nome_beneficiario = '';
  formulario.rg = '';
  formulario.comunidade = '';
  formulario.qtd = '';
  formulario.data_entrega = new Date().toISOString().slice(0, 10);
  modalAberto.value = true;
}

function fecharModal() {
  modalAberto.value = false;
  itemEmEdicao.value = null;
}

function salvar() {
  emit('lancar-entrega', {
    prestacao_conta_item_id: itemEmEdicao.value.id,
    nome_beneficiario: formulario.nome_beneficiario,
    rg: formulario.rg || null,
    comunidade: formulario.comunidade || null,
    qtd: Number(formulario.qtd),
    data_entrega: formulario.data_entrega,
  });

  fecharModal();
}

function formatarData(valor) {
  if (!valor) return '—';

  const [ano, mes, dia] = String(valor).split('-');

  return dia ? `${dia}/${mes}/${ano}` : valor;
}

/**
 * Campos do card de entrega no mobile (regra 9).
 *
 * A quantidade fica de fora: ela e curta e ja aparece no titulo do card seria
 * ruido. Os quatro escolhidos sao os que identificam a entrega.
 */
const CAMPOS_MOBILE_ENTREGA = [
  { key: 'c-rg', label: 'RG' },
  { key: 'c-comunidade', label: 'Comunidade' },
  { key: 'qtd', label: 'Qtd' },
  { key: 'c-entrega', label: 'Entrega' },
];
</script>
