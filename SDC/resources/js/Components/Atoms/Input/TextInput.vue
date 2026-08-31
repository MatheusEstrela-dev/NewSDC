<template>
  <input
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    :maxlength="maxlength"
    :class="inputClasses"
    @input="aoDigitar"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
  />
</template>

<script setup>
import { computed } from 'vue';
import { aplicarMascara } from '@/utils/inputMasks';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: Boolean,
    default: false,
  },
  maxlength: {
    type: [String, Number],
    default: null,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  /** Nome de mascara em utils/inputMasks. Sem o prop o input e texto livre. */
  mask: {
    type: String,
    default: undefined,
  },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

/**
 * A mascara e aplicada AQUI, e nao no componente pai, porque so aqui existe o
 * elemento do DOM -- e sem tocar nele a mascara simplesmente nao funciona.
 *
 * O motivo: quando o caractere digitado e rejeitado, o valor mascarado fica
 * IGUAL ao modelValue que ja estava la. O Vue compara a prop com o vnode
 * anterior, nao ve diferenca, e nao repinta o input. O caractere invalido
 * continua visivel na tela mesmo estando fora do modelo -- a tela mostra
 * "dfsddfsf" e o formulario envia string vazia.
 *
 * Por isso o valor e escrito de volta no elemento quando diverge. O cursor vai
 * para o fim nesse caso, que e o comportamento certo ao digitar no fim do campo
 * (o caso normal) e aceitavel ao editar no meio.
 */
function aoDigitar(evento) {
  const elemento = evento.target;
  const valor = props.mask ? aplicarMascara(props.mask, elemento.value) : elemento.value;

  if (elemento.value !== valor) {
    elemento.value = valor;
  }

  emit('update:modelValue', valor);
}

const sizeClasses = {
  sm: 'atom-input-sm',
  md: 'atom-input-md',
  lg: 'atom-input-lg',
};

const isFilled = computed(() => {
  if (props.readonly || props.disabled) return false;
  const value = props.modelValue;
  return value !== null && value !== undefined && value !== '';
});

const stateClass = computed(() => {
  if (props.readonly) return 'atom-input-readonly';
  if (props.error) return 'atom-input-error';
  if (isFilled.value) return 'atom-input-filled';
  return 'atom-input-normal';
});

const inputClasses = computed(() => {
  return [
    'atom-input',
    stateClass.value,
    sizeClasses[props.size],
    props.disabled ? 'atom-input-disabled' : '',
  ].filter(Boolean).join(' ');
});
</script>

