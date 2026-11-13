<template>
  <PaeCard :title="titulo">
    <div class="space-y-6">
      <div
        v-if="!canUpload"
        class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-700 dark:text-amber-200"
      >
        Salve as Informacoes Gerais ou abra um protocolo antes de adicionar anexos.
      </div>

      <PaeAnexosDropzone
        v-if="!readOnly"
        :categorias="categorias"
        :categoria-padrao="categoriaAtiva"
        :disabled="!canUpload"
        :progress="progress"
        @upload="$emit('upload', $event)"
        @concluido="aoConcluirEnvio"
      />

      <!-- Leitura do PDF: sugestao para o analista conferir; nada e gravado aqui. -->
      <div v-if="leitura.estado !== 'ocioso'" class="rounded-xl border border-blue-500/30 bg-blue-500/5 p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">Dados lidos do PDF</p>
            <p class="truncate text-xs text-slate-500 dark:text-slate-400" :title="leitura.nome">{{ leitura.nome }}</p>
          </div>
          <button type="button" class="flex-shrink-0 text-xs font-semibold text-slate-500 hover:underline dark:text-slate-400" @click="leitura.estado = 'ocioso'">Fechar</button>
        </div>

        <p v-if="leitura.estado === 'lendo'" class="mt-3 text-sm text-slate-600 dark:text-slate-300">Lendo o documento...</p>
        <p v-else-if="leitura.estado === 'vazio'" class="mt-3 text-sm text-amber-700 dark:text-amber-300">{{ leitura.mensagem }}</p>
        <template v-else>
          <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
            <div v-for="linha in leitura.linhas" :key="linha.campo" class="min-w-0">
              <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ linha.rotulo }}</dt>
              <dd class="break-words text-slate-900 dark:text-slate-100">{{ linha.valor }}</dd>
            </div>
          </dl>
          <div v-if="!readOnly && leitura.aplicaveis" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Os dados já foram levados para os campos vazios das Informações Gerais e da Ficha cadastral (Anexo B). Abra cada uma, confira e salve.
            </p>
            <Button class="w-full sm:w-auto" variant="primary" size="sm" @click="usarNasInformacoesGerais">Abrir Informações Gerais</Button>
          </div>
        </template>
      </div>

      <div
        v-if="visibleBackendError"
        class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm font-medium text-red-600 dark:text-red-300"
      >
        {{ errorMessage }}
      </div>

      <div class="border-t border-slate-200 pt-5 dark:border-slate-700">
        <div v-if="!itensVisiveis.length" class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center dark:border-slate-700 dark:bg-slate-900/40">
          <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ categoriaAtiva ? `Nenhum anexo em ${rotuloCategoria(categoriaAtiva)}.` : 'Nenhum anexo cadastrado.' }}
          </p>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-500">Os arquivos enviados aparecerao aqui.</p>
        </div>

        <div v-else class="space-y-5">
          <div v-for="grupo in grupos" :key="grupo.categoria" class="space-y-3">
            <h4 v-if="categorias.length && !categoriaAtiva" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              {{ rotuloCategoria(grupo.categoria) }} <span class="font-normal">({{ grupo.itens.length }})</span>
            </h4>
            <div
              v-for="anexo in grupo.itens"
              :key="anexo.id"
              class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800/50 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                  {{ anexo.nome_original }}
                </p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  {{ anexo.tamanho_formatado }} <span v-if="anexo.descricao">- {{ anexo.descricao }}</span>
                </p>
                <p v-if="anexo.local_armazenamento" class="mt-1 text-xs text-slate-500 dark:text-slate-500">
                  Local: {{ anexo.local_armazenamento }}
                </p>
              </div>
  
              <div class="flex flex-shrink-0 gap-2">
                <button
                  v-if="anexo.mime_type === 'application/pdf' && formularioId"
                  type="button"
                  class="rounded-lg border border-emerald-500/40 px-3 py-2 text-xs font-semibold text-emerald-600 transition-colors hover:bg-emerald-500/10 dark:text-emerald-300"
                  title="Ler os dados do PAE deste PDF"
                  @click="lerPdf(anexo)"
                >
                  Ler PDF
                </button>
                <button
                  v-if="visualizavel(anexo)"
                  type="button"
                  class="rounded-lg border border-blue-500/40 px-3 py-2 text-xs font-semibold text-blue-600 transition-colors hover:bg-blue-500/10 dark:text-blue-300"
                  @click="$emit('view', anexo.id)"
                >
                  Visualizar
                </button>
                <button
                  type="button"
                  class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                  @click="$emit('download', anexo.id)"
                >
                  Baixar
                </button>
                <button
                  v-if="!readOnly"
                  type="button"
                  :disabled="saving"
                  class="rounded-lg border border-red-500/40 px-3 py-2 text-xs font-semibold text-red-500 transition-colors hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                  @click="$emit('remove', anexo.id)"
                >
                  {{ saving ? 'Aguarde...' : 'Remover' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="historico.length" class="border-t border-slate-200 pt-5 dark:border-slate-700">
        <h4 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">
          Historico de arquivos anexados
        </h4>
        <ul class="space-y-2">
          <li
            v-for="item in historico"
            :key="`hist-${item.id}`"
            class="flex flex-col gap-1 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs dark:border-slate-700 dark:bg-slate-900/40 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="min-w-0">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ item.nome_original }}</span>
              <span v-if="categorias.length" class="ml-2 rounded-full bg-slate-200 px-2 py-0.5 text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ rotuloCategoria(item.categoria) }}</span>
              <span class="ml-2 text-slate-500 dark:text-slate-400">{{ item.tamanho_formatado }}</span>
            </div>
            <div class="flex items-center gap-3 text-slate-500 dark:text-slate-400">
              <span v-if="item.enviado_por">por {{ item.enviado_por }}</span>
              <span>{{ item.created_at }}</span>
              <span
                v-if="item.removido"
                class="rounded-full bg-red-500/10 px-2 py-0.5 font-semibold text-red-500 dark:text-red-400"
              >
                Removido {{ item.deleted_at ? `em ${item.deleted_at}` : '' }}
              </span>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </PaeCard>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import { guardarLeitura, leituraParaFicha, leituraParaInfoGerais, ROTULOS_LEITURA } from '@/utils/paeAnexos';
import { router } from '@inertiajs/vue3';
import { computed, nextTick, reactive } from 'vue';
import PaeAnexosDropzone from './PaeAnexosDropzone.vue';
import PaeCard from './PaeCard.vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  historico: {
    type: Array,
    default: () => [],
  },
  formularioId: {
    type: [Number, String],
    default: null,
  },
  protocoloId: {
    type: [Number, String],
    default: null,
  },
  saving: {
    type: Boolean,
    default: false,
  },
  progress: {
    type: Number,
    default: 0,
  },
  statusMessage: {
    type: String,
    default: '',
  },
  errorMessage: {
    type: String,
    default: '',
  },
  readOnly: {
    type: Boolean,
    default: false,
  },
  // Lista {value, label} das categorias (Anexo A..J e Outros); vazia mantem a lista simples.
  categorias: {
    type: Array,
    default: () => [],
  },
  // Categoria aberta na pasta; null mostra todas, agrupadas.
  categoriaAtiva: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['upload', 'remove', 'download', 'view']);

