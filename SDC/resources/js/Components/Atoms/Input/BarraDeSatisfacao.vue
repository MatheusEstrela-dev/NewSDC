<template>
  <fieldset :aria-describedby="error ? erroId : undefined">
    <legend v-if="legenda" :id="legendaId" class="mb-3 text-sm font-medium text-slate-700 dark:text-slate-200">
      {{ legenda }}<span v-if="obrigatorio" class="ml-0.5 text-red-500" aria-hidden="true">*</span>
    </legend>

    <!-- Radios nativos (sr-only) por baixo de cada segmento: setas, Tab e leitor
         de tela funcionam como em qualquer grupo de radio. -->
    <div
      role="radiogroup"
      :aria-labelledby="legenda ? legendaId : undefined"
      :aria-label="legenda ? undefined : 'Nível de satisfação'"
      class="flex gap-1"
    >
      <label
        v-for="(opcao, indice) in opcoes"
        :key="opcao.value"
        class="group relative flex-1"
        :class="disabled ? 'cursor-not-allowed' : 'cursor-pointer'"
        :title="opcao.label"
      >
        <input
          type="radio"
          class="peer sr-only"
          :name="nome"
          :value="opcao.value"
          :checked="modelValue === opcao.value"
          :disabled="disabled"
          :aria-label="`${opcao.value} de ${opcoes.length}: ${opcao.label}`"
          @change="$emit('update:modelValue', opcao.value)"
        />
        <!-- A opacidade vai so no fundo (camada propria): o numero mantem contraste pleno. -->
        <span
          class="relative flex h-11 items-center justify-center text-sm font-bold transition-all duration-150 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400 peer-focus-visible:ring-offset-2 dark:peer-focus-visible:ring-offset-slate-800"
          :class="[
            preenchido(opcao) ? (CORES_NUMERO[opcao.color] ?? CORES_NUMERO.slate) : 'text-slate-700 dark:text-slate-200',
            indice === 0 && 'rounded-l-full',
            indice === opcoes.length - 1 && 'rounded-r-full',
            modelValue === opcao.value && 'scale-y-110 shadow-md',
          ]"
          aria-hidden="true"
        >
          <span
            class="absolute inset-0 rounded-[inherit] transition-opacity duration-150"
            :class="[
              CORES_SEGMENTO[opcao.color] ?? CORES_SEGMENTO.slate,
              preenchido(opcao) ? 'opacity-100' : 'opacity-30',
              !disabled && !preenchido(opcao) && 'group-hover:opacity-60',
            ]"
          />
          <span class="relative">{{ opcao.value }}</span>
        </span>
      </label>
    </div>

    <div class="mt-1.5 flex justify-between text-xs font-medium" aria-hidden="true">
      <span :class="CORES_TEXTO[opcoes[0]?.color] ?? CORES_TEXTO.slate">{{ opcoes[0]?.label }}</span>
      <span :class="CORES_TEXTO[opcoes[opcoes.length - 1]?.color] ?? CORES_TEXTO.slate">{{ opcoes[opcoes.length - 1]?.label }}</span>
    </div>

    <p class="mt-3 flex min-h-[1.75rem] items-center justify-center" aria-live="polite">
      <span
        v-if="selecionada"
        class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold"
        :class="CORES_SELO[selecionada.color] ?? CORES_SELO.slate"
      >
        {{ selecionada.value }} · {{ selecionada.label }}
      </span>
      <span v-else class="text-sm text-slate-400 dark:text-slate-500">Selecione um nível de 1 a {{ opcoes.length }}</span>
    </p>
    <p v-if="error" :id="erroId" class="mt-1 text-center text-xs text-red-600 dark:text-red-400">{{ error }}</p>
  </fieldset>
</template>

<script setup>
/**
 * Barra de satisfacao clicavel em N segmentos (a avaliacao do curso do
 * Treinamento usa 5: do vermelho "Pessimo" ao verde "Excelente"). As opcoes
 * vem do backend (enum NotaAvaliacaoCurso::toSelectArray): { value, label, color }.
 * Os segmentos ate o nivel escolhido ficam cheios e os demais esmaecidos; o
 * rotulo do nivel escolhido aparece logo abaixo.
 */
import { computed, useId } from 'vue';

const props = defineProps({
  modelValue: { type: Number, default: null },
  /** @type {Array<{ value: number, label: string, color: string }>} */
  opcoes: { type: Array, required: true },
  legenda: { type: String, default: '' },
  nome: { type: String, default: 'nota' },
  /** Asterisco vermelho na legenda (padrao do Label dos formularios). */
  obrigatorio: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const uid = useId();
const legendaId = `barra-satisfacao-legenda-${uid}`;
const erroId = `barra-satisfacao-erro-${uid}`;

const selecionada = computed(() => props.opcoes.find((o) => o.value === props.modelValue) ?? null);

const preenchido = (opcao) => props.modelValue !== null && opcao.value <= props.modelValue;
</script>

<script>
/*
 * Cores por nota (NotaAvaliacaoCurso::getColor, red -> green), exportadas para
 * o selo "Sua avaliacao" usar as mesmas (padrao do ActionButton). Ficam no
 * .vue porque o Tailwind so varre resources/js/**\/*.vue, e por extenso porque
 * string dinamica (`bg-${cor}-500`) some no purge.
 */
export const CORES_SEGMENTO = {
  red: 'bg-red-500',
  orange: 'bg-orange-500',
  yellow: 'bg-yellow-400',
  lime: 'bg-lime-500',
  green: 'bg-green-600',
  slate: 'bg-slate-300 dark:bg-slate-600',
};

// Numero sobre o segmento cheio: escuro nas cores claras (amarelo, lima, laranja), branco nas demais.
export const CORES_NUMERO = {
  red: 'text-white',
  orange: 'text-slate-900',
  yellow: 'text-slate-900',
  lime: 'text-slate-900',
  green: 'text-white',
  slate: 'text-white',
};

export const CORES_TEXTO = {
  red: 'text-red-600 dark:text-red-400',
  orange: 'text-orange-600 dark:text-orange-400',
  yellow: 'text-yellow-600 dark:text-yellow-400',
  lime: 'text-lime-600 dark:text-lime-400',
  green: 'text-green-700 dark:text-green-400',
  slate: 'text-slate-500',
};

export const CORES_SELO = {
  red: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
  orange: 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300',
  yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-300',
  lime: 'bg-lime-100 text-lime-800 dark:bg-lime-500/15 dark:text-lime-300',
  green: 'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-300',
  slate: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
};
</script>
