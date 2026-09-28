<template>
  <div>
    <Button variant="primary" size="md" :icon="BoltIcon" icon-position="left" :disabled="enviando" @click="executar">
      {{ enviando ? 'Solicitando…' : 'Automação' }}
    </Button>
    <p v-if="erro" class="mt-1 text-xs text-red-500">{{ erro }}</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';

const props = defineProps({ demandaId: { type: Number, required: true } });
const enviando = ref(false);
const erro = computed(() => usePage().props.errors?.automacao);

function executar() {
  enviando.value = true;
  router.post(route('demandas.automacao', props.demandaId), {}, { preserveScroll: true, onFinish: () => { enviando.value = false; } });
}
</script>
