<template>
  <div>
    <Head title="Plano de Contingencia" />
    <PlanConIndexTemplate
      :stats="stats"
      :recent-activities="recentActivities"
      :can-export="can('plancon.export')"
      :can-upload="can('plancon.upload') && podeEnviar"
      @export="handleExport"
      @upload="showUploadModal = true"
    />

    <UploadModal
      :show="showUploadModal"
      module-name="Plano de Contingencia"
      :municipios="municipiosParaEnvio"
      accepted-types=".pdf,.doc,.docx,.odt"
      :max-file-size="20"
      @close="showUploadModal = false"
      @upload="handleUpload"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PlanConIndexTemplate from '@/Templates/PlanCon/PlanConIndexTemplate.vue';
import UploadModal from '@/Components/Organisms/PlanCon/UploadModal.vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

defineProps({
  stats: {
    type: Object,
    required: true,
  },
  recentActivities: {
    type: Array,
    default: () => [],
  },
  podeEnviar: {
    type: Boolean,
    default: false,
  },
  // Vem preenchido so para conta estadual (sem orgao): ela escolhe por qual
  // municipio o plano esta sendo enviado. Usuario municipal recebe [].
  municipiosParaEnvio: {
    type: Array,
    default: () => [],
  },
});

const showUploadModal = ref(false);

const handleExport = () => {
  window.location.href = route('plancon.export');
};

const handleUpload = ({ formData }) => {
  router.post(route('plancon.planos.store'), formData, {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      showUploadModal.value = false;
    },
  });
};
</script>
