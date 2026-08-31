<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  planoId: { type: [Number, String], required: true },
  historico: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
  nome: '',
  latitude: '',
  longitude: '',
});

const fmtData = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('pt-BR');
};

const historicoOrdenado = computed(() => props.historico ?? []);

// Uma linha de erro para o formulario inteiro: a coordenada fora da faixa de MG
// precisa aparecer tanto quanto o nome duplicado, e enumerar campo a campo aqui
// so repetiria o que o backend ja nomeia.
const erro = computed(() => Object.values(form.errors)[0] ?? null);

function fechar() {
  form.reset();
  form.clearErrors();
  emit('close');
}

function gravar() {
  if (!form.nome.trim()) return;
  form.post(route('pmda.planos.comunidades.solicitar', props.planoId), {
    preserveScroll: true,
    // Mantem o modal aberto: o historico recarrega com a nova solicitacao.
    onSuccess: () => { form.reset('nome', 'latitude', 'longitude'); },
  });
}
</script>

<template>
  <Modal :show="show" max-width="2xl" @close="fechar">
    <div class="space-y-4 p-5">
      <header class="flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Solicitação de Inclusão de Comunidade</h2>
        <button type="button" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="fechar">✕</button>
      </header>

      <p class="text-xs text-slate-500 dark:text-slate-400">
        Caso não encontre a comunidade na lista, solicite o cadastro abaixo. A CEDEC fará a análise e,
        uma vez aprovada, a comunidade ficará disponível para os PMDA do município.
      </p>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_140px_140px_auto]">
        <div>
          <label class="pmda-field-label">Nome da Comunidade <span class="req">*</span></label>
          <TextInput v-model="form.nome" :maxlength="150" placeholder="Ex: Comunidade Pau d'Água" />
        </div>
        <div>
          <label class="pmda-field-label">Latitude</label>
          <TextInput v-model="form.latitude" mask="coordenada" :maxlength="12" placeholder="-19.1234" />
        </div>
        <div>
          <label class="pmda-field-label">Longitude</label>
          <TextInput v-model="form.longitude" mask="coordenada" :maxlength="12" placeholder="-46.1231" />
        </div>
        <div class="flex items-end">
          <Button
            variant="success"
            size="sm"
            full-width
            :disabled="!form.nome.trim() || form.processing"
            :loading="form.processing"
            @click="gravar"
          >
            Adicionar
          </Button>
        </div>
      </div>
      <p v-if="erro" class="text-xs text-red-600">{{ erro }}</p>

      <div>
        <h3 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
          Histórico de Comunidades enviadas para Cadastro
        </h3>
        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
          <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
            <thead class="bg-slate-50 dark:bg-slate-800/40">
              <tr class="text-left text-slate-500 dark:text-slate-400">
                <th class="px-4 py-2 font-medium">Comunidade</th>
                <th class="px-4 py-2 font-medium">Situação</th>
                <th class="px-4 py-2 font-medium">Data</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="s in historicoOrdenado" :key="s.id" class="text-slate-700 dark:text-slate-300">
                <td class="px-4 py-2.5 font-medium">{{ s.nome }}</td>
                <td class="px-4 py-2.5">
                  <Badge :cor="s.status_cor">{{ s.status_label }}</Badge>
                  <span v-if="s.motivo_rejeicao" class="ml-2 text-xs text-red-500">({{ s.motivo_rejeicao }})</span>
                </td>
                <td class="px-4 py-2.5 text-slate-500">{{ fmtData(s.created_at) }}</td>
              </tr>
              <tr v-if="historicoOrdenado.length === 0">
                <td colspan="3" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Nenhuma solicitação enviada ainda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex justify-end pt-1">
        <Button variant="secondary" size="sm" type="button" @click="fechar">Fechar</Button>
      </div>
    </div>
  </Modal>
</template>
