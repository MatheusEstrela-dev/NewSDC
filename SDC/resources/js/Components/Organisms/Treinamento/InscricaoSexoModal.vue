<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import SexoInscricaoField from '@/Components/Molecules/Treinamento/SexoInscricaoField.vue';

/*
 * Confirmacao da inscricao com a pergunta de sexo. `padrao` e o sexo da
 * ultima inscricao do mesmo inscrito (pre-preenche). Emite `confirmar` com
 * 'F', 'M' ou '' (nao informado); quem abre faz o POST.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  titulo: { type: String, default: '' },
  padrao: { type: String, default: null },
  processando: { type: Boolean, default: false },
});
const emit = defineEmits(['fechar', 'confirmar']);

const sexo = ref('');

watch(() => props.show, (aberto) => {
  if (aberto) sexo.value = props.padrao ?? '';
});
</script>

<template>
  <Modal :show="show" max-width="md" @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="emit('confirmar', sexo)">
      <Heading :level="3" class="text-lg font-semibold">Confirmar inscrição</Heading>
      <Text v-if="titulo" color="muted">{{ titulo }}</Text>

      <SexoInscricaoField v-model="sexo" />

      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="emit('fechar')">Voltar</Button>
        <Button type="submit" variant="primary" :loading="processando">Inscrever-se</Button>
      </div>
    </form>
  </Modal>
</template>
