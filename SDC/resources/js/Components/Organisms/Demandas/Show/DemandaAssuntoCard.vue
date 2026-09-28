<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-3 text-lg font-bold text-slate-900 dark:text-slate-100">Assunto</h2>
    <FilterField :model-value="assuntoSelecionadoId ?? ''" label="" type="select" :options="assuntos" :disabled="!podeEditar"
      @update:model-value="selecionarAssunto" />
    <p v-if="erroGeral" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ erroGeral }}</p>

    <div v-if="camposExibidos.length" class="mt-4 grid gap-3 sm:grid-cols-2">
      <CampoDinamico v-for="c in camposExibidos" :key="c.label" :campo="c" :model-value="valores[c.label]" :disabled="!podeEditar"
        :erro="erros[`campos_customizados.${c.label}`]"
        @update:model-value="(v) => (valores[c.label] = v)" @blur="aoDesfocarCampo" />
    </div>

    <div v-if="assuntoAlterado" class="mt-3 flex gap-2">
      <button type="button"
        class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-500"
        @click="salvarNovoAssunto">
        Salvar assunto
      </button>
      <button type="button"
        class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 dark:border-slate-600 dark:text-slate-200"
        @click="cancelar">
        Cancelar
      </button>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import CampoDinamico from '@/Components/Molecules/Demandas/CampoDinamico.vue';
import { useDemandaAutosave } from '@/Composables/demandas';

const props = defineProps({
  demandaId: { type: Number, required: true },
  assuntoId: { type: Number, default: null },
  assuntos: { type: Array, default: () => [] },
  campos: { type: Array, default: () => [] },
  valoresIniciais: { type: Object, default: () => ({}) },
  podeEditar: { type: Boolean, default: false },
});

// o <select> nativo sempre emite $event.target.value como string; assuntoId e
// assuntos[].value chegam do backend como numero. Sem normalizar, toda comparacao
// de id falha e a troca de assunto nunca encontra os campos do assunto novo.
function normalizarId(v) {
  return v === '' || v === null || v === undefined ? null : Number(v);
}

// assunto ja salvo no servidor (props.assuntoId) x assunto escolhido no select
// (assuntoSelecionadoId), que pode ainda nao ter sido salvo.
const assuntoSelecionadoId = ref(normalizarId(props.assuntoId));
const valores = reactive({ ...props.valoresIniciais });

watch(() => props.valoresIniciais, (v) => Object.assign(valores, v));
watch(() => props.assuntoId, (v) => { assuntoSelecionadoId.value = normalizarId(v); });

const erros = computed(() => usePage().props.errors ?? {});
const erroGeral = computed(() => {
  if (erros.value.assunto_id) {
    return erros.value.assunto_id;
  }

  const temErroDeCampo = Object.keys(erros.value).some((k) => k.startsWith('campos_customizados.'));

  return temErroDeCampo ? 'Preencha os campos obrigatórios do assunto selecionado.' : null;
});
const { salvar } = useDemandaAutosave(props.demandaId);

const assuntoAlterado = computed(() => normalizarId(assuntoSelecionadoId.value) !== normalizarId(props.assuntoId));

function camposDoAssunto(id) {
  const idNormalizado = normalizarId(id);

  if (idNormalizado === normalizarId(props.assuntoId)) {
    return props.campos;
  }

  return props.assuntos.find((a) => normalizarId(a.value) === idNormalizado)?.campos ?? [];
}

const camposExibidos = computed(() => camposDoAssunto(assuntoSelecionadoId.value));

function valoresVaziosPara(campos) {
  return Object.fromEntries(campos.map((c) => [c.label, c.tipo === 'checkbox' ? false : '']));
}

function selecionarAssunto(v) {
  const novoId = normalizarId(v);

  // voltou, pelo proprio select, para o assunto que ja esta salvo (ex.: A -> B
  // -> A de novo): restaura os valores reais em vez de tratar como assunto
  // novo, senao valoresVaziosPara() apaga os dados salvos na tela e o proximo
  // blur autosalva campos em branco por cima do que ja estava no servidor.
  if (novoId === normalizarId(props.assuntoId)) {
    cancelar();
    return;
  }

  assuntoSelecionadoId.value = novoId;

  const camposNovoAssunto = camposDoAssunto(novoId);
  const temCampoTexto = camposNovoAssunto.some((c) => c.tipo !== 'checkbox');

  if (!temCampoTexto) {
    salvar({ assunto_id: novoId, campos_customizados: valoresVaziosPara(camposNovoAssunto) });
    return;
  }

  // assunto novo tem campo obrigatorio: nao salva ainda, so troca os campos
  // exibidos (vazios) e espera o usuario preencher e clicar em "Salvar assunto".
  Object.keys(valores).forEach((k) => delete valores[k]);
  Object.assign(valores, valoresVaziosPara(camposNovoAssunto));
}

function salvarNovoAssunto() {
  const camposNovoAssunto = camposDoAssunto(assuntoSelecionadoId.value);
  const dados = Object.fromEntries(
    camposNovoAssunto.map((c) => [c.label, valores[c.label] ?? (c.tipo === 'checkbox' ? false : '')]),
  );
  salvar({ assunto_id: assuntoSelecionadoId.value, campos_customizados: dados });
}

function cancelar() {
  assuntoSelecionadoId.value = normalizarId(props.assuntoId);
  Object.keys(valores).forEach((k) => delete valores[k]);
  Object.assign(valores, props.valoresIniciais);
}

function aoDesfocarCampo() {
  if (assuntoAlterado.value) {
    return;
  }

  const somenteDoAssunto = Object.fromEntries(
    props.campos.map((c) => [c.label, valores[c.label] ?? (c.tipo === 'checkbox' ? false : '')]),
  );
  salvar({ campos_customizados: somenteDoAssunto });
}
</script>
