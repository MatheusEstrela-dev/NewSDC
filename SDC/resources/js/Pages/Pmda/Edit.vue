<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PmdaDetailTemplate from '@/Templates/Pmda/PmdaDetailTemplate.vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
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

const dados = props.plano?.data ?? props.plano;

const form = useForm({
  // ISS
  cobra_iss: dados.cobra_iss ?? false,
  num_lei_iss: dados.num_lei_iss ?? '',
  aliquota_iss: dados.aliquota_iss ?? '',
  resp_cob_iss: dados.resp_cob_iss ?? '',
  // Municipio / Prefeitura
  nome_prefeito: dados.nome_prefeito ?? '',
  tel_prefeitura: dados.tel_prefeitura ?? '',
  email_prefeitura: dados.email_prefeitura ?? '',
  tel_prefeito: dados.tel_prefeito ?? '',
  cel_prefeito: dados.cel_prefeito ?? '',
  cep: dados.cep ?? '',
  endereco: dados.endereco ?? '',
  bairro: dados.bairro ?? '',
  populacao: dados.populacao ?? '',
  pop_rural: dados.pop_rural ?? '',
  area: dados.area ?? '',
  // COMPDEC
  compdec_coordenador: dados.compdec_coordenador ?? '',
  compdec_email: dados.compdec_email ?? '',
  compdec_tel: dados.compdec_tel ?? '',
  compdec_decreto: dados.compdec_decreto ?? '',
  compdec_lei: dados.compdec_lei ?? '',
});

function salvar() {
  form.put(route('pmda.planos.update', dados.id), { preserveScroll: true });
}

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

    <!-- Form do plano: ISS + Municipio + COMPDEC, um unico Salvar no final -->
    <template #iss>
      <form class="space-y-6" @submit.prevent="salvar">
        <PmdaIssForm :form="form" />
        <PmdaMunicipioForm :form="form" />
        <PmdaCompdecForm :form="form" />
        <div class="flex justify-end">
          <Button variant="success" :disabled="form.processing" @click="salvar">Salvar PMDA</Button>
        </div>
      </form>
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
