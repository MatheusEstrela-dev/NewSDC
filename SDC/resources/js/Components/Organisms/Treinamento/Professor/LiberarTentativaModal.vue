<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { mensagensDeErro, useProvaApi } from '@/Composables/treinamento/useProvaApi';

/**
 * Libera tentativas extras para um aluno travado em uma prova (1 a 5).
 * Emite `liberado` quando o backend confirma, para a tela recarregar a linha.
 */
const MAX_QUANTIDADE = 5;

const props = defineProps({
  show: { type: Boolean, default: false },
  treinamentoId: { type: Number, required: true },
  aluno: { type: Object, default: null },
  prova: { type: Object, default: null },
});
const emit = defineEmits(['fechar', 'liberado']);

const api = useProvaApi(props.treinamentoId);
const quantidade = ref(1);
const erros = ref([]);
const processando = ref(false);

watch(() => props.show, (aberto) => {
  if (aberto) {
    quantidade.value = 1;
    erros.value = [];
  }
});

async function confirmar() {
  processando.value = true;
  erros.value = [];
  try {
    await api.liberarTentativa(props.prova.prova_id, {
      inscricao_id: props.aluno.inscricao_id,
      quantidade: Number(quantidade.value),
    });
    emit('liberado');
    emit('fechar');
  } catch (e) {
    erros.value = mensagensDeErro(e);
  } finally {
    processando.value = false;
  }
}
</script>

<template>
  <Modal :show="show" max-width="md" @close="emit('fechar')">
    <form v-if="aluno && prova" class="space-y-4 p-6" @submit.prevent="confirmar">
      <Heading :level="3" class="text-lg font-semibold">Liberar tentativa</Heading>
      <Text color="muted">
        {{ aluno.nome }} esgotou as tentativas da prova "{{ prova.titulo }}"
        ({{ prova.tentativas_enviadas }} de {{ prova.tentativas_permitidas }}).
        Confirme quantas tentativas extras serao liberadas. A acao fica registrada.
      </Text>

      <ul v-if="erros.length" role="alert" class="list-disc space-y-1 pl-5 text-sm text-red-600">
        <li v-for="(erro, indice) in erros" :key="indice">{{ erro }}</li>
      </ul>

      <div>
        <label for="liberar-quantidade" class="mb-1 block text-sm font-medium">Quantidade (1 a {{ MAX_QUANTIDADE }})</label>
        <input
          id="liberar-quantidade"
          v-model="quantidade"
          type="number"
          min="1"
          :max="MAX_QUANTIDADE"
          class="w-24 rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
        >
      </div>

      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="emit('fechar')">Cancelar</Button>
        <Button type="submit" :loading="processando">Liberar</Button>
      </div>
    </form>
  </Modal>
</template>
