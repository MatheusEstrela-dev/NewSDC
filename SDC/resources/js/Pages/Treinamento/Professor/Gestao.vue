<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import MenuCurso from '@/Components/Organisms/Treinamento/Professor/MenuCurso.vue';
import GestaoCursoPainel from '@/Components/Organisms/Treinamento/Gestao/GestaoCursoPainel.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  curso: { type: Object, required: true },
  historico: { type: Object, required: true },
  tipoEventoCronogramaOptions: { type: Array, default: () => [] },
  inscricoes_url: { type: String, default: null },
  pendentes: { type: Number, default: 0 },
});
</script>

<template>
  <div>
    <PageHeader
      :title="curso.titulo"
      description="Vagas, período, cronograma e histórico do curso."
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[14rem_1fr]">
      <MenuCurso :curso="curso" ativo="gestao" :pendentes="pendentes" />

      <GestaoCursoPainel
        :curso="curso"
        :historico="historico"
        :tipo-evento-cronograma-options="tipoEventoCronogramaOptions"
        :inscricoes-url="inscricoes_url"
      />
    </div>
  </div>
</template>
