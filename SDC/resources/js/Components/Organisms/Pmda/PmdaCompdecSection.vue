<script setup>
import { computed, ref } from 'vue';
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import UsersGroupIcon from '@/Components/Icons/UsersGroupIcon.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import CompdecFichaModal from '@/Components/Organisms/Pmda/CompdecFichaModal.vue';
import CompdecDocumentosModal from '@/Components/Organisms/Pmda/CompdecDocumentosModal.vue';
import CompdecEquipeModal from '@/Components/Organisms/Pmda/CompdecEquipeModal.vue';

const props = defineProps({
  form: { type: Object, required: true },
  plano: { type: Object, required: true },
  ficha: { type: Object, default: () => ({}) },
  anexos: { type: Array, default: () => [] },
  equipe: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
});

defineEmits(['next', 'prev']);

const showFicha = ref(false);
const showDocs = ref(false);
const showEquipe = ref(false);

const funcaoLabel = (v) => ({
  coordenador: 'Coordenador', agente: 'Agente', tecnico: 'Técnico', apoio: 'Apoio', outro: 'Outro',
}[v] ?? v);

const equipeAtiva = computed(() => (props.equipe ?? []).filter((m) => m.ativo));
</script>

<template>
  <PmdaWizardPanel
    :step="3"
    title="Equipe COMPDEC"
    subtitle="Cadastro e atualização dos membros da equipe."
    :icon="UsersGroupIcon"
    :saving="saving"
    @next="$emit('next')"
    @prev="$emit('prev')"
  >
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
        Coordenador(a) Municipal de Defesa Civil
      </h3>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-md border border-blue-300 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/20"
          @click="showFicha = true"
        >
          Cadastro do COMPDEC
        </button>
        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
          @click="showEquipe = true"
        >
          Editar Equipe COMPDEC
        </button>
        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
          @click="showDocs = true"
        >
          Documentos (Leis e Decretos)
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
      <div>
        <label class="pmda-field-label">Nome Completo <span class="req">*</span></label>
        <TextInput v-model="form.compdec_coordenador" :maxlength="110" placeholder="Ex: Maria Pereira" />
      </div>
      <div>
        <label class="pmda-field-label">Celular / WhatsApp <span class="req">*</span></label>
        <TextInput v-model="form.compdec_tel" :maxlength="20" placeholder="( ) _____-____" />
      </div>
      <div>
        <label class="pmda-field-label">E-mail Institucional <span class="req">*</span></label>
        <TextInput v-model="form.compdec_email" type="email" :maxlength="110" placeholder="defesacivil@municipio.mg.gov.br" />
      </div>
    </div>

    <div class="mt-6">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Equipe Técnica / Membros</h3>
    </div>

    <!-- Resumo da equipe ativa (gestao completa no modal) -->
    <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr class="text-left text-slate-500 dark:text-slate-400">
            <th class="px-4 py-2 font-medium">Nome</th>
            <th class="px-4 py-2 font-medium">Função</th>
            <th class="px-4 py-2 font-medium">CPF</th>
            <th class="px-4 py-2 font-medium">Telefone</th>
            <th class="px-4 py-2 font-medium">Celular</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-for="m in equipeAtiva" :key="m.id" class="text-slate-700 dark:text-slate-300">
            <td class="px-4 py-2.5 font-medium">{{ m.nome }}</td>
            <td class="px-4 py-2.5">{{ m.funcao_label || funcaoLabel(m.funcao) }}</td>
            <td class="px-4 py-2.5">{{ m.cpf || '—' }}</td>
            <td class="px-4 py-2.5">{{ m.telefone || '—' }}</td>
            <td class="px-4 py-2.5">{{ m.celular || '—' }}</td>
          </tr>
          <tr v-if="equipeAtiva.length === 0">
            <td colspan="5" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
              Nenhum membro cadastrado. Use "Editar Equipe COMPDEC".
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <CompdecFichaModal :show="showFicha" :plano-id="plano.id" :ficha="ficha" @close="showFicha = false" />
    <CompdecEquipeModal :show="showEquipe" :plano-id="plano.id" :equipe="equipe" @close="showEquipe = false" />
    <CompdecDocumentosModal :show="showDocs" :plano-id="plano.id" :anexos="anexos" :ficha="ficha" @close="showDocs = false" />
  </PmdaWizardPanel>
</template>
