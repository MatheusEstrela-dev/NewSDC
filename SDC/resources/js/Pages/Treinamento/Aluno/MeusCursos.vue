<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import CertificadoItem from '@/Components/Molecules/Treinamento/CertificadoItem.vue';
import CursoEadCard from '@/Components/Molecules/Treinamento/CursoEadCard.vue';
import SecaoHeader from '@/Components/Molecules/Treinamento/SecaoHeader.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  meus_cursos: { type: Array, default: () => [] },
  disponiveis: { type: Array, default: () => [] },
  certificados: { type: Array, default: () => [] },
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
      <SecaoHeader titulo="Meus cursos" :total="meus_cursos.length" />

      <CardBase v-if="meus_cursos.length === 0">
        <Text color="muted">Você ainda não está inscrito em nenhum curso EAD.</Text>
      </CardBase>

      <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        <CursoEadCard v-for="curso in meus_cursos" :key="curso.id" :curso="curso" modo="inscrito" />
      </div>
    </section>

    <section v-if="certificados.length" class="mt-8">
      <SecaoHeader titulo="Meus certificados" :total="certificados.length" />

      <div class="space-y-4">
        <CertificadoItem
          v-for="certificado in certificados"
          :key="certificado.id"
          :certificado="certificado"
          :imprimir-url="route('treinamentos.certificados.imprimir', certificado.id)"
        />
      </div>
    </section>

    <section class="mt-8">
      <SecaoHeader titulo="Cursos disponíveis" :total="disponiveis.length" />

      <CardBase v-if="disponiveis.length === 0">
        <Text color="muted">Nenhum curso aberto para inscrição no momento.</Text>
      </CardBase>

      <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        <CursoEadCard
          v-for="curso in disponiveis"
          :key="curso.id"
          :curso="curso"
          modo="disponivel"
          :loading="inscrevendo === curso.id"
          @inscrever="inscrever"
        />
      </div>
    </section>
  </div>
</template>
