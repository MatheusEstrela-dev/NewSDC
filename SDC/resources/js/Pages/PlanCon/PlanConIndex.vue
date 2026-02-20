<template>
  <div>
    <Head title="Plano de Contingencia" />
    <PlanConIndexTemplate
      :stats="effectiveStats"
      :recent-activities="recentActivities"
      :can-export="can('plancon.export')"
      :can-upload="can('plancon.upload')"
      @export="handleExport"
      @upload="showUploadModal = true"
    />

    <UploadModal
      :show="showUploadModal"
      module-name="Plano de Contingencia"
      accepted-types=".pdf"
      :max-file-size="10"
      @close="showUploadModal = false"
      @upload="handleUpload"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PlanConIndexTemplate from '@/Templates/PlanCon/PlanConIndexTemplate.vue';
import UploadModal from '@/Components/Organisms/PlanCon/UploadModal.vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalMunicipios: 853,
      municipiosComPlano: 729,
      municipiosSemPlano: 124,
      percentualComPlano: 85.5,
      totalPlanos: 729,
      planosRegulares: 714,
      planosIrregulares: 15,
      percentualRegulares: 97.9,
    }),
  },
  recentActivities: {
    type: Array,
    default: () => [],
  },
});

const showUploadModal = ref(false);

const effectiveStats = computed(() => {
  if (props.stats && props.stats.totalMunicipios > 0) {
    return props.stats;
  }
  return {
    totalMunicipios: 853,
    municipiosComPlano: 729,
    municipiosSemPlano: 124,
    percentualComPlano: 85.5,
    totalPlanos: 729,
    planosRegulares: 714,
    planosIrregulares: 15,
    percentualRegulares: 97.9,
  };
});

const handleExport = () => {
  window.location.href = route('plancon.export');
};

const handleUpload = (file) => {
  showUploadModal.value = false;
};
</script>
