<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import ZonaArquivoVideo from '@/Components/Organisms/Treinamento/Conteudo/ZonaArquivoVideo.vue';
import { useUploadEmPartes } from '@/Composables/treinamento/useUploadEmPartes';
import { formatarDuracao } from '@/Composables/treinamento/formatarDuracao';

const INTERVALO_STATUS_MS = 5000;
const STATUS_TERMINAIS = ['PRONTO', 'FALHOU'];
// Respostas que nao melhoram com nova tentativa.
const ERROS_DEFINITIVOS = [401, 403, 404, 422];

const props = defineProps({
  video: { type: Object, required: true },
  // Arquivo escolhido no seletor ("Enviar novo"): o envio comeca sozinho.
  arquivoInicial: { type: Object, default: null },
});

const emit = defineEmits(['atualizado', 'arquivo-consumido']);

const { enviar, cancelar, progresso, estado, erro: erroEnvio, statusFinal, rotuloFinal } = useUploadEmPartes({ videoId: () => props.video.id });

const erroArquivo = ref(null);
const substituindo = ref(false);
const desistiuDeAcompanhar = ref(false);

const erro = computed(() => erroArquivo.value ?? erroEnvio.value);
const emEnvio = computed(() => estado.value === 'enviando' || estado.value === 'finalizando');
const emProcessamento = computed(() => ['ENVIADO', 'PROCESSANDO'].includes(props.video.status));
// Logo apos o envio o status da prop pode ainda nao ter alcancado o servidor.
const aguardandoProcessamento = computed(() => estado.value === 'enviado' && !STATUS_TERMINAIS.includes(props.video.status));
const acompanhar = computed(() => props.video.pode_gerenciar
  && !desistiuDeAcompanhar.value
  && (emProcessamento.value || aguardandoProcessamento.value));
const podeSubstituir = computed(() => ['PRONTO', 'FALHOU'].includes(props.video.status) && !substituindo.value && !emEnvio.value);
const mostrarZona = computed(() => !emEnvio.value
  && (substituindo.value || (estado.value !== 'enviado' && ['SEM_VIDEO', 'UPLOAD_ABERTO'].includes(props.video.status))));

// ---- Acompanhamento do processamento ---------------------------------------

let intervalo = null;
let consultaAtual = null;

function mesclar(dados) {
  return {
    ...props.video,
    status: dados.status,
    status_label: dados.status_label,
    duracao_segundos: dados.duracao_segundos,
    erro: dados.erro,
    tem_versao: dados.hls_versao != null,
    upload_aberto: dados.upload,
  };
}

async function consultar() {
  if (consultaAtual) return;
  consultaAtual = new AbortController();
  try {
    const { data } = await window.axios.get(route('treinamentos.professor.videos.status', props.video.id), { signal: consultaAtual.signal });
    emit('atualizado', mesclar(data));
  } catch (e) {
    // Rede e 5xx: a proxima consulta tenta de novo. O resto nao se resolve sozinho.
    if (ERROS_DEFINITIVOS.includes(e.response?.status)) {
      desistiuDeAcompanhar.value = true;
      pararAcompanhamento();
    }
  } finally {
    consultaAtual = null;
  }
}

function pararAcompanhamento() {
  clearInterval(intervalo);
  intervalo = null;
  consultaAtual?.abort();
  consultaAtual = null;
}

watch(acompanhar, (ativo) => {
  pararAcompanhamento();
  if (ativo) intervalo = setInterval(consultar, INTERVALO_STATUS_MS);
}, { immediate: true });

watch(estado, (novo) => {
  if (novo !== 'enviado') return;
  substituindo.value = false;
  desistiuDeAcompanhar.value = false;
  // Estado devolvido por finalizar; sem rotulo na resposta, a consulta imediata traz o status.
  if (rotuloFinal.value) emit('atualizado', { ...props.video, status: statusFinal.value, status_label: rotuloFinal.value, erro: null });
  consultar();
});

onUnmounted(pararAcompanhamento);

// ---- Escolha do arquivo -----------------------------------------------------

function iniciar(arquivo) {
  erroArquivo.value = null;
  enviar(arquivo);
}

watch(() => props.arquivoInicial, (arquivo) => {
  if (!arquivo) return;
  emit('arquivo-consumido');
  iniciar(arquivo);
}, { immediate: true });

function cancelarSubstituicao() {
  substituindo.value = false;
  erroArquivo.value = null;
}

async function reprocessar() {
  erroArquivo.value = null;
  try {
    await window.axios.post(route('treinamentos.professor.videos.reprocessar', props.video.id));
    await consultar();
  } catch (e) {
    erroArquivo.value = e.response?.data?.message || 'Não foi possível reprocessar o vídeo.';
  }
}
</script>

<template>
  <div v-if="video.pode_gerenciar" class="space-y-2">
    <ZonaArquivoVideo v-if="mostrarZona" @arquivo="iniciar" @erro="erroArquivo = $event" />

    <Button v-if="substituindo && mostrarZona" size="sm" variant="outline" @click="cancelarSubstituicao">Cancelar substituição</Button>
    <Text v-if="(substituindo || podeSubstituir) && video.usos > 1" size="xs" color="muted">
      Este vídeo é usado em {{ video.usos }} itens; substituir o arquivo altera todos.
    </Text>

    <Text v-if="desistiuDeAcompanhar" size="xs" color="muted">Não foi possível acompanhar o processamento; recarregue a página.</Text>

    <div v-if="emEnvio" class="space-y-1">
      <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
        <div class="h-full rounded-full bg-sky-600 transition-all" :style="{ width: `${progresso}%` }" />
      </div>
      <div class="flex items-center justify-between gap-2">
        <Text size="xs" color="muted">
          {{ estado === 'finalizando' ? 'Finalizando o envio...' : `Enviando: ${progresso}%` }}
        </Text>
        <Button v-if="estado === 'enviando'" size="sm" variant="outline" @click="cancelar">Cancelar envio</Button>
      </div>
    </div>

    <Text v-if="estado === 'cancelado'" size="xs" color="muted">Envio cancelado.</Text>
    <p v-if="erro" class="text-sm text-red-600">{{ erro }}</p>
    <p v-else-if="video.status === 'FALHOU' && video.erro" class="text-sm text-red-600">{{ video.erro }}</p>

    <Text v-if="video.status === 'PRONTO' && video.duracao_segundos != null" size="xs" color="muted">
      Duração: {{ formatarDuracao(video.duracao_segundos) }}
    </Text>

    <div v-if="podeSubstituir" class="flex flex-wrap gap-2">
      <Button v-if="video.status === 'FALHOU'" size="sm" variant="outline" @click="reprocessar">Tentar de novo</Button>
      <Button size="sm" variant="outline" @click="substituindo = true">Substituir arquivo</Button>
    </div>
  </div>
</template>
