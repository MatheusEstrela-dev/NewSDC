<template>
  <AuthenticatedLayout title="Ajuda Humanitaria - Beneficiarios">
    <BeneficiarioIndexTemplate
      :beneficiarios="beneficiarios.data || []"
      :statistics="statistics"
      :pagination="beneficiarios"
      :can-create="can('humanitaria.beneficiarios.create')"
      :can-edit="can('humanitaria.beneficiarios.edit')"
      :can-delete="can('humanitaria.beneficiarios.delete')"
      @create="openCreateModal"
      @view="viewBeneficiario"
      @edit="editBeneficiario"
      @delete="deleteBeneficiario"
      @filter="filterByStatus"
    />

    <!-- Modal de Criar/Editar -->
    <teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <!-- Overlay -->
          <div class="fixed inset-0 bg-gray-900/75 dark:bg-gray-900/75 bg-gray-500/75 transition-opacity" @click="closeModal"></div>

          <!-- Modal -->
          <div class="inline-block align-bottom bg-slate-800 dark:bg-slate-800 bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-700 dark:border-slate-700 border-slate-200">
            <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-slate-100 dark:text-slate-100 text-slate-900">
                  {{ editingBeneficiario ? 'Editar Beneficiário' : 'Novo Beneficiário' }}
                </h3>
                <button @click="closeModal" class="text-slate-400 dark:text-slate-400 text-slate-500 hover:text-slate-300 dark:hover:text-slate-300 hover:text-slate-600">
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- TODO: Adicionar formulário completo -->
              <div class="text-center py-8">
                <p class="text-slate-400 dark:text-slate-400 text-slate-500">
                  Formulário em desenvolvimento...
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </teleport>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BeneficiarioIndexTemplate from '@/Templates/AjudaHumanitaria/BeneficiarioIndexTemplate.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
  beneficiarios: {
    type: Object,
    required: true,
  },
  statistics: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
});

const showModal = ref(false);
const editingBeneficiario = ref(null);

const openCreateModal = () => {
  editingBeneficiario.value = null;
  showModal.value = true;
};

const viewBeneficiario = (id) => {
  router.visit(route('ajuda-humanitaria.beneficiarios.show', id));
};

const editBeneficiario = (id) => {
  editingBeneficiario.value = props.beneficiarios.data.find(b => b.id === id);
  showModal.value = true;
};

const deleteBeneficiario = (id) => {
  if (confirm('Tem certeza que deseja excluir este beneficiário?')) {
    router.delete(route('ajuda-humanitaria.beneficiarios.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        // TODO: Toast de sucesso
      },
    });
  }
};

const filterByStatus = (status) => {
  router.visit(route('ajuda-humanitaria.beneficiarios.index', { status }), {
    preserveState: true,
    preserveScroll: true,
  });
};

const closeModal = () => {
  showModal.value = false;
  editingBeneficiario.value = null;
};
</script>
