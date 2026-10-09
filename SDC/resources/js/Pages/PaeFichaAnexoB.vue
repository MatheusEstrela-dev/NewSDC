<template>
  <Head :title="`Ficha cadastral - ${protocolo.num_protocolo}`" />

  <PaeTelaLayout
    v-model:aba="aba"
    titulo="Ficha cadastral"
    subtitulo="PAE · Anexo B, item 2"
    :icone="ClipboardDocumentListIcon"
    :protocolo="protocolo"
    :status-label="seloRotulo"
    :status-variant="seloVariante"
    :abas="abas"
  >
    <template #avisos>
      <PaeAviso v-if="rascunho" tom="info">Os dados existentes do empreendimento aparecem como pré-preenchimento. Salve a ficha para criar a primeira versão deste protocolo.</PaeAviso>
      <PaeAviso v-if="historica" tom="neutro">Você está consultando a versão {{ ficha.versao }}. <a :href="urlAtual" class="font-semibold underline">Abrir versão atual</a></PaeAviso>
      <PaeAviso v-if="protocolo.arquivado" tom="aviso">Este protocolo está arquivado. A ficha permanece disponível para consulta.</PaeAviso>
      <PaeAviso v-if="municipios_alterados" tom="aviso">A lista ZAS/ZSS mudou depois desta versão. A próxima gravação da ficha incluirá a lista atual.</PaeAviso>
    </template>

    <template #default="{ aba: ativa }">
      <form v-if="ativa === 'cadastro'" class="space-y-4" @submit.prevent="salvar">
        <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true" :disabled="!podeEditar || form.processing">Salvar</button>
        <CollapsibleSection v-for="grupo in grupos" :key="grupo.titulo" namespace="pae" :section-id="`ficha-${grupo.chave}`" :title="grupo.titulo" :subtitle="grupo.ajuda" :icon="DocumentTextIcon">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <template v-for="campo in grupo.campos" :key="campo.chave">
              <FormField v-if="campo.chave === 'municipio_sede_id' && !podeEditar" :model-value="ficha.municipio_sede_nome || 'Não informado'" :label="campo.rotulo" disabled />
              <FormSelect v-else-if="campo.chave === 'municipio_sede_id'" v-model="form.municipio_sede_id" :label="campo.rotulo" :options="opcoesMunicipios" placeholder="Não informado" :error="form.errors[campo.chave]" />
              <FormTextarea v-else-if="campo.tipo === 'textarea'" v-model="form[campo.chave]" class="sm:col-span-2" :label="campo.rotulo" :rows="3" :maxlength="campo.maxlength" :disabled="!podeEditar" :error="form.errors[campo.chave]" />
              <FormField v-else v-model="form[campo.chave]" :class="campo.largo ? 'sm:col-span-2' : ''" :type="campo.tipo" :step="campo.step" :maxlength="campo.maxlength" :label="campo.rotulo" :disabled="!podeEditar" :error="form.errors[campo.chave]" />
            </template>
          </div>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="ficha-municipios" title="Municípios da ZAS e da ZSS" subtitle="A lista é cadastrada na triagem do protocolo e preservada em cada versão da ficha." :icon="MapPinIcon">
          <div v-if="ficha.municipios_snapshot?.length" class="flex flex-wrap gap-2">
            <span v-for="municipio in ficha.municipios_snapshot" :key="municipio.municipio_id" class="max-w-full break-words rounded-full border border-slate-300 px-3 py-1 text-sm text-slate-700 dark:border-slate-600 dark:text-slate-200">
              {{ municipio.nome }} <span class="text-slate-500 dark:text-slate-400">({{ [municipio.na_zas ? 'ZAS' : null, municipio.na_zss ? 'ZSS' : null].filter(Boolean).join(' / ') }})</span>
            </span>
          </div>
          <p v-else class="text-sm text-amber-700 dark:text-amber-300">Nenhum município informado.</p>
          <a :href="route('pae.protocolos.index', { triagem: protocolo.id })" class="mt-4 inline-block text-sm font-semibold text-blue-700 underline dark:text-blue-300">Abrir triagem do protocolo</a>
          <p v-if="form.errors.municipios_snapshot" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ form.errors.municipios_snapshot }}</p>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="ficha-listas" title="Rios e estruturas associadas" :icon="MapIcon">
          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div v-for="lista in listas" :key="lista.chave" class="min-w-0 space-y-3">
              <FormSelect :model-value="estadoLista(lista.chave)" :label="lista.rotulo" :options="OPCOES_ESTADO_LISTA" placeholder="" :disabled="!podeEditar" @update:model-value="alterarEstadoLista(lista.chave, $event)" />
              <div v-if="Array.isArray(form[lista.chave]) && form[lista.chave].length" class="space-y-2">
                <div v-for="(item, indice) in form[lista.chave]" :key="indice" class="flex items-start gap-2">
                  <FormField v-model="form[lista.chave][indice]" class="min-w-0 flex-1" maxlength="255" :aria-label="`${lista.rotulo} ${indice + 1}`" :disabled="!podeEditar" />
                  <Button v-if="podeEditar" variant="outline" size="sm" @click="removerItem(lista.chave, indice)">Remover</Button>
                </div>
              </div>
              <Button v-if="podeEditar && Array.isArray(form[lista.chave])" variant="outline" size="sm" @click="adicionarItem(lista.chave)">Adicionar item</Button>
              <p v-if="form.errors[lista.chave]" class="text-xs text-red-600 dark:text-red-300">{{ form.errors[lista.chave] }}</p>
              <p v-for="(item, chave) in errosDaLista(lista.chave)" :key="chave" class="text-xs text-red-600 dark:text-red-300">{{ item }}</p>
            </div>
          </div>
        </CollapsibleSection>
      </form>

      <div v-else class="space-y-4">
        <CollapsibleSection namespace="pae" section-id="ficha-versao" title="Versão e pendências" :icon="ClipboardDocumentListIcon">
          <p class="text-sm text-slate-600 dark:text-slate-300">{{ rascunho ? 'Rascunho não salvo' : `Versão ${ficha.versao}` }}</p>
          <p v-if="ficha.criado_em" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ficha.autor_nome || 'Autor não disponível' }} · {{ formatarDataHora(ficha.criado_em) }}</p>
          <p v-if="!pendencias.length" class="mt-3 text-sm text-green-700 dark:text-green-300">Dados do item 2 informados.</p>
          <div v-else class="mt-3">
            <p class="text-sm font-medium text-amber-700 dark:text-amber-300">{{ pendencias.length }} campo(s) pendente(s) nesta versão</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
              <li v-for="campo in pendencias" :key="campo">{{ rotuloPendencia(campo) }}</li>
            </ul>
          </div>
          <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">As pendências são informativas e atualizam após salvar. Não mudam o status do PAE nesta fase.</p>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="ficha-historico" title="Histórico" :subtitle="`${versoes.length} versão(ões)`" :icon="ClockIcon" tom="neutro">
          <p v-if="!versoes.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhuma versão salva.</p>
          <ul v-else class="space-y-2">
            <li v-for="item in versoes" :key="item.versao">
              <a :href="urlVersao(item.versao)" class="block rounded-lg border px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="item.versao === ficha.versao ? 'border-blue-500 text-blue-800 dark:text-blue-200' : 'border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-300'">
                <span class="font-semibold">Versão {{ item.versao }}</span>
                <span class="mt-1 block break-words text-xs">{{ item.autor_nome || 'Autor não disponível' }} · {{ formatarDataHora(item.criado_em) }}</span>
              </a>
            </li>
          </ul>
        </CollapsibleSection>
      </div>
    </template>

    <template #rodape>
      <PaeAviso v-if="camposComErro" tom="erro">{{ camposComErro }} campo(s) com erro na aba Cadastro</PaeAviso>
      <PaeAviso v-if="form.errors.base_versao || form.errors.protocolo" tom="erro">{{ form.errors.base_versao || form.errors.protocolo }}</PaeAviso>
      <div v-if="podeEditar" class="flex flex-col sm:flex-row sm:justify-end">
        <Button class="w-full sm:w-auto" :loading="form.processing" @click="salvar">{{ form.processing ? 'Salvando...' : 'Salvar ficha cadastral' }}</Button>
      </div>
    </template>
  </PaeTelaLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import PaeTelaLayout from '@/Templates/Pae/PaeTelaLayout.vue';
