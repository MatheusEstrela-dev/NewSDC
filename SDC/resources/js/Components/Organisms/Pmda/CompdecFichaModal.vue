<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import {
  XMarkIcon,
  BuildingOffice2Icon,
  InformationCircleIcon,
  AtSymbolIcon,
  ShieldCheckIcon,
  ScaleIcon,
  PhoneIcon,
  WrenchScrewdriverIcon,
  UsersIcon,
  AcademicCapIcon,
  CameraIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  show: { type: Boolean, default: false },
  planoId: { type: [Number, String], required: true },
  ficha: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close']);
const { show: toast } = useToast();

const TABS = [
  { id: 'dados', label: 'Dados Cadastrais', icon: InformationCircleIcon },
  { id: 'contatos', label: 'Contatos e Acesso', icon: AtSymbolIcon },
  { id: 'estrutura', label: 'Estrutura & Capacitação', icon: ShieldCheckIcon },
];
const activeTab = ref('dados');

const STATUS = [
  { value: 'ativo', label: 'Ativo' },
  { value: 'inativo', label: 'Inativo' },
  { value: 'em_implantacao', label: 'Em Implantação' },
  { value: 'suspenso', label: 'Suspenso' },
];
const SIM_NAO = [
  { value: '1', label: 'Sim' },
  { value: '0', label: 'Não' },
];

const form = useForm({
  status: 'ativo',
  possui_compdec: true,
  lei_criacao_numero: '', lei_criacao_data: '',
  decreto_numero: '', decreto_data: '',
  portaria_numero: '', portaria_data: '',
  nao_possui_lei: false, nao_possui_decreto: false, nao_possui_portaria: false,
  email: '', email_secundario: '', email_terciario: '',
  telefone: '', telefone_secundario: '', fax: '', endereco: '',
  associacao: '',
  qtd_efetivo: 0, possui_efetivo: false,
  qtd_nupdec: 0, possui_nupdec: false, capacitacao_nupdec: '',
  tem_sede_propria: false, tem_viatura: false, tem_computador: false,
  tem_mapeamento_risco: false, tem_simulado: false, tem_cartao_pdc: false,
  possui_capacitacao_pdc: false, data_capacitacao_pdc: '',
  tem_curso_gestao: false, data_curso_gestao: '',
  tem_curso_sco: false, data_curso_sco: '',
  tem_workshop_pdc: false, data_workshop_pdc: '',
  tem_experiencia: false, tempo_experiencia_anos: '',
});

const simNao = (key) => computed({
  get: () => (form[key] ? '1' : '0'),
  set: (v) => { form[key] = v === '1'; },
});
const possuiCompdec = simNao('possui_compdec');
const possuiEfetivo = simNao('possui_efetivo');
const possuiNupdec = simNao('possui_nupdec');

const statusBadge = computed(() => ({
  ativo: 'bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-300',
  inativo: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300',
  em_implantacao: 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300',
  suspenso: 'bg-slate-200 dark:bg-slate-600/40 text-slate-600 dark:text-slate-300',
}[form.status] ?? 'bg-slate-200 text-slate-600'));
const statusLabel = computed(() => STATUS.find((s) => s.value === form.status)?.label ?? '—');

const fotoInput = ref(null);
const fotoBusy = ref(false);

watch(() => props.show, (v) => {
  if (!v) return;
  activeTab.value = 'dados';
  form.clearErrors();
  Object.keys(form.data()).forEach((k) => {
    if (props.ficha[k] !== undefined && props.ficha[k] !== null) form[k] = props.ficha[k];
  });
});

function fechar() { emit('close'); }

function gravar() {
  toast('Salvando ficha do COMPDEC...', 'info');
  form.put(route('pmda.planos.compdec.update', props.planoId), {
    preserveScroll: true,
    onSuccess: () => { toast('Ficha do COMPDEC salva com sucesso.', 'success'); fechar(); },
    onError: () => toast('Nao foi possivel salvar a ficha do COMPDEC.', 'error'),
  });
}

function selecionarFoto() { fotoInput.value?.click(); }
function enviarFoto(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  fotoBusy.value = true;
  toast('Enviando foto do coordenador...', 'info');
  router.post(route('pmda.planos.compdec.foto.upload', props.planoId), { foto: file }, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => toast('Foto do coordenador atualizada.', 'success'),
    onError: () => toast('Nao foi possivel atualizar a foto.', 'error'),
    onFinish: () => { fotoBusy.value = false; if (fotoInput.value) fotoInput.value.value = ''; },
  });
}
function removerFoto() {
  fotoBusy.value = true;
  toast('Removendo foto do coordenador...', 'info');
  router.delete(route('pmda.planos.compdec.foto.destroy', props.planoId), {
    preserveScroll: true,
    onSuccess: () => toast('Foto do coordenador removida.', 'success'),
    onError: () => toast('Nao foi possivel remover a foto.', 'error'),
    onFinish: () => { fotoBusy.value = false; },
  });
}
</script>

