<template>
  <div class="flex flex-wrap items-center gap-2">
    <button type="button" :disabled="disabled || modelValue <= min" :class="botao" :aria-label="`Diminuir ${passo} ${rotulo}`" data-stepper-menos @click="definir(modelValue - passo)">-</button>
    <input
      :id="id"
      type="number"
      inputmode="numeric"
      :min="min"
      :max="max"
      step="1"
      :value="modelValue"
      :disabled="disabled"
      class="h-8 w-20 rounded-lg border border-slate-300 bg-white px-2 text-center text-sm tabular-nums text-slate-900 focus:border-blue-500 focus:ring-blue-500 disabled:cursor-not-allowed dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
      data-stepper-valor
      @input="digitar($event.target)"
      @blur="$event.target.value = modelValue"
    />
    <span class="text-sm text-slate-600 dark:text-slate-300">{{ rotulo }}</span>
    <button type="button" :disabled="disabled || modelValue >= max" :class="botao" :aria-label="`Aumentar ${passo} ${rotulo}`" data-stepper-mais @click="definir(modelValue + passo)">+</button>
  </div>
</template>

<script setup>
const props = defineProps({
  id: { type: String, required: true },
  modelValue: { type: Number, required: true },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  passo: { type: Number, default: 1 },
  rotulo: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const botao = 'h-8 w-8 rounded-lg border border-slate-300 bg-white text-sm font-bold text-slate-700 transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700';
const limitar = (valor) => Math.min(props.max, Math.max(props.min, Math.trunc(Number(valor) || 0)));

function definir(valor) {
  emit('update:modelValue', limitar(valor));
}

// Campo vazio aguarda o blur; fora da faixa volta ao limite na hora.
function digitar(campo) {
  if (campo.value === '') return;
  const valor = limitar(campo.value);
  emit('update:modelValue', valor);
  if (String(valor) !== campo.value) campo.value = valor;
}
</script>
