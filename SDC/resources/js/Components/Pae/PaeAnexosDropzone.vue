<template>
  <div class="space-y-4">
    <!-- Area unica: soltar arquivos ou clicar para escolher. -->
    <div
      role="button"
      tabindex="0"
      :aria-disabled="disabled"
      class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-6 py-10 text-center transition-colors"
      :class="[
        disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
        arrastando
          ? 'border-blue-500 bg-blue-500/10'
          : 'border-slate-300 bg-slate-50 hover:border-blue-500/60 dark:border-slate-700 dark:bg-slate-900/40',
      ]"
      @click="abrirSeletor"
      @keydown.enter.prevent="abrirSeletor"
      @keydown.space.prevent="abrirSeletor"
      @dragover.prevent="arrastando = !disabled"
      @dragleave.prevent="arrastando = false"
      @drop.prevent="onDrop"
    >
      <span class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-500/15 text-blue-500">
        <ArrowUpTrayIcon class="h-7 w-7" />
      </span>
      <p class="text-base font-semibold text-slate-900 dark:text-white">
        {{ arrastando ? 'Solte para adicionar' : 'Arraste os arquivos do PAE para cá' }}
      </p>
      <p class="text-sm text-slate-500 dark:text-slate-400">
        ou <span class="font-semibold text-blue-600 dark:text-blue-400">clique para escolher</span>. {{ destinoFixo ? `Tudo vai para ${rotulo(categoriaPadrao)}.` : 'Cada arquivo vai para a pasta do seu anexo, definida pelo nome.' }}
      </p>
      <p class="text-xs text-slate-500 dark:text-slate-500">
        PDF, imagem, XLSX, PPTX, DOCX e mapas (KMZ, KML, shapefile, ZIP) · até 150 MB por arquivo
      </p>
      <input ref="seletor" type="file" multiple class="hidden" :accept="aceitos" @change="onEscolha" />
    </div>

    <p v-if="aviso" class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-xs text-amber-700 dark:text-amber-200">
      {{ aviso }}
    </p>

    <!-- Fila: cada arquivo com a pasta de destino e o andamento. -->
    <ul v-if="fila.length" class="space-y-2">
      <li
        v-for="item in fila"
        :key="item.id"
        class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/50"
      >
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white" :title="item.arquivo.name">{{ item.arquivo.name }}</p>
            <p class="text-xs" :class="item.estado === 'erro' ? 'text-red-500 dark:text-red-400' : 'text-slate-500 dark:text-slate-400'">
              {{ formatarTamanho(item.arquivo.size) }} · {{ legenda(item) }}
            </p>
          </div>

          <span
            class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-700 dark:text-slate-200"
            :title="`Pasta de destino: ${rotulo(item.categoria)}`"
          >
            <FolderIcon class="h-3.5 w-3.5 flex-shrink-0" />
            <span class="truncate">{{ rotulo(item.categoria) }}</span>
          </span>

          <CheckCircleIcon v-if="item.estado === 'enviado'" class="h-5 w-5 flex-shrink-0 text-emerald-500" />
          <button
            v-else
            type="button"
            class="flex-shrink-0 rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-red-500 disabled:opacity-40 dark:hover:bg-slate-700"
            :disabled="item.estado === 'enviando'"
            :title="`Tirar ${item.arquivo.name} da fila`"
            @click="tirar(item.id)"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>

        <div v-if="item.estado === 'enviando'" class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
          <div class="h-full rounded-full bg-blue-500 transition-all duration-300" :style="{ width: `${Math.max(8, Math.min(100, progress))}%` }" />
        </div>
      </li>
    </ul>

    <div v-if="pendentes.length || enviando" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-xs text-slate-500 dark:text-slate-400">
        {{ enviados }} de {{ fila.length }} enviado(s)
      </p>
      <Button class="w-full sm:w-auto" variant="primary" size="md" :loading="enviando" :disabled="disabled || enviando || !pendentes.length" @click="enviarProximo">
        {{ enviando ? 'Enviando...' : `Enviar ${pendentes.length} arquivo(s)` }}
      </Button>
    </div>
  </div>
</template>

