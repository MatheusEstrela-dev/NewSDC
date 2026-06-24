<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PmdaDetailTemplate from '@/Templates/Pmda/PmdaDetailTemplate.vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';
import PmdaIssForm from '@/Components/Organisms/Pmda/PmdaIssForm.vue';
import PmdaComunidadesSection from '@/Components/Organisms/Pmda/PmdaComunidadesSection.vue';

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({
  plano: { type: Object, required: true },
});

// O Controller retorna um JsonResource: os dados ficam em `plano.data` quando empacotado.
const dados = props.plano?.data ?? props.plano;

function voltar() {
  router.visit(route('pmda.planos.index'));
}
</script>

<template>
  <Head :title="`PMDA — ${dados.protocolo ?? 'Plano'}`" />
  <PmdaDetailTemplate :title="`PMDA ${dados.protocolo ?? ''}`">
    <template #actions>
      <div class="flex items-center gap-3">
        <PmdaStatusBadge :label="dados.status_label" :color-class="dados.status_color" />
        <button
          type="button"
          class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
          @click="voltar"
        >
          Voltar
        </button>
      </div>
    </template>

    <template #iss>
      <PmdaIssForm :plano="dados" />
    </template>

    <template #comunidades>
      <PmdaComunidadesSection :plano-id="dados.id" :comunidades="dados.comunidades ?? []" />
    </template>
  </PmdaDetailTemplate>
</template>
