<script setup>
/**
 * Pausar, retomar, desativar e reativar um curso EAD. Usado no card de "Meus
 * cursos" (professor) e no detalhe do treinamento (admin), com a mesma
 * confirmacao antes de desativar.
 */
import { router } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  curso: { type: Object, required: true }, // { id, titulo, situacao }
  size: { type: String, default: 'sm' },
});

const { show: toast } = useToast();
const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

function executar(rota) {
  router.post(route(rota, props.curso.id), {}, {
    preserveScroll: true,
    onError: (errors) => toast(errors.ciclo || 'Nao foi possivel alterar o curso.', 'error'),
  });
}

function desativar() {
  pedirConfirmacao({
    title: 'Desativar curso',
    message: `Desativar "${props.curso.titulo}"?`,
    description: 'O curso sai do catalogo e os alunos perdem o acesso a sala. Quem ja concluiu mantem o certificado, e o curso pode ser reativado depois.',
    variant: 'danger',
    confirmText: 'Desativar',
  }, () => executar('treinamentos.professor.desativar'));
}
</script>

<template>
  <Button v-if="curso.situacao === 'ATIVO'" :size="size" variant="warning" @click="executar('treinamentos.professor.pausar')">Pausar</Button>
  <Button v-if="curso.situacao === 'PAUSADO'" :size="size" variant="success" @click="executar('treinamentos.professor.retomar')">Retomar</Button>
  <Button v-if="curso.situacao !== 'DESATIVADO'" :size="size" variant="danger" @click="desativar">Desativar</Button>
  <Button v-else :size="size" variant="success" @click="executar('treinamentos.professor.reativar')">Reativar</Button>

  <ConfirmDialog
    :is-open="confirmacao.aberto"
    v-bind="confirmacao.opcoes"
    @confirm="confirmar"
    @cancel="cancelar"
  />
</template>
