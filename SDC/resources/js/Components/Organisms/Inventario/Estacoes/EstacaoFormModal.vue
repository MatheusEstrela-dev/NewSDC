<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="p-4 sm:p-6">
      <ModalHeader :title="estacao ? 'Editar estação' : 'Nova estação'" @close="fechar" />

      <form class="mt-4" @submit.prevent="salvar">
        <FormSection :cols="1">
          <FormField v-model="form.nome" label="Nome" required maxlength="120" :error="form.errors.nome" />
          <FormField v-model="form.ponto_rede" label="Ponto de rede" maxlength="120" :error="form.errors.ponto_rede" />
          <BuscaUsuarioField
            v-model="form.user_id"
            label="Usuário"
            :url="route('inventario.usuarios.buscar')"
            :selecionado="estacao?.usuario"
            :error="form.errors.user_id"
          />
        </FormSection>

        <div class="mt-2 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700/50">
          <Button type="button" variant="outline" :disabled="form.processing" @click="fechar">Cancelar</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Salvar</Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Modal from '@/Components/Modal.vue';
import ModalHeader from '@/Components/Molecules/Modal/ModalHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import BuscaUsuarioField from '@/Components/Molecules/Form/BuscaUsuarioField.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  // Nulo = cadastro; objeto = edicao (traz o ocupante em `usuario`).
  estacao: { type: Object, default: null },
});
const emit = defineEmits(['close']);

const form = useForm({ nome: '', ponto_rede: '', user_id: '' });

// Cada abertura recarrega os campos da estacao escolhida e limpa erros antigos.
watch(() => props.show, (aberto) => {
  if (!aberto) return;
  form.clearErrors();
  form.nome = props.estacao?.nome || '';
  form.ponto_rede = props.estacao?.ponto_rede || '';
  form.user_id = props.estacao?.user_id || '';
});

function fechar() {
  if (form.processing) return;
  emit('close');
}

function salvar() {
  if (form.processing) return;
  const editando = Boolean(props.estacao);
  const url = editando
    ? route('inventario.estacoes.update', props.estacao.id)
    : route('inventario.estacoes.store');
  // Sem usuario a estacao fica livre: o backend espera null, nao string vazia.
  form.transform((dados) => ({ ...dados, user_id: dados.user_id || null }));
  form[editando ? 'put' : 'post'](url, { onSuccess: () => emit('close') });
}
</script>
