<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="max-h-[85vh] overflow-y-auto p-6">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
        {{ edicao ? 'Editar material' : 'Novo material' }}
      </h3>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        O catálogo alimenta a lista de itens do pedido de ajuda humanitária.
      </p>

      <form class="mt-4 space-y-4" @submit.prevent="salvar">
        <div>
          <InputLabel for="material-nome" value="Nome *" />
          <TextInput
            id="material-nome"
            v-model="form.nome"
            type="text"
            class="mt-1 block w-full"
            maxlength="255"
            required
          />
          <InputError :message="form.errors.nome" class="mt-2" />
        </div>

        <div>
          <InputLabel for="material-descricao" value="Descrição" />
          <textarea
            id="material-descricao"
            v-model="form.descricao"
            rows="3"
            maxlength="2000"
            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
          />
          <InputError :message="form.errors.descricao" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <InputLabel for="material-unidade" value="Unidade de medida *" />
            <!-- Campo livre com sugestao: o catalogo migrado tem UN, Metro e
                 Unitario, e um select fecharia a porta para a proxima. -->
            <TextInput
              id="material-unidade"
              v-model="form.unidade_medida"
              type="text"
              class="mt-1 block w-full"
              list="material-unidades"
              maxlength="30"
              required
            />
            <datalist id="material-unidades">
              <option v-for="unidade in unidades" :key="unidade" :value="unidade" />
            </datalist>
            <InputError :message="form.errors.unidade_medida" class="mt-2" />
          </div>

          <div>
            <InputLabel value="Disponibilidade" />
            <label class="mt-3 flex items-center gap-2">
              <input
                v-model="form.disponivel_para_pedido"
                type="checkbox"
                class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900"
              />
              <span class="text-sm text-slate-700 dark:text-slate-300">Disponível para pedido</span>
            </label>
            <InputError :message="form.errors.disponivel_para_pedido" class="mt-2" />
          </div>
        </div>

        <p v-if="edicao && material.codigo_legado" class="text-xs text-slate-500 dark:text-slate-400">
          Código no sistema anterior:
          <span class="font-mono">{{ material.codigo_legado }}</span>. Não é editável, porque é a
          chave que liga este material ao histórico migrado.
        </p>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
          <SecondaryButton type="button" @click="fechar">Cancelar</SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            {{ form.processing ? 'Salvando...' : 'Salvar' }}
          </PrimaryButton>
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
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  /** Material em edicao; objeto vazio significa cadastro novo. */
  material: { type: Object, default: () => ({}) },
  unidades: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const edicao = computed(() => Boolean(props.material?.id));

const form = useForm({
  nome: '',
  descricao: '',
  unidade_medida: 'UN',
  disponivel_para_pedido: true,
});

// Recarrega ao abrir, e nao ao montar: o modal e um so para os 25 materiais da
// pagina, entao o conteudo muda sem o componente ser recriado.
watch(
  () => [props.show, props.material?.id],
  ([aberto]) => {
    if (!aberto) return;

    form.clearErrors();
    form.nome = props.material?.nome ?? '';
    form.descricao = props.material?.descricao ?? '';
    form.unidade_medida = props.material?.unidade_medida ?? 'UN';
    form.disponivel_para_pedido = props.material?.disponivel_para_pedido ?? true;
  },
);

function salvar() {
  const opcoes = {
    preserveScroll: true,
    onSuccess: () => {
      emit('saved');
      emit('close');
    },
  };

  if (edicao.value) {
    form.put(route('ajuda-humanitaria.materiais.update', props.material.id), opcoes);
    return;
  }

  form.post(route('ajuda-humanitaria.materiais.store'), opcoes);
}

function fechar() {
  form.clearErrors();
  emit('close');
}
</script>
