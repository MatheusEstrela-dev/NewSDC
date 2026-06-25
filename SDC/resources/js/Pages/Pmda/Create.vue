<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PmdaDetailTemplate from '@/Templates/Pmda/PmdaDetailTemplate.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PmdaIssForm from '@/Components/Organisms/Pmda/PmdaIssForm.vue';
import PmdaMunicipioForm from '@/Components/Organisms/Pmda/PmdaMunicipioForm.vue';
import PmdaCompdecForm from '@/Components/Organisms/Pmda/PmdaCompdecForm.vue';

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({
  municipio: { type: Object, required: true },
});

const form = useForm({
  municipio_id: props.municipio.id,
  // ISS
  cobra_iss: false, num_lei_iss: '', aliquota_iss: '', resp_cob_iss: '',
  // Municipio / Prefeitura
  nome_prefeito: '', tel_prefeitura: '', email_prefeitura: '', tel_prefeito: '',
  cel_prefeito: '', cep: '', endereco: '', bairro: '', populacao: '', pop_rural: '', area: '',
  // COMPDEC
  compdec_coordenador: '', compdec_email: '', compdec_tel: '', compdec_decreto: '', compdec_lei: '',
});

function salvar() {
  form.post(route('pmda.planos.store'));
}

function voltar() {
  router.visit(route('pmda.planos.index'));
}
</script>

<template>
  <Head title="Novo PMDA" />
  <PmdaDetailTemplate :title="`Novo PMDA — ${municipio.nome} / ${municipio.uf}`">
    <template #actions>
      <button
        type="button"
        class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
        @click="voltar"
      >
        Voltar
      </button>
    </template>

    <template #iss>
      <form class="space-y-6" @submit.prevent="salvar">
        <PmdaIssForm :form="form" />
        <PmdaMunicipioForm :form="form" />
        <PmdaCompdecForm :form="form" />
        <div class="flex justify-end">
          <Button variant="success" :disabled="form.processing" @click="salvar">Criar PMDA</Button>
        </div>
      </form>
    </template>
  </PmdaDetailTemplate>
</template>
