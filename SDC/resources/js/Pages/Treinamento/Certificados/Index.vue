<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import CancelarCertificadoModal from '@/Components/Organisms/Treinamento/CancelarCertificadoModal.vue';
import CertificadosList from '@/Components/Organisms/Treinamento/CertificadosList.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  treinamento: { type: Object, required: true },
  certificados: { type: Object, required: true },
});

const { can } = usePermissions();
const { show: toast } = useToast();

const pagination = computed(() => {
  const m = props.certificados?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 15,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

function irParaPagina(page) {
  router.visit(route('treinamentos.certificados.index', props.treinamento.id), {
    data: { page },
    preserveState: true,
  });
}

function reemitir(certificado) {
  router.post(route('treinamentos.certificados.reemitir', certificado.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Certificado reemitido.', 'success'),
    onError: () => toast('Não foi possível reemitir o certificado.', 'error'),
  });
}

const certificadoACancelar = ref(null);
const cancelando = ref(false);
const erroCancelamento = ref(null);

function abrirCancelamento(certificado) {
  erroCancelamento.value = null;
  certificadoACancelar.value = certificado;
}

function fecharCancelamento() {
  certificadoACancelar.value = null;
}

function cancelar(motivo) {
  cancelando.value = true;
  router.post(route('treinamentos.certificados.cancelar', certificadoACancelar.value.id), { motivo }, {
    preserveScroll: true,
    // O aviso de sucesso ou de recusa do dominio vem no flash, exibido pelo layout.
    onSuccess: fecharCancelamento,
    onError: (erros) => { erroCancelamento.value = erros.motivo || 'Não foi possível cancelar o certificado.'; },
    onFinish: () => { cancelando.value = false; },
  });
}
</script>

<template>
  <div class="treinamento-certificados-container">
    <PageHeader
      title="Certificados"
      :description="treinamento.titulo"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <CardBase class="p-6">
      <CertificadosList
        :certificados="certificados.data"
        :can-reemitir="can('treinamento.certificados.reemitir')"
        :can-cancelar="can('treinamento.certificados.cancelar')"
        @reemitir="reemitir"
        @cancelar="abrirCancelamento"
      />

      <div v-if="pagination" class="mt-6">
        <Pagination :pagination="pagination" @page-change="irParaPagina" />
      </div>
    </CardBase>

    <CancelarCertificadoModal
      :show="certificadoACancelar !== null"
      :certificado="certificadoACancelar"
      :processando="cancelando"
      :erro="erroCancelamento"
      @fechar="fecharCancelamento"
      @confirmar="cancelar"
    />
  </div>
</template>

<style scoped>
.treinamento-certificados-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
