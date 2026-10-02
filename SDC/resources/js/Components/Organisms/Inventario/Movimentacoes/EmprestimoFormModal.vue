<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="p-4 sm:p-6">
      <ModalHeader title="Novo empréstimo" @close="fechar" />

      <form class="mt-4" @submit.prevent="salvar">
        <FormSection :cols="1">
          <FormSelect v-model="form.equipamento_id" label="Equipamento" required :options="opcoesEquipamento" :error="form.errors.equipamento_id" />
          <FormField v-model="form.quantidade" label="Quantidade" type="number" required :error="form.errors.quantidade" />
          <FormSelect v-model="form.usuario_destino_id" label="Usuário de destino" :options="opcoesUsuario" placeholder="Nenhum" :error="form.errors.usuario_destino_id" />
          <FormSelect v-model="form.estacao_destino_id" label="Estação de destino" :options="opcoesEstacao" placeholder="Nenhuma" :error="form.errors.estacao_destino_id" />
          <FormField v-model="form.data_prevista_devolucao" label="Devolução prevista" type="date" :error="form.errors.data_prevista_devolucao" />
          <FormTextarea v-model="form.observacao" label="Observação" :rows="3" :error="form.errors.observacao" />
        </FormSection>

        <div class="mt-2 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700/50">
          <Button type="button" variant="outline" :disabled="form.processing" @click="fechar">Cancelar</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Registrar</Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Modal from '@/Components/Modal.vue';
import ModalHeader from '@/Components/Molecules/Modal/ModalHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  equipamentos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  estacoes: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const opcoesEquipamento = computed(() => props.equipamentos.map((e) => ({ value: e.id, label: `${e.nome} — ${e.patrimonio} (${e.quantidade})` })));
const opcoesUsuario = computed(() => props.usuarios.map((u) => ({ value: u.id, label: u.name })));
const opcoesEstacao = computed(() => props.estacoes.map((e) => ({ value: e.id, label: e.nome })));

// tipo fixo: remanejamento agora e sempre em lote, pelo formulario proprio.
const form = useForm({
  equipamento_id: '', tipo: 'emprestimo', quantidade: 1, usuario_destino_id: '',
  estacao_destino_id: '', data_prevista_devolucao: '', observacao: '',
});

// Emprestimo e da quantidade integral (regra do MovimentacaoService).
watch(() => form.equipamento_id, (id) => {
  form.quantidade = props.equipamentos.find((e) => e.id === Number(id))?.quantidade ?? 1;
});

function fechar() {
  if (form.processing) return;
  emit('close');
}

function salvar() {
  if (form.processing) return;
  form
    .transform((dados) => ({
      ...dados,
      quantidade: Number(dados.quantidade),
      usuario_destino_id: dados.usuario_destino_id || null,
      estacao_destino_id: dados.estacao_destino_id || null,
      data_prevista_devolucao: dados.data_prevista_devolucao || null,
    }))
    .post(route('inventario.movimentacoes.store'), {
      preserveScroll: true,
      onSuccess: () => { form.reset(); emit('close'); },
    });
}
</script>
