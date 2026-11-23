<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import SexoInscricaoField from '@/Components/Molecules/Treinamento/SexoInscricaoField.vue';
import DataNascimentoInscricaoField from '@/Components/Molecules/Treinamento/DataNascimentoInscricaoField.vue';

/*
 * Confirmacao da inscricao com as perguntas de sexo e data de nascimento.
 * `padrao` e `dataNascimentoPadrao` vem da ultima inscricao do mesmo inscrito
 * (pre-preenchem). Emite `confirmar` com { sexo, data_nascimento }, vazios =
 * nao informado; quem abre faz o POST.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  titulo: { type: String, default: '' },
  padrao: { type: String, default: null },
  dataNascimentoPadrao: { type: String, default: null },
  processando: { type: Boolean, default: false },
});
const emit = defineEmits(['fechar', 'confirmar']);

const sexo = ref('');
const dataNascimento = ref('');

watch(() => props.show, (aberto) => {
  if (!aberto) return;
  sexo.value = props.padrao ?? '';
  dataNascimento.value = props.dataNascimentoPadrao ?? '';
});
</script>

<template>
  <Modal :show="show" max-width="md" @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="emit('confirmar', { sexo, data_nascimento: dataNascimento })">
      <Heading :level="3" class="text-lg font-semibold">Confirmar inscrição</Heading>
      <Text v-if="titulo" color="muted">{{ titulo }}</Text>

      <SexoInscricaoField v-model="sexo" />
      <DataNascimentoInscricaoField v-model="dataNascimento" />

      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="emit('fechar')">Voltar</Button>
        <Button type="submit" variant="primary" :loading="processando">Inscrever-se</Button>
      </div>
    </form>
  </Modal>
</template>
