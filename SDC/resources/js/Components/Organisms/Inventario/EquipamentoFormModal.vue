<template>
  <Modal :show="show" @close="emit('close')">
    <form class="space-y-4 bg-white p-6 dark:bg-slate-900" @submit.prevent="submit">
      <h2 class="text-xl font-semibold text-slate-900 dark:text-white">{{ equipamento ? 'Editar equipamento' : 'Novo equipamento' }}</h2>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-model="form.nome" label="Nome" required :error="form.errors.nome" />
        <FormField v-model="form.patrimonio" label="Patrimônio" required :error="form.errors.patrimonio" />
        <FormField v-model="form.numero_serie" label="Número de série" :error="form.errors.numero_serie" />
        <FormField v-model="form.ramal" label="Ramal" :error="form.errors.ramal" />
        <FormSelect v-model="form.categoria_id" label="Categoria" :options="categorias" :error="form.errors.categoria_id" />
        <FormField v-model="form.quantidade" label="Quantidade" type="number" required :error="form.errors.quantidade" />
        <FormField v-model="form.unidade" label="Unidade" :error="form.errors.unidade" />
        <FormField v-model="form.diretoria" label="Diretoria" :error="form.errors.diretoria" />
      </div>
      <FormTextarea v-model="form.observacao" label="Observações" :error="form.errors.observacao" />
      <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
        <Checkbox v-model:checked="form.emprestavel" /> Permitir empréstimo
      </label>
      <div class="flex justify-end gap-2">
        <Button type="button" variant="secondary" @click="emit('close')">Cancelar</Button>
        <Button type="submit" variant="primary" :disabled="form.processing">Salvar</Button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  equipamento: { type: Object, default: null },
  categorias: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);
const form = useForm({
  nome: '', patrimonio: '', numero_serie: '', ramal: '', categoria_id: '',
  unidade: '', diretoria: '', emprestavel: false, quantidade: 1, observacao: '',
});

watch(() => props.equipamento, (equipamento) => {
  form.clearErrors();
  form.defaults({
    nome: equipamento?.nome || '',
    patrimonio: equipamento?.patrimonio || '',
    numero_serie: equipamento?.numero_serie || '',
    ramal: equipamento?.ramal || '',
    categoria_id: equipamento?.categoria_id || '',
    unidade: equipamento?.unidade || '',
    diretoria: equipamento?.diretoria || '',
    emprestavel: Boolean(equipamento?.emprestavel),
    quantidade: equipamento?.quantidade || 1,
    observacao: equipamento?.observacao || '',
  });
  form.reset();
}, { immediate: true });

function submit() {
  const options = { preserveScroll: true, onSuccess: () => emit('close') };
  if (props.equipamento) {
    form.put(`/inventario/equipamentos/${props.equipamento.id}`, options);
  } else {
    form.post('/inventario/equipamentos', options);
  }
}
</script>
