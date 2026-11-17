<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { applyCpfMask } from '@/utils/cpfMask';
import { useToast } from '@/Composables/useToast';

/**
 * Libera um CPF bloqueado pela regra das 3 inscricoes no curso da turma. A
 * contagem recomeca a partir da liberacao e o motivo fica gravado.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  cursoId: { type: Number, required: true },
});
const emit = defineEmits(['fechar']);

const { show: toast } = useToast();
const cpfMascarado = ref('');

const form = useForm({
  cpf: '',
  curso_id: props.cursoId,
  motivo: '',
});

watch(() => props.show, (aberto) => {
  if (aberto) {
    form.reset();
    form.clearErrors();
    form.curso_id = props.cursoId;
    cpfMascarado.value = '';
  }
});

function digitarCpf(evento) {
  const digitos = evento.target.value.replace(/\D/g, '').slice(0, 11);
  form.cpf = digitos;
  cpfMascarado.value = applyCpfMask(digitos);
}

function confirmar() {
  form.post(route('treinamentos.inscricoes.liberar'), {
    preserveScroll: true,
    onSuccess: () => {
      toast('CPF liberado para novas inscrições neste curso.', 'success');
      emit('fechar');
    },
  });
}
</script>

<template>
  <Modal :show="show" max-width="md" @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="confirmar">
      <Heading :level="3" class="text-lg font-semibold">Liberar CPF bloqueado</Heading>
      <Text color="muted">
        O limite é de 3 inscrições por curso. Ao liberar, a contagem do CPF neste curso recomeça
        a partir de agora. A ação fica registrada com o motivo.
      </Text>

      <div>
        <label for="liberar-cpf" class="mb-1 block text-sm font-medium">CPF</label>
        <input
          id="liberar-cpf"
          :value="cpfMascarado"
          type="text"
          inputmode="numeric"
          maxlength="14"
          autocomplete="off"
          class="min-h-[44px] w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
          @input="digitarCpf"
        >
        <p v-if="form.errors.cpf" class="mt-1 text-xs text-red-600">{{ form.errors.cpf }}</p>
      </div>

      <div>
        <label for="liberar-motivo" class="mb-1 block text-sm font-medium">Motivo (mínimo de 10 caracteres)</label>
        <textarea
          id="liberar-motivo"
          v-model="form.motivo"
          rows="3"
          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
        />
        <p v-if="form.errors.motivo" class="mt-1 text-xs text-red-600">{{ form.errors.motivo }}</p>
        <p v-if="form.errors.curso_id" class="mt-1 text-xs text-red-600">{{ form.errors.curso_id }}</p>
      </div>

      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="emit('fechar')">Cancelar</Button>
        <Button type="submit" :loading="form.processing">Liberar</Button>
      </div>
    </form>
  </Modal>
</template>