<template>
  <Modal :show="show" max-width="5xl" @close="fechar">
    <div class="flex max-h-[90vh] flex-col overflow-hidden">
      <div v-if="form.processing || fotoBusy" class="h-1 w-full overflow-hidden bg-slate-200 dark:bg-slate-700"><div class="h-full w-1/2 animate-pulse rounded-r-full bg-blue-600" /></div>
      <!-- Header: foto dedicada, identificacao do COMPDEC e bloco legivel da prefeitura -->
      <div class="relative border-b border-slate-200 bg-gradient-to-r from-slate-100 via-white to-blue-50 px-6 py-5 dark:border-slate-700/50 dark:from-slate-800 dark:via-slate-800 dark:to-slate-900">
        <button type="button" class="absolute right-4 top-4 rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-700/50 dark:hover:text-white" @click="fechar">
          <XMarkIcon class="h-5 w-5" />
        </button>

        <div class="grid grid-cols-1 gap-5 pr-10 lg:grid-cols-[112px_minmax(240px,1fr)_minmax(360px,420px)] lg:items-center">
          <!-- Foto (circulo) -->
          <div class="group relative mx-auto shrink-0 lg:mx-0">
            <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-slate-200 shadow-lg ring-1 ring-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:ring-slate-600">
              <img v-if="ficha.foto_coordenador_url" :src="ficha.foto_coordenador_url" alt="Foto" class="h-full w-full object-cover" />
              <BuildingOffice2Icon v-else class="h-10 w-10 text-slate-400" />
            </div>
            <button type="button" class="absolute inset-0 flex flex-col items-center justify-center gap-0.5 rounded-full bg-black/45 text-[11px] font-semibold text-white opacity-0 transition-opacity group-hover:opacity-100" :disabled="fotoBusy" @click="selecionarFoto">
              <CameraIcon class="h-5 w-5" /> Alterar
            </button>
            <input ref="fotoInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="enviarFoto" />
          </div>

          <!-- Titulo + meta -->
          <div class="min-w-0 text-center lg:text-left">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400">Cadastro municipal</p>
            <h2 class="mt-1 text-xl font-bold leading-tight text-slate-900 dark:text-white">Ficha Cadastral do COMPDEC</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
              Municipio: <span class="font-semibold text-blue-700 dark:text-blue-300">{{ ficha.municipio_nome || 'N/A' }}</span>
            </p>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-2 lg:justify-start">
              <span class="rounded-md px-2.5 py-1 text-xs font-bold uppercase" :class="statusBadge">{{ statusLabel }}</span>
              <span v-if="ficha.municipio_regiao" class="rounded-full bg-white/70 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:ring-slate-600/60">{{ ficha.municipio_regiao }}</span>
              <span v-if="ficha.municipio_mesorregiao" class="rounded-full bg-white/70 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:ring-slate-600/60">{{ ficha.municipio_mesorregiao }}</span>
              <button v-if="ficha.foto_coordenador_url" type="button" class="rounded-full px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-500/10" :disabled="fotoBusy" @click="removerFoto">Remover foto</button>
            </div>
          </div>

          <!-- Bloco direito: dados da prefeitura -->
          <section class="min-w-0 rounded-lg border border-blue-100 bg-white/95 p-4 shadow-sm ring-1 ring-white/70 dark:border-slate-600/50 dark:bg-slate-800/80 dark:ring-slate-700/60">
            <div class="mb-3 flex items-center gap-2 border-b border-slate-100 pb-2 dark:border-slate-700">
              <BuildingOffice2Icon class="h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" />
              <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400">Prefeitura</p>
                <h3 class="text-sm font-bold leading-tight text-slate-900 dark:text-white">Dados do Executivo Municipal</h3>
              </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-[13px] leading-snug sm:grid-cols-2">
              <div class="sm:col-span-2">
                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Prefeito</dt>
                <dd class="mt-0.5 break-words font-semibold text-slate-800 dark:text-slate-100">{{ ficha.prefeito_nome || 'N/A' }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Endereco</dt>
                <dd class="mt-0.5 break-words text-slate-700 dark:text-slate-200">{{ ficha.prefeitura_endereco || 'N/A' }}</dd>
              </div>
              <div>
                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Bairro</dt>
                <dd class="mt-0.5 break-words text-slate-700 dark:text-slate-200">{{ ficha.prefeitura_bairro || 'N/A' }}</dd>
              </div>
              <div>
                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">CEP</dt>
                <dd class="mt-0.5 break-words text-slate-700 dark:text-slate-200">{{ ficha.prefeitura_cep || 'N/A' }}</dd>
              </div>
            </dl>
          </section>
        </div>
      </div>

      <!-- Abas (pílula) -->
      <nav class="flex items-center gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50 px-6 py-2 scrollbar-hide dark:border-slate-700/50 dark:bg-slate-800/50">
        <button
          v-for="t in TABS" :key="t.id" type="button"
          class="flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-medium transition-all"
          :class="activeTab === t.id
            ? 'border border-blue-200 bg-blue-100 text-blue-600 dark:border-blue-500/30 dark:bg-blue-500/20 dark:text-blue-400'
            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700/50 dark:hover:text-slate-200'"
          @click="activeTab = t.id"
        >
          <component :is="t.icon" class="h-4 w-4" />
          <span class="hidden sm:inline">{{ t.label }}</span>
        </button>
      </nav>

      <!-- Corpo -->
      <div class="flex-1 overflow-y-auto bg-slate-50 p-6 scrollbar-hide dark:bg-slate-900/50">
        <Transition mode="out-in" enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">

          <!-- ABA 1: DADOS -->
          <div v-if="activeTab === 'dados'" class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
              <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-800 dark:text-slate-100"><BuildingOffice2Icon class="h-5 w-5 text-blue-500" /> Informações do COMPDEC</h3>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="md:col-span-3">
                  <label class="pmda-field-label">Nome do Município</label>
                  <input type="text" :value="ficha.municipio_nome || ''" disabled class="w-full cursor-not-allowed rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-slate-500 dark:border-slate-600 dark:bg-slate-700" />
                </div>
                <div><label class="pmda-field-label">Possui COMPDEC?</label><SelectInput v-model="possuiCompdec" :options="SIM_NAO" placeholder="" /></div>
                <div><label class="pmda-field-label">Possui Efetivo?</label><SelectInput v-model="possuiEfetivo" :options="SIM_NAO" placeholder="" /></div>
                <div><label class="pmda-field-label">Situação do COMPDEC</label><SelectInput v-model="form.status" :options="STATUS" placeholder="" /></div>
                <div>
                  <label class="pmda-field-label">Região de Desenvolvimento</label>
                  <input type="text" :value="ficha.municipio_regiao || '—'" disabled class="w-full cursor-not-allowed rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-slate-500 dark:border-slate-600 dark:bg-slate-700" />
                </div>
                <div>
                  <label class="pmda-field-label">Mesorregião</label>
                  <input type="text" :value="ficha.municipio_mesorregiao || '—'" disabled class="w-full cursor-not-allowed rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-slate-500 dark:border-slate-600 dark:bg-slate-700" />
                </div>
                <div><label class="pmda-field-label">Associação de Municípios</label><TextInput v-model="form.associacao" :maxlength="150" /></div>
              </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
              <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-800 dark:text-slate-100"><ScaleIcon class="h-5 w-5 text-blue-500" /> Base Legal & Portarias</h3>
              <div class="grid grid-cols-1 gap-x-8 gap-y-4 md:grid-cols-2">
                <div class="grid grid-cols-[1fr_1fr_auto] gap-3">
                  <div><label class="pmda-field-label">Nº Lei</label><TextInput v-model="form.lei_criacao_numero" :maxlength="50" /></div>
                  <div><label class="pmda-field-label">Data Lei</label><TextInput v-model="form.lei_criacao_data" type="date" /></div>
                  <label class="flex items-end gap-1 pb-2 text-xs text-slate-500"><input v-model="form.nao_possui_lei" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600" /> N/P</label>
                </div>
                <div class="grid grid-cols-[1fr_1fr_auto] gap-3">
                  <div><label class="pmda-field-label">Nº Decreto</label><TextInput v-model="form.decreto_numero" :maxlength="50" /></div>
                  <div><label class="pmda-field-label">Data Decreto</label><TextInput v-model="form.decreto_data" type="date" /></div>
                  <label class="flex items-end gap-1 pb-2 text-xs text-slate-500"><input v-model="form.nao_possui_decreto" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600" /> N/P</label>
                </div>
                <div class="grid grid-cols-[1fr_1fr_auto] gap-3">
                  <div><label class="pmda-field-label">Nº Portaria</label><TextInput v-model="form.portaria_numero" :maxlength="50" /></div>
                  <div><label class="pmda-field-label">Data Portaria</label><TextInput v-model="form.portaria_data" type="date" /></div>
                  <label class="flex items-end gap-1 pb-2 text-xs text-slate-500"><input v-model="form.nao_possui_portaria" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600" /> N/P</label>
                </div>
              </div>
            </section>
          </div>

          <!-- ABA 2: CONTATOS -->
          <div v-else-if="activeTab === 'contatos'" class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
              <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-800 dark:text-slate-100"><PhoneIcon class="h-5 w-5 text-blue-500" /> Contato do COMPDEC</h3>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="md:col-span-3"><label class="pmda-field-label">Endereço (COMPDEC)</label><TextInput v-model="form.endereco" :maxlength="1000" /></div>
                <div><label class="pmda-field-label">Telefone</label><TextInput v-model="form.telefone" :maxlength="20" /></div>
                <div><label class="pmda-field-label">Telefone 2</label><TextInput v-model="form.telefone_secundario" :maxlength="20" /></div>
                <div><label class="pmda-field-label">Fax</label><TextInput v-model="form.fax" :maxlength="20" /></div>
                <div><label class="pmda-field-label">E-mail Principal</label><TextInput v-model="form.email" type="email" :maxlength="255" /></div>
                <div><label class="pmda-field-label">E-mail Alternativo 1</label><TextInput v-model="form.email_secundario" type="email" :maxlength="255" /></div>
                <div><label class="pmda-field-label">E-mail Alternativo 2</label><TextInput v-model="form.email_terciario" type="email" :maxlength="255" /></div>
              </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
              <div class="mb-3 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold text-slate-800 dark:text-slate-100"><AtSymbolIcon class="h-5 w-5 text-blue-500" /> Prefeitura / Acesso</h3>
                <span class="text-xs text-slate-400">Editável na aba ISS</span>
              </div>
              <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm md:grid-cols-3">
                <div><dt class="text-xs text-slate-500">E-mail da Prefeitura</dt><dd class="text-slate-700 dark:text-slate-200">{{ ficha.prefeitura_email || '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Telefone da Prefeitura</dt><dd class="text-slate-700 dark:text-slate-200">{{ ficha.prefeitura_telefone || '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Telefone do Prefeito</dt><dd class="text-slate-700 dark:text-slate-200">{{ ficha.prefeito_telefone || '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Celular do Prefeito</dt><dd class="text-slate-700 dark:text-slate-200">{{ ficha.prefeito_celular || '—' }}</dd></div>
              </dl>
            </section>
          </div>

          <!-- ABA 3: ESTRUTURA -->
          <div v-else class="space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
              <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
                <span class="text-sm font-medium leading-tight text-slate-500" title="Atualizado pelo Plano de Contingência">Plano de Contingência?</span>
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="ficha.tem_plano_contingencia ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-500'">{{ ficha.tem_plano_contingencia ? 'Sim' : 'Não' }}</span>
              </div>
              <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
                <span class="text-sm font-medium leading-tight text-slate-700 dark:text-slate-200">Cartão de Defesa Civil?</span>
                <input v-model="form.tem_cartao_pdc" type="checkbox" class="h-5 w-5 rounded text-blue-600 focus:ring-blue-500" />
              </label>
              <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
                <span class="text-sm font-medium leading-tight text-slate-700 dark:text-slate-200">Realiza Simulados?</span>
                <input v-model="form.tem_simulado" type="checkbox" class="h-5 w-5 rounded text-blue-600 focus:ring-blue-500" />
              </label>
              <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
                <span class="text-sm font-medium leading-tight text-slate-700 dark:text-slate-200">Mapeamento de risco?</span>
                <input v-model="form.tem_mapeamento_risco" type="checkbox" class="h-5 w-5 rounded text-blue-600 focus:ring-blue-500" />
              </label>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
              <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
                <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-800 dark:text-slate-100"><WrenchScrewdriverIcon class="h-5 w-5 text-blue-500" /> Estrutura da COMPDEC</h3>
                <div class="space-y-2">
                  <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 dark:border-slate-700/50 dark:bg-slate-800/40"><input v-model="form.tem_sede_propria" type="checkbox" class="h-5 w-5 rounded text-blue-600" /><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Sede Própria</span></label>
                  <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 dark:border-slate-700/50 dark:bg-slate-800/40"><input v-model="form.tem_viatura" type="checkbox" class="h-5 w-5 rounded text-blue-600" /><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Viaturas</span></label>
                  <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 dark:border-slate-700/50 dark:bg-slate-800/40"><input v-model="form.tem_computador" type="checkbox" class="h-5 w-5 rounded text-blue-600" /><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Computadores</span></label>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
                <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-800 dark:text-slate-100"><UsersIcon class="h-5 w-5 text-blue-500" /> Informações NUPDEC</h3>
                <div class="space-y-4">
                  <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-3 dark:border-slate-700/50 dark:bg-slate-800/40">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Possui NUPDEC?</span>
                    <div class="w-28"><SelectInput v-model="possuiNupdec" :options="SIM_NAO" placeholder="" /></div>
                  </div>
                  <div v-if="form.possui_nupdec" class="grid grid-cols-2 gap-4">
                    <div><label class="pmda-field-label">Quantos NUPDEC's</label><TextInput v-model="form.qtd_nupdec" type="number" /></div>
                    <div><label class="pmda-field-label">Quantos integrantes</label><TextInput v-model="form.qtd_efetivo" type="number" /></div>
                  </div>
                  <div><label class="pmda-field-label">Capacitação dos Membros</label><TextInput v-model="form.capacitacao_nupdec" :maxlength="255" /></div>
                </div>
              </section>
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
              <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-800 dark:text-slate-100"><AcademicCapIcon class="h-5 w-5 text-blue-500" /> Cursos e Experiência</h3>
              <div class="space-y-3">
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                  <label class="flex cursor-pointer items-center gap-3"><input v-model="form.possui_capacitacao_pdc" type="checkbox" class="h-5 w-5 rounded text-blue-600" /><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Possui Capacitação em Proteção e Defesa Civil?</span></label>
                  <div v-if="form.possui_capacitacao_pdc" class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50"><label class="pmda-field-label">Data do Curso</label><TextInput v-model="form.data_capacitacao_pdc" type="date" class="max-w-48" /></div>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                  <label class="flex cursor-pointer items-start gap-3"><input v-model="form.tem_curso_gestao" type="checkbox" class="mt-0.5 h-5 w-5 rounded text-blue-600" /><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Possui Curso de Gestão em Proteção e Defesa Civil e Mudanças Climáticas</span></label>
                  <div v-if="form.tem_curso_gestao" class="mt-3 ml-8"><label class="pmda-field-label">Data do Curso</label><TextInput v-model="form.data_curso_gestao" type="date" class="max-w-48" /></div>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                  <label class="flex cursor-pointer items-start gap-3"><input v-model="form.tem_curso_sco" type="checkbox" class="mt-0.5 h-5 w-5 rounded text-blue-600" /><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Possui Curso de SCO (Sistema de Comandos e Operações)</span></label>
                  <div v-if="form.tem_curso_sco" class="mt-3 ml-8"><label class="pmda-field-label">Data do Curso</label><TextInput v-model="form.data_curso_sco" type="date" class="max-w-48" /></div>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-slate-700/50 dark:bg-slate-800/40">
                  <h4 class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">Capacidade Gerencial</h4>
                  <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                      <label class="flex cursor-pointer items-center gap-3"><input v-model="form.tem_workshop_pdc" type="checkbox" class="h-5 w-5 rounded text-blue-600" /><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Participou de Workshop</span></label>
                      <div v-if="form.tem_workshop_pdc" class="mt-3 ml-8"><label class="pmda-field-label">Data</label><TextInput v-model="form.data_workshop_pdc" type="date" class="max-w-48" /></div>
                    </div>
                    <div>
                      <label class="flex cursor-pointer items-center gap-3"><input v-model="form.tem_experiencia" type="checkbox" class="h-5 w-5 rounded text-blue-600" /><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Possui experiência na área</span></label>
                      <div v-if="form.tem_experiencia" class="mt-3 ml-8"><label class="pmda-field-label">Tempo (anos)</label><TextInput v-model="form.tempo_experiencia_anos" type="number" class="max-w-28" /></div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </Transition>
      </div>

      <!-- Rodapé -->
      <footer class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4 dark:border-slate-700/50 dark:bg-slate-800">
        <Button variant="danger" size="sm" type="button" @click="fechar">Cancelar</Button>
        <Button variant="primary" size="sm" :loading="form.processing" :disabled="form.processing" @click="gravar">Salvar Alterações</Button>
      </footer>
    </div>
  </Modal>
</template>

<style scoped>
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>
