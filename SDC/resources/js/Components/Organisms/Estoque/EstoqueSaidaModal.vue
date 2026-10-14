<template>
  <Modal :show="show" max-width="3xl" @close="fechar">
    <form class="space-y-4 p-4 sm:p-6" @submit.prevent="salvar">
      <header class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Saída — romaneio de entrega</h2>
        <button type="button" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="fechar">✕</button>
      </header>
      <p class="text-xs text-slate-500 dark:text-slate-400">
        Sai primeiro o lote que vence primeiro. Lote vencido ou em quarentena não sai. Faltando saldo em qualquer item, nada sai.
      </p>

      <div v-if="form.errors.estoque" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ form.errors.estoque }}</div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <label class="block">
          <span class="campo-rotulo">Almoxarifado *</span>
          <select v-model="form.almoxarifado_id" class="campo" required>
            <option v-for="a in almoxarifados" :key="a.value" :value="a.value">{{ a.label }}</option>
          </select>
        </label>
        <label class="block">
          <span class="campo-rotulo">Destino *</span>
          <select v-model="form.destino_tipo" class="campo" required>
            <option value="municipio">Município</option>
            <option value="abrigo">Abrigo</option>
          </select>
        </label>
        <label v-if="form.destino_tipo === 'municipio'" class="block">
          <span class="campo-rotulo">Município *</span>
          <select v-model="form.destino_municipio_id" class="campo" required>
            <option value="" disabled>Selecione…</option>
            <option v-for="m in municipios" :key="m.id" :value="m.id">{{ m.nome }}</option>
          </select>
          <span v-if="form.errors.destino_municipio_id" class="campo-erro">{{ form.errors.destino_municipio_id }}</span>
        </label>
        <label v-else class="block">
          <span class="campo-rotulo">Abrigo *</span>
          <input v-model="form.destino_descricao" type="text" maxlength="150" placeholder="Nome do abrigo" class="campo" required />
          <span v-if="form.errors.destino_descricao" class="campo-erro">{{ form.errors.destino_descricao }}</span>
        </label>
      </div>

      <div v-if="kits.length" class="flex flex-wrap items-end gap-2 rounded-md bg-slate-50 p-3 dark:bg-slate-800/50">
        <label class="block flex-1">
          <span class="campo-rotulo">Adicionar kits</span>
          <select v-model="kitEscolhido" class="campo">
            <option value="">Selecione um kit…</option>
            <option v-for="k in kits" :key="k.id" :value="k.id">{{ k.nome }} (montáveis: {{ k.montaveis }})</option>
          </select>
        </label>
        <input v-model.number="kitQuantidade" type="number" min="1" inputmode="numeric" class="campo campo-grande w-28" aria-label="Quantidade de kits" />
        <Button variant="secondary" size="lg" type="button" :disabled="!kitEscolhido" @click="adicionarKit">Adicionar</Button>
      </div>

      <div class="space-y-3">
        <div v-for="(item, i) in form.itens" :key="i" class="grid grid-cols-[1fr_7rem_auto] items-end gap-2 sm:grid-cols-[1fr_9rem_auto]">
          <label class="block min-w-0">
            <span class="campo-rotulo">Produto</span>
            <select v-model="item.produto_id" class="campo" required>
              <option value="" disabled>Selecione…</option>
              <option v-for="p in catalogo" :key="p.id" :value="p.id">{{ p.nome }} — disp. {{ disponivel(p.id) }}</option>
            </select>
          </label>
          <label class="block">
            <span class="campo-rotulo">Qtd.</span>
            <input v-model.number="item.quantidade" type="number" min="1" inputmode="numeric" class="campo campo-grande" :class="excede(item) ? 'border-red-500 text-red-600' : ''" required />
          </label>
          <button type="button" class="mb-1 h-12 rounded-md border border-slate-300 px-3 text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-800" :disabled="form.itens.length === 1" aria-label="Remover item" @click="form.itens.splice(i, 1)">✕</button>
          <p v-if="excede(item)" class="col-span-3 -mt-1 text-xs font-semibold text-red-600">
            Disponível neste almoxarifado: {{ disponivel(item.produto_id) }} (pedido total {{ totalPedido(item.produto_id) }})
          </p>
        </div>
        <button type="button" class="text-sm font-semibold text-sky-600 hover:underline" @click="form.itens.push({ produto_id: '', quantidade: 1 })">+ Outro item</button>
      </div>

      <label class="block">
        <span class="campo-rotulo">Observação</span>
        <textarea v-model="form.observacao" rows="2" maxlength="2000" class="campo"></textarea>
      </label>

      <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
        <Button variant="secondary" size="lg" type="button" @click="fechar">Cancelar</Button>
        <!-- Conferencia de saldo antes de fechar: o servidor confere de novo. -->
        <Button variant="primary" size="lg" type="submit" :loading="form.processing" :disabled="form.processing || temExcesso">
          Fechar romaneio
        </Button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  catalogo: { type: Array, default: () => [] },
  almoxarifados: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
  kits: { type: Array, default: () => [] },
  // Lote clicado ({produto_id, almoxarifado_id}) ou kit ({kit}).
  inicial: { type: Object, default: null },
});
const emit = defineEmits(['close']);