import { ClipboardDocumentListIcon, ClockIcon, DocumentTextIcon, MapIcon, MapPinIcon } from '@heroicons/vue/24/outline';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
  { chave: 'identificacao', titulo: 'Identificação da barragem', campos: [
    { chave: 'nome_barragem', rotulo: 'Nome da barragem', tipo: 'text', maxlength: 255 },
    { chave: 'nome_mina', rotulo: 'Nome da mina', tipo: 'text', maxlength: 255 },
    { chave: 'metodo_construtivo', rotulo: 'Método construtivo', tipo: 'text', maxlength: 100 },
    { chave: 'volume_reservatorio', rotulo: 'Volume do reservatório (m³)', tipo: 'number', step: '0.01' },
  ] },
  { chave: 'localizacao', titulo: 'Localização', ajuda: 'Coordenadas geográficas da estrutura em graus decimais.', campos: [
    { chave: 'municipio_sede_id', rotulo: 'Município sede' },
    { chave: 'latitude', rotulo: 'Latitude', tipo: 'number', step: '0.0000001' },
    { chave: 'longitude', rotulo: 'Longitude', tipo: 'number', step: '0.0000001' },
  ] },
  { chave: 'rejeito', titulo: 'Rejeito ou resíduo', campos: [
    { chave: 'tipo_rejeito', rotulo: 'Tipo do rejeito ou resíduo', tipo: 'textarea', maxlength: 5000, largo: true },
    { chave: 'toxicidade', rotulo: 'Toxicidade conforme ABNT NBR 10004', tipo: 'text', maxlength: 255, largo: true },
  ] },
  { chave: 'zas', titulo: 'ZAS e ZSS', ajuda: 'Na população total da ZAS, considere moradores, trabalhadores e público flutuante.', campos: [
    { chave: 'extensao_zas_km', rotulo: 'Extensão da ZAS (km)', tipo: 'number', step: '0.001' },
    { chave: 'populacao_zas', rotulo: 'População total da ZAS', tipo: 'number', step: 1 },
    { chave: 'populacao_zas_mobilidade_reduzida', rotulo: 'População da ZAS com dificuldade de locomoção ou necessidades especiais', tipo: 'number', step: 1 },
    { chave: 'populacao_zss', rotulo: 'População total da ZSS', tipo: 'number', step: 1 },
  ] },
  { chave: 'edificacoes', titulo: 'Edificações sensíveis na ZAS', campos: [
    { chave: 'edificacoes_hospitalares', rotulo: 'Unidades hospitalares', tipo: 'number', step: 1 },
    { chave: 'edificacoes_escolares', rotulo: 'Unidades escolares', tipo: 'number', step: 1 },
    { chave: 'edificacoes_prisionais', rotulo: 'Unidades prisionais', tipo: 'number', step: 1 },
    { chave: 'edificacoes_outras', rotulo: 'Outras edificações sensíveis', tipo: 'number', step: 1 },
  ] },
];

