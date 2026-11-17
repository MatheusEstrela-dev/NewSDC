<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import LeitorCredencial from '@/Components/Organisms/Treinamento/ValidarParticipante/LeitorCredencial.vue';
import PesquisaServidor from '@/Components/Organisms/Treinamento/ValidarParticipante/PesquisaServidor.vue';
import SituacaoParticipante from '@/Components/Organisms/Treinamento/ValidarParticipante/SituacaoParticipante.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  resultado: { type: Object, default: null },
  erro: { type: String, default: null },
});

const situacaoRef = ref(null);
const temConteudo = computed(() => props.resultado !== null || props.erro !== null);

function consultar(valor) {
  router.visit(route('treinamentos.validar-participante.consultar', valor));
}

// No celular a situacao sobe para o topo (order-first); rola ate ela para nao ficar fora de vista.
function mostrarSituacao() {
  if (!temConteudo.value) return;

  nextTick(() => situacaoRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

onMounted(mostrarSituacao);
watch(() => [props.resultado, props.erro], mostrarSituacao);
</script>

<template>
  <div class="w-full pb-8">
    <PageHeader
      title="Validar participante"
      description="Leia o QR da credencial ou pesquise por CPF ou número do certificado"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-3 lg:gap-6">
      <div class="min-w-0 lg:col-start-2 lg:row-start-1">
        <LeitorCredencial @ler="consultar" />
      </div>

      <div class="min-w-0 lg:col-start-1 lg:row-start-1">
        <PesquisaServidor @pesquisar="consultar" />
      </div>

      <div
        ref="situacaoRef"
        class="min-w-0 scroll-mt-4 lg:order-none lg:col-start-3 lg:row-start-1"
        :class="temConteudo ? 'order-first' : ''"
      >
        <SituacaoParticipante
          :resultado="resultado"
          :erro="erro"
          :url-proximo="route('treinamentos.validar-participante')"
        />
      </div>
    </div>
  </div>
</template>
