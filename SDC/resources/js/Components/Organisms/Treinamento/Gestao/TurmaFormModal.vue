<script setup>
/**
 * Criar/editar turma (Area do Gestor, issue #86). `turma` null = criacao;
 * preenchida = edicao. Datas, vagas e periodo sao regra do GestaoCursoComandos
 * (ver backend) - aqui so se monta o formulario e se mostra o erro que voltar.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import MunicipioBuscaSelect from '@/Components/Molecules/Treinamento/MunicipioBuscaSelect.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  treinamentoId: { type: [Number, String], required: true },
  turma: { type: Object, default: null },
  municipios: { type: Array, default: () => [] },
});
const emit = defineEmits(['fechar', 'salvo']);

const editando = computed(() => props.turma !== null);

const form = useForm({
  nome: '',
  data_inicio: '',
  data_fim: '',
  numero_vagas: '',
  local: '',
  municipio_id: null,
});

watch(() => props.show, (aberto) => {
  if (!aberto) return;

  form.clearErrors();
  form.reset();

  if (props.turma) {
    form.nome = props.turma.nome ?? '';
    form.data_inicio = props.turma.data_inicio ?? '';
    form.data_fim = props.turma.data_fim ?? '';
    form.numero_vagas = props.turma.numero_vagas ?? '';
    form.local = props.turma.local ?? '';
    form.municipio_id = props.turma.municipio_id ?? null;
  }
});

function salvar() {
  const rota = editando.value
    ? route('treinamentos.gestor.turmas.update', [props.treinamentoId, props.turma.id])
    : route('treinamentos.gestor.turmas.store', props.treinamentoId);
  const metodo = editando.value ? 'put' : 'post';

  form[metodo](rota, {
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
      <Heading :level="3" class="text-lg font-semibold">{{ editando ? 'Editar turma' : 'Nova turma' }}</Heading>

      <div>
        <label class="field-label">Nome <span class="text-red-500">*</span></label>
        <TextInput v-model="form.nome" :maxlength="120" placeholder="Ex: Turma 1 - Manhã" />
        <p v-if="form.errors.nome" class="field-error">{{ form.errors.nome }}</p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="field-label">Data de início <span class="text-red-500">*</span></label>
          <TextInput v-model="form.data_inicio" type="date" />
          <p v-if="form.errors.data_inicio" class="field-error">{{ form.errors.data_inicio }}</p>
        </div>
        <div>
          <label class="field-label">Data de término <span class="text-red-500">*</span></label>
          <TextInput v-model="form.data_fim" type="date" />
          <p v-if="form.errors.data_fim" class="field-error">{{ form.errors.data_fim }}</p>
        </div>
      </div>

      <div>
        <label class="field-label">Número de vagas <span class="text-red-500">*</span></label>
        <TextInput v-model="form.numero_vagas" type="number" min="1" />
        <p v-if="form.errors.numero_vagas" class="field-error">{{ form.errors.numero_vagas }}</p>
      </div>

      <div>
        <label class="field-label">Local</label>
        <TextInput v-model="form.local" :maxlength="255" />
      </div>

      <div>
        <label class="field-label">Município</label>
        <MunicipioBuscaSelect v-model="form.municipio_id" :municipios="municipios" />
        <p v-if="form.errors.municipio_id" class="field-error">{{ form.errors.municipio_id }}</p>
      </div>

      <p v-if="form.errors.turma" class="field-error">{{ form.errors.turma }}</p>

      <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <Button type="button" variant="outline" @click="emit('fechar')">Cancelar</Button>
        <Button type="submit" variant="primary" :loading="form.processing">{{ editando ? 'Salvar' : 'Criar turma' }}</Button>
      </div>
    </form>
  </Modal>
</template>

<style scoped>
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
.field-error { @apply mt-1 text-xs text-red-500; }
</style>
