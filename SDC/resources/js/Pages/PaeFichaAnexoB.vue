<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6">
    <Head :title="`Ficha cadastral - ${protocolo.num_protocolo}`" />

    <header class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">PAE · Anexo B, item 2</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Ficha cadastral</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Protocolo {{ protocolo.num_protocolo }}</p>
      </div>
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="router.visit(route('pae.protocolos.index'))">
        Voltar aos protocolos
      </button>
    </header>

    <div v-if="rascunho" class="rounded-lg border border-blue-300 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100">
      Os dados existentes do empreendimento aparecem como pré-preenchimento. Salve a ficha para criar a primeira versão deste protocolo.
    </div>
    <div v-if="historica" class="rounded-lg border border-slate-300 bg-slate-100 p-4 text-sm text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
      Você está consultando a versão {{ ficha.versao }}. <a :href="urlAtual" class="font-semibold underline">Abrir versão atual</a>
    </div>
    <div v-if="protocolo.arquivado" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
      Este protocolo está arquivado. A ficha permanece disponível para consulta.
    </div>
    <div v-if="municipios_alterados" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
      A lista ZAS/ZSS mudou depois desta versão. A próxima gravação da ficha incluirá a lista atual.
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_17rem]">
      <form class="min-w-0 space-y-5" @submit.prevent="salvar">
        <section v-for="grupo in grupos" :key="grupo.titulo" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ grupo.titulo }}</h2>
          <p v-if="grupo.ajuda" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ grupo.ajuda }}</p>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label v-for="campo in grupo.campos" :key="campo.chave" class="block text-sm font-medium text-slate-700 dark:text-slate-200" :class="campo.largo ? 'sm:col-span-2' : ''">
              {{ campo.rotulo }}
              <input v-if="campo.chave === 'municipio_sede_id' && !podeEditar" :value="ficha.municipio_sede_nome || 'Não informado'" type="text" disabled class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
              <select v-else-if="campo.chave === 'municipio_sede_id'" v-model="form.municipio_sede_id" :aria-invalid="Boolean(form.errors[campo.chave])" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option :value="null">Não informado</option>
                <option v-for="municipio in municipios_disponiveis" :key="municipio.id" :value="municipio.id">{{ municipio.nome }} / {{ municipio.uf }}</option>
              </select>
              <textarea v-else-if="campo.tipo === 'textarea'" v-model="form[campo.chave]" :disabled="!podeEditar" :maxlength="campo.maxlength" rows="3" :aria-invalid="Boolean(form.errors[campo.chave])" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
              <input v-else v-model="form[campo.chave]" :type="campo.tipo" :step="campo.step" :min="campo.min" :maxlength="campo.maxlength" :disabled="!podeEditar" :aria-invalid="Boolean(form.errors[campo.chave])" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
              <span v-if="form.errors[campo.chave]" class="mt-1 block text-xs text-red-600 dark:text-red-300">{{ form.errors[campo.chave] }}</span>
            </label>
          </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Municípios da ZAS e da ZSS</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">A lista é cadastrada na triagem do protocolo e preservada em cada versão da ficha.</p>
          <div v-if="ficha.municipios_snapshot?.length" class="mt-4 flex flex-wrap gap-2">
            <span v-for="municipio in ficha.municipios_snapshot" :key="municipio.municipio_id" class="rounded-full border border-slate-300 px-3 py-1 text-sm text-slate-700 dark:border-slate-600 dark:text-slate-200">
              {{ municipio.nome }} <span class="text-slate-500 dark:text-slate-400">({{ [municipio.na_zas ? 'ZAS' : null, municipio.na_zss ? 'ZSS' : null].filter(Boolean).join(' / ') }})</span>
            </span>
          </div>
          <p v-else class="mt-4 text-sm text-amber-700 dark:text-amber-300">Nenhum município informado.</p>
          <a :href="route('pae.protocolos.index', { triagem: protocolo.id })" class="mt-4 inline-block text-sm font-semibold text-blue-700 underline dark:text-blue-300">Abrir triagem do protocolo</a>
          <p v-if="form.errors.municipios_snapshot" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ form.errors.municipios_snapshot }}</p>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Rios e estruturas associadas</h2>
          <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div v-for="lista in listas" :key="lista.chave">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200" :for="`estado-${lista.chave}`">{{ lista.rotulo }}</label>
              <select :id="`estado-${lista.chave}`" :value="estadoLista(lista.chave)" :disabled="!podeEditar" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-white" @change="alterarEstadoLista(lista.chave, $event.target.value)">
                <option value="pendente">Não informado</option>
                <option value="nenhum">Nenhum</option>
                <option value="informado">Informar itens</option>
              </select>
              <div v-if="Array.isArray(form[lista.chave]) && form[lista.chave].length" class="mt-3 space-y-2">
                <div v-for="(item, indice) in form[lista.chave]" :key="indice" class="flex gap-2">
                  <input v-model="form[lista.chave][indice]" type="text" maxlength="255" :disabled="!podeEditar" :aria-label="`${lista.rotulo} ${indice + 1}`" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                  <button v-if="podeEditar" type="button" class="rounded-lg border border-slate-300 px-3 text-sm text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="removerItem(lista.chave, indice)">Remover</button>
                </div>
              </div>
              <button v-if="podeEditar && Array.isArray(form[lista.chave])" type="button" class="mt-2 text-sm font-semibold text-blue-700 dark:text-blue-300" @click="adicionarItem(lista.chave)">Adicionar item</button>
              <p v-if="form.errors[lista.chave]" class="mt-1 text-xs text-red-600 dark:text-red-300">{{ form.errors[lista.chave] }}</p>
              <p v-for="(item, chave) in errosDaLista(lista.chave)" :key="chave" class="mt-1 text-xs text-red-600 dark:text-red-300">{{ item }}</p>
            </div>
          </div>
        </section>

        <p v-if="form.errors.base_versao || form.errors.protocolo" class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200">
          {{ form.errors.base_versao || form.errors.protocolo }}
        </p>
        <button v-if="podeEditar" type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
          {{ form.processing ? 'Salvando...' : 'Salvar ficha cadastral' }}
        </button>
      </form>

      <aside class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="font-semibold text-slate-900 dark:text-white">Versão e pendências</h2>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ rascunho ? 'Rascunho não salvo' : `Versão ${ficha.versao}` }}</p>
          <p v-if="ficha.criado_em" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ficha.autor_nome || 'Autor não disponível' }} · {{ formatarData(ficha.criado_em) }}</p>
          <p v-if="!pendencias.length" class="mt-3 text-sm text-green-700 dark:text-green-300">Dados do item 2 informados.</p>
          <div v-else class="mt-3">
            <p class="text-sm font-medium text-amber-700 dark:text-amber-300">{{ pendencias.length }} campo(s) pendente(s) nesta versão</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
              <li v-for="campo in pendencias" :key="campo">{{ rotuloPendencia(campo) }}</li>
            </ul>
          </div>
          <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">As pendências são informativas e atualizam após salvar. Não mudam o status do PAE nesta fase.</p>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="font-semibold text-slate-900 dark:text-white">Histórico</h2>
          <p v-if="!versoes.length" class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nenhuma versão salva.</p>
          <ul v-else class="mt-3 space-y-2">
            <li v-for="item in versoes" :key="item.versao">
              <a :href="urlVersao(item.versao)" class="block rounded-lg border px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="item.versao === ficha.versao ? 'border-blue-500 text-blue-800 dark:text-blue-200' : 'border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-300'">
                <span class="font-semibold">Versão {{ item.versao }}</span>
                <span class="mt-1 block text-xs">{{ item.autor_nome || 'Autor não disponível' }} · {{ formatarData(item.criado_em) }}</span>
              </a>
            </li>
          </ul>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  ficha: { type: Object, required: true },
  versoes: { type: Array, default: () => [] },
  municipios_atuais: { type: Array, default: () => [] },
  municipios_disponiveis: { type: Array, default: () => [] },
  pendencias: { type: Array, default: () => [] },
  municipios_alterados: { type: Boolean, default: false },
  can_edit: { type: Boolean, default: false },
  rascunho: { type: Boolean, default: false },
  historica: { type: Boolean, default: false },
  versao_atual: { type: Number, default: 0 },
});

