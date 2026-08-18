<template>
  <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h3 class="mb-3 text-sm font-bold text-slate-900 dark:text-slate-100">Fotos do imovel</h3>

    <div v-if="fotos.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      <figure v-for="foto in fotos" :key="foto.id" class="overflow-hidden rounded-md border border-slate-200 dark:border-slate-700">
        <img
          :src="foto.thumb || foto.url"
          :alt="rotuloAngulo(foto.angulo)"
          class="h-32 w-full object-cover"
          loading="lazy"
        >
        <figcaption class="px-2 py-1 text-xs text-slate-600 dark:text-slate-300">
          {{ rotuloAngulo(foto.angulo) }}
          <span v-if="foto.observacao" class="block truncate text-slate-400" :title="foto.observacao">
            {{ foto.observacao }}
          </span>
        </figcaption>
      </figure>
    </div>

    <!--
      Vazio NAO e erro, e o estado normal de todo cadastro importado.
      A migracao nao trouxe arquivo nenhum: as fotos do imovel do legado eram
      30.574 links do Google Drive, e o arquivo fisico nunca esteve no sistema.
      Tratar isso como falha faria a tela gritar em 8.096 cadastros.
    -->
    <ListEmptyState
      v-else
      title="Nenhuma foto anexada"
      :helper="ajudaVazio"
    />
  </section>
</template>

<script setup>
import { computed } from 'vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

const props = defineProps({
  fotos: { type: Array, default: () => [] },
  /** True quando o cadastro veio do legado (tem legacy_id). */
  doLegado: { type: Boolean, default: false },
});

const ROTULOS = {
  frontal: 'Frontal',
  lateral_direita: 'Lateral direita',
  lateral_esquerda: 'Lateral esquerda',
  fundo: 'Fundo',
  local_instalacao_1: 'Local da instalacao 1',
  local_instalacao_2: 'Local da instalacao 2',
  opcional_1: 'Opcional 1',
  opcional_2: 'Opcional 2',
  opcional_3: 'Opcional 3',
  opcional_4: 'Opcional 4',
};

const ajudaVazio = computed(() => (props.doLegado
  ? 'Cadastro importado do sistema antigo: as fotos ficavam no Google Drive e nao vieram na migracao.'
  : 'Envie as fotos ao editar o cadastro.'));

function rotuloAngulo(angulo) {
  return ROTULOS[angulo] ?? angulo ?? 'Sem angulo';
}
</script>
