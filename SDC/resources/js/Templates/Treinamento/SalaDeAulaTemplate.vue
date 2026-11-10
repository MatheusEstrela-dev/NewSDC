<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import ItemProjeto from '@/Components/Organisms/Treinamento/Sala/ItemProjeto.vue';
import PlayerVideo from '@/Components/Organisms/Treinamento/Sala/PlayerVideo.vue';
import TrilhaLateral from '@/Components/Organisms/Treinamento/Sala/TrilhaLateral.vue';
import VisualizadorPdf from '@/Components/Organisms/Treinamento/Sala/VisualizadorPdf.vue';
import { useProgressoVideo } from '@/Composables/treinamento/useProgressoVideo';
import { useRotasSala } from '@/Composables/treinamento/useRotasSala';
import { useToast } from '@/Composables/useToast';

const ATRASO_PROGRESSO_MS = 1000;

const props = defineProps({
  curso: { type: Object, required: true },
  estado: { type: Object, required: true },
  escopo: { type: String, required: true, validator: (v) => ['aluno', 'portal', 'previa'].includes(v) },
});

const { show: toast } = useToast();

const previa = computed(() => props.escopo === 'previa');
const rotas = useRotasSala(props.escopo, props.escopo === 'portal' ? props.curso.slug : props.curso.id);

// Estado da trilha: nasce da pagina e e substituido pela resposta de cada progresso.
const estadoLocal = ref(props.estado);
watch(() => props.estado, (novo) => { estadoLocal.value = novo; });

const itens = computed(() => estadoLocal.value.pastas.flatMap((pasta, indice) => pasta.itens.map((item) => ({
  ...item,
  pasta: pasta.titulo,
  pastaIndice: indice + 1,
}))));

const itemAtualId = ref(estadoLocal.value.proximo_item_id ?? itens.value[0]?.id ?? null);

const indiceAtual = computed(() => itens.value.findIndex((i) => i.id === itemAtualId.value));
const itemAtual = computed(() => itens.value[indiceAtual.value] ?? null);
const anterior = computed(() => itens.value[indiceAtual.value - 1] ?? null);
const proximo = computed(() => itens.value[indiceAtual.value + 1] ?? null);
const podeAvancar = computed(() => proximo.value?.liberado === true);

// Progresso (pagina do PDF ou segundo do video): um envio por pausa de 1 s.
let temporizador = null;
let pendente = null;
let falhou = false;

async function enviarPendente() {
  clearTimeout(temporizador);
  temporizador = null;
  if (!pendente) return;

  const { url, posicao } = pendente;
  pendente = null;

  try {
    const { data } = await axios.post(url, { posicao });
    estadoLocal.value = data;
    falhou = false;
  } catch {
    if (!falhou) toast('Nao foi possivel salvar seu progresso agora. Voce pode continuar lendo.', 'warning');
    falhou = true;
  }
}

function registrarProgresso(item, valor) {
  const url = rotas.progresso(item.id);
  if (!url || valor <= item.posicao) return;

  if (pendente && pendente.url !== url) enviarPendente();
  pendente = { url, posicao: pendente?.url === url ? Math.max(pendente.posicao, valor) : valor };
  clearTimeout(temporizador);
  temporizador = setTimeout(enviarPendente, ATRASO_PROGRESSO_MS);
}

// Um controle de progresso por item de video, para lembrar o ponto ja enviado.
const progressosVideo = new Map();

function progressoVideo(item) {
  if (!progressosVideo.has(item.id)) {
    progressosVideo.set(item.id, useProgressoVideo({
      enviar: async (segundos) => registrarProgresso(item, segundos),
      duracao: item.video?.duracao_segundos ?? 0,
      posicaoInicial: item.posicao,
    }));
  }

  return progressosVideo.get(item.id);
}

function selecionar(id) {
  enviarPendente();
  itemAtualId.value = id;
}

