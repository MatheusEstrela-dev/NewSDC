<template>
  <div ref="raiz" class="form-field" @focusout="aoSairDoCampo">
    <Label v-if="label" :for-id="inputId" :required="required" :size="labelSize">
      {{ label }}
    </Label>
    <div class="relative">
      <input
        :id="inputId"
        ref="campo"
        :value="termo"
        type="text"
        role="combobox"
        autocomplete="off"
        aria-autocomplete="list"
        :aria-expanded="aberto ? 'true' : 'false'"
        :aria-controls="listaId"
        :aria-activedescendant="ativo >= 0 ? opcaoId(ativo) : undefined"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="error || hint ? ajudaId : undefined"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="classesCampo"
        @input="aoDigitar"
        @focus="aoFocar"
        @click="abrir"
        @keydown="aoTeclar"
      />
      <ChevronUpDownIcon class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" aria-hidden="true" />
    </div>

    <!-- No fluxo da pagina, e nao flutuando: dentro de modal com rolagem propria
         a lista absoluta ficava cortada pela borda do painel. -->
    <div v-show="aberto" class="mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
      <!-- tabindex -1: arrastar a barra de rolagem foca a lista, e nao fora do campo. -->
      <ul :id="listaId" ref="lista" role="listbox" tabindex="-1" :aria-label="label || 'Usuários'" class="max-h-60 overflow-y-auto py-1 outline-none">
        <li
          v-for="(opcao, i) in opcoes"
          :id="opcaoId(i)"
          :key="opcao.livre ? 'livre' : opcao.id"
          role="option"
          :aria-selected="estaSelecionada(opcao) ? 'true' : 'false'"
          :class="classesOpcao(opcao, i)"
          @mousedown.prevent="escolher(opcao)"
          @mousemove="ativo = i"
        >
          <span class="min-w-0 truncate" :title="opcao.livre ? undefined : opcao.name">{{ opcao.name }}</span>
          <CheckIcon v-if="estaSelecionada(opcao)" class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
        </li>
      </ul>
      <p v-if="aviso" role="status" aria-live="polite" class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
        {{ aviso }}
      </p>
    </div>

    <p v-if="error" :id="ajudaId" class="mt-1 text-xs text-red-600 dark:text-red-400">
      {{ error }}
    </p>
    <p v-else-if="hint" :id="ajudaId" class="mt-1 text-xs text-slate-500">
      {{ hint }}
    </p>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/24/outline';
import Label from '../../Atoms/Typography/Label.vue';
import { useBuscaSobDemanda } from '@/Composables/ui/useBuscaSobDemanda';

/**
 * Campo de escolha de usuario com busca sob demanda (combobox ARIA). Substitui
 * o select com a lista inteira de usuarios. O endpoint recebe `?q=` e devolve
 * [{ id, name }]; o v-model guarda so o id (null quando livre).
 */
