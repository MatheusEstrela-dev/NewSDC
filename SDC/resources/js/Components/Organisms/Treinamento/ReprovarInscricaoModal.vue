<script setup>
/**
 * Reprovacao de inscricao com motivo obrigatorio (RN: o inscrito e a trilha
 * precisam do porque). Substitui o prompt() nativo. O erro de validacao do
 * servidor (ReprovarInscricaoRequest) aparece abaixo do campo.
 */
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  show: { type: Boolean, default: false },
  inscricao: { type: Object, default: null }, // { id, inscrito_nome }
});
const emit = defineEmits(['fechar']);

const { show: toast } = useToast();
const form = useForm({ observacoes: '' });
const tentou = ref(false);

const MAX = 1000;
const erroLocal = computed(() => (tentou.value && !form.observacoes.trim() ? 'Informe o motivo da reprovação.' : ''));

watch(() => props.show, (aberto) => {
  if (aberto) {
    form.reset();
    form.clearErrors();
    tentou.value = false;
  }
});

function confirmar() {
  tentou.value = true;
  if (!form.observacoes.trim() || !props.inscricao) return;

  form.transform((dados) => ({ observacoes: dados.observacoes.trim() }))
    .post(route('treinamentos.inscricoes.reprovar', props.inscricao.id), {
      preserveScroll: true,
      onSuccess: () => {
        toast('Inscrição reprovada.', 'success');
        emit('fechar');
      },
      onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível reprovar.', 'error'),
    });
}
</script>

<template>
  <Modal :show="show" max-width="md" @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="confirmar">
      <Heading :level="3" class="text-lg font-semibold">Reprovar inscrição</Heading>
      <Text size="sm" color="muted">
        {{ inscricao?.inscrito_nome || 'O inscrito' }} será avisado da reprovação. Informe o motivo.
      </Text>

      <div>
        <label for="motivo-reprovacao" class="field-label">Motivo da reprovação</label>
        <textarea
          id="motivo-reprovacao"
          v-model="form.observacoes"
          rows="4"
          :maxlength="MAX"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        ></textarea>
        <p v-if="erroLocal || form.errors.observacoes" class="field-error">{{ erroLocal || form.errors.observacoes }}</p>
      </div>

      <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <Button type="button" variant="outline" @click="emit('fechar')">Voltar</Button>
        <Button type="submit" variant="danger" :loading="form.processing">Reprovar</Button>
      </div>
    </form>
  </Modal>
</template>

<style scoped>
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
.field-error { @apply mt-1 text-xs text-red-500; }
</style>