const listas = [
  { chave: 'cursos_agua', rotulo: 'Rios e cursos d’água diretamente afetados' },
  { chave: 'estruturas_associadas', rotulo: 'Estruturas associadas (ECJ, pilhas, diques etc.)' },
];
const OPCOES_ESTADO_LISTA = [{ value: 'pendente', label: 'Não informado' }, { value: 'nenhum', label: 'Nenhum' }, { value: 'informado', label: 'Informar itens' }];
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
const opcoesMunicipios = computed(() => props.municipios_disponiveis.map(m => ({ value: m.id, label: `${m.nome} / ${m.uf}` })));

const aba = ref('cadastro');
const abas = computed(() => [
  { id: 'cadastro', label: 'Cadastro', icon: DocumentTextIcon },
  { id: 'versoes', label: 'Versões e pendências', icon: ClockIcon, badge: props.pendencias.length || null },
]);
const seloRotulo = computed(() => (props.rascunho ? 'Rascunho' : `Versão ${props.ficha.versao}${props.historica ? ' (histórica)' : ''}`));
const seloVariante = computed(() => (props.rascunho ? 'default' : (props.pendencias.length ? 'warning' : 'success')));

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

const camposComErro = computed(() => Object.keys(form.errors).filter(chave => chave !== 'base_versao' && chave !== 'protocolo').length);

function salvar() {
  form.transform(dados => ({
    ...dados,
    ...Object.fromEntries(numericos.map(chave => [chave, dados[chave] === '' ? null : dados[chave]])),
    municipio_sede_id: dados.municipio_sede_id === '' ? null : dados.municipio_sede_id,
  })).put(route('pae.protocolo.ficha-anexo-b.salvar', props.protocolo.id), { preserveScroll: true, onError: () => { aba.value = 'cadastro'; } });
}

function urlVersao(versao) {
  return route('pae.protocolo.ficha-anexo-b.show', { paeProtocolo: props.protocolo.id, versao });
}

function rotuloPendencia(chave) {
  return rotulos[chave] ?? chave;
}

function formatarDataHora(valor) {
  return valor ? new Date(valor).toLocaleString('pt-BR') : '';
}
</script>
