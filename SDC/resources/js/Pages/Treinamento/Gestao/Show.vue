<script setup>
/**
 * Gestao do curso aberta pelo detalhe do curso, para as modalidades sem Area
 * do Gestor (presencial, semipresencial, online). O curso EAD usa a aba
 * Gestao da Area do Gestor (Professor/Gestao), com o mesmo painel.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import GestaoCursoPainel from '@/Components/Organisms/Treinamento/Gestao/GestaoCursoPainel.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  curso: { type: Object, required: true },
  historico: { type: Object, required: true },
  tipoEventoCronogramaOptions: { type: Array, default: () => [] },
  links: { type: Object, default: () => ({}) },
});
</script>

<template>
  <div class="w-full space-y-6 pb-8">
    <PageHeader
      :title="curso.titulo"
      :description="`Gestão do curso · ${curso.tipo_label}`"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    >
      <template #actions>
        <Button variant="outline" size="sm" :href="links.detalhes">Ver detalhes do curso</Button>
      </template>
    </PageHeader>

    <GestaoCursoPainel
      :curso="curso"
      :historico="historico"
      :tipo-evento-cronograma-options="tipoEventoCronogramaOptions"
      :inscricoes-url="links.inscricoes"
    />
  </div>
</template>
