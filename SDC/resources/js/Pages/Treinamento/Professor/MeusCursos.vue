<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  cursos: { type: Array, default: () => [] },
});
</script>

<template>
  <div>
    <PageHeader
      title="Meus cursos"
      description="Cursos EAD em que voce foi designado como professor"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <CardBase v-if="cursos.length === 0" class="mt-6">
      <Text color="muted">Voce ainda nao foi designado para nenhum curso.</Text>
    </CardBase>

    <div v-else class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      <CardBase v-for="curso in cursos" :key="curso.id">
        <Heading :level="3" class="text-base font-semibold">{{ curso.titulo }}</Heading>

        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
          <Text size="xs" color="muted">Publico: {{ curso.publico_alvo_label }}</Text>
          <Text size="xs" color="muted">Status: {{ curso.status_label }}{{ curso.publicado ? ' (publicado)' : '' }}</Text>
          <Text size="xs" color="muted">{{ curso.alunos }} aluno(s)</Text>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <Button size="sm" :href="route('treinamentos.professor.conteudo', curso.id)">Conteudo</Button>
          <Button size="sm" variant="outline" :href="route('treinamentos.professor.previa', curso.id)">Previa</Button>
        </div>
      </CardBase>
    </div>
  </div>
</template>
