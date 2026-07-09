<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { XMarkIcon, UsersIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  show: { type: Boolean, default: false },
  planoId: { type: [Number, String], required: true },
  equipe: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);
const { show: toast } = useToast();

const FUNCOES = [
  { value: 'coordenador', label: 'Coordenador' },
  { value: 'agente', label: 'Agente' },
  { value: 'tecnico', label: 'Técnico' },
  { value: 'apoio', label: 'Apoio' },
  { value: 'outro', label: 'Outro' },
];
const funcaoLabel = (v) => FUNCOES.find((f) => f.value === v)?.label ?? v;

const ativos = computed(() => (props.equipe ?? []).filter((m) => m.ativo));
const inativos = computed(() => (props.equipe ?? []).filter((m) => !m.ativo));

const editandoId = ref(null);
const acaoBusy = ref(false);
const form = useForm({
  nome: '', funcao: 'agente', cpf: '', telefone: '', celular: '', email: '', ativo: true,
});

function resetForm() {
  editandoId.value = null;
  form.reset();
  form.clearErrors();
}

watch(() => props.show, (v) => { if (v) resetForm(); });

function salvar() {
  if (!form.nome.trim()) return;
  const editando = Boolean(editandoId.value);
  toast(editando ? 'Atualizando membro da equipe...' : 'Gravando membro da equipe...', 'info');
  if (editandoId.value) {
    form.put(route('pmda.planos.compdec.equipe.update', [props.planoId, editandoId.value]), {
      preserveScroll: true,
      onSuccess: () => { toast('Membro da equipe atualizado.', 'success'); resetForm(); },
      onError: () => toast('Nao foi possivel atualizar o membro da equipe.', 'error'),
    });
  } else {
    form.post(route('pmda.planos.compdec.equipe.store', props.planoId), {
      preserveScroll: true,
      onSuccess: () => { toast('Membro da equipe gravado.', 'success'); resetForm(); },
      onError: () => toast('Nao foi possivel gravar o membro da equipe.', 'error'),
    });
  }
}

function editar(m) {
  editandoId.value = m.id;
  form.nome = m.nome ?? '';
  form.funcao = m.funcao ?? 'agente';
  form.cpf = m.cpf ?? '';
  form.telefone = m.telefone ?? '';
  form.celular = m.celular ?? '';
  form.email = m.email ?? '';
  form.ativo = m.ativo;
}

function definirAtivo(m, ativo) {
  acaoBusy.value = true;
  toast(ativo ? 'Reativando membro da equipe...' : 'Inativando membro da equipe...', 'info');
  router.put(route('pmda.planos.compdec.equipe.update', [props.planoId, m.id]), {
    nome: m.nome, funcao: m.funcao, cpf: m.cpf, telefone: m.telefone, celular: m.celular, email: m.email, ativo,
  }, {
    preserveScroll: true,
    onSuccess: () => toast(ativo ? 'Membro da equipe reativado.' : 'Membro da equipe inativado.', 'success'),
    onError: () => toast('Nao foi possivel atualizar o membro da equipe.', 'error'),
    onFinish: () => { acaoBusy.value = false; },
  });
}

function excluir(m) {
  acaoBusy.value = true;
  toast('Excluindo membro da equipe...', 'info');
  router.delete(route('pmda.planos.compdec.equipe.destroy', [props.planoId, m.id]), {
    preserveScroll: true,
    onSuccess: () => toast('Membro da equipe excluido.', 'success'),
    onError: () => toast('Nao foi possivel excluir o membro da equipe.', 'error'),
    onFinish: () => { acaoBusy.value = false; },
  });
}
</script>

