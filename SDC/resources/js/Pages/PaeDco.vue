<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6">
    <Head :title="`DCO - ${protocolo.num_protocolo}`" />

    <header class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">PAE · Resolução GMG nº 83/2024</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Declaração de Conformidade e Operacionalidade</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Protocolo {{ protocolo.num_protocolo }}</p>
      </div>
      <a :href="route('pae.protocolos.index')" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Voltar aos protocolos</a>
    </header>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Situação anual</h2>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Competência {{ resumo.competencia_anual }} · prazo {{ formatarData(resumo.vencimento) }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-semibold" :class="classeSituacao">{{ rotuloSituacao }}</span>
      </div>
      <p v-if="resumo.entrega_tardia" class="mt-3 text-sm text-amber-700 dark:text-amber-300">A DCO desta competência foi apresentada após 30 de junho. O atraso permanece registrado no histórico.</p>
      <p v-if="resumo.alerta_legado" class="mt-3 text-sm text-amber-700 dark:text-amber-300">Este CCPAE é anterior ao registro de aplicabilidade da DCO. A CEDEC deve avaliar o protocolo.</p>
      <p v-if="['atrasada', 'nao_conforme'].includes(resumo.situacao)" class="mt-3 text-sm text-red-700 dark:text-red-300">Pendência para análise da CEDEC. O sistema não altera automaticamente a vigência do CCPAE.</p>
      <p v-if="protocolo.arquivado" class="mt-3 text-sm text-amber-700 dark:text-amber-300">Protocolo arquivado: histórico disponível somente para leitura.</p>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Aplicabilidade</h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">A decisão vigente é a avaliação mais recente da CEDEC.</p>
        <div v-if="resumo.avaliacao" class="mt-4 rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-800">
          <p class="font-semibold text-slate-900 dark:text-white">{{ resumo.avaliacao.resultado === 'aplicavel' ? 'DCO aplicável' : 'DCO não aplicável' }}</p>
          <p class="mt-1 text-slate-700 dark:text-slate-200">{{ resumo.avaliacao.fundamentacao }}</p>
          <p class="mt-1 text-slate-500 dark:text-slate-400">SEI {{ resumo.avaliacao.num_sei }} · {{ resumo.avaliacao.decisor?.name || 'Responsável não disponível' }} · {{ formatarData(resumo.avaliacao.decidido_em) }}</p>
        </div>
        <p v-else class="mt-4 text-sm text-amber-700 dark:text-amber-300">Aplicabilidade ainda não avaliada. A emissão de CCPAE ficará bloqueada.</p>

        <form v-if="can_validar" class="mt-5 space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700" @submit.prevent="avaliar">
          <h3 class="font-medium text-slate-900 dark:text-white">Registrar nova avaliação</h3>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Resultado
            <select v-model="avaliacao.resultado" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800">
              <option value="aplicavel">Aplicável</option>
              <option value="nao_aplicavel">Não aplicável</option>
            </select>
            <ErroCampo :mensagem="avaliacao.errors.resultado" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Fundamentação
            <textarea v-model="avaliacao.fundamentacao" rows="3" maxlength="5000" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <ErroCampo :mensagem="avaliacao.errors.fundamentacao" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Número SEI
            <input v-model="avaliacao.num_sei" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <ErroCampo :mensagem="avaliacao.errors.num_sei" />
          </label>
          <p v-if="errosGerais(avaliacao, CAMPOS_AVALIACAO)" class="text-sm text-red-600 dark:text-red-300">{{ errosGerais(avaliacao, CAMPOS_AVALIACAO) }}</p>
          <button type="submit" :disabled="avaliacao.processing" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Registrar avaliação</button>
        </form>

        <h3 class="mt-6 font-medium text-slate-900 dark:text-white">Histórico de avaliações</h3>
        <p v-if="!resumo.avaliacoes.length" class="mt-2 text-sm text-slate-500">Nenhuma avaliação registrada.</p>
        <ol v-else class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
          <li v-for="item in resumo.avaliacoes" :key="item.id" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <strong>{{ item.resultado === 'aplicavel' ? 'Aplicável' : 'Não aplicável' }}</strong> · SEI {{ item.num_sei }} · {{ formatarData(item.decidido_em) }}
            <p class="mt-1">{{ item.fundamentacao }}</p>
          </li>
        </ol>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Declarações apresentadas</h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Cada apresentação gera uma versão. A mais recente da competência prevalece na emissão.</p>

        <form v-if="can_validar && resumo.avaliacao?.resultado === 'aplicavel'" class="mt-5 grid gap-3 border-b border-slate-200 pb-5 sm:grid-cols-2 dark:border-slate-700" @submit.prevent="registrarDocumento">
          <label class="block text-sm text-slate-700 dark:text-slate-200">Competência
            <input v-model.number="documento.competencia" type="number" min="2022" :max="resumo.competencia_anual" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <ErroCampo :mensagem="documento.errors.competencia" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Resultado conferido
            <select v-model="documento.resultado" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800">
              <option value="positiva">Positiva</option>
              <option value="nao_conforme">Não conforme</option>
            </select>
            <ErroCampo :mensagem="documento.errors.resultado" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Data da DCO
            <input v-model="documento.dt_documento" type="date" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <ErroCampo :mensagem="documento.errors.dt_documento" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Apresentada à CEDEC em
            <input v-model="documento.dt_apresentacao" type="date" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <ErroCampo :mensagem="documento.errors.dt_apresentacao" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Número SEI
            <input v-model="documento.num_sei" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <ErroCampo :mensagem="documento.errors.num_sei" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Arquivo PDF (até 20 MiB)
            <input ref="arquivoInput" type="file" accept="application/pdf,.pdf" required class="mt-1 w-full text-sm" @change="documento.arquivo = $event.target.files?.[0] || null" />
            <ErroCampo :mensagem="documento.errors.arquivo" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200 sm:col-span-2">Observação
            <textarea v-model="documento.observacao" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-800" />
            <ErroCampo :mensagem="documento.errors.observacao" />
          </label>
          <p v-if="errosGerais(documento, CAMPOS_DOCUMENTO)" class="text-sm text-red-600 dark:text-red-300 sm:col-span-2">{{ errosGerais(documento, CAMPOS_DOCUMENTO) }}</p>
          <button type="submit" :disabled="documento.processing" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 sm:col-span-2">Registrar DCO</button>
        </form>

        <p v-if="!resumo.documentos.length" class="mt-4 text-sm text-slate-500 dark:text-slate-400">Nenhuma DCO apresentada.</p>
        <ul v-else class="mt-4 space-y-3">
          <li v-for="item in resumo.documentos" :key="item.id" class="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <strong class="text-slate-900 dark:text-white">{{ item.competencia }} · versão {{ item.versao }} · {{ item.resultado === 'positiva' ? 'Positiva' : 'Não conforme' }}</strong>
              <a v-if="can_view" :href="route('pae.protocolo.dco.documentos.download', [protocolo.id, item.id])" class="font-semibold text-blue-700 underline dark:text-blue-300">Baixar PDF</a>
            </div>
            <p class="mt-1 text-slate-600 dark:text-slate-300">DCO de {{ formatarData(item.dt_documento) }} · apresentada {{ formatarData(item.dt_apresentacao) }} · SEI {{ item.num_sei }}</p>
            <p v-if="item.entrega_tardia" class="mt-1 text-amber-700 dark:text-amber-300">Apresentação após o prazo anual.</p>
            <p v-if="item.observacao" class="mt-1 text-slate-600 dark:text-slate-300">{{ item.observacao }}</p>
          </li>
        </ul>
      </section>
    </div>

    <section v-if="resumo.ccpae" class="rounded-xl border border-slate-200 bg-white p-5 text-sm dark:border-slate-700 dark:bg-slate-900">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Evidência usada no CCPAE</h2>
      <p class="mt-2 text-slate-700 dark:text-slate-300">{{ resumo.ccpae.codigo }} · avaliação #{{ resumo.ccpae.dco_avaliacao_id || 'legada, sem referência' }} · DCO #{{ resumo.ccpae.dco_documento_id || 'não exigida ou legada' }}</p>
    </section>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, h, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  resumo: { type: Object, required: true },
  can_validar: { type: Boolean, default: false },
  can_view: { type: Boolean, default: false },
});

