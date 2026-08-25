<template>
  <RatCollapsibleSection
    section-id="natureza"
    title="Natureza / COBRADE"
    subtitle="Classificação COBRADE e identificação da operação"
    icon-class="rat-section-icon-success"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
      </svg>
    </template>

    <div class="rat-grid-2">
      <FormSelect
        label="Grupo do Desastre"
        :model-value="grupo"
        :options="opcoesGrupo"
        placeholder="Selecione o Grupo"
        required
        @update:model-value="selecionarGrupo"
      />
      <FormSelect
        label="Evento"
        :model-value="modelValue.nat_codigo"
        :options="opcoesEvento"
        :placeholder="grupo ? 'Selecione o Evento' : 'Selecione o Grupo primeiro'"
        :disabled="!grupo"
        required
        @update:model-value="selecionarEvento"
      />
    </div>

    <FormField
      label="COBRADE"
      :model-value="modelValue.nat_codigo"
      placeholder="Preenchido pelo Evento"
      readonly
      :hint="cobradeSelecionado?.descricao"
    />

    <FormField
      label="Nome da Operação (Opcional)"
      :model-value="modelValue.nat_nome_operacao"
      placeholder="Ex: Operação Chuvas de Verão"
      hint="Informe o nome da operação caso se aplique"
      @update:model-value="emit('update:modelValue', { ...modelValue, nat_nome_operacao: $event })"
    />
  </RatCollapsibleSection>
</template>

<script setup>
import { computed, ref } from 'vue';
import FormField from '@/Components/Form/FormField.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      nat_codigo: '',
      nat_cobrade_id: '',
      nat_nome_operacao: '',
    }),
  },
  // Tabela oficial do COBRADE vinda do banco (dec_cobrade), enviada pelo
  // controller. Cada item: { value: id, codigo, label, nome, descricao, grupo }.
  cobrades: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue']);

const cobradeSelecionado = computed(() =>
  props.cobrades.find((c) => c.codigo === props.modelValue.nat_codigo)
);

// O grupo nao e gravado no RAT: ele sai do proprio codigo escolhido. So quando
// o usuario troca o grupo (e ainda nao escolheu o evento novo) e que a escolha
// dele precisa ser lembrada aqui.
const grupoEscolhido = ref(null);

const grupo = computed(() => grupoEscolhido.value ?? cobradeSelecionado.value?.grupo ?? '');

// A lista ja vem ordenada por codigo, entao os grupos saem na ordem oficial
// (Geologico, Hidrologico, ...) e nao em ordem alfabetica.
const opcoesGrupo = computed(() => {
  const grupos = [];

  props.cobrades.forEach((c) => {
    if (c.grupo && !grupos.includes(c.grupo)) {
      grupos.push(c.grupo);
    }
  });

  return grupos.map((g) => ({ value: g, label: g }));
});

const opcoesEvento = computed(() =>
  props.cobrades
    .filter((c) => c.grupo === grupo.value)
    .map((c) => ({ value: c.codigo, label: c.nome }))
);

function selecionarGrupo(novoGrupo) {
  grupoEscolhido.value = novoGrupo;

  // Trocar de grupo invalida o evento anterior: ele pertence a outro ramo da
  // classificacao. Limpar evita gravar um COBRADE que a tela nao mostra mais.
  if (cobradeSelecionado.value?.grupo !== novoGrupo) {
    emit('update:modelValue', { ...props.modelValue, nat_codigo: '', nat_cobrade_id: null });
  }
}

function selecionarEvento(codigo) {
  const escolhido = props.cobrades.find((c) => c.codigo === codigo);

  emit('update:modelValue', {
    ...props.modelValue,
    nat_codigo: codigo,
    nat_cobrade_id: escolhido ? escolhido.value : null,
  });
}
</script>
