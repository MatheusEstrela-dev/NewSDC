<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  meus_cursos: { type: Array, default: () => [] },
  disponiveis: { type: Array, default: () => [] },
});

const { show: toast } = useToast();
const inscrevendo = ref(null);

function inscrever(curso) {
  inscrevendo.value = curso.id;
  router.post(route('treinamentos.inscrever', curso.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Inscrição realizada.', 'success'),
    onError: (errors) => toast(errors.inscricao || 'Não foi possível concluir a inscrição.', 'error'),
    onFinish: () => { inscrevendo.value = null; },
  });
}
</script>

<template>
  <div>
    <PageHeader
      title="Meus cursos"
      description="Cursos EAD em que você está inscrito e cursos abertos para inscrição"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <section class="mt-6">
      <Heading :level="2" class="mb-3 text-lg font-semibold">Meus cursos</Heading>

      <CardBase v-if="meus_cursos.length === 0">
        <Text color="muted">Você ainda não está inscrito em nenhum curso EAD.</Text>
      </CardBase>

      <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <CardBase v-for="curso in meus_cursos" :key="curso.id">
          <div class="flex items-start justify-between gap-2">
            <Heading :level="3" class="text-base font-semibold">{{ curso.titulo }}</Heading>
            <Badge v-if="!curso.aprovada" variant="warning">{{ curso.status_label }}</Badge>
          </div>
          <Text v-if="curso.carga_horaria" size="xs" color="muted" class="mt-1">{{ curso.carga_horaria }}h</Text>

          <div v-if="curso.aprovada" class="mt-4">
            <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
              <div class="h-full bg-amber-500" :style="{ width: `${curso.percentual}%` }" />
            </div>
            <Text size="xs" color="muted" class="mt-1">{{ Math.round(curso.percentual) }}% concluído</Text>
            <Button size="sm" class="mt-3" :href="curso.sala_url">Continuar</Button>
          </div>
        </CardBase>
      </div>
    </section>

    <section class="mt-8">
      <Heading :level="2" class="mb-3 text-lg font-semibold">Cursos disponíveis</Heading>

      <CardBase v-if="disponiveis.length === 0">
        <Text color="muted">Nenhum curso aberto para inscrição no momento.</Text>
      </CardBase>

      <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <CardBase v-for="curso in disponiveis" :key="curso.id">
          <Heading :level="3" class="text-base font-semibold">{{ curso.titulo }}</Heading>
          <Text v-if="curso.carga_horaria" size="xs" color="muted" class="mt-1">{{ curso.carga_horaria }}h</Text>
          <Text v-if="curso.descricao" size="sm" color="muted" class="mt-2 line-clamp-3">{{ curso.descricao }}</Text>
          <Button size="sm" class="mt-4" :loading="inscrevendo === curso.id" @click="inscrever(curso)">Inscrever-se</Button>
        </CardBase>
      </div>
    </section>
  </div>
</template>