const CAMPOS_AVALIACAO = ['resultado', 'fundamentacao', 'num_sei'];
const CAMPOS_DOCUMENTO = ['competencia', 'resultado', 'dt_documento', 'dt_apresentacao', 'num_sei', 'arquivo', 'observacao'];

const ErroCampo = (componentProps) => (componentProps.mensagem
  ? h('span', { class: 'mt-1 block text-xs text-red-600 dark:text-red-300' }, componentProps.mensagem)
  : null);
ErroCampo.props = ['mensagem'];

// Junta os erros de chaves sem campo proprio no formulario (ex.: avaliacao, dco).
function errosGerais(form, campos) {
  return Object.entries(form.errors)
    .filter(([campo]) => !campos.includes(campo))
    .map(([, mensagem]) => mensagem)
    .join(' ');
}

const arquivoInput = ref(null);

const avaliacao = useForm({ resultado: 'aplicavel', fundamentacao: '', num_sei: '', chave_idempotencia: crypto.randomUUID() });
const documento = useForm({ competencia: props.resumo.competencia_anual, resultado: 'positiva', dt_documento: '', dt_apresentacao: '', num_sei: '', observacao: '', arquivo: null, chave_idempotencia: crypto.randomUUID() });

const rotulos = { nao_avaliada: 'Não avaliada', nao_aplicavel: 'Não aplicável', comprovada: 'Comprovada', aguardando_prazo: 'Aguardando prazo', pendente_emissao: 'Pendente para emissão', atrasada: 'Atrasada', nao_conforme: 'Não conforme' };
const rotuloSituacao = computed(() => rotulos[props.resumo.situacao] || props.resumo.situacao);
const classeSituacao = computed(() => ({
  comprovada: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  nao_aplicavel: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-100',
  aguardando_prazo: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
  pendente_emissao: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
  atrasada: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  nao_conforme: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
}[props.resumo.situacao] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-100'));

// Datas puras (YYYY-MM-DD) nao passam por Date, que deslocaria um dia; valores com horario viram data local.
function formatarData(valor) {
  if (!valor) return '—';
  const texto = String(valor);
  if (texto.length > 10) {
    const data = new Date(texto);
    if (!Number.isNaN(data.getTime())) return data.toLocaleDateString('pt-BR');
  }
  const [ano, mes, dia] = texto.slice(0, 10).split('-');
  return `${dia}/${mes}/${ano}`;
}

function avaliar() {
  avaliacao.post(route('pae.protocolo.dco.avaliar', props.protocolo.id), {
    preserveScroll: true,
    onSuccess: () => { avaliacao.reset('fundamentacao', 'num_sei'); avaliacao.chave_idempotencia = crypto.randomUUID(); },
  });
}

function registrarDocumento() {
  documento.post(route('pae.protocolo.dco.documentos.store', props.protocolo.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { documento.reset('dt_documento', 'dt_apresentacao', 'num_sei', 'observacao', 'arquivo'); if (arquivoInput.value) arquivoInput.value.value = ''; documento.chave_idempotencia = crypto.randomUUID(); },
  });
}
</script>
