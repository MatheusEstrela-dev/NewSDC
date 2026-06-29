<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import UsersGroupIcon from '@/Components/Icons/UsersGroupIcon.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';

const props = defineProps({
  form: { type: Object, required: true },
  plano: { type: Object, required: true },
  saving: { type: Boolean, default: false },
});

defineEmits(['next', 'prev']);

const novo = ref({ nome: '', cargo: '', telefone: '' });
const addingMembro = ref(false);

function adicionarMembro() {
  if (!novo.value.nome.trim()) return;
  addingMembro.value = true;
  router.post(route('pmda.planos.membros.store', props.plano.id), { ...novo.value }, {
    preserveScroll: true,
    onSuccess: () => { novo.value = { nome: '', cargo: '', telefone: '' }; },
    onFinish: () => { addingMembro.value = false; },
  });
}

function removerMembro(id) {
  router.delete(route('pmda.membros.destroy', id), { preserveScroll: true });
}
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
    <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
      Coordenador(a) Municipal de Defesa Civil
    </h3>
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

    <div class="mt-6 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Equipe Técnica / Outros Membros</h3>
    </div>

    <!-- Linha de adicao -->
    <div class="mt-3 grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700/50 dark:bg-slate-800/40 md:grid-cols-[1fr_1fr_1fr_auto]">
      <TextInput v-model="novo.nome" placeholder="Nome" :maxlength="110" />
      <TextInput v-model="novo.cargo" placeholder="Cargo/Função" :maxlength="80" />
      <TextInput v-model="novo.telefone" placeholder="Telefone" :maxlength="20" />
      <button
        type="button"
        class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 disabled:opacity-50 dark:border-emerald-500/40 dark:text-emerald-300 dark:hover:bg-emerald-500/10"
        :disabled="addingMembro || !novo.nome.trim()"
        @click="adicionarMembro"
      >
        + Adicionar Membro
      </button>
    </div>

    <!-- Tabela -->
    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr class="text-left text-slate-500 dark:text-slate-400">
            <th class="px-4 py-2 font-medium">Nome</th>
            <th class="px-4 py-2 font-medium">Cargo/Função</th>
            <th class="px-4 py-2 font-medium">Telefone</th>
            <th class="px-4 py-2 text-right font-medium">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-for="m in plano.compdec_membros" :key="m.id" class="text-slate-700 dark:text-slate-300">
            <td class="px-4 py-2.5">{{ m.nome }}</td>
            <td class="px-4 py-2.5">{{ m.cargo || '—' }}</td>
            <td class="px-4 py-2.5">{{ m.telefone || '—' }}</td>
            <td class="px-4 py-2.5 text-right">
              <button type="button" class="text-sm font-medium text-red-600 hover:underline dark:text-red-400" @click="removerMembro(m.id)">
                Remover
              </button>
            </td>
          </tr>
          <tr v-if="!plano.compdec_membros || plano.compdec_membros.length === 0">
            <td colspan="4" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
              Nenhum membro cadastrado.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </PmdaWizardPanel>
</template>
