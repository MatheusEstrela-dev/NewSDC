<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { useToast } from '@/Composables/useToast';

/*
 * Criar/editar curso do catalogo. Na pagina do catalogo grava por Inertia e a
 * lista recarrega. Em `rapido` (atalho "Novo curso" do formulario da turma)
 * grava por JSON e devolve o curso criado em `criado`, sem sair da pagina.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  curso: { type: Object, default: null },
  rapido: { type: Boolean, default: false },
});
const emit = defineEmits(['fechar', 'criado']);

const { show: toast } = useToast();
const form = useForm({ nome: '', sigla: '', descricao: '', ativo: true });
const erroRapido = ref({});
const processandoRapido = ref(false);

watch(() => props.show, (aberto) => {
  if (!aberto) return;
  form.defaults({
    nome: props.curso?.nome ?? '',
    sigla: props.curso?.sigla ?? '',
    descricao: props.curso?.descricao ?? '',
    ativo: props.curso?.ativo ?? true,
  }).reset();
  form.clearErrors();
  erroRapido.value = {};
});

const erro = (campo) => (props.rapido ? erroRapido.value[campo]?.[0] : form.errors[campo]);

async function salvarRapido() {
  processandoRapido.value = true;
  erroRapido.value = {};
  try {
    const { data } = await axios.post(route('treinamentos.catalogo.store'), form.data(), {
      headers: { Accept: 'application/json' },
    });
    emit('criado', data.data);
    emit('fechar');
  } catch (e) {
    if (e.response?.status === 422) erroRapido.value = e.response.data.errors ?? {};
    else toast('Nao foi possivel cadastrar o curso.', 'error');
  } finally {
    processandoRapido.value = false;
  }
}

function salvar() {
  if (props.rapido) return salvarRapido();

  const opcoes = {
    preserveScroll: true,
    onSuccess: () => emit('fechar'),
    onError: () => toast('Verifique os campos do formulario.', 'error'),
  };
  if (props.curso) form.put(route('treinamentos.catalogo.update', props.curso.id), opcoes);
  else form.post(route('treinamentos.catalogo.store'), opcoes);
}
</script>

<template>
  <Modal :show="show" max-width="md" centralizado @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="salvar">
      <Heading :level="3" class="text-lg font-semibold">{{ curso ? 'Editar curso' : 'Novo curso' }}</Heading>

      <div>
        <label for="curso-nome" class="field-label">Nome <span class="text-red-500">*</span></label>
        <TextInput id="curso-nome" v-model="form.nome" :maxlength="255" placeholder="Ex: SCO - Sistema de Comando em Operações" />
        <p v-if="erro('nome')" class="field-error">{{ erro('nome') }}</p>
      </div>

      <div>
        <label for="curso-sigla" class="field-label">Sigla</label>
        <TextInput id="curso-sigla" v-model="form.sigla" :maxlength="30" placeholder="Ex: SCO" />
        <p v-if="erro('sigla')" class="field-error">{{ erro('sigla') }}</p>
      </div>

      <div>
        <label for="curso-descricao" class="field-label">Descrição</label>
        <textarea
          id="curso-descricao"
          v-model="form.descricao"
          rows="3"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        ></textarea>
        <p v-if="erro('descricao')" class="field-error">{{ erro('descricao') }}</p>
      </div>

      <label v-if="curso" class="flex min-h-[44px] items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input v-model="form.ativo" type="checkbox" class="h-4 w-4 rounded border-slate-300 dark:border-slate-600" />
        Curso ativo (aparece ao criar novas turmas)
      </label>

      <div class="flex justify-end gap-2">
        <Button type="button" variant="secondary" @click="emit('fechar')">Cancelar</Button>
        <Button type="submit" variant="success" :loading="form.processing || processandoRapido" :disabled="!form.nome.trim()">
          {{ curso ? 'Salvar' : 'Cadastrar' }}
        </Button>
      </div>
    </form>
  </Modal>
</template>

<style scoped>
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
.field-error { @apply mt-1 text-xs text-red-500; }
</style>
