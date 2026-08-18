<template>
  <div class="space-y-3">
    <ToggleField
      :model-value="atendido"
      label="Atendido por carro-pipa"
      description="Marque se a familia recebe agua por carro-pipa hoje"
      @update:model-value="alternarAtendido"
    />

    <!--
      Os responsaveis so aparecem quando ha atendimento. No legado as cinco
      colunas respAt* existiam soltas e podiam ficar marcadas com atendPipa =
      'nao', o que gerava linha de atendimento para quem nao e atendido.
    -->
    <fieldset v-if="atendido" class="rounded-md border border-slate-200 p-3 dark:border-slate-700">
      <legend class="px-1 text-xs font-medium text-slate-600 dark:text-slate-300">
        Quem atende
      </legend>

      <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
        <label
          v-for="opcao in opcoes"
          :key="opcao.value"
          class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200"
        >
          <input
            type="checkbox"
            class="rounded border-slate-300 dark:border-slate-600"
            :checked="responsaveis.includes(opcao.value)"
            @change="alternarResponsavel(opcao.value)"
          >
          <span>{{ opcao.label }}</span>
        </label>
      </div>

      <FormField
        v-if="responsaveis.includes('outros')"
        class="mt-3"
        :model-value="descricaoOutro"
        label="Qual outro responsavel"
        type="text"
        maxlength="255"
        :error="error?.atendimento_pipa_outro"
        @update:model-value="$emit('update:descricaoOutro', $event)"
      />
    </fieldset>
  </div>
</template>

<script setup>
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';

const props = defineProps({
  atendido: { type: Boolean, default: false },
  /** ResponsavelPipa::options() vindo do controller: [{value, label}]. */
  opcoes: { type: Array, default: () => [] },
  responsaveis: { type: Array, default: () => [] },
  descricaoOutro: { type: String, default: '' },
  error: { type: Object, default: null },
});

const emit = defineEmits([
  'update:atendido',
  'update:responsaveis',
  'update:descricaoOutro',
]);

/**
 * Desmarcar o atendimento limpa os responsaveis e a descricao: deixar valor
 * orfao no payload faria o backend gravar atendimento para quem nao e atendido.
 */
function alternarAtendido(valor) {
  emit('update:atendido', valor);

  if (!valor) {
    emit('update:responsaveis', []);
    emit('update:descricaoOutro', '');
  }
}

function alternarResponsavel(valor) {
  const proximos = props.responsaveis.includes(valor)
    ? props.responsaveis.filter((r) => r !== valor)
    : [...props.responsaveis, valor];

  emit('update:responsaveis', proximos);

  // Saiu "outros", a descricao nao faz mais sentido.
  if (!proximos.includes('outros')) {
    emit('update:descricaoOutro', '');
  }
}
</script>