const CATEGORIA_PADRAO = 'outros';

// O navegador so abre PDF e imagem; os demais formatos vao direto para download.
function visualizavel(anexo) {
  const mime = anexo.mime_type ?? '';
  return mime === 'application/pdf' || mime.startsWith('image/');
}

const titulo = computed(() => (props.categoriaAtiva ? `Anexos · ${rotuloCategoria(props.categoriaAtiva)}` : 'Anexos'));

const itensVisiveis = computed(() => (props.categoriaAtiva
  ? props.items.filter((anexo) => (anexo.categoria ?? CATEGORIA_PADRAO) === props.categoriaAtiva)
  : props.items));

// Grupos na ordem das categorias; sem categorias, um grupo unico com tudo.
const grupos = computed(() => {
  if (!props.categorias.length) {
    return [{ categoria: CATEGORIA_PADRAO, itens: itensVisiveis.value }];
  }

  return props.categorias
    .map((opcao) => ({
      categoria: opcao.value,
      itens: itensVisiveis.value.filter((anexo) => (anexo.categoria ?? CATEGORIA_PADRAO) === opcao.value),
    }))
    .filter((grupo) => grupo.itens.length);
});

function rotuloCategoria(valor) {
  return props.categorias.find((opcao) => opcao.value === (valor ?? CATEGORIA_PADRAO))?.label ?? 'Outros';
}

