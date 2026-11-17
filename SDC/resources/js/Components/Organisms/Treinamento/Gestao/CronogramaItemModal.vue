<script setup>
/**
 * Criar/editar item do cronograma (Area do Gestor, issue #86). `item` null =
 * criacao; `dataInicial` pre-preenche o inicio quando a pessoa clica num dia
 * vazio do calendario.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  treinamentoId: { type: [Number, String], required: true },
  item: { type: Object, default: null },
  turmas: { type: Array, default: () => [] },
  tipoOptions: { type: Array, default: () => [] },
  dataInicial: { type: String, default: null },
});
const emit = defineEmits(['fechar', 'salvo']);

const editando = computed(() => props.item !== null);

const opcoesTurma = computed(() => [
  { value: '', label: 'Curso inteiro (todas as turmas)' },
  ...props.turmas.map((t) => ({ value: String(t.id), label: t.nome })),
]);

function paraDatetimeLocal(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const form = useForm({
  titulo: '',
  descricao: '',
  tipo: 'AULA',
  inicio: '',
  fim: '',
  local: '',
  turma_id: '',
});

watch(() => props.show, (aberto) => {
  if (!aberto) return;

  form.clearErrors();
  form.reset();

  if (props.item) {
    form.titulo = props.item.titulo ?? '';
    form.descricao = props.item.descricao ?? '';
    form.tipo = props.item.tipo ?? 'AULA';
    form.inicio = paraDatetimeLocal(props.item.inicio);
    form.fim = paraDatetimeLocal(props.item.fim);
    form.local = props.item.local ?? '';
    form.turma_id = props.item.turma_id ? String(props.item.turma_id) : '';
  } else if (props.dataInicial) {
    form.inicio = `${props.dataInicial}T08:00`;
  }
});

function salvar() {
  const rota = editando.value
    ? route('treinamentos.gestor.cronograma.update', [props.treinamentoId, props.item.id])
    : route('treinamentos.gestor.cronograma.store', props.treinamentoId);
  const metodo = editando.value ? 'put' : 'post';

  form.transform((dados) => ({ ...dados, turma_id: dados.turma_id || null }))[metodo](rota, {
    preserveScroll: true,
    onSuccess: () => {
      emit('salvo');
      emit('fechar');
    },
  });
}
</script>

<template>
  <Modal :show="show" max-width="lg" @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="salvar">
      <Heading :level="3" class="text-lg font-semibold">{{ editando ? 'Editar item do cronograma' : 'Novo item do cronograma' }}</Heading>

      <div>
        <label class="field-label">Título <span class="text-red-500">*</span></label>
        <TextInput v-model="form.titulo" :maxlength="160" />
        <p v-if="form.errors.titulo" class="field-error">{{ form.errors.titulo }}</p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="field-label">Tipo</label>
          <SelectInput v-model="form.tipo" :options="tipoOptions" placeholder="" />
        </div>
        <div>
          <label class="field-label">Turma</label>
          <SelectInput v-model="form.turma_id" :options="opcoesTurma" placeholder="" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="field-label">Início <span class="text-red-500">*</span></label>
          <TextInput v-model="form.inicio" type="datetime-local" />
          <p v-if="form.errors.inicio" class="field-error">{{ form.errors.inicio }}</p>
        </div>
        <div>
          <label class="field-label">Fim</label>
          <TextInput v-model="form.fim" type="datetime-local" />
          <p v-if="form.errors.fim" class="field-error">{{ form.errors.fim }}</p>
        </div>
      </div>

      <div>
        <label class="field-label">Local</label>
        <TextInput v-model="form.local" :maxlength="255" />
      </div>

      <div>
        <label class="field-label">Descrição</label>
        <textarea
          v-model="form.descricao"
          rows="3"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        ></textarea>
      </div>

      <p v-if="form.errors.cronograma" class="field-error">{{ form.errors.cronograma }}</p>

      <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <Button type="button" variant="outline" @click="emit('fechar')">Cancelar</Button>
        <Button type="submit" variant="primary" :loading="form.processing">{{ editando ? 'Salvar' : 'Adicionar' }}</Button>
      </div>
    </form>
  </Modal>
</template>

<style scoped>
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
.field-error { @apply mt-1 text-xs text-red-500; }
</style>
