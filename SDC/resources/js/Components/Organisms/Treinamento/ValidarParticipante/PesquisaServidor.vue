<script setup>
import { computed, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import { extrairTokenCredencial } from '@/Composables/treinamento/extrairTokenCredencial';

/*
 * Pesquisa manual do servidor, por abas: CPF (com mascara) ou numero do
 * certificado. Emite `pesquisar` com o valor ja normalizado.
 */
const emit = defineEmits(['pesquisar']);

const ABAS = [
  { id: 'cpf', rotulo: 'CPF' },
  { id: 'certificado', rotulo: 'Nº do certificado' },
];

const aba = ref('cpf');
const valores = ref({ cpf: '', certificado: '' });
const aviso = ref('');

const mascararCpf = (texto) => {
  const d = texto.replace(/\D/g, '').slice(0, 11);

  return d
    .replace(/^(\d{3})(\d)/, '$1.$2')
    .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/\.(\d{3})(\d)/, '.$1-$2');
};

const valor = computed({
  get: () => valores.value[aba.value],
  set: (texto) => {
    valores.value[aba.value] = aba.value === 'cpf' ? mascararCpf(texto) : texto.toUpperCase();
    aviso.value = '';
  },
});

function trocarAba(id) {
  aba.value = id;
  aviso.value = '';
}

function pesquisar() {
  const bruto = valores.value[aba.value];
  const normalizado = extrairTokenCredencial(bruto);
  const digitos = bruto.replace(/\D/g, '');
  const valido = aba.value === 'cpf'
    ? normalizado !== null && /^\d{11}$/.test(normalizado) && digitos.length === 11
    : normalizado !== null && /^[A-Z]+-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(normalizado);

  if (!valido) {
    aviso.value = aba.value === 'cpf'
      ? 'Informe um CPF com 11 dígitos.'
      : 'Informe o número no formato CEDEC-XXXX-XXXX.';
    return;
  }

  emit('pesquisar', normalizado);
}

// Setas navegam entre as abas (padrao WAI-ARIA de tablist).
function teclaAba(evento, indice) {
  if (!['ArrowRight', 'ArrowLeft'].includes(evento.key)) return;

  const passo = evento.key === 'ArrowRight' ? 1 : -1;
  const proxima = ABAS[(indice + passo + ABAS.length) % ABAS.length];

  trocarAba(proxima.id);
  document.getElementById('aba-pesquisa-' + proxima.id)?.focus();
}
</script>

<template>
  <CardBase class="space-y-4">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pesquisar</h2>

    <div class="grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-900" role="tablist" aria-label="Pesquisar por">
      <button
        v-for="(item, indice) in ABAS"
        :id="'aba-pesquisa-' + item.id"
        :key="item.id"
        type="button"
        role="tab"
        :aria-selected="aba === item.id"
        :aria-controls="'painel-pesquisa-' + item.id"
        :tabindex="aba === item.id ? 0 : -1"
        class="min-h-[44px] rounded-md px-3 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
        :class="aba === item.id
          ? 'bg-white text-blue-700 shadow-sm dark:bg-slate-700 dark:text-white'
          : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
        @click="trocarAba(item.id)"
        @keydown="teclaAba($event, indice)"
      >
        {{ item.rotulo }}
      </button>
    </div>

    <form
      :id="'painel-pesquisa-' + aba"
      role="tabpanel"
      :aria-labelledby="'aba-pesquisa-' + aba"
      class="space-y-3"
      @submit.prevent="pesquisar"
    >
      <label for="campo-pesquisa" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
        {{ aba === 'cpf' ? 'CPF do servidor' : 'Número do certificado' }}
      </label>
      <input
        id="campo-pesquisa"
        v-model="valor"
        type="text"
        :inputmode="aba === 'cpf' ? 'numeric' : 'text'"
        :maxlength="aba === 'cpf' ? 14 : 20"
        :placeholder="aba === 'cpf' ? '000.000.000-00' : 'CEDEC-XXXX-XXXX'"
        autocomplete="off"
        autocapitalize="characters"
        spellcheck="false"
        class="block min-h-[44px] w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
        :class="aba === 'certificado' ? 'uppercase' : ''"
      />
      <p v-if="aviso" class="text-sm text-amber-700 dark:text-amber-300" role="alert">{{ aviso }}</p>
      <Button type="submit" variant="primary" size="lg" full-width :disabled="!valor.trim()">Consultar</Button>
    </form>
  </CardBase>
</template>
