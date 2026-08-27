<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import { useCopiarTexto } from '@/Composables/useCopiarTexto';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps({
  plantaoId: {
    type: Number,
    required: true,
  },
});

const texto = ref('');
const carregando = ref(false);
const erro = ref('');
const { copiar, copiado } = useCopiarTexto();

// O CollapsibleSection (Molecules/CollapsibleSection.vue) nao emite evento de
// abertura: guarda o estado internamente via useCollapsibleSection e nao
// expoe nem @open nem a flag de expansao para o pai. Por isso o texto e
// buscado assim que o painel monta, em vez de sob demanda na primeira
// abertura da secao.
const carregar = async () => {
  if (texto.value !== '' || carregando.value) return;

  carregando.value = true;
  erro.value = '';

  try {
    const { data } = await axios.get(route('plantao.passagem.relatorio', props.plantaoId));
    texto.value = data.texto;
  } catch {
    erro.value = 'Nao foi possivel carregar o relatorio.';
  } finally {
    carregando.value = false;
  }
};

onMounted(carregar);

const handleCopiar = async () => {
  await carregar();
  if (texto.value !== '') {
    await copiar(texto.value);
  }
};
</script>

<template>
  <CollapsibleSection
    namespace="plantao"
    section-id="relatorio-passagem"
    title="Relatorio de passagem de servico"
    subtitle="Texto pronto para colar no grupo"
    :icon="DocumentTextIcon"
  >
    <div class="space-y-3">
      <p v-if="erro" class="text-sm text-red-600 dark:text-red-400">
        {{ erro }}
      </p>

      <pre
        v-if="texto"
        class="max-h-96 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 text-xs leading-relaxed text-gray-800 dark:bg-gray-900 dark:text-gray-200"
      >{{ texto }}</pre>

      <p v-else-if="carregando" class="text-sm text-gray-500 dark:text-gray-400">
        Montando o relatorio...
      </p>

      <Button
        variant="primary"
        size="md"
        :icon="ClipboardIcon"
        icon-position="left"
        :disabled="carregando"
        @click="handleCopiar"
      >
        {{ copiado ? 'Copiado' : 'Copiar para WhatsApp' }}
      </Button>
    </div>
  </CollapsibleSection>
</template>
