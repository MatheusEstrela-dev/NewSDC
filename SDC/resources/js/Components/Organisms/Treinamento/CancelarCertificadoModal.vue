<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

/*
 * Confirmacao de cancelamento de certificado. O motivo e obrigatorio (o back
 * valida e registra). Emite `confirmar` com o motivo; quem abre faz o POST.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  certificado: { type: Object, default: null },
  processando: { type: Boolean, default: false },
  erro: { type: String, default: null },
});
const emit = defineEmits(['fechar', 'confirmar']);

const motivo = ref('');

watch(() => props.show, (aberto) => {
  if (aberto) motivo.value = '';
});
</script>

<template>
  <Modal :show="show" max-width="md" @close="emit('fechar')">
    <form v-if="certificado" class="space-y-4 p-6" @submit.prevent="emit('confirmar', motivo.trim())">
      <Heading :level="3" class="text-lg font-semibold">Cancelar certificado</Heading>
      <Text color="muted">
        O certificado de {{ certificado.inscrito_nome || 'este participante' }} deixará de valer na página
        pública de validação. O cancelamento não pode ser desfeito por esta tela e o certificado não poderá ser reemitido.
      </Text>

      <p v-if="erro" role="alert" class="text-sm text-red-600">{{ erro }}</p>

      <div>
        <label for="cancelar-certificado-motivo" class="mb-1 block text-sm font-medium">Motivo do cancelamento</label>
        <textarea
          id="cancelar-certificado-motivo"
          v-model="motivo"
          rows="3"
          maxlength="1000"
          required
          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
        />
      </div>

      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="emit('fechar')">Voltar</Button>
        <Button type="submit" variant="danger" :loading="processando" :disabled="!motivo.trim()">Cancelar certificado</Button>
      </div>
    </form>
  </Modal>
</template>
