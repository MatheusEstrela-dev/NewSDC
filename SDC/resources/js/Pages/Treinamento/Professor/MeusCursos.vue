<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CursoEadCard from '@/Components/Molecules/Treinamento/CursoEadCard.vue';
import CapaCursoUpload from '@/Components/Molecules/Treinamento/CapaCursoUpload.vue';
import SecaoHeader from '@/Components/Molecules/Treinamento/SecaoHeader.vue';
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

    <section class="mt-6">
      <SecaoHeader titulo="Cursos" :total="cursos.length" />

      <CardBase v-if="cursos.length === 0">
        <Text color="muted">Você ainda não foi designado para nenhum curso.</Text>
      </CardBase>

      <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        <CursoEadCard v-for="curso in cursos" :key="curso.id" :curso="curso" modo="professor">
          <template #capa-acoes>
            <CapaCursoUpload :curso="curso" />
          </template>

          <template #corpo>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
              <span>Público: {{ curso.publico_alvo_label }}</span>
              <span>{{ curso.alunos }} aluno(s)</span>
              <Badge v-if="curso.designado === false" variant="warning">Sem professor designado</Badge>
            </div>
          </template>

          <template #rodape>
            <div class="flex flex-wrap gap-2">
              <Button size="sm" :href="route('treinamentos.professor.conteudo', curso.id)">Conteúdo</Button>
              <Button size="sm" variant="outline" :href="route('treinamentos.professor.previa', curso.id)">Prévia</Button>
              <AcoesCicloCurso :curso="curso" />
            </div>
          </template>
        </CursoEadCard>
      </div>
    </section>
  </div>
</template>
