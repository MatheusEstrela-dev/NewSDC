<script setup>
/**
 * Cancelamento do curso pela Area do Gestor (issue #86). Motivo e opcional;
 * a transicao de status (quem decide se cancelar e permitido a partir do
 * status atual) e do TreinamentoService, chamado por GestaoCursoComandos.
 */
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  treinamentoId: { type: [Number, String], required: true },
  tituloCurso: { type: String, default: '' },
});
const emit = defineEmits(['fechar']);

const form = useForm({ motivo: '' });

function confirmar() {
  form.post(route('treinamentos.gestor.cancelar', props.treinamentoId), {
    preserveScroll: true,
    onSuccess: () => emit('fechar'),
  });
}
</script>

<template>
  <Modal :show="show" max-width="md" @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="confirmar">
      <Heading :level="3" class="text-lg font-semibold">Cancelar curso</Heading>
      <Text size="sm" color="muted">
        {{ tituloCurso }} será marcado como cancelado. Essa ação fica registrada no histórico e não pode ser desfeita pela tela.
      </Text>

      <div>
        <label class="field-label">Motivo (opcional)</label>
        <textarea
          v-model="form.motivo"
          rows="3"
          maxlength="500"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        ></textarea>
        <p v-if="form.errors.motivo" class="field-error">{{ form.errors.motivo }}</p>
        <p v-if="form.errors.cancelar" class="field-error">{{ form.errors.cancelar }}</p>
      </div>

      <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <Button type="button" variant="outline" @click="emit('fechar')">Voltar</Button>
        <Button type="submit" variant="danger" :loading="form.processing">Cancelar curso</Button>
      </div>
    </form>
  </Modal>
</template>

<style scoped>
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
.field-error { @apply mt-1 text-xs text-red-500; }
</style>
