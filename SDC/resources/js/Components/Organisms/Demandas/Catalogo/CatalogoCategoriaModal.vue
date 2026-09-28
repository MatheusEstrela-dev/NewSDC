<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="p-6">
      <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
          {{ isEdicao ? 'Editar categoria' : 'Nova categoria' }}
        </h3>
        <button type="button" class="text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-200" @click="fechar">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <form class="mt-4" @submit.prevent="salvar">
        <FormSection :cols="1">
          <FormField
            v-model="form.nome"
            label="Nome"
            required
            maxlength="100"
            :error="form.errors.nome"
          />
          <FormTextarea
            v-model="form.descricao"
            label="Descrição"
            :rows="3"
            maxlength="2000"
            :error="form.errors.descricao"
          />
          <FormSelect
            v-model="form.parent_id"
            label="Categoria principal"
            :options="opcoesCategoriaPrincipal"
            placeholder="Nenhuma (esta e uma categoria principal)"
            :error="form.errors.parent_id"
          />
        </FormSection>

        <div class="mt-2 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700/50">
          <Button type="button" variant="outline" @click="fechar">Cancelar</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Salvar</Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  categoria: { type: Object, default: null },
  // Categorias principais (sem pai) disponiveis para virar pai desta -- a propria
  // categoria em edicao ja vem excluida pelo componente pai, que e quem conhece a
  // lista completa.
  categoriasPrincipais: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const isEdicao = computed(() => Boolean(props.categoria?.id));

const opcoesCategoriaPrincipal = computed(() =>
  props.categoriasPrincipais.map((categoria) => ({ value: categoria.id, label: `Subcategoria de ${categoria.nome}` })),
);

const form = useForm({
  nome: '',
  descricao: '',
  parent_id: '',
});

// Reabastece o formulario sempre que o modal abre -- tanto para criar (campos
// limpos) quanto para editar (dados da categoria clicada).
watch(
  () => props.show,
  (aberto) => {
    if (!aberto) return;

    form.clearErrors();
    form.nome = props.categoria?.nome ?? '';
    form.descricao = props.categoria?.descricao ?? '';
    form.parent_id = props.categoria?.parent_id ?? '';
  },
);

function fechar() {
  if (form.processing) return;
  emit('close');
}

function salvar() {
  const opcoes = {
    preserveScroll: true,
    onSuccess: () => emit('saved'),
  };

  form.transform((dados) => ({ ...dados, parent_id: dados.parent_id || null }));

  if (isEdicao.value) {
    form.put(route('admin.demandas.categorias.update', props.categoria.id), opcoes);
  } else {
    form.post(route('admin.demandas.categorias.store'), opcoes);
  }
}
</script>
