<template>
  <Modal :show="show" max-width="3xl" @close="fechar">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Nova entrada de material</h3>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        O saldo do depósito é atualizado no mesmo instante em que a entrada é gravada.
      </p>

      <form class="mt-4 space-y-4" @submit.prevent="salvar">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <InputLabel for="entrada-deposito" value="Depósito *" />
            <select
              id="entrada-deposito"
              v-model="form.deposito_id"
              class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
              required
            >
              <option :value="null">Selecione o depósito</option>
              <option v-for="d in opcoes.depositos" :key="d.id" :value="d.id">
                {{ d.sigla }} — {{ d.nome }}
              </option>
            </select>
            <InputError :message="form.errors.deposito_id" class="mt-2" />
          </div>

          <div>
            <InputLabel for="entrada-recebido" value="Recebido em *" />
            <TextInput
              id="entrada-recebido"
              v-model="form.recebido_em"
              type="date"
              class="mt-1 block w-full"
              :max="hoje"
              required
            />
            <InputError :message="form.errors.recebido_em" class="mt-2" />
          </div>

          <div>
            <InputLabel for="entrada-nf" value="Nota fiscal" />
            <TextInput id="entrada-nf" v-model="form.nota_fiscal" type="text" maxlength="70" class="mt-1 block w-full" />
            <InputError :message="form.errors.nota_fiscal" class="mt-2" />
          </div>

          <div>
            <InputLabel for="entrada-fonte" value="Fonte de recurso" />
            <select
              id="entrada-fonte"
              v-model="form.fonte_recurso_id"
              class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
            >
              <option :value="null">Não informada</option>
              <option v-for="f in opcoes.fontes" :key="f.id" :value="f.id">{{ f.nome }}</option>
            </select>
            <InputError :message="form.errors.fonte_recurso_id" class="mt-2" />
          </div>

          <div class="md:col-span-2">
            <InputLabel for="entrada-fornecedor" value="Fornecedor" />
            <select
              id="entrada-fornecedor"
              v-model="form.fornecedor_id"
              class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
            >
              <option :value="null">Não informado</option>
              <option v-for="f in opcoes.fornecedores" :key="f.id" :value="f.id">{{ f.nome }}</option>
            </select>
            <InputError :message="form.errors.fornecedor_id" class="mt-2" />
          </div>
        </div>

        <div class="border-t border-slate-200 pt-4 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Materiais recebidos</h4>
            <SecondaryButton type="button" @click="adicionarItem">Adicionar item</SecondaryButton>
          </div>

          <InputError :message="form.errors.itens" class="mt-2" />

          <div class="mt-3 space-y-3">
            <div
              v-for="(item, indice) in form.itens"
              :key="indice"
              class="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-3 md:grid-cols-12 dark:border-slate-700"
            >
              <div class="md:col-span-5">
                <InputLabel :for="`item-material-${indice}`" value="Material *" />
                <select
                  :id="`item-material-${indice}`"
                  v-model="item.material_ah_id"
                  class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
                  required
                >
                  <option :value="null">Selecione</option>
                  <option v-for="m in opcoes.materiais" :key="m.id" :value="m.id">{{ m.nome }}</option>
                </select>
                <InputError :message="form.errors[`itens.${indice}.material_ah_id`]" class="mt-1" />
              </div>

              <div class="md:col-span-2">
                <InputLabel :for="`item-qtd-${indice}`" :value="`Qtd (${unidadeDe(item.material_ah_id)}) *`" />
                <TextInput
                  :id="`item-qtd-${indice}`"
                  v-model="item.qtd"
                  type="number"
                  step="0.001"
                  min="0.001"
                  class="mt-1 block w-full text-sm"
                  required
                />
                <InputError :message="form.errors[`itens.${indice}.qtd`]" class="mt-1" />
              </div>

              <div class="md:col-span-2">
                <InputLabel :for="`item-valor-${indice}`" value="Valor unit." />
                <TextInput
                  :id="`item-valor-${indice}`"
                  v-model="item.valor_unitario"
                  type="number"
                  step="0.01"
                  min="0"
                  class="mt-1 block w-full text-sm"
                />
                <InputError :message="form.errors[`itens.${indice}.valor_unitario`]" class="mt-1" />
              </div>

              <div class="md:col-span-2">
                <InputLabel :for="`item-validade-${indice}`" value="Validade" />
                <TextInput
                  :id="`item-validade-${indice}`"
                  v-model="item.data_validade"
                  type="date"
                  class="mt-1 block w-full text-sm"
                />
                <InputError :message="form.errors[`itens.${indice}.data_validade`]" class="mt-1" />
              </div>

              <div class="flex items-end md:col-span-1">
                <!-- O ultimo item nao pode sair: entrada sem material nao
                     movimenta estoque, e o backend recusaria. -->
                <SecondaryButton
                  type="button"
                  :disabled="form.itens.length === 1"
                  @click="removerItem(indice)"
                >
                  Remover
                </SecondaryButton>
              </div>
            </div>
          </div>
        </div>

        <div>
          <InputLabel for="entrada-observacao" value="Observação" />
          <textarea
            id="entrada-observacao"
            v-model="form.observacao"
            rows="2"
            maxlength="2000"
            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
          />
          <InputError :message="form.errors.observacao" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
          <SecondaryButton type="button" @click="fechar">Cancelar</SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            {{ form.processing ? 'Registrando...' : 'Registrar entrada' }}
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
  /** Listas vindas do controller; nulo para quem nao pode movimentar estoque. */
  opcoes: {
    type: Object,
    default: () => ({ depositos: [], materiais: [], fontes: [], fornecedores: [] }),
  },
});

