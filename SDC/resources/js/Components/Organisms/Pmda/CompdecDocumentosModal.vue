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
  DocumentTextIcon,
  ExclamationTriangleIcon,
  CloudArrowUpIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  show: { type: Boolean, default: false },
  planoId: { type: [Number, String], required: true },
  anexos: { type: Array, default: () => [] },
  ficha: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close']);
const { show: toast } = useToast();

const TIPOS = [
  { value: 'lei', label: 'Lei de Criação' },
  { value: 'decreto', label: 'Decreto de Regulamentação' },
  { value: 'portaria', label: 'Portaria de Nomeação' },
  { value: 'regimento', label: 'Regimento Interno' },
  { value: 'outros', label: 'Outros' },
];

const DOCUMENTOS_LEGADO = [
  {
    tipo: 'decreto',
    flag: 'nao_possui_decreto',
    titulo: 'Decreto de Regulamentação',
    descricao: 'Nao possui Decreto de Regulamentação da Lei de Criação do COMPDEC',
  },
  {
    tipo: 'portaria',
    flag: 'nao_possui_portaria',
    titulo: 'Portaria de Nomeação',
    descricao: 'Nao possui Portaria de Nomeação do Coordenador Municipal de Defesa Civil',
  },
  {
    tipo: 'lei',
    flag: 'nao_possui_lei',
    titulo: 'Lei de Criação',
    descricao: 'Nao possui Lei de Criação do COMPDEC',
  },
];

const BOOLS_FICHA = [
  'tem_sede_propria', 'tem_viatura', 'tem_mapeamento_risco', 'tem_simulado', 'tem_cartao_pdc',
  'tem_computador', 'tem_curso_gestao', 'tem_curso_sco', 'tem_workshop_pdc', 'tem_experiencia',
  'possui_capacitacao_pdc', 'possui_compdec', 'possui_nupdec', 'possui_efetivo',
  'nao_possui_lei', 'nao_possui_decreto', 'nao_possui_portaria',
];

const tipoLabel = (v) => TIPOS.find((t) => t.value === v)?.label ?? v;
const fmt = (iso) => {
  if (!iso) return '--';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '--' : d.toLocaleDateString('pt-BR');
};

const showUpload = ref(false);
const arquivoInput = ref(null);
const uploadConcluido = ref(false);
const uploadEmAndamento = ref(false);

const ausenciaForm = useForm(Object.fromEntries(BOOLS_FICHA.map((key) => [key, false])));

const form = useForm({
  tipo: 'decreto',
  titulo: '',
  numero: '',
  data_emissao: '',
  data_validade: '',
  descricao: '',
  arquivo: null,
});

const documentosComStatus = computed(() => DOCUMENTOS_LEGADO.map((doc) => ({
  ...doc,
  anexado: props.anexos.some((anexo) => anexo.tipo === doc.tipo),
  ausente: Boolean(ausenciaForm[doc.flag]),
})));

const uploadOcupado = computed(() => uploadEmAndamento.value || form.processing);

const uploadPercentual = computed(() => {
  if (uploadConcluido.value) return 100;
  if (form.progress?.percentage) return Math.max(8, Math.min(100, Math.round(form.progress.percentage)));
  return uploadOcupado.value ? 45 : 0;
});

watch(
  () => [props.show, props.ficha],
  () => {
    if (!props.show) return;
    BOOLS_FICHA.forEach((key) => { ausenciaForm[key] = Boolean(props.ficha?.[key]); });
    ausenciaForm.clearErrors();
  },
  { immediate: true, deep: true },
);

function fechar() {
  emit('close');
}

function salvarDeclaracoes() {
  toast('Salvando declaracoes de documentos...', 'info');
  ausenciaForm.put(route('pmda.planos.compdec.update', props.planoId), {
    preserveScroll: true,
    onSuccess: () => toast('Declaracoes de documentos salvas.', 'success'),
    onError: () => toast('Nao foi possivel salvar as declaracoes.', 'error'),
  });
}

