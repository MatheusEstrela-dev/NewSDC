<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';

const props = defineProps({
  plano: { type: Object, required: true },
});

defineEmits(['next', 'prev']);

const TIPOS = [
  { value: 1, label: 'COPASA' },
  { value: 2, label: 'COPANOR' },
  { value: 3, label: 'Barragem' },
  { value: 4, label: 'SAAE/DMAE' },
  { value: 5, label: 'Poço Público' },
  { value: 6, label: 'Poço Particular' },
];
const SITUACOES = [
  { value: 'ATIVO', label: 'Ativo' },
  { value: 'SECO', label: 'Seco' },
];

const novo = ref({ nome: '', tipo: 1, situacao: 'ATIVO' });
const adding = ref(false);

function adicionar() {
  if (!novo.value.nome.trim()) return;
  adding.value = true;
  router.post(route('pmda.planos.pontos.store', props.plano.id), { ...novo.value }, {
    preserveScroll: true,
    onSuccess: () => { novo.value = { nome: '', tipo: 1, situacao: 'ATIVO' }; },
    onFinish: () => { adding.value = false; },
  });
}

function remover(pontoId) {
  router.delete(route('pmda.planos.pontos.destroy', [props.plano.id, pontoId]), { preserveScroll: true });
}
</script>

<template>
  <PmdaWizardPanel
    :step="4"
    title="Dados do Ponto de Captação"
    subtitle="Gestão de barragens, poços e rios."
    :icon="MapIcon"
    @next="$emit('next')"
    @prev="$emit('prev')"
  >
    <div class="pmda-info-box warn mb-4">
      Informe todos os mananciais, poços artesianos, reservatórios da COPASA ou locais onde os
      caminhões-pipa farão o carregamento de água para distribuição.
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_180px_140px_auto]">
      <div>
        <label class="pmda-field-label">Nome do Ponto <span class="req">*</span></label>
        <TextInput v-model="novo.nome" :maxlength="150" placeholder="Ex: Poço Artesiano Comunidade X" />
      </div>
      <div>
        <label class="pmda-field-label">Tipo <span class="req">*</span></label>
        <SelectInput v-model="novo.tipo" :options="TIPOS" placeholder="" />
      </div>
      <div>
        <label class="pmda-field-label">Situação</label>
        <SelectInput v-model="novo.situacao" :options="SITUACOES" placeholder="" />
      </div>
      <div class="flex items-end">
        <button
          type="button"
          class="w-full rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900 disabled:opacity-50 dark:bg-slate-700 dark:hover:bg-slate-600"
          :disabled="adding || !novo.nome.trim()"
          @click="adicionar"
        >
          Adicionar Ponto
        </button>
      </div>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr class="text-left text-slate-500 dark:text-slate-400">
            <th class="px-4 py-2 font-medium">Nome do Ponto</th>
            <th class="px-4 py-2 font-medium">Tipo</th>
            <th class="px-4 py-2 font-medium">Situação</th>
            <th class="px-4 py-2 text-right font-medium">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-for="p in plano.pontos" :key="p.id" class="text-slate-700 dark:text-slate-300">
            <td class="px-4 py-2.5 font-medium">{{ p.nome }}</td>
            <td class="px-4 py-2.5">{{ p.tipo_label }}</td>
            <td class="px-4 py-2.5">
              <span
                class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="p.situacao === 'SECO'
                  ? 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'
                  : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'"
              >
                {{ p.situacao === 'SECO' ? 'Seco' : 'Ativo' }}
              </span>
            </td>
            <td class="px-4 py-2.5 text-right">
              <button type="button" class="text-sm font-medium text-red-600 hover:underline dark:text-red-400" @click="remover(p.id)">
                Remover
              </button>
            </td>
          </tr>
          <tr v-if="!plano.pontos || plano.pontos.length === 0">
            <td colspan="4" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
              Nenhum ponto de captação adicionado.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </PmdaWizardPanel>
</template>
