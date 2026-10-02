<template>
  <Modal :show="show" max-width="2xl" @close="fechar">
    <div class="p-4 sm:p-6">
      <ModalHeader title="Novo cadastro" @close="fechar" />

      <form class="mt-4" @submit.prevent="salvar">
        <CadastroAcessoFields :form="form" />

        <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700/50">
          <Button type="button" variant="outline" @click="fechar">Cancelar</Button>
          <Button type="submit" variant="primary" :disabled="form.processing" :loading="form.processing">Salvar</Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ModalHeader from '@/Components/Molecules/Modal/ModalHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CadastroAcessoFields from '@/Components/Organisms/Acessos/CadastroAcessoFields.vue';
import { dadosFormularioAcesso } from '@/Support/acessos';

const props = defineProps({
  show: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);

const form = useForm(dadosFormularioAcesso());

// Cada abertura limpa os erros de um envio anterior que falhou.
watch(() => props.show, (aberto) => {
  if (aberto) form.clearErrors();
});

function fechar() {
  emit('close');
}

function salvar() {
  form.post('/acessos', {
    onSuccess: () => { emit('close'); form.reset(); },
  });
}
</script>
