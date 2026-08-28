<script setup>
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import EscalaVagaModal from '@/Components/Organisms/Plantao/EscalaVagaModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EscalaIndexTemplate from '@/Templates/Plantao/EscalaIndexTemplate.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  competencia: { type: Object, required: true },
  escala: { type: Object, default: null },
  eventos: { type: Array, default: () => [] },
  minhasVagas: { type: Array, default: () => [] },
  tiposTurno: { type: Array, default: () => [] },
  plantonistas: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const modalVaga = ref({ show: false, vaga: null, dataSugerida: '' });
const dialogPublicar = ref({ open: false, loading: false });
const dialogAssumir = ref({ open: false, loading: false, itemId: null });

/**
 * Navegar no calendario recarrega o mes.
 *
 * Reload PARCIAL: `can`, `tiposTurno` e `plantonistas` nao mudam ao trocar de
 * mes, e as props do Inertia sao closures reavaliadas a cada visita completa --
 * sem o `only`, cada clique em "proximo mes" refaria a consulta de
 * plantonistas e o mapeamento de permissoes a toa.
 */
const mudarMes = ({ ano, mes }) => {
  if (ano === props.competencia.ano && mes === props.competencia.mes) return;

  router.get(
    route('plantao.escala.index'),
    { ano, mes },
    {
      preserveState: true,
      preserveScroll: true,
      only: ['competencia', 'escala', 'eventos', 'minhasVagas'],
    },
  );
};

const criarEscala = () => {
  router.post(route('plantao.escala.store'), {
    ano: props.competencia.ano,
    mes: props.competencia.mes,
  });
};

const abrirVagaNova = (data) => {
  modalVaga.value = { show: true, vaga: null, dataSugerida: data };
};

const abrirVagaExistente = (vaga) => {
  // Sem permissao de montar, clicar num turno nao abre nada: para o
  // plantonista comum o calendario e leitura.
  if (!props.can?.montar || !props.escala?.editavel) return;

  modalVaga.value = { show: true, vaga, dataSugerida: '' };
};

const fecharVaga = () => {
  modalVaga.value = { show: false, vaga: null, dataSugerida: '' };
};

const pedirPublicacao = () => {
  dialogPublicar.value = { open: true, loading: false };
};

const confirmarPublicacao = () => {
  if (!props.escala?.id) return;

  dialogPublicar.value.loading = true;
  router.post(
    route('plantao.escala.publicar', props.escala.id),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        dialogPublicar.value = { open: false, loading: false };
      },
      onFinish: () => {
        dialogPublicar.value.loading = false;
      },
    },
  );
};

const pedirAssuncao = (itemId) => {
  dialogAssumir.value = { open: true, loading: false, itemId };
};

const confirmarAssuncao = () => {
  const { itemId } = dialogAssumir.value;
  if (!itemId) return;

  dialogAssumir.value.loading = true;
  router.post(
    route('plantao.escala.itens.assumir', itemId),
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        dialogAssumir.value = { open: false, loading: false, itemId: null };
      },
    },
  );
};

const irParaPlantonistas = () => {
  router.get(route('plantao.plantonistas.index'));
};
</script>

<template>
  <EscalaIndexTemplate
    :competencia="competencia"
    :escala="escala"
    :eventos="eventos"
    :minhas-vagas="minhasVagas"
    :can="can"
    @criar-escala="criarEscala"
    @publicar="pedirPublicacao"
    @mudar-mes="mudarMes"
    @selecionar-dia="abrirVagaNova"
    @selecionar-vaga="abrirVagaExistente"
    @assumir="pedirAssuncao"
    @gerir-plantonistas="irParaPlantonistas"
  />

  <EscalaVagaModal
    :show="modalVaga.show"
    :vaga="modalVaga.vaga"
    :data-sugerida="modalVaga.dataSugerida"
    :escala-id="escala?.id ?? null"
    :tipos-turno="tiposTurno"
    :plantonistas="plantonistas"
    :pode-remover="!!can.montar"
    @close="fecharVaga"
  />

  <ConfirmDialog
    :is-open="dialogPublicar.open"
    variant="warning"
    title="Publicar escala"
    message="Publicar a escala e notificar os plantonistas?"
    description="Cada plantonista escalado recebe um aviso com as datas dele. Depois de publicada, qualquer troca de vaga tambem notifica quem entra e quem sai."
    confirm-text="Publicar"
    cancel-text="Cancelar"
    :loading="dialogPublicar.loading"
    @confirm="confirmarPublicacao"
    @cancel="dialogPublicar = { open: false, loading: false }"
  />

  <ConfirmDialog
    :is-open="dialogAssumir.open"
    variant="info"
    title="Assumir turno"
    message="Abrir o plantao desta vaga agora?"
    description="O turno sera aberto com a data e o horario da escala, e voce passa a ser o responsavel pela passagem de servico."
    confirm-text="Assumir"
    cancel-text="Cancelar"
    :loading="dialogAssumir.loading"
    @confirm="confirmarAssuncao"
    @cancel="dialogAssumir = { open: false, loading: false, itemId: null }"
  />
</template>
