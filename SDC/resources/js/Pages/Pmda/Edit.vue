<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PmdaDetailTemplate from '@/Templates/Pmda/PmdaDetailTemplate.vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';
import PmdaIssForm from '@/Components/Organisms/Pmda/PmdaIssForm.vue';
import PmdaMunicipioForm from '@/Components/Organisms/Pmda/PmdaMunicipioForm.vue';
import PmdaCompdecForm from '@/Components/Organisms/Pmda/PmdaCompdecForm.vue';
import PmdaPontosSection from '@/Components/Organisms/Pmda/PmdaPontosSection.vue';
import PmdaComunidadesSection from '@/Components/Organisms/Pmda/PmdaComunidadesSection.vue';

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({
  plano: { type: Object, required: true },
  pontos_disponiveis: { type: Array, default: () => [] },
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
          class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
          @click="voltar"
        >
          Voltar
        </button>
      </div>
    </template>

    <template #iss>
      <PmdaIssForm :plano="dados" />
    </template>

    <template #municipio>
      <div class="space-y-6">
        <PmdaMunicipioForm :plano="dados" />
        <PmdaCompdecForm :plano="dados" />
      </div>
    </template>

    <template #pontos>
      <PmdaPontosSection
        :plano-id="dados.id"
        :pontos="dados.pontos ?? []"
        :disponiveis="pontos_disponiveis"
      />
    </template>

    <template #comunidades>
      <PmdaComunidadesSection :plano-id="dados.id" :comunidades="dados.comunidades ?? []" />
    </template>
  </PmdaDetailTemplate>
</template>