const grupos = [
  { titulo: 'Identificação da barragem', campos: [
    { chave: 'nome_barragem', rotulo: 'Nome da barragem', tipo: 'text', maxlength: 255 },
    { chave: 'nome_mina', rotulo: 'Nome da mina', tipo: 'text', maxlength: 255 },
    { chave: 'metodo_construtivo', rotulo: 'Método construtivo', tipo: 'text', maxlength: 100 },
    { chave: 'volume_reservatorio', rotulo: 'Volume do reservatório (m³)', tipo: 'number', min: 0, step: '0.01' },
  ] },
  { titulo: 'Localização', ajuda: 'Coordenadas geográficas da estrutura em graus decimais.', campos: [
    { chave: 'municipio_sede_id', rotulo: 'Município sede' },
    { chave: 'latitude', rotulo: 'Latitude', tipo: 'number', step: '0.0000001' },
    { chave: 'longitude', rotulo: 'Longitude', tipo: 'number', step: '0.0000001' },
  ] },
  { titulo: 'Rejeito ou resíduo', campos: [
    { chave: 'tipo_rejeito', rotulo: 'Tipo do rejeito ou resíduo', tipo: 'textarea', maxlength: 5000, largo: true },
    { chave: 'toxicidade', rotulo: 'Toxicidade conforme ABNT NBR 10004', tipo: 'text', maxlength: 255, largo: true },
  ] },
  { titulo: 'ZAS e ZSS', ajuda: 'Na população total da ZAS, considere moradores, trabalhadores e público flutuante.', campos: [
    { chave: 'extensao_zas_km', rotulo: 'Extensão da ZAS (km)', tipo: 'number', min: 0, step: '0.001' },
    { chave: 'populacao_zas', rotulo: 'População total da ZAS', tipo: 'number', min: 0, step: 1 },
    { chave: 'populacao_zas_mobilidade_reduzida', rotulo: 'População da ZAS com dificuldade de locomoção ou necessidades especiais', tipo: 'number', min: 0, step: 1 },
    { chave: 'populacao_zss', rotulo: 'População total da ZSS', tipo: 'number', min: 0, step: 1 },
  ] },
  { titulo: 'Edificações sensíveis na ZAS', campos: [
    { chave: 'edificacoes_hospitalares', rotulo: 'Unidades hospitalares', tipo: 'number', min: 0, step: 1 },
    { chave: 'edificacoes_escolares', rotulo: 'Unidades escolares', tipo: 'number', min: 0, step: 1 },
    { chave: 'edificacoes_prisionais', rotulo: 'Unidades prisionais', tipo: 'number', min: 0, step: 1 },
    { chave: 'edificacoes_outras', rotulo: 'Outras edificações sensíveis', tipo: 'number', min: 0, step: 1 },
  ] },
];

