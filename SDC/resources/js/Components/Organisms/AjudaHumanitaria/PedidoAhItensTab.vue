<template>
  <div class="space-y-6">
    <!--
      RN-09: solicitado e liberado lado a lado. O solicitado congela quando o
      pedido sai da edicao; o liberado so pode ser lancado depois disso. Por
      isso os dois blocos nunca aceitam inclusao ao mesmo tempo.
    -->
    <div class="grid gap-6 lg:grid-cols-2">
      <BlocoItens
        titulo="Solicitado pelo município"
        :itens="itensSolicitados"
        :pode-incluir="podeIncluirSolicitado"
        :pode-remover="podeIncluirSolicitado"
        vazio="Nenhum material solicitado"
        @incluir="abrirModal('P')"
        @remover="(id) => $emit('remover-item', id)"
      />

      <BlocoItens
        titulo="Liberado pelo CEDEC"
        :itens="itensLiberados"
        :pode-incluir="podeIncluirLiberado"
        :pode-remover="podeIncluirLiberado"
        vazio="Nada liberado ainda"
        @incluir="abrirModal('L')"
        @remover="(id) => $emit('remover-item', id)"
      />
    </div>

    <p v-if="avisoDeMomento" class="text-xs text-slate-500 dark:text-slate-400">
      {{ avisoDeMomento }}
    </p>

    <Modal :show="modalAberto" max-width="lg" @close="fecharModal">
      <div class="p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
          {{ tipoEmEdicao === 'L' ? 'Liberar material' : 'Incluir material solicitado' }}
        </h3>

        <div class="mt-4 space-y-4">
          <FormSelect
            v-model="formulario.material_ah_id"
            label="Material"
            :options="opcoesMaterial"
            :required="true"
            placeholder="Selecione o material"
          />

          <div class="grid gap-4 sm:grid-cols-2">
            <FormField
              v-model="formulario.qtd"
              label="Quantidade"
              type="number"
              :required="true"
            />
            <FormField
              v-model="formulario.qtd_familia_atendida"
              label="Famílias atendidas"
              type="number"
              :required="true"
            />
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <Button variant="secondary" size="md" @click="fecharModal">Cancelar</Button>
          <Button variant="primary" size="md" :disabled="!podeSalvar" @click="salvar">
            Incluir
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, h, reactive, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Modal from '@/Components/Modal.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

const props = defineProps({
  itensSolicitados: { type: Array, default: () => [] },
  itensLiberados: { type: Array, default: () => [] },
  materiais: { type: Array, default: () => [] },
  status: { type: Number, default: null },
  canEdit: { type: Boolean, default: false },
  canLiberarItens: { type: Boolean, default: false },
});

const emit = defineEmits(['incluir-item', 'remover-item']);

const EDICAO_COMPDEC = 0;

const emEdicao = computed(() => props.status === EDICAO_COMPDEC);
const podeIncluirSolicitado = computed(() => props.canEdit && emEdicao.value);
const podeIncluirLiberado = computed(() => props.canLiberarItens && !emEdicao.value);

const avisoDeMomento = computed(() => {
  if (emEdicao.value) {
    return 'As quantidades liberadas só podem ser definidas depois que o pedido entrar em análise.';
  }

  return 'O pedido já foi enviado; os materiais solicitados ficam congelados como registro do que o município pediu.';
});

const opcoesMaterial = computed(() =>
  props.materiais.map((m) => ({ value: m.id, label: `${m.nome} (${m.unidade_medida})` })),
);

const modalAberto = ref(false);
const tipoEmEdicao = ref('P');

const formulario = reactive({
  material_ah_id: '',
  qtd: '',
  qtd_familia_atendida: '',
});

const podeSalvar = computed(
  () => formulario.material_ah_id !== '' && Number(formulario.qtd) > 0,
);

function abrirModal(tipo) {
  tipoEmEdicao.value = tipo;
  formulario.material_ah_id = '';
  formulario.qtd = '';
  formulario.qtd_familia_atendida = '';
  modalAberto.value = true;
}

function fecharModal() {
  modalAberto.value = false;
}

function salvar() {
  const material = props.materiais.find((m) => m.id === Number(formulario.material_ah_id));

  emit('incluir-item', {
    material_ah_id: formulario.material_ah_id,
    descricao_item: material?.nome ?? '',
    qtd: Number(formulario.qtd),
    qtd_familia_atendida: Number(formulario.qtd_familia_atendida || 0),
    tipo: tipoEmEdicao.value,
  });

  fecharModal();
}

/** Bloco de uma das duas listas de item. */
const BlocoItens = (props, { emit }) => {
  const cabecalho = h('div', { class: 'mb-3 flex items-center justify-between gap-2' }, [
    h('p', { class: 'text-sm font-semibold text-slate-700 dark:text-slate-200' }, props.titulo),
    props.podeIncluir
      ? h(
          Button,
          { variant: 'primary', size: 'sm', onClick: () => emit('incluir') },
          () => 'Incluir',
        )
      : null,
  ]);

  if (!props.itens?.length) {
    return h('div', {}, [cabecalho, h(ListEmptyState, { title: props.vazio })]);
  }

  return h('div', {}, [
    cabecalho,
    h('table', { class: 'min-w-full text-sm' }, [
      h('thead', {}, [
        h('tr', { class: 'text-left text-xs uppercase text-slate-500 dark:text-slate-400' }, [
          h('th', { class: 'py-2' }, 'Material'),
          h('th', { class: 'py-2 text-right' }, 'Qtd'),
          h('th', { class: 'py-2 text-right' }, 'Famílias'),
          props.podeRemover ? h('th', { class: 'py-2' }) : null,
        ]),
      ]),
      h(
        'tbody',
        {},
        props.itens.map((item) =>
          h('tr', { key: item.id, class: 'border-t border-slate-100 dark:border-slate-800' }, [
            h('td', { class: 'py-2 text-slate-700 dark:text-slate-200' }, item.descricao_item),
            h('td', { class: 'py-2 text-right text-slate-700 dark:text-slate-200' }, item.qtd),
            h('td', { class: 'py-2 text-right text-slate-700 dark:text-slate-200' }, item.qtd_familia_atendida),
            props.podeRemover
              ? h('td', { class: 'py-2 text-right' }, [
                  h(
                    'button',
                    {
                      type: 'button',
                      class: 'text-xs font-medium text-red-600 hover:underline dark:text-red-400',
                      onClick: () => emit('remover', item.id),
                    },
                    'Remover',
                  ),
                ])
              : null,
          ]),
        ),
      ),
    ]),
  ]);
};
BlocoItens.props = ['titulo', 'itens', 'podeIncluir', 'podeRemover', 'vazio'];
BlocoItens.emits = ['incluir', 'remover'];
</script>
