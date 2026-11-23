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
 * Cadastro rapido de curso do catalogo (atalho "Novo curso" do formulario de
 * criar/editar curso): grava por JSON e devolve o curso criado em `criado`, sem
 * sair da pagina. A tela do catalogo e a edicao foram removidas.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  /** Mantida por compatibilidade com quem ja passa `rapido`; o modal so cadastra. */
  rapido: { type: Boolean, default: true },
});
const emit = defineEmits(['fechar', 'criado']);

const { show: toast } = useToast();
const form = useForm({ nome: '', sigla: '', descricao: '', ativo: true });
const erroRapido = ref({});
const processandoRapido = ref(false);

watch(() => props.show, (aberto) => {
  if (!aberto) return;
  form.defaults({
    nome: '',
    sigla: '',
    descricao: '',
    ativo: true,
  }).reset();
  form.clearErrors();
  erroRapido.value = {};
});

const erro = (campo) => erroRapido.value[campo]?.[0];

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
  return salvarRapido();
}
</script>

<template>
  <Modal :show="show" max-width="md" centralizado @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="salvar">
      <Heading :level="3" class="text-lg font-semibold">Novo curso</Heading>

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

      <div class="flex justify-end gap-2">
        <Button type="button" variant="secondary" @click="emit('fechar')">Cancelar</Button>
        <Button type="submit" variant="success" :loading="form.processing || processandoRapido" :disabled="!form.nome.trim()">
          Cadastrar
        </Button>
      </div>
    </form>
  </Modal>
</template>

<style scoped>
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
.field-error { @apply mt-1 text-xs text-red-500; }
</style>
