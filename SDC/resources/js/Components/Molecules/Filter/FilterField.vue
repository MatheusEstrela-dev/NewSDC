<template>
  <div class="filter-field">
    <Label v-if="label" :for-id="fieldId" :size="'sm'" color="muted">
      {{ label }}
    </Label>
    <component
      :is="fieldComponent"
      :id="fieldId"
      :model-value="modelValue"
      v-bind="fieldProps"
      @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Label from '../../Atoms/Typography/Label.vue';
import TextInput from '../../Atoms/Input/TextInput.vue';
import SelectInput from '../../Atoms/Input/SelectInput.vue';
import DateInput from '../../Atoms/Input/DateInput.vue';
import SearchInput from '../../Atoms/Input/SearchInput.vue';

const props = defineProps({
  modelValue: {
    type: [String, Number, Object],
    default: '',
  },
  label: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: 'text',
    validator: (value) => ['text', 'select', 'date', 'search'].includes(value),
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['update:modelValue']);

const fieldId = computed(() => {
  return `filter-${props.label.toLowerCase().replace(/\s+/g, '-')}`;
});

const fieldComponent = computed(() => {
  const components = {
    text: TextInput,
    select: SelectInput,
    date: DateInput,
    search: SearchInput,
  };
  return components[props.type] || TextInput;
});

const fieldProps = computed(() => {
  const base = {
    placeholder: props.placeholder,
    disabled: props.disabled,
  };
  
  if (props.type === 'select') {
    return { ...base, options: props.options };
  }
  
  return base;
});
</script>

<style scoped>
.filter-field {
  @apply w-full;
}
</style>

