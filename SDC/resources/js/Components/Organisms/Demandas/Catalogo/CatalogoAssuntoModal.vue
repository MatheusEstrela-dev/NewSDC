<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="p-6">
      <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
          {{ isEdicao ? 'Editar assunto' : 'Novo assunto' }}
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
            maxlength="150"
            :error="form.errors.nome"
          />
          <FormSelect
            v-model="form.categoria_id"
            label="Categoria"
            :options="opcoesCategoria"
            placeholder="Sem categoria"
            :error="form.errors.categoria_id"
          />
        </FormSection>

        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
          Campos dinâmicos e automação são configurados depois de salvar, pelo botão "Campos e automação" na lista.
        </p>

        <div class="mt-4 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700/50">
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
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  assunto: { type: Object, default: null },
  // Todas as categorias (principais e subcategorias), para o assunto poder
  // apontar para qualquer uma delas.
  categorias: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const isEdicao = computed(() => Boolean(props.assunto?.id));

const opcoesCategoria = computed(() =>
  props.categorias.map((categoria) => ({
    value: categoria.id,
    label: categoria.parent_id ? `↳ ${categoria.nome}` : categoria.nome,
  })),
);

const form = useForm({
  nome: '',
  categoria_id: '',
});

// Reabastece o formulario sempre que o modal abre -- tanto para criar (campos
// limpos) quanto para editar (dados do assunto clicado).
watch(
  () => props.show,
  (aberto) => {
    if (!aberto) return;

    form.clearErrors();
    form.nome = props.assunto?.nome ?? '';
    form.categoria_id = props.assunto?.categoria_id ?? '';
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

  form.transform((dados) => ({ ...dados, categoria_id: dados.categoria_id || null }));

  if (isEdicao.value) {
    form.put(route('admin.demandas.assuntos.update', props.assunto.id), opcoes);
  } else {
    form.post(route('admin.demandas.assuntos.store'), opcoes);
  }
}
</script>