const canUpload = computed(() => !!props.formularioId || !!props.protocoloId);
const visibleBackendError = computed(() => !!props.errorMessage);

// Leitura do PDF (PaeLeituraPdfService): sugestoes para conferencia.
const MOTIVOS_SEM_LEITURA = {
  nao_pdf: 'Só é possível ler arquivos PDF.',
  sem_texto: 'Este PDF é uma imagem escaneada, sem texto para ler.',
  sem_campos: 'O texto do PDF não traz os dados básicos do PAE no formato esperado.',
  indisponivel: 'Não foi possível ler o arquivo agora.',
};

const leitura = reactive({ estado: 'ocioso', nome: '', mensagem: '', linhas: [], aplicaveis: 0, valores: {} });

async function buscarLeitura(anexo) {
  try {
    const resposta = await fetch(route('pae.formulario.anexos.leitura', [props.formularioId, anexo.id]), {
      headers: { Accept: 'application/json' },
    });
    return resposta.ok ? await resposta.json() : { lido: false, motivo: 'indisponivel' };
  } catch {
    return { lido: false, motivo: 'indisponivel' };
  }
}

/**
 * Le um ou mais PDFs e junta o que encontrou (o primeiro valor de cada campo
 * vence, entao o PAE principal deve vir na frente). O que couber nas
 * Informacoes Gerais ja fica separado para o relatorio abrir preenchido.
 */
async function lerPdfs(anexos) {
  if (!anexos.length || !props.formularioId) return;

  const nome = anexos.length === 1 ? anexos[0].nome_original : `${anexos.length} PDFs enviados`;
  Object.assign(leitura, { estado: 'lendo', nome, mensagem: '', linhas: [], aplicaveis: 0, valores: {} });

  const junto = { campos: {}, municipio_id: null };
  let motivo = 'sem_campos';
  for (const anexo of anexos) {
    const dados = await buscarLeitura(anexo);
    if (!dados.lido) {
      motivo = dados.motivo ?? motivo;
      continue;
    }
    junto.campos = { ...dados.campos, ...junto.campos };
    junto.municipio_id ??= dados.municipio_id;
  }

  if (!Object.keys(junto.campos).length) {
    Object.assign(leitura, { estado: 'vazio', mensagem: MOTIVOS_SEM_LEITURA[motivo] ?? MOTIVOS_SEM_LEITURA.indisponivel });
    return;
  }

  const valores = leituraParaInfoGerais(junto);
  const ficha = leituraParaFicha(junto);
  if (!props.readOnly && props.protocoloId) {
    guardarLeitura(props.protocoloId, 'relatorio', valores);
    guardarLeitura(props.protocoloId, 'ficha', ficha);
  }

  Object.assign(leitura, {
    estado: 'lido',
    linhas: Object.entries(ROTULOS_LEITURA)
      .filter(([campo]) => junto.campos[campo])
      .map(([campo, rotulo]) => ({ campo, rotulo, valor: junto.campos[campo] })),
    aplicaveis: Object.keys(valores).length + Object.keys(ficha).length,
    valores,
  });
}

function lerPdf(anexo) {
  return lerPdfs([anexo]);
}

// Terminado o envio, le sozinho os PDFs enviados, com o PAE principal na frente.
async function aoConcluirEnvio(enviados) {
  await nextTick();

  const pdfs = enviados
    .filter((enviado) => enviado.nome.toLowerCase().endsWith('.pdf'))
    .sort((x, y) => Number(y.categoria === 'anexo_b') - Number(x.categoria === 'anexo_b'))
    .map((enviado) => props.items.find((item) => item.nome_original === enviado.nome && item.categoria === enviado.categoria))
    .filter(Boolean);

  lerPdfs(pdfs);
}

// A sugestao ja foi guardada na leitura; aqui so abre o relatorio para conferir.
function usarNasInformacoesGerais() {
  if (!props.protocoloId) return;
  router.visit(route('pae.index', { protocolo_id: props.protocoloId }));
}
</script>
