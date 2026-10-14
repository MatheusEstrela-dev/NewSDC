<template>
  <Modal :show="show" max-width="2xl" @close="$emit('close')">
    <form class="space-y-4 p-4 sm:p-6" @submit.prevent="salvar">
      <header class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ titulo }}</h2>
        <button type="button" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="$emit('close')">✕</button>
      </header>

      <!-- Produto -->
      <div v-if="tipo === 'produto'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <label class="block"><span class="campo-rotulo">SKU *</span><input v-model="form.sku" maxlength="40" class="campo uppercase" required /><span v-if="form.errors.sku" class="campo-erro">{{ form.errors.sku }}</span></label>
        <label class="block"><span class="campo-rotulo">Patrimônio</span><input v-model="form.patrimonio" maxlength="40" class="campo" /><span v-if="form.errors.patrimonio" class="campo-erro">{{ form.errors.patrimonio }}</span></label>
        <label class="block sm:col-span-2"><span class="campo-rotulo">Nome *</span><input v-model="form.nome" maxlength="150" class="campo" required /></label>
        <label class="block">
          <span class="campo-rotulo">Categoria *</span>
          <select v-model="form.categoria" class="campo" required>
            <option v-for="c in categorias" :key="c.value" :value="c.value">{{ c.label }}</option>
          </select>
        </label>
        <label class="block"><span class="campo-rotulo">Unidade</span><input v-model="form.unidade_base" maxlength="10" placeholder="un, kg, cx" class="campo" /></label>
        <label class="block"><span class="campo-rotulo">Estoque mínimo</span><input v-model.number="form.estoque_minimo" type="number" min="0" inputmode="numeric" class="campo" /></label>
        <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700 dark:text-slate-300"><input v-model="form.controla_validade" type="checkbox" class="rounded" /> Controla validade (alimentos, medicamentos)</label>
      </div>

      <!-- Almoxarifado -->
      <div v-else-if="tipo === 'almoxarifado'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <label class="block sm:col-span-2"><span class="campo-rotulo">Nome *</span><input v-model="form.nome" maxlength="120" class="campo" required /></label>
        <label class="block">
          <span class="campo-rotulo">Tipo *</span>
          <select v-model="form.tipo" class="campo"><option value="central">Central</option><option value="regional">Regional</option><option value="movel">Unidade móvel</option></select>
        </label>
        <label class="block"><span class="campo-rotulo">Capacidade (unidades)</span><input v-model.number="form.capacidade" type="number" min="1" inputmode="numeric" class="campo" /></label>
        <label class="block sm:col-span-2">
          <span class="campo-rotulo">Município</span>
          <select v-model="form.municipio_id" class="campo"><option value="">—</option><option v-for="m in municipios" :key="m.id" :value="m.id">{{ m.nome }}</option></select>
        </label>
      </div>

      <!-- Kit -->
      <div v-else class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <label class="block"><span class="campo-rotulo">Código *</span><input v-model="form.codigo" maxlength="40" class="campo uppercase" required /><span v-if="form.errors.codigo" class="campo-erro">{{ form.errors.codigo }}</span></label>
          <label class="block sm:col-span-2"><span class="campo-rotulo">Nome *</span><input v-model="form.nome" maxlength="150" class="campo" required /></label>
        </div>
        <div v-for="(item, i) in form.itens" :key="i" class="grid grid-cols-[1fr_6rem_auto] items-end gap-2">
          <label class="block min-w-0"><span class="campo-rotulo">Componente</span>
            <select v-model="item.produto_id" class="campo" required><option value="" disabled>Selecione…</option><option v-for="p in catalogo" :key="p.id" :value="p.id">{{ p.nome }}</option></select>
          </label>
          <label class="block"><span class="campo-rotulo">Qtd.</span><input v-model.number="item.quantidade" type="number" min="1" class="campo" required /></label>
          <button type="button" class="mb-1 h-11 rounded-md border border-slate-300 px-3 text-slate-500 dark:border-slate-600" :disabled="form.itens.length === 1" @click="form.itens.splice(i, 1)">✕</button>
        </div>
        <button type="button" class="text-sm font-semibold text-sky-600 hover:underline" @click="form.itens.push({ produto_id: '', quantidade: 1 })">+ Componente</button>
      </div>

      <ul v-if="Object.keys(form.errors).length" class="list-disc rounded-md bg-red-50 py-2 pl-8 pr-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">
        <li v-for="(msg, campo) in form.errors" :key="campo">{{ msg }}</li>
      </ul>

      <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
        <Button variant="secondary" size="lg" type="button" @click="$emit('close')">Cancelar</Button>
        <Button variant="primary" size="lg" type="submit" :loading="form.processing" :disabled="form.processing">Salvar</Button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

/** Cadastro rapido do estoque: produto (#32), almoxarifado e kit (#33). */
const props = defineProps({
  show: { type: Boolean, default: false },
  tipo: { type: String, default: 'produto' }, // produto | almoxarifado | kit
  categorias: { type: Array, default: () => [] },
  catalogo: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const VAZIOS = {
  produto: { sku: '', patrimonio: '', nome: '', categoria: 'alimentos', unidade_base: 'un', estoque_minimo: 0, controla_validade: false },
  almoxarifado: { nome: '', tipo: 'regional', capacidade: '', municipio_id: '' },
  kit: { codigo: '', nome: '', itens: [{ produto_id: '', quantidade: 1 }] },
};
const ROTAS = { produto: 'estoque.produtos.store', almoxarifado: 'estoque.almoxarifados.store', kit: 'estoque.kits.store' };

const form = useForm({ ...VAZIOS.produto, ...VAZIOS.almoxarifado, ...VAZIOS.kit });
const titulo = computed(() => ({ produto: 'Novo produto', almoxarifado: 'Novo almoxarifado', kit: 'Novo kit' })[props.tipo]);

watch(() => props.show, (aberto) => {
  if (!aberto) return;
  form.clearErrors();
  Object.assign(form, JSON.parse(JSON.stringify(VAZIOS[props.tipo])));
});

function salvar() {
  const campos = Object.keys(VAZIOS[props.tipo]);
  form.transform((d) => Object.fromEntries(campos.map((c) => [c, d[c] === '' ? null : d[c]])))
    .post(route(ROTAS[props.tipo]), { preserveScroll: true, onSuccess: () => emit('close') });
}
</script>

<style scoped>
.campo-rotulo { @apply mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-300; }
.campo { @apply block w-full rounded-md border-slate-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-100; min-height: 2.75rem; }
.campo-erro { @apply mt-1 block text-xs text-red-600; }
</style>
