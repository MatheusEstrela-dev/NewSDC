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

// assunto ja salvo no servidor (props.assuntoId) x assunto escolhido no select
// (assuntoSelecionadoId), que pode ainda nao ter sido salvo.
const assuntoSelecionadoId = ref(props.assuntoId);
const valores = reactive({ ...props.valoresIniciais });

watch(() => props.valoresIniciais, (v) => Object.assign(valores, v));
watch(() => props.assuntoId, (v) => { assuntoSelecionadoId.value = v; });

const erros = computed(() => usePage().props.errors ?? {});
const erroGeral = computed(() => erros.value.assunto_id);
const { salvar } = useDemandaAutosave(props.demandaId);

const assuntoAlterado = computed(() => assuntoSelecionadoId.value !== props.assuntoId);

function camposDoAssunto(id) {
  if (id === props.assuntoId) {
    return props.campos;
  }

  return props.assuntos.find((a) => a.value === id)?.campos ?? [];
}

const camposExibidos = computed(() => camposDoAssunto(assuntoSelecionadoId.value));

function valoresVaziosPara(campos) {
  return Object.fromEntries(campos.map((c) => [c.label, c.tipo === 'checkbox' ? false : '']));
}

function selecionarAssunto(v) {
  const novoId = v || null;
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
  assuntoSelecionadoId.value = props.assuntoId;
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