function abrirUpload(tipo = 'decreto') {
  form.reset();
  form.clearErrors();
  uploadConcluido.value = false;
  uploadEmAndamento.value = false;
  form.tipo = tipo;
  form.titulo = tipoLabel(tipo);
  form.descricao = '';
  showUpload.value = true;
}

function onArquivo(e) {
  form.arquivo = e.target.files?.[0] ?? null;
}

function recarregarAnexos() {
  router.reload({ only: ['compdec_anexos'], preserveScroll: true, preserveState: true });
}

function fecharUpload() {
  if (uploadOcupado.value) return;
  showUpload.value = false;
  uploadConcluido.value = false;
  uploadEmAndamento.value = false;
}

function enviar() {
  if (!form.arquivo || !form.titulo.trim() || uploadOcupado.value) return;

  uploadEmAndamento.value = true;
  uploadConcluido.value = false;
  form.clearErrors();

  form.post(route('pmda.planos.compdec.anexos.store', props.planoId), {
    forceFormData: true,
    preserveScroll: true,
    onStart: () => {
      uploadEmAndamento.value = true;
    },
    onSuccess: () => {
      uploadConcluido.value = true;
      toast('Documento anexado ao histórico com sucesso.', 'success');
      recarregarAnexos();
      window.setTimeout(() => {
        showUpload.value = false;
        uploadConcluido.value = false;
        form.reset();
        if (arquivoInput.value) arquivoInput.value.value = '';
      }, 900);
    },
    onError: () => {
      uploadConcluido.value = false;
      toast('Nao foi possivel anexar o documento. Confira os campos e tente novamente.', 'error');
    },
    onFinish: () => {
      uploadEmAndamento.value = false;
    },
  });
}

function visualizar(anexo) {
  window.open(route('pmda.planos.compdec.anexos.download', [props.planoId, anexo.id]), '_blank');
}

function remover(anexo) {
  toast('Removendo documento...', 'info');
  router.delete(route('pmda.planos.compdec.anexos.destroy', [props.planoId, anexo.id]), {
    preserveScroll: true,
    onSuccess: () => { toast('Documento removido.', 'success'); recarregarAnexos(); },
    onError: () => toast('Nao foi possivel remover o documento.', 'error'),
  });
}
</script>

