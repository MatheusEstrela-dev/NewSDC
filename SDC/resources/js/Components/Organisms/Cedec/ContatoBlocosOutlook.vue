<script setup>
/**
 * Lista dos blocos de contato prontos para o Outlook da Cidade Administrativa.
 *
 * ORGANISMO: e aqui que a interacao mora. useCopiarTexto e invocado UMA vez, neste
 * componente. Se cada ContatoBloco invocasse o proprio, o feedback de "copiado" de
 * cada instancia seria independente mas nenhuma saberia qual bloco o usuario copiou,
 * e a molecula passaria a carregar estado, quebrando a camada.
 *
 * O `copiado` devolvido pelo composable NAO e usado: quem manda no feedback e
 * indiceCopiado, que sabe de QUAL bloco se trata.
 */
import { computed, ref } from 'vue';
import ContatoBloco from '@/Components/Molecules/Cedec/ContatoBloco.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import { useCopiarTexto } from '@/Composables/useCopiarTexto';
import { useToast } from '@/Composables/useToast.js';

const props = defineProps({
  blocos: { type: Array, default: () => [] },
  tamanhoBloco: { type: Number, default: 50 },
});

const { copiar } = useCopiarTexto();
const { show } = useToast();

const indiceCopiado = ref(null);

const totalDeContatos = computed(
  () => props.blocos.reduce((soma, bloco) => soma + bloco.total, 0),
);

const copiarBloco = async (bloco) => {
  const ok = await copiar(bloco.texto);

  if (!ok) {
    show('Não foi possível copiar o bloco. Selecione o texto e copie manualmente.', 'error');
    return;
  }

  indiceCopiado.value = bloco.indice;
  show(`Parte ${bloco.indice} copiada: ${bloco.total} contatos.`, 'success');

  setTimeout(() => {
    if (indiceCopiado.value === bloco.indice) {
      indiceCopiado.value = null;
    }
  }, 2500);
};
</script>

<template>
  <div class="space-y-4">
    <p class="text-xs text-slate-500 dark:text-slate-400">
      O limite de destinatários por envio do Outlook da Cidade Administrativa é de
      {{ tamanhoBloco }}. Os {{ totalDeContatos }} contatos abaixo já estão divididos:
      copie uma parte por envio.
    </p>

    <ListEmptyState
      v-if="blocos.length === 0"
      title="Nenhum contato preenchido"
      helper="Nenhuma prefeitura tem contato cadastrado para esta aba."
    />

    <ContatoBloco
      v-for="bloco in blocos"
      v-else
      :key="bloco.indice"
      :indice="bloco.indice"
      :total="bloco.total"
      :texto="bloco.texto"
      :copiado="indiceCopiado === bloco.indice"
      @copiar="copiarBloco(bloco)"
    />
  </div>
</template>
