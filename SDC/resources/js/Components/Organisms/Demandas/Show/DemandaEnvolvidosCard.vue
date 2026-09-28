<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100"><UserIcon class="h-5 w-5" /> Envolvidos</h2>
    <p class="text-xs text-slate-500">Solicitante</p>
    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ demanda.solicitante?.name ?? '—' }}</p>
    <p v-if="demanda.criado_por && demanda.criado_por.id !== demanda.solicitante?.id" class="mt-1 text-xs text-slate-500">Aberto por {{ demanda.criado_por.name }}</p>
    <p class="mt-4 text-xs text-slate-500">Responsável</p>
    <!--
      Desatribuir nao e uma feature -- o endpoint de assign exige responsavel_id.
      "Ninguem" so aparece como placeholder (nao entra em `options`) quando ainda
      nao ha responsavel: representa o estado atual, e reselecionar o valor ja
      selecionado nao dispara @change, entao nunca chama transferir(). Havendo
      responsavel, a opcao some da lista -- nao ha como "voltar" para Ninguem.
    -->
    <FilterField v-if="podeGerir" :model-value="demanda.atribuido_para?.id ?? ''" label="" type="select"
      :options="usuarios" :placeholder="demanda.atribuido_para ? '' : 'Ninguém'" @update:model-value="transferir" />
    <p v-else class="font-semibold text-slate-900 dark:text-slate-100">{{ demanda.atribuido_para?.name ?? 'Ninguém' }}</p>
  </section>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import UserIcon from '@/Components/Icons/UserIcon.vue';

const props = defineProps({
  demanda: { type: Object, required: true },
  usuarios: { type: Array, default: () => [] },
  podeGerir: { type: Boolean, default: false },
});

function transferir(id) {
  // o <select> nativo sempre emite o valor como string; atribuido_para.id chega
  // do backend como numero, entao a comparacao precisa normalizar os dois lados.
  if (!id || Number(id) === Number(props.demanda.atribuido_para?.id)) return;
  router.post(route('admin.demandas.assign', props.demanda.id), { responsavel_id: Number(id) }, { preserveScroll: true });
}
</script>
