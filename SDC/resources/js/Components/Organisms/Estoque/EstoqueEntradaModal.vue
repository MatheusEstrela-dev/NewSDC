<template>
  <Modal :show="show" max-width="2xl" @close="fechar">
    <form class="space-y-4 p-4 sm:p-6" @submit.prevent="salvar">
      <header class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Entrada em estoque</h2>
        <button type="button" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="fechar">✕</button>
      </header>
      <p class="text-xs text-slate-500 dark:text-slate-400">Várias unidades de um mesmo lote de uma vez. Lote repetido soma no saldo existente.</p>

      <div v-if="form.errors.estoque" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ form.errors.estoque }}</div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <label class="block sm:col-span-2">
          <span class="campo-rotulo">Produto *</span>
          <select v-model="form.produto_id" class="campo" required>
            <option value="" disabled>Selecione…</option>
            <option v-for="p in catalogo" :key="p.id" :value="p.id">{{ p.nome }} ({{ p.sku }})</option>
          </select>
          <span v-if="form.errors.produto_id" class="campo-erro">{{ form.errors.produto_id }}</span>
        </label>
        <label class="block">
          <span class="campo-rotulo">Almoxarifado *</span>
          <select v-model="form.almoxarifado_id" class="campo" required>
            <option v-for="a in almoxarifados" :key="a.value" :value="a.value">{{ a.label }}</option>
          </select>
        </label>
        <label class="block">
          <span class="campo-rotulo">Lote *</span>
          <input v-model="form.lote" type="text" maxlength="40" class="campo uppercase" required />
          <span v-if="form.errors.lote" class="campo-erro">{{ form.errors.lote }}</span>
        </label>
        <label class="block">
          <span class="campo-rotulo">Validade {{ produto?.controla_validade ? '*' : '' }}</span>
          <input v-model="form.validade" type="date" class="campo" :required="produto?.controla_validade" />
          <span v-if="form.errors.validade" class="campo-erro">{{ form.errors.validade }}</span>
        </label>
        <label class="block">
          <span class="campo-rotulo">Quantidade *</span>
          <!-- Campo grande: uso com luva e em tablet (#33, UX de campo). -->
          <input v-model.number="form.quantidade" type="number" inputmode="numeric" min="1" class="campo campo-grande" required />
          <span v-if="form.errors.quantidade" class="campo-erro">{{ form.errors.quantidade }}</span>
        </label>
        <label class="block sm:col-span-2">
          <span class="campo-rotulo">Fornecedor / doador</span>
          <input v-model="form.fornecedor" type="text" maxlength="150" class="campo" />
        </label>
        <label class="block">
          <span class="campo-rotulo">Endereço no depósito</span>
          <input v-model="form.endereco" type="text" maxlength="40" placeholder="Ex.: Rua A, prateleira 3" class="campo" />
        </label>
        <label class="block">
          <span class="campo-rotulo">Documento</span>
          <input v-model="form.documento" type="text" maxlength="60" placeholder="Nota, termo de doação…" class="campo" />
        </label>
      </div>

      <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
        <Button variant="secondary" size="lg" type="button" @click="fechar">Cancelar</Button>
        <Button variant="success" size="lg" type="submit" :loading="form.processing" :disabled="form.processing">Registrar entrada</Button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  catalogo: { type: Array, default: () => [] },
  almoxarifados: { type: Array, default: () => [] },
  // Linha de lote clicada: pre-preenche produto, almoxarifado, lote e validade.
  inicial: { type: Object, default: null },
});
const emit = defineEmits(['close']);

const form = useForm({
  produto_id: '', almoxarifado_id: '', lote: '', validade: '', quantidade: '',
  fornecedor: '', endereco: '', documento: '',
});

const produto = computed(() => props.catalogo.find((p) => p.id === Number(form.produto_id)) ?? null);

watch(() => props.show, (aberto) => {
  if (!aberto) return;
  form.reset();
  form.clearErrors();
  const i = props.inicial;
  form.produto_id = i?.produto_id ?? '';
  form.almoxarifado_id = i?.almoxarifado_id ?? props.almoxarifados[0]?.value ?? '';
  form.lote = i?.lote ?? '';
  form.validade = i?.validade ?? '';
  form.fornecedor = i?.fornecedor ?? '';
  form.endereco = i?.endereco ?? '';
});

function fechar() {
  emit('close');
}

function salvar() {
  form.post(route('estoque.entradas.store'), { preserveScroll: true, onSuccess: fechar });
}
</script>

<style scoped>
.campo-rotulo { @apply mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-300; }
.campo { @apply block w-full rounded-md border-slate-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-100; min-height: 2.75rem; }
.campo-grande { @apply text-2xl font-bold; min-height: 3.5rem; }
.campo-erro { @apply mt-1 block text-xs text-red-600; }
</style>