const emit = defineEmits(['close']);

function itemVazio() {
  return { material_ah_id: null, qtd: '', valor_unitario: '', data_validade: '' };
}

const hoje = new Date().toISOString().slice(0, 10);

const form = useForm({
  deposito_id: null,
  fonte_recurso_id: null,
  fornecedor_id: null,
  nota_fiscal: '',
  recebido_em: hoje,
  observacao: '',
  itens: [itemVazio()],
});

// Limpa ao reabrir: o modal fica montado entre uma entrada e a proxima, e sem
// isso o formulario voltaria preenchido com o lancamento anterior.
watch(
  () => props.show,
  (aberto) => {
    if (!aberto) return;

    form.clearErrors();
    form.defaults({
      deposito_id: null,
      fonte_recurso_id: null,
      fornecedor_id: null,
      nota_fiscal: '',
      recebido_em: hoje,
      observacao: '',
      itens: [itemVazio()],
    });
    form.reset();
  },
);

const unidadePorMaterial = computed(
  () => new Map((props.opcoes?.materiais ?? []).map((m) => [m.id, m.unidade])),
);

function unidadeDe(materialId) {
  return unidadePorMaterial.value.get(materialId) ?? 'un';
}

function adicionarItem() {
  form.itens.push(itemVazio());
}

function removerItem(indice) {
  if (form.itens.length === 1) return;

  form.itens.splice(indice, 1);
}

function salvar() {
  form.transform((dados) => ({
    ...dados,
    // Campo vazio vira null: o backend valida numeric, e '' seria recusado
    // como valor invalido em vez de tratado como ausente.
    itens: dados.itens.map((item) => ({
      material_ah_id: item.material_ah_id,
      qtd: item.qtd,
      valor_unitario: item.valor_unitario === '' ? null : item.valor_unitario,
      data_validade: item.data_validade === '' ? null : item.data_validade,
    })),
    nota_fiscal: dados.nota_fiscal || null,
    observacao: dados.observacao || null,
  })).post(route('ajuda-humanitaria.entradas.store'), {
    preserveScroll: true,
    // Sem onSuccess que fecha: o controller redireciona para o detalhe da
    // entrada recem-criada, e a pagina inteira troca.
    onError: () => form.transform((dados) => dados),
  });
}

function fechar() {
  form.clearErrors();
  emit('close');
}
</script>