onBeforeUnmount(enviarPendente);
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800/80">
    <div class="flex items-center gap-3 bg-slate-900 px-4 py-3 text-white">
      <Heading :level="1" class="min-w-0 flex-1 truncate text-sm font-bold sm:text-base">{{ curso.titulo }}</Heading>
      <span class="rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-semibold">{{ Math.round(estadoLocal.percentual) }}%</span>
      <Link :href="rotas.fechar()" class="text-xs hover:underline">Fechar &#10005;</Link>
    </div>

    <div v-if="previa" class="bg-amber-100 px-4 py-2 text-center text-sm font-medium text-amber-800">
      Pré-visualização: o progresso não é salvo
    </div>

    <div class="grid min-h-[520px] grid-cols-1 lg:grid-cols-[300px_1fr]">
      <TrilhaLateral :estado="estadoLocal" :item-atual-id="itemAtualId" @selecionar="selecionar" />

      <div class="flex min-w-0 flex-col">
        <template v-if="itemAtual">
          <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 px-5 py-3 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
            <span>Pasta {{ itemAtual.pastaIndice }} · {{ itemAtual.pasta }} &rsaquo;</span>
            <b class="text-slate-800 dark:text-slate-100">{{ itemAtual.titulo }}</b>
            <span class="flex-1" />
            <a
              v-if="itemAtual.tipo === 'PDF' && itemAtual.tem_arquivo"
              :href="rotas.arquivo(itemAtual.id, true)"
              class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
            >
              Baixar PDF
            </a>
          </div>

          <VisualizadorPdf
            v-if="itemAtual.tipo === 'PDF' && itemAtual.tem_arquivo"
            :key="itemAtual.id"
            :url="rotas.arquivo(itemAtual.id)"
            :posicao-inicial="itemAtual.posicao"
            :total-paginas="itemAtual.total_paginas ?? 1"
            @pagina="registrarProgresso(itemAtual, $event)"
          />
          <PlayerVideo
            v-else-if="itemAtual.tipo === 'VIDEO' && itemAtual.video?.url_master"
            :key="itemAtual.id"
            :url-master="itemAtual.video.url_master"
            :posicao-inicial="itemAtual.posicao"
            :duracao="itemAtual.video.duracao_segundos ?? 0"
            :concluido="itemAtual.status === 'CONCLUIDO'"
            @posicao="progressoVideo(itemAtual).aoPosicao($event)"
            @terminou="progressoVideo(itemAtual).aoTerminar()"
          />
          <p v-else-if="itemAtual.tipo === 'VIDEO'" class="p-6 text-sm text-slate-500">
            V�deo em prepara��o<template v-if="itemAtual.video?.status_label">: {{ itemAtual.video.status_label }}</template>
          </p>
          <ItemProjeto
            v-else-if="itemAtual.tipo === 'PROJETO'"
            :key="itemAtual.id"
            :item="itemAtual"
            :url-entrega="rotas.entrega(itemAtual.id)"
            :url-arquivo="rotas.arquivo(itemAtual.id, true)"
          />
          <p v-else class="p-6 text-sm text-slate-500">Este item ainda não tem arquivo.</p>
        </template>
        <p v-else class="p-6 text-sm text-slate-500">Este curso ainda não tem conteúdo.</p>

        <div class="mt-auto flex items-center gap-3 border-t border-slate-200 px-5 py-3 text-xs dark:border-slate-700">
          <Button variant="outline" size="sm" :disabled="!anterior" @click="selecionar(anterior.id)">&lsaquo; Anterior</Button>
          <span class="flex-1 text-center text-slate-500 dark:text-slate-400">
            <template v-if="!previa && itemAtual?.tipo === 'PDF' && itemAtual.posicao > 1">Retoma na página em que você parou</template>
          </span>
          <Button size="sm" :disabled="!podeAvancar" @click="selecionar(proximo.id)">Próximo &rsaquo;</Button>
        </div>
      </div>
    </div>
  </div>
</template>
