<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CursoSelos from '@/Components/Molecules/Treinamento/CursoSelos.vue';
import AcoesCicloCurso from '@/Components/Organisms/Treinamento/Professor/AcoesCicloCurso.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useCursosAoVivo } from '@/Composables/treinamento/useCursosAoVivo';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  cursos: { type: Array, default: () => [] },
  // So quem ja cria treinamento (admin) abre curso; o backend recusa os demais.
  pode_abrir: { type: Boolean, default: false },
});

useCursosAoVivo(['cursos', 'pode_abrir'], { professor: true });



</script>

<template>
  <div>
    <PageHeader
      title="Meus cursos"
      :description="pode_abrir ? 'Todos os cursos EAD' : 'Cursos EAD em que você foi designado como professor'"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    >
      <template v-if="pode_abrir" #actions>
        <Button size="sm" :href="route('treinamentos.create', { categoria: 'CURSO', tipo: 'EAD' })">Abrir curso</Button>
      </template>
    </PageHeader>

    <CardBase v-if="cursos.length === 0" class="mt-6">
      <Text color="muted">Você ainda não foi designado para nenhum curso.</Text>
    </CardBase>

    <div v-else class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      <CardBase v-for="curso in cursos" :key="curso.id">
        <div class="flex flex-wrap items-center gap-2">
          <CursoSelos :publicado="curso.publicado" :situacao="curso.situacao" :situacao-label="curso.situacao_label" />
          <Badge v-if="curso.designado === false" variant="warning">Sem professor designado</Badge>
        </div>

        <Heading :level="3" class="mt-2 text-base font-semibold">{{ curso.titulo }}</Heading>

        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
          <Text size="xs" color="muted">Público: {{ curso.publico_alvo_label }}</Text>
          <Text size="xs" color="muted">{{ curso.alunos }} aluno(s)</Text>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <Button size="sm" :href="route('treinamentos.professor.conteudo', curso.id)">Conteúdo</Button>
          <Button size="sm" variant="outline" :href="route('treinamentos.professor.previa', curso.id)">Prévia</Button>
          <AcoesCicloCurso :curso="curso" />
        </div>
      </CardBase>
    </div>

  </div>
</template>
