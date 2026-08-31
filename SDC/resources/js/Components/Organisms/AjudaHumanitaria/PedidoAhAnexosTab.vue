<template>
  <div class="space-y-6">
    <div v-if="canAnexos" class="flex flex-wrap items-end gap-3">
      <div class="flex-1">
        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">
          Documento em PDF, até 2 MB
        </label>
        <input
          ref="campoArquivo"
          type="file"
          accept="application/pdf"
          class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-blue-700 dark:text-slate-300"
          @change="selecionar"
        />
      </div>
      <Button variant="primary" size="md" :disabled="!arquivo" @click="enviar">Anexar</Button>
    </div>

    <ListEmptyState
      v-if="!anexos.length"
      title="Nenhum documento anexado"
      helper="Decreto, ofício e demais comprovantes do pleito"
    />

    <ul v-else class="divide-y divide-slate-100 dark:divide-slate-800">
      <li v-for="a in anexos" :key="a.id" class="flex items-center justify-between gap-3 py-3">
        <div class="flex min-w-0 items-center gap-3">
          <DocumentTextIcon class="h-5 w-5 shrink-0 text-slate-400" />
          <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">
              {{ a.nome }}
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              {{ formatarTamanho(a.tamanho) }} · {{ formatarDataHora(a.criado_em) }}
            </p>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
          <a
            :href="urlDownload(a.id)"
            class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
          >
            Baixar
          </a>
          <button
            v-if="canAnexos"
            type="button"
            class="text-xs font-medium text-red-600 hover:underline dark:text-red-400"
            @click="$emit('remover-anexo', a.id)"
          >
            Remover
          </button>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

const props = defineProps({
  anexos: { type: Array, default: () => [] },
  pedidoId: { type: Number, required: true },
  canAnexos: { type: Boolean, default: false },
});

const emit = defineEmits(['anexar', 'remover-anexo']);

const arquivo = ref(null);
const campoArquivo = ref(null);

function selecionar(evento) {
  arquivo.value = evento.target.files?.[0] ?? null;
}

function enviar() {
  if (!arquivo.value) return;

  emit('anexar', arquivo.value);

  arquivo.value = null;

  if (campoArquivo.value) {
    campoArquivo.value.value = '';
  }
}

// O download passa por rota autorizada em vez de URL do disco: o disco do
// modulo nao e publico e o arquivo herda o escopo por municipio da policy.
function urlDownload(mediaId) {
  return route('ajuda-humanitaria.pedidos.anexos.download', [props.pedidoId, mediaId]);
}

function formatarTamanho(bytes) {
  if (!bytes) return '—';

  const kb = bytes / 1024;

  return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`;
}

function formatarDataHora(valor) {
  if (!valor) return '—';

  return new Date(valor).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