<script setup>
/**
 * Caixa de envio dos anexos do protocolo: recebe varios arquivos de uma vez,
 * define a pasta de cada um (a aberta na arvore ou, em "Todas", a do nome do
 * arquivo) e envia em fila, um por requisicao. O
 * envio em si continua com quem escuta `upload` (mesmo contrato de antes).
 */
import Button from '@/Components/Atoms/Button/Button.vue';
import { categoriaSugerida, EXTENSOES_ACEITAS, extensaoDe, formatarTamanho, TAMANHO_MAXIMO_BYTES } from '@/utils/paeAnexos';
import { ArrowUpTrayIcon, CheckCircleIcon, FolderIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const props = defineProps({
  categorias: { type: Array, default: () => [] },
  // Pasta aberta na arvore: com ela, e o destino de tudo; sem ela ("Todas"),
  // a pasta de cada arquivo vem do nome. Nao ha escolha manual, para a
  // estrutura chegar padronizada ao servidor.
  categoriaPadrao: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  progress: { type: Number, default: 0 },
});

const emit = defineEmits(['upload', 'concluido']);

const aceitos = EXTENSOES_ACEITAS.map((e) => `.${e}`).join(',');

const fila = ref([]);
const seletor = ref(null);
const arrastando = ref(false);
const enviando = ref(false);
const aviso = ref('');
let sequencia = 0;

const pendentes = computed(() => fila.value.filter((item) => item.estado === 'fila' || item.estado === 'erro'));
const enviados = computed(() => fila.value.filter((item) => item.estado === 'enviado').length);

const destinoFixo = computed(() => !!props.categoriaPadrao);

function rotulo(valor) {
  return props.categorias.find((opcao) => opcao.value === valor)?.label ?? 'Outros';
}

function legenda(item) {
  return {
    fila: 'na fila',
    enviando: 'enviando...',
    enviado: 'enviado',
    erro: item.erro || 'falhou; tente de novo',
  }[item.estado];
}

function abrirSeletor() {
  if (!props.disabled) seletor.value?.click();
}

function onEscolha(evento) {
  adicionar(Array.from(evento.target.files ?? []));
  evento.target.value = '';
}

function onDrop(evento) {
  arrastando.value = false;
  if (!props.disabled) adicionar(Array.from(evento.dataTransfer?.files ?? []));
}

function adicionar(arquivos) {
  const recusados = [];

  for (const arquivo of arquivos) {
    if (!EXTENSOES_ACEITAS.includes(extensaoDe(arquivo.name))) {
      recusados.push(`${arquivo.name} (formato não aceito)`);
    } else if (arquivo.size > TAMANHO_MAXIMO_BYTES) {
      recusados.push(`${arquivo.name} (acima de 150 MB)`);
    } else {
      fila.value.push({
        id: ++sequencia,
        arquivo,
        categoria: destinoFixo.value ? props.categoriaPadrao : categoriaSugerida(arquivo.name, props.categorias),
        estado: 'fila',
        erro: '',
      });
    }
  }

  aviso.value = recusados.length ? `Não adicionado: ${recusados.join('; ')}.` : '';
}

function tirar(id) {
  fila.value = fila.value.filter((item) => item.id !== id);
}

// Um arquivo por vez; erro marca o item e segue para o proximo.
function enviarProximo() {
  const item = fila.value.find((candidato) => candidato.estado === 'fila')
    ?? (enviando.value ? null : fila.value.find((candidato) => candidato.estado === 'erro'));

  if (!item) {
    enviando.value = false;
    const feitos = fila.value.filter((candidato) => candidato.estado === 'enviado');
    if (feitos.length) {
      emit('concluido', feitos.map((feito) => ({ nome: feito.arquivo.name, categoria: feito.categoria })));
      fila.value = fila.value.filter((candidato) => candidato.estado !== 'enviado');
    }
    return;
  }

  enviando.value = true;
  item.estado = 'enviando';
  item.erro = '';

  emit('upload', {
    arquivo: item.arquivo,
    descricao: '',
    categoria: item.categoria,
    onSuccess: () => {
      item.estado = 'enviado';
      window.setTimeout(enviarProximo, 0);
    },
    onError: (mensagem) => {
      item.estado = 'erro';
      item.erro = mensagem;
      window.setTimeout(enviarProximo, 0);
    },
  });
}
</script>