const form = useForm({
  almoxarifado_id: '', destino_tipo: 'municipio', destino_municipio_id: '', destino_descricao: '',
  itens: [{ produto_id: '', quantidade: 1 }], observacao: '',
});
const kitEscolhido = ref('');
const kitQuantidade = ref(1);

const disponivel = (produtoId) => {
  const p = props.catalogo.find((x) => x.id === Number(produtoId));
  return Number(p?.disponivel?.[form.almoxarifado_id] ?? 0);
};
const totalPedido = (produtoId) => form.itens
  .filter((i) => Number(i.produto_id) === Number(produtoId))
  .reduce((s, i) => s + Number(i.quantidade || 0), 0);
const excede = (item) => item.produto_id !== '' && totalPedido(item.produto_id) > disponivel(item.produto_id);
const temExcesso = computed(() => form.itens.some(excede));

function adicionarKit() {
  const kit = props.kits.find((k) => k.id === Number(kitEscolhido.value));
  if (!kit) return;
  const n = Math.max(1, Number(kitQuantidade.value) || 1);
  form.itens = form.itens.filter((i) => i.produto_id !== '');
  kit.itens.forEach((c) => form.itens.push({ produto_id: c.produto_id, quantidade: c.quantidade * n }));
  kitEscolhido.value = '';
  kitQuantidade.value = 1;
}

watch(() => props.show, (aberto) => {
  if (!aberto) return;
  form.reset();
  form.clearErrors();
  form.almoxarifado_id = props.inicial?.almoxarifado_id ?? props.almoxarifados[0]?.value ?? '';
  form.itens = props.inicial?.produto_id ? [{ produto_id: props.inicial.produto_id, quantidade: 1 }] : [{ produto_id: '', quantidade: 1 }];
  if (props.inicial?.kit) {
    kitEscolhido.value = props.inicial.kit.id;
    adicionarKit();
  }
});

function fechar() {
  emit('close');
}

function salvar() {
  if (temExcesso.value) return;
  form.transform((d) => ({
    ...d,
    destino_municipio_id: d.destino_tipo === 'municipio' ? d.destino_municipio_id : null,
    destino_descricao: d.destino_tipo === 'abrigo' ? d.destino_descricao : null,
  })).post(route('estoque.saidas.store'), { preserveScroll: true, onSuccess: fechar });
}
</script>

<style scoped>
.campo-rotulo { @apply mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-300; }
.campo { @apply block w-full rounded-md border-slate-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-100; min-height: 2.75rem; }
.campo-grande { @apply text-2xl font-bold; min-height: 3rem; }
.campo-erro { @apply mt-1 block text-xs text-red-600; }
</style>