<template>
  <Modal :show="show" max-width="5xl" @close="fechar">
    <div class="flex max-h-[88vh] flex-col overflow-hidden bg-white dark:bg-slate-900">
      <div v-if="ausenciaForm.processing" class="h-1 w-full overflow-hidden bg-slate-200 dark:bg-slate-700"><div class="h-full w-1/2 animate-pulse rounded-r-full bg-blue-600" /></div>
      <header class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-blue-50 px-6 py-5 dark:border-slate-700/50 dark:from-slate-800 dark:via-slate-800 dark:to-slate-900">
        <div class="flex min-w-0 items-start gap-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:ring-blue-500/30">
            <DocumentTextIcon class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400">Anexo Leis e Decretos</p>
            <h2 class="mt-0.5 text-lg font-bold leading-tight text-slate-900 dark:text-white">Documentos do COMPDEC</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
              Informe as declarações do legado e anexe somente Lei de Criação, Decreto de Regulamentação e Portaria de Nomeação.
            </p>
          </div>
        </div>
        <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700 dark:hover:text-slate-100" @click="fechar">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </header>

      <div class="flex-1 space-y-5 overflow-y-auto bg-slate-50 p-6 dark:bg-slate-900/60">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/60 dark:bg-slate-800/70">
          <div class="mb-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">Leis e Decretos</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Marque apenas quando o municipio realmente não possuir o documento.</p>
          </div>

          <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div
              v-for="doc in documentosComStatus"
              :key="doc.flag"
              class="flex flex-col gap-3 rounded-lg border p-4 transition dark:border-slate-700"
              :class="doc.ausente
                ? 'border-amber-200 bg-amber-50/80 dark:border-amber-500/30 dark:bg-amber-500/10'
                : 'cursor-pointer border-slate-200 bg-slate-50 hover:border-blue-300 hover:bg-blue-50/60 hover:shadow-sm dark:bg-slate-900/40 dark:hover:border-blue-500/30 dark:hover:bg-blue-500/10'"
              :role="doc.ausente ? null : 'button'"
              :tabindex="doc.ausente ? -1 : 0"
              :title="doc.ausente ? 'Declarado sem documento' : 'Clique para anexar'"
              @click="!doc.ausente && abrirUpload(doc.tipo)"
              @keydown.enter="!doc.ausente && abrirUpload(doc.tipo)"
            >
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ doc.titulo }}</span>
                  <span class="mt-1 block text-xs leading-5 text-slate-600 dark:text-slate-300">{{ doc.descricao }}</span>
                </div>
                <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                  :class="doc.anexado
                    ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300'
                    : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                >
                  <CheckCircleIcon class="h-3.5 w-3.5" />
                  {{ doc.anexado ? 'Anexado' : 'Sem anexo' }}
                </span>
              </div>

              <div class="mt-auto flex items-center justify-between gap-3 pt-1">
                <!-- Switch "Nao possui" (nao abre o upload) -->
                <button
                  type="button"
                  role="switch"
                  :aria-checked="doc.ausente"
                  class="flex items-center gap-2"
                  @click.stop="ausenciaForm[doc.flag] = !ausenciaForm[doc.flag]"
                >
                  <span class="relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors" :class="doc.ausente ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-600'">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="doc.ausente ? 'translate-x-4' : 'translate-x-0'" />
                  </span>
                  <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Não possui</span>
                </button>

                <span v-if="!doc.ausente" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400">
                  <CloudArrowUpIcon class="h-4 w-4" /> Anexar
                </span>
                <span v-else class="text-xs font-medium text-amber-600 dark:text-amber-300">Declarado sem documento</span>
              </div>
            </div>
          </div>

          <div class="mt-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
            <p class="font-semibold">Favor NÃO anexar documento fora do conteúdo solicitado.</p>
          </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/60 dark:bg-slate-800/70">
          <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700/60">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Histórico de documentos</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Clique em um bloco acima (Lei, Decreto ou Portaria) para anexar. Aqui ficam os arquivos enviados, com tipo, nome, datas e histórico.</p>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
              <thead class="bg-slate-50 dark:bg-slate-800/40">
                <tr class="text-left text-slate-500 dark:text-slate-400">
                  <th class="px-4 py-2.5 font-semibold">Arquivo</th>
                  <th class="px-4 py-2.5 font-semibold">Tipo</th>
                  <th class="px-4 py-2.5 font-semibold">Número</th>
                  <th class="px-4 py-2.5 font-semibold">Enviado em</th>
                  <th class="px-4 py-2.5 font-semibold">Emissão</th>
                  <th class="px-4 py-2.5 font-semibold">Validade</th>
                  <th class="px-4 py-2.5 font-semibold">Descrição</th>
                  <th class="px-4 py-2.5 text-right font-semibold">Ações</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-for="a in anexos" :key="a.id" class="text-slate-700 dark:text-slate-300">
                  <td class="px-4 py-3">
                    <div class="max-w-xs">
                      <p class="truncate font-semibold text-slate-900 dark:text-slate-100" :title="a.arquivo_nome_original || a.titulo">{{ a.arquivo_nome_original || a.titulo }}</p>
                      <p class="mt-0.5 text-xs text-slate-400">{{ a.arquivo_tamanho_formatado || 'Arquivo anexado' }}</p>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">{{ a.tipo_label ?? tipoLabel(a.tipo) }}</span>
                  </td>
                  <td class="px-4 py-3">{{ a.numero || '--' }}</td>
                  <td class="px-4 py-3">{{ a.arquivo_enviado_em_formatado || a.created_at_formatado || '--' }}</td>
                  <td class="px-4 py-3">{{ fmt(a.data_emissao) }}</td>
                  <td class="px-4 py-3">{{ fmt(a.data_validade) }}</td>
                  <td class="max-w-xs px-4 py-3 text-slate-500 dark:text-slate-400">{{ a.descricao || '--' }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-right">
                    <button v-if="a.tem_arquivo" type="button" class="mr-3 text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400" @click="visualizar(a)">Visualizar</button>
                    <button type="button" class="text-sm font-semibold text-red-600 hover:underline dark:text-red-400" @click="remover(a)">Excluir</button>
                  </td>
                </tr>
                <tr v-if="anexos.length === 0">
                  <td colspan="8" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">Nenhum documento no histórico.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <footer class="flex items-center justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4 dark:border-slate-700/60 dark:bg-slate-900">
        <Button variant="danger" size="sm" type="button" @click="fechar">Cancelar</Button>
        <Button variant="primary" size="sm" :loading="ausenciaForm.processing" :disabled="ausenciaForm.processing" @click="salvarDeclaracoes">Salvar Declarações</Button>
      </footer>
    </div>
  </Modal>

  <Modal :show="showUpload" max-width="lg" @close="fecharUpload">
    <div class="space-y-4 p-5">
      <header class="flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Novo Documento</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Preencha os dados do documento e selecione o arquivo.</p>
        </div>
        <button type="button" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-slate-800" :disabled="uploadOcupado" @click="fecharUpload">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </header>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
          <label class="pmda-field-label">Tipo do Documento</label>
          <SelectInput v-model="form.tipo" :options="TIPOS" placeholder="" />
        </div>
        <div>
          <label class="pmda-field-label">Título <span class="req">*</span></label>
          <TextInput v-model="form.titulo" :maxlength="150" />
        </div>
        <div>
          <label class="pmda-field-label">Número</label>
          <TextInput v-model="form.numero" :maxlength="50" />
        </div>
        <div>
          <label class="pmda-field-label">Data de Emissão</label>
          <TextInput v-model="form.data_emissao" type="date" />
        </div>
        <div>
          <label class="pmda-field-label">Validade</label>
          <TextInput v-model="form.data_validade" type="date" />
        </div>
        <div class="sm:col-span-2">
          <label class="pmda-field-label">Descrição</label>
          <TextInput v-model="form.descricao" :maxlength="255" />
        </div>
        <div class="sm:col-span-2">
          <label class="pmda-field-label">Arquivo (PDF/DOC) <span class="req">*</span></label>
          <input ref="arquivoInput" type="file" accept=".pdf,.doc,.docx,.odt" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60 dark:text-slate-300 dark:file:bg-slate-700" :disabled="uploadOcupado" @change="onArquivo" />
          <div v-if="uploadOcupado || uploadConcluido" class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/10" aria-live="polite">
            <div class="mb-2 flex items-center justify-between text-xs font-semibold text-emerald-700 dark:text-emerald-200">
              <span>{{ uploadConcluido ? 'Upload concluido. Atualizando historico...' : 'Enviando arquivo para o historico...' }}</span>
              <span>{{ uploadPercentual }}%</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-white ring-1 ring-emerald-200 dark:bg-slate-800 dark:ring-emerald-500/30">
              <div
                class="h-full rounded-full bg-emerald-500 transition-all duration-300 ease-out"
                :class="uploadOcupado && !form.progress ? 'animate-pulse' : ''"
                :style="{ width: `${uploadPercentual}%` }"
              />
            </div>
          </div>
          <span v-if="form.errors.arquivo" class="mt-1 block text-xs text-red-600">{{ form.errors.arquivo }}</span>
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <Button variant="secondary" size="sm" type="button" :disabled="uploadOcupado" @click="fecharUpload">Cancelar</Button>
        <Button variant="success" size="sm" :loading="uploadOcupado" :disabled="!form.arquivo || !form.titulo.trim() || uploadOcupado" @click="enviar">{{ uploadOcupado ? 'Enviando...' : 'Enviar' }}</Button>
      </div>
    </div>
  </Modal>
</template>