const props = defineProps({
  modelValue: { type: [String, Number], default: null },
  // Endpoint de busca. Cada tela passa o seu, com a permissao do proprio modulo.
  url: { type: String, required: true },
  // Usuario ja gravado ({ id, name }): mostra o nome sem precisar buscar.
  selecionado: { type: Object, default: null },
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'Digite o nome para buscar' },
  // Texto da opcao que limpa o campo; vazio esconde a opcao.
  textoVazio: { type: String, default: 'Livre' },
  minimo: { type: Number, default: 2 },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  labelSize: { type: String, default: 'md' },
  id: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const { resultados, carregando, falhou, buscar, limpar } = useBuscaSobDemanda(() => props.url, { minimo: props.minimo });

const raiz = ref(null);
const campo = ref(null);
const lista = ref(null);
const aberto = ref(false);
const digitou = ref(false);
const ativo = ref(-1);
// Ultimo usuario escolhido aqui, para mostrar o nome antes do pai repassar o id.
const escolhido = ref(null);

const vazio = (valor) => valor === null || valor === undefined || valor === '';

const nomeSelecionado = computed(() => {
  if (vazio(props.modelValue)) return '';
  const id = String(props.modelValue);
  const conhecido = [escolhido.value, props.selecionado].find((u) => u && String(u.id) === id);
  return conhecido?.name ?? '';
});

const termo = ref(nomeSelecionado.value);
watch(nomeSelecionado, (nome) => {
  if (!aberto.value) termo.value = nome;
});

const opcoes = computed(() => {
  const encontrados = digitou.value ? resultados.value : [];
  return props.textoVazio ? [{ id: null, name: props.textoVazio, livre: true }, ...encontrados] : encontrados;
});

const aviso = computed(() => {
  if (falhou.value) return 'Não foi possível buscar usuários. Tente novamente.';
  if (carregando.value) return 'Buscando...';
  if (!digitou.value || termo.value.trim().length < props.minimo) return `Digite ao menos ${props.minimo} letras para buscar.`;
  if (!resultados.value.length) return 'Nenhum usuário encontrado.';
  return '';
});

const inputId = computed(() => props.id || (props.label ? `busca-${props.label.toLowerCase().replace(/\s+/g, '-')}` : 'busca-usuario'));
const listaId = computed(() => `${inputId.value}-lista`);
const ajudaId = computed(() => `${inputId.value}-ajuda`);
const opcaoId = (i) => `${inputId.value}-opcao-${i}`;

// Mesmas classes do TextInput, com espaco a direita para o icone.
const classesCampo = computed(() => {
  let estado = 'atom-input-normal';
  if (props.error) estado = 'atom-input-error';
  else if (!vazio(props.modelValue)) estado = 'atom-input-filled';
  return ['atom-input', 'atom-input-md', '!pr-10', estado, props.disabled && 'atom-input-disabled'].filter(Boolean);
});

function estaSelecionada(opcao) {
  return opcao.livre ? vazio(props.modelValue) : String(opcao.id) === String(props.modelValue);
}

function classesOpcao(opcao, i) {
  return [
    'flex cursor-pointer items-center justify-between gap-2 px-4 py-2.5 text-sm',
    opcao.livre && 'italic',
    i === ativo.value && 'bg-emerald-50 text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200',
    i !== ativo.value && (opcao.livre ? 'text-slate-500 dark:text-slate-400' : 'text-slate-700 dark:text-slate-200'),
  ];
}

function abrir() {
  if (props.disabled || aberto.value) return;
  aberto.value = true;
  ativo.value = -1;
}

// Seleciona o nome atual: digitar ja substitui, sem apagar letra por letra.
function aoFocar() {
  abrir();
  campo.value?.select();
}

function fechar() {
  aberto.value = false;
  digitou.value = false;
  ativo.value = -1;
  limpar();
  // Sem escolha nova, o campo volta a mostrar quem ja estava selecionado.
  termo.value = nomeSelecionado.value;
}

function aoDigitar(evento) {
  termo.value = evento.target.value;
  digitou.value = true;
  aberto.value = true;
  ativo.value = -1;
  buscar(termo.value);
}

function escolher(opcao) {
  escolhido.value = opcao.livre ? null : { id: opcao.id, name: opcao.name };
  emit('update:modelValue', opcao.livre ? null : opcao.id);
  fechar();
  termo.value = opcao.livre ? '' : opcao.name;
}

function mover(passo) {
  const total = opcoes.value.length;
  if (!total) return;
  ativo.value = (ativo.value + passo + total) % total;
}

function aoTeclar(evento) {
  switch (evento.key) {
    case 'ArrowDown':
      evento.preventDefault();
      if (!aberto.value) abrir();
      mover(1);
      break;
    case 'ArrowUp':
      evento.preventDefault();
      if (!aberto.value) abrir();
      mover(-1);
      break;
    case 'Enter':
      // Com a lista aberta, Enter escolhe a opcao e nao envia o formulario.
      if (!aberto.value) return;
      evento.preventDefault();
      if (ativo.value >= 0) escolher(opcoes.value[ativo.value]);
      break;
    case 'Escape':
      // Fecha so a lista: sem o stopPropagation o Escape fecharia o modal.
      if (!aberto.value) return;
      evento.preventDefault();
      evento.stopPropagation();
      fechar();
      break;
    case 'Tab':
      if (aberto.value) fechar();
      break;
    default:
  }
}

function aoSairDoCampo(evento) {
  if (aberto.value && !raiz.value?.contains(evento.relatedTarget)) fechar();
}

// Mantem a opcao ativa visivel ao navegar pelo teclado numa lista longa.
watch(ativo, async (i) => {
  if (i < 0) return;
  await nextTick();
  lista.value?.children[i]?.scrollIntoView({ block: 'nearest' });
});
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>
