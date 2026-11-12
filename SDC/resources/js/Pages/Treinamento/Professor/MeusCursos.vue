<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import CursoSelos from '@/Components/Molecules/Treinamento/CursoSelos.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  cursos: { type: Array, default: () => [] },
  // So quem ja cria treinamento (admin) abre curso; o backend recusa os demais.
  pode_abrir: { type: Boolean, default: false },
});

const { show: toast } = useToast();
const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

function executar(rota, curso) {
  router.post(route(rota, curso.id), {}, {
    preserveScroll: true,
    onError: (errors) => toast(errors.ciclo || 'Nao foi possivel alterar o curso.', 'error'),
  });
}

function desativar(curso) {
  pedirConfirmacao({
    title: 'Desativar curso',
    message: `Desativar "${curso.titulo}"?`,
    description: 'O curso sai do catalogo e os alunos perdem o acesso a sala. Quem ja concluiu mantem o certificado, e voce pode reativar o curso depois.',
    variant: 'danger',
    confirmText: 'Desativar',
  }, () => executar('treinamentos.professor.desativar', curso));
}
</script>

<template>
  <div>
    <PageHeader
      title="Meus cursos"
      description="Cursos EAD em que voce foi designado como professor"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    >
      <template v-if="pode_abrir" #actions>
        <Button size="sm" :href="route('treinamentos.create', { categoria: 'CURSO', tipo: 'EAD' })">Abrir curso</Button>
      </template>
    </PageHeader>

    <CardBase v-if="cursos.length === 0" class="mt-6">
      <Text color="muted">Voce ainda nao foi designado para nenhum curso.</Text>
    </CardBase>

    <div v-else class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      <CardBase v-for="curso in cursos" :key="curso.id">
        <CursoSelos :publicado="curso.publicado" :situacao="curso.situacao" :situacao-label="curso.situacao_label" />

        <Heading :level="3" class="mt-2 text-base font-semibold">{{ curso.titulo }}</Heading>

        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
          <Text size="xs" color="muted">Publico: {{ curso.publico_alvo_label }}</Text>
          <Text size="xs" color="muted">{{ curso.alunos }} aluno(s)</Text>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <Button size="sm" :href="route('treinamentos.professor.conteudo', curso.id)">Conteudo</Button>
          <Button size="sm" variant="outline" :href="route('treinamentos.professor.previa', curso.id)">Previa</Button>
          <Button v-if="curso.situacao === 'ATIVO'" size="sm" variant="warning" @click="executar('treinamentos.professor.pausar', curso)">Pausar</Button>
          <Button v-if="curso.situacao === 'PAUSADO'" size="sm" variant="success" @click="executar('treinamentos.professor.retomar', curso)">Retomar</Button>
          <Button v-if="curso.situacao !== 'DESATIVADO'" size="sm" variant="danger" @click="desativar(curso)">Desativar</Button>
          <Button v-else size="sm" variant="success" @click="executar('treinamentos.professor.reativar', curso)">Reativar</Button>
        </div>
      </CardBase>
    </div>

    <ConfirmDialog
      :is-open="confirmacao.aberto"
      v-bind="confirmacao.opcoes"
      @confirm="confirmar"
      @cancel="cancelar"
    />
  </div>
</template>
