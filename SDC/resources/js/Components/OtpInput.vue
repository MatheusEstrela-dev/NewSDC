<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    length: { type: Number, default: 6 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'complete']);

const digits = ref(Array.from({ length: props.length }, (_, i) => props.modelValue[i] || ''));
const refs = ref([]);

const value = computed(() => digits.value.join(''));

watch(value, (v) => {
    emit('update:modelValue', v);
    if (v.length === props.length && !v.includes('')) {
        emit('complete', v);
    }
});

watch(() => props.modelValue, (mv) => {
    const arr = Array.from({ length: props.length }, (_, i) => mv[i] || '');
    digits.value = arr;
});

function setDigit(index, char) {
    // Reassign array completo pra garantir reactivity (mutacao por indice
    // em ref<Array> as vezes nao dispara watchers/computeds dependendo de
    // como o Proxy e acessado em compile time vs render time).
    const next = [...digits.value];
    next[index] = char;
    digits.value = next;
}

function onInput(event, index) {
    const raw = event.target.value.replace(/\D/g, '');
    if (!raw) {
        setDigit(index, '');
        return;
    }
    setDigit(index, raw[0]);
    if (index < props.length - 1) {
        nextTick(() => refs.value[index + 1]?.focus());
    }
}

function onKeyDown(event, index) {
    if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
        nextTick(() => refs.value[index - 1]?.focus());
    }
    if (event.key === 'ArrowLeft' && index > 0) refs.value[index - 1]?.focus();
    if (event.key === 'ArrowRight' && index < props.length - 1) refs.value[index + 1]?.focus();
}

function onPaste(event) {
    const text = (event.clipboardData || window.clipboardData).getData('text');
    const clean = text.replace(/\D/g, '').slice(0, props.length);
    if (!clean) return;
    event.preventDefault();
    const next = Array.from({ length: props.length }, (_, i) => clean[i] || '');
    digits.value = next;
    const focusIdx = Math.min(clean.length, props.length - 1);
    nextTick(() => refs.value[focusIdx]?.focus());
}

defineExpose({
    focus: () => refs.value[0]?.focus(),
    clear: () => {
        digits.value = Array.from({ length: props.length }, () => '');
        refs.value[0]?.focus();
    },
});
</script>

<template>
    <div class="flex gap-2 justify-center">
        <input
            v-for="(_, index) in length"
            :key="index"
            :ref="(el) => (refs[index] = el)"
            :value="digits[index]"
            :disabled="disabled"
            type="text"
            inputmode="numeric"
            maxlength="1"
            class="w-12 h-14 text-center text-2xl font-mono border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100"
            @input="onInput($event, index)"
            @keydown="onKeyDown($event, index)"
            @paste="onPaste"
        />
    </div>
</template>
