<template>
  <div
    class="rounded-lg border p-3 transition-colors"
    :class="conferido
      ? 'border-emerald-300 bg-emerald-50/60 dark:border-emerald-500/40 dark:bg-emerald-500/5'
      : 'border-slate-200 bg-white dark:border-slate-700/50 dark:bg-slate-900/40'"
  >
    <label class="flex cursor-pointer items-start gap-3">
      <input
        type="checkbox"
        class="mt-0.5 rounded border-slate-300 dark:border-slate-600"
        :checked="conferido"
        :disabled="somenteLeitura"
        @change="$emit('update:conferido', $event.target.checked)"
      >
      <span class="min-w-0 flex-1">
        <span class="block text-sm font-medium text-slate-900 dark:text-slate-100">{{ item.label }}</span>
        <span v-if="item.unidade_rotulo" class="block text-xs text-slate-400">
          Medido em {{ item.unidade_rotulo }}
        </span>
      </span>
    </label>

    <!--
      Quantidade so aparece em item que TEM quantidade: dois em metro (calha e
      tubulacao) e quatro pecas de PVC em unidade. Isso vem do proprio enum, e
      nao de uma lista paralela aqui.
    -->
    <div v-if="item.unidade && conferido" class="mt-2">
      <FormField
        :model-value="quantidade"
        :label="`Quantidade (${item.unidade_rotulo})`"
        inputmode="decimal"
        :disabled="somenteLeitura"
        :error="error?.quantidade"
        @update:model-value="$emit('update:quantidade', $event)"
      />
    </div>

    <!--
      Fixacao e o unico item com subquantidade. No legado eram tres colunas
      soltas (fix_abracadeira, fix_bucha, fix_parafuso); aqui viram chaves de um
      jsonb, o que permite acrescentar peca sem migration.
    -->
    <div v-if="item.aceita_detalhes && conferido" class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
      <FormField
        v-for="chave in CHAVES_DETALHE"
        :key="chave"
        :model-value="detalhes?.[chave] ?? ''"
        :label="ROTULOS_DETALHE[chave]"
        maxlength="30"
        :disabled="somenteLeitura"
        @update:model-value="atualizarDetalhe(chave, $event)"
      />
    </div>

    <div v-if="conferido" class="mt-2">
      <FormField
        :model-value="observacao"
        label="Observacao"
        maxlength="255"
        :disabled="somenteLeitura"
        :error="error?.observacao"
        @update:model-value="$emit('update:observacao', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import FormField from '@/Components/Molecules/Form/FormField.vue';

/**
 * Uma linha do checklist de instalacao. Molecula burra: o que o item aceita vem
 * pronto na prop `item`, que e um elemento de ItemInstalacao::options().
 */
const props = defineProps({
  /** { value, label, unidade, unidade_rotulo, aceita_detalhes } */
  item: { type: Object, required: true },
  conferido: { type: Boolean, default: false },
  quantidade: { type: [String, Number], default: '' },
  detalhes: { type: Object, default: null },
  observacao: { type: String, default: '' },
  somenteLeitura: { type: Boolean, default: false },
  error: { type: Object, default: null },
});

const emit = defineEmits([
  'update:conferido',
  'update:quantidade',
  'update:detalhes',
  'update:observacao',
]);

const CHAVES_DETALHE = ['abracadeira', 'bucha', 'parafuso'];

const ROTULOS_DETALHE = {
  abracadeira: 'Abracadeira',
  bucha: 'Bucha',
  parafuso: 'Parafuso',
};

function atualizarDetalhe(chave, valor) {
  emit('update:detalhes', { ...(props.detalhes ?? {}), [chave]: valor });
}
</script>