<template>
  <Modal :show="show" max-width="5xl" @close="$emit('close')">
    <div class="flex max-h-[90vh] flex-col overflow-hidden">
      <div v-if="form.processing || acaoBusy" class="h-1 w-full overflow-hidden bg-slate-200 dark:bg-slate-700"><div class="h-full w-1/2 animate-pulse rounded-r-full bg-blue-600" /></div>
      <!-- Header -->
      <div class="flex items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-100 to-slate-200 px-6 py-4 dark:border-slate-700/50 dark:from-slate-800 dark:to-slate-900">
        <div class="flex items-center gap-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-slate-700/50">
            <UsersIcon class="h-6 w-6 text-blue-500 dark:text-blue-400" />
          </div>
          <div>
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Editar Equipe COMPDEC</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Cadastro e atualização dos membros (coordenador, agentes e técnicos).</p>
          </div>
        </div>
        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-700/50 dark:hover:text-white" @click="$emit('close')">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <div class="flex-1 space-y-6 overflow-y-auto bg-slate-50 p-6 scrollbar-hide dark:bg-slate-900/50">
        <!-- Formulario de membro -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
          <h3 class="mb-4 text-base font-bold text-slate-800 dark:text-slate-100">{{ editandoId ? 'Editar Membro' : 'Adicionar Membro' }}</h3>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div><label class="pmda-field-label">Nome <span class="req">*</span></label><TextInput v-model="form.nome" :maxlength="110" /></div>
            <div><label class="pmda-field-label">Função <span class="req">*</span></label><SelectInput v-model="form.funcao" :options="FUNCOES" placeholder="" /></div>
            <div><label class="pmda-field-label">CPF</label><TextInput v-model="form.cpf" :maxlength="20" /></div>
            <div><label class="pmda-field-label">Telefone</label><TextInput v-model="form.telefone" :maxlength="20" /></div>
            <div><label class="pmda-field-label">Celular</label><TextInput v-model="form.celular" :maxlength="20" /></div>
            <div><label class="pmda-field-label">E-mail</label><TextInput v-model="form.email" type="email" :maxlength="150" /></div>
          </div>
          <p v-if="form.errors.equipe || form.errors.nome || form.errors.funcao" class="mt-2 text-xs text-red-600">
            {{ form.errors.equipe || form.errors.nome || form.errors.funcao }}
          </p>
          <div class="mt-4 flex justify-end gap-2">
            <Button v-if="editandoId" variant="secondary" size="sm" type="button" @click="resetForm">Cancelar edição</Button>
            <Button variant="success" size="sm" :loading="form.processing" :disabled="!form.nome.trim() || form.processing" @click="salvar">
              {{ editandoId ? 'Atualizar Membro' : 'Gravar Membro' }}
            </Button>
          </div>
        </section>

        <!-- Equipe ativa -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
          <h3 class="mb-3 text-base font-bold text-slate-800 dark:text-slate-100">Equipe COMPDEC</h3>
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
              <thead class="bg-slate-50 dark:bg-slate-800/40">
                <tr class="text-left text-slate-500 dark:text-slate-400">
                  <th class="px-4 py-2 font-medium">Nome</th>
                  <th class="px-4 py-2 font-medium">CPF</th>
                  <th class="px-4 py-2 font-medium">Função</th>
                  <th class="px-4 py-2 font-medium">Telefone</th>
                  <th class="px-4 py-2 font-medium">Celular</th>
                  <th class="px-4 py-2 font-medium">E-mail</th>
                  <th class="px-4 py-2 text-right font-medium">Ações</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-for="m in ativos" :key="m.id" class="text-slate-700 dark:text-slate-300">
                  <td class="px-4 py-2.5 font-medium">{{ m.nome }}</td>
                  <td class="px-4 py-2.5">{{ m.cpf || '—' }}</td>
                  <td class="px-4 py-2.5">{{ m.funcao_label || funcaoLabel(m.funcao) }}</td>
                  <td class="px-4 py-2.5">{{ m.telefone || '—' }}</td>
                  <td class="px-4 py-2.5">{{ m.celular || '—' }}</td>
                  <td class="px-4 py-2.5">{{ m.email || '—' }}</td>
                  <td class="whitespace-nowrap px-4 py-2.5 text-right">
                    <button type="button" class="mr-3 text-sm font-medium text-blue-600 hover:underline" @click="editar(m)">Editar</button>
                    <button type="button" class="mr-3 text-sm font-medium text-amber-600 hover:underline" @click="definirAtivo(m, false)">Inativar</button>
                    <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="excluir(m)">Excluir</button>
                  </td>
                </tr>
                <tr v-if="ativos.length === 0">
                  <td colspan="7" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">Nenhum membro ativo.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Anteriores / inativos -->
        <section v-if="inativos.length" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
          <h3 class="mb-3 text-base font-bold text-slate-700 dark:text-slate-200">Agentes / Coordenadores Anteriores</h3>
          <div class="overflow-x-auto rounded-lg border border-red-200 dark:border-red-500/30">
            <table class="min-w-full divide-y divide-red-100 text-sm dark:divide-red-500/20">
              <thead class="bg-red-50 dark:bg-red-500/10">
                <tr class="text-left text-red-700 dark:text-red-300">
                  <th class="px-4 py-2 font-medium">Nome</th>
                  <th class="px-4 py-2 font-medium">CPF</th>
                  <th class="px-4 py-2 font-medium">Função</th>
                  <th class="px-4 py-2 font-medium">Telefone</th>
                  <th class="px-4 py-2 text-right font-medium">Ações</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-red-100 dark:divide-red-500/20">
                <tr v-for="m in inativos" :key="m.id" class="bg-red-50/40 text-slate-600 dark:bg-red-500/5 dark:text-slate-300">
                  <td class="px-4 py-2.5">{{ m.nome }}</td>
                  <td class="px-4 py-2.5">{{ m.cpf || '—' }}</td>
                  <td class="px-4 py-2.5">{{ m.funcao_label || funcaoLabel(m.funcao) }}</td>
                  <td class="px-4 py-2.5">{{ m.telefone || '—' }}</td>
                  <td class="whitespace-nowrap px-4 py-2.5 text-right">
                    <button type="button" class="mr-3 text-sm font-medium text-green-600 hover:underline" @click="definirAtivo(m, true)">Reativar</button>
                    <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="excluir(m)">Excluir</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <footer class="flex shrink-0 items-center justify-end border-t border-slate-200 bg-white px-6 py-4 dark:border-slate-700/50 dark:bg-slate-800">
        <Button variant="danger" size="sm" type="button" @click="$emit('close')">Cancelar</Button>
      </footer>
    </div>
  </Modal>
</template>

<style scoped>
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>
