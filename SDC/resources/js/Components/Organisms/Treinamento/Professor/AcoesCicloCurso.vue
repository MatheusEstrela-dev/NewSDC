<script setup>
/**
 * Pausar, retomar, desativar e reativar um curso EAD (so a chefia, RN04). Usado no card de "Meus
 * cursos" (professor) e no detalhe do treinamento (admin), com a mesma
 * confirmacao antes de desativar.
 */
import { router } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';
import { useToast } from '@/Composables/useToast';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
  curso: { type: Object, required: true }, // { id, titulo, situacao }
  size: { type: String, default: 'sm' },
});

const { show: toast } = useToast();
const { can } = usePermissions();

// RN04: pausar/retomar e desativar/reativar sao da chefia, cada um com seu slug.
const podePausar = () => can('treinamento.cursos.pausar');
const podeDesativar = () => can('treinamento.cursos.cancelar');
const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

function executar(rota, sucesso = null) {
  router.post(route(rota, props.curso.id), {}, {
    preserveScroll: true,
    onSuccess: () => { if (sucesso) toast(sucesso, 'success'); },
    onError: (errors) => toast(errors.ciclo || 'Nao foi possivel alterar o curso.', 'error'),
  });
}

function pausar() {
  pedirConfirmacao({
    title: 'Pausar curso',
    message: `Pausar "${props.curso.titulo}"?`,
    description: 'Os alunos perdem o acesso à sala até o curso ser retomado. O progresso deles é mantido.',
    variant: 'warning',
    confirmText: 'Pausar',
  }, () => executar('treinamentos.professor.pausar', 'Curso pausado.'));
}

function retomar() {
  pedirConfirmacao({
    title: 'Retomar curso',
    message: `Retomar "${props.curso.titulo}"?`,
    description: 'Os alunos voltam a ter acesso à sala do curso.',
    variant: 'info',
    confirmText: 'Retomar',
  }, () => executar('treinamentos.professor.retomar', 'Curso retomado.'));
}

function desativar() {
  pedirConfirmacao({
    title: 'Desativar curso',
    message: `Desativar "${props.curso.titulo}"?`,
    description: 'O curso sai do catalogo e os alunos perdem o acesso a sala. Quem ja concluiu mantem o certificado, e o curso pode ser reativado depois.',
    variant: 'danger',
    confirmText: 'Desativar',
  }, () => executar('treinamentos.professor.desativar', 'Curso desativado.'));
}
</script>

<template>
  <Button v-if="podePausar() && curso.situacao === 'ATIVO'" :size="size" variant="warning" @click="pausar">Pausar</Button>
  <Button v-if="podePausar() && curso.situacao === 'PAUSADO'" :size="size" variant="success" @click="retomar">Retomar</Button>
  <Button v-if="podeDesativar() && curso.situacao !== 'DESATIVADO'" :size="size" variant="danger" @click="desativar">Desativar</Button>
  <Button v-else-if="podeDesativar()" :size="size" variant="success" @click="executar('treinamentos.professor.reativar', 'Curso reativado.')">Reativar</Button>

  <ConfirmDialog
    :is-open="confirmacao.aberto"
    v-bind="confirmacao.opcoes"
    @confirm="confirmar"
    @cancel="cancelar"
  />
</template>