const listas = [
  { chave: 'cursos_agua', rotulo: 'Rios e cursos d’água diretamente afetados' },
  { chave: 'estruturas_associadas', rotulo: 'Estruturas associadas (ECJ, pilhas, diques etc.)' },
];
const rotulos = Object.fromEntries([
  ...grupos.flatMap(grupo => grupo.campos.map(campo => [campo.chave, campo.rotulo])),
  ...listas.map(lista => [lista.chave, lista.rotulo]),
  ['municipios_zas', 'Municípios na ZAS'],
  ['municipios_zss', 'Municípios na ZSS'],
]);
const numericos = grupos.flatMap(grupo => grupo.campos.filter(campo => campo.tipo === 'number').map(campo => campo.chave));

function valoresIniciais(ficha) {
  return {
    base_versao: ficha.versao ?? 0,
    ...Object.fromEntries(grupos.flatMap(grupo => grupo.campos.map(campo => [campo.chave, ficha[campo.chave] ?? '']))),
    cursos_agua: ficha.cursos_agua === null ? null : [...(ficha.cursos_agua ?? [])],
    estruturas_associadas: ficha.estruturas_associadas === null ? null : [...(ficha.estruturas_associadas ?? [])],
  };
}

const form = useForm(valoresIniciais(props.ficha));
const podeEditar = computed(() => props.can_edit && !props.historica && !props.protocolo.arquivado);
const urlAtual = computed(() => route('pae.protocolo.ficha-anexo-b.show', props.protocolo.id));

watch(() => [props.ficha.versao, props.historica], ([versao, historica], [anterior, historicaAnterior] = []) => {
  if (versao !== anterior || historica !== historicaAnterior) {
    Object.assign(form, valoresIniciais(props.ficha));
    form.clearErrors();
  }
});

function estadoLista(chave) {
  if (form[chave] === null) return 'pendente';
  return form[chave].length === 0 ? 'nenhum' : 'informado';
}

function alterarEstadoLista(chave, estado) {
  form[chave] = estado === 'pendente' ? null : estado === 'nenhum' ? [] : (form[chave]?.length ? form[chave] : ['']);
}

function adicionarItem(chave) {
  form[chave].push('');
}

function removerItem(chave, indice) {
  form[chave].splice(indice, 1);
}

function errosDaLista(chave) {
  return Object.fromEntries(Object.entries(form.errors).filter(([campo]) => campo.startsWith(`${chave}.`)));
}

function salvar() {
  form.transform(dados => ({
    ...dados,
    ...Object.fromEntries(numericos.map(chave => [chave, dados[chave] === '' ? null : dados[chave]])),
    municipio_sede_id: dados.municipio_sede_id === '' ? null : dados.municipio_sede_id,
  })).put(route('pae.protocolo.ficha-anexo-b.salvar', props.protocolo.id), { preserveScroll: true });
}

function urlVersao(versao) {
  return route('pae.protocolo.ficha-anexo-b.show', { paeProtocolo: props.protocolo.id, versao });
}

function rotuloPendencia(chave) {
  return rotulos[chave] ?? chave;
}

function formatarData(valor) {
  return valor ? new Date(valor).toLocaleString('pt-BR') : '';
}
</script>
