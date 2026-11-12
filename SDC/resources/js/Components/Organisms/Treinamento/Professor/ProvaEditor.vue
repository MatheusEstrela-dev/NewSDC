<script setup>
import { ref, watch } from 'vue';
import draggable from '@/lib/vuedraggable-src/vuedraggable.js';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import QuestaoFormModal from '@/Components/Organisms/Treinamento/Professor/QuestaoFormModal.vue';
import { useToast } from '@/Composables/useToast';
import { mensagensDeErro, useProvaApi } from '@/Composables/treinamento/useProvaApi';

/**
 * Editor de uma prova: configuracao (nota minima, tentativas) e lista de
 * questoes com criar, editar, reordenar e excluir.
 */
const props = defineProps({
  treinamentoId: { type: Number, required: true },
  provaId: { type: Number, required: true },
});
const emit = defineEmits(['alterado']);

const api = useProvaApi(props.treinamentoId);
const { show: toast } = useToast();

const prova = ref(null);
const questoes = ref([]);
const carregando = ref(false);
const falhaAoCarregar = ref('');

const configuracao = ref({ nota_minima: 70, max_tentativas: 3 });
const salvandoConfig = ref(false);
const errosConfig = ref([]);

const modalAberto = ref(false);
const questaoEmEdicao = ref(null);

function aplicar(dados) {
  prova.value = dados;
  questoes.value = dados.questoes.map((q) => ({ ...q }));
  configuracao.value = { nota_minima: dados.nota_minima, max_tentativas: dados.max_tentativas };
}

async function carregar() {
  carregando.value = true;
  falhaAoCarregar.value = '';
  errosConfig.value = [];
  try {
    aplicar(await api.carregar(props.provaId));
  } catch (e) {
    prova.value = null;
    falhaAoCarregar.value = mensagensDeErro(e, 'Nao foi possivel carregar a prova.')[0];
  } finally {
    carregando.value = false;
  }
}

watch(() => props.provaId, carregar, { immediate: true });

async function salvarConfiguracao() {
  salvandoConfig.value = true;
  errosConfig.value = [];
  try {
    aplicar(await api.configurar(props.provaId, {
      nota_minima: Number(configuracao.value.nota_minima),
      max_tentativas: Number(configuracao.value.max_tentativas),
    }));
    toast('Configuracao da prova salva.');
    emit('alterado');
  } catch (e) {
    errosConfig.value = mensagensDeErro(e);
  } finally {
    salvandoConfig.value = false;
  }
}

function abrirQuestao(questao = null) {
  questaoEmEdicao.value = questao;
  modalAberto.value = true;
}

async function salvarQuestao(corpo) {
  if (questaoEmEdicao.value) {
    await api.atualizarQuestao(props.provaId, questaoEmEdicao.value.id, corpo);
  } else {
    await api.criarQuestao(props.provaId, corpo);
  }
  await carregar();
  emit('alterado');
}

async function excluirQuestao(questao) {
  const aviso = 'Excluir esta questao? Se alunos ja responderam, a questao deixa de valer para novas tentativas, mas as respostas antigas continuam registradas.';
  if (!window.confirm(aviso)) return;
  try {
    await api.excluirQuestao(props.provaId, questao.id);
    await carregar();
    emit('alterado');
  } catch (e) {
    toast(mensagensDeErro(e)[0], 'error');
  }
}

async function persistirOrdem() {
  try {
    await api.ordenarQuestoes(props.provaId, questoes.value.map((q) => q.id));
  } catch (e) {
    toast(mensagensDeErro(e)[0], 'error');
    await carregar();
  }
}

function mover(indice, delta) {
  const destino = indice + delta;
  if (destino < 0 || destino >= questoes.value.length) return;
  const copia = [...questoes.value];
  [copia[indice], copia[destino]] = [copia[destino], copia[indice]];
  questoes.value = copia;
  persistirOrdem();
}

const campo = 'w-24 rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800';
</script>

<template>
  <div class="space-y-4">
    <CardBase v-if="carregando && !prova">
      <Text color="muted">Carregando a prova...</Text>
    </CardBase>

    <CardBase v-else-if="falhaAoCarregar">
      <p role="alert" class="text-sm text-red-600">{{ falhaAoCarregar }}</p>
    </CardBase>

    <template v-else-if="prova">
      <CardBase>
        <Heading :level="3" class="text-base font-semibold">{{ prova.titulo }}</Heading>
        <form class="mt-3 flex flex-wrap items-end gap-4" @submit.prevent="salvarConfiguracao">
          <div>
            <label for="prova-nota" class="mb-1 block text-sm font-medium">Nota minima (0 a 100)</label>
            <input id="prova-nota" v-model="configuracao.nota_minima" type="number" min="0" max="100" :class="campo">
          </div>
          <div>
            <label for="prova-tentativas" class="mb-1 block text-sm font-medium">Maximo de tentativas</label>
            <input id="prova-tentativas" v-model="configuracao.max_tentativas" type="number" min="1" max="20" :class="campo">
          </div>
          <Button type="submit" size="sm" variant="outline" :loading="salvandoConfig">Salvar configuracao</Button>
        </form>
        <ul v-if="errosConfig.length" role="alert" class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">
          <li v-for="(erro, indice) in errosConfig" :key="indice">{{ erro }}</li>
        </ul>
      </CardBase>

      <div class="flex flex-wrap items-center justify-between gap-3">
        <Text color="muted">{{ questoes.length }} questao(oes)</Text>
        <Button size="sm" @click="abrirQuestao()">+ Nova questao</Button>
      </div>

      <CardBase v-if="questoes.length === 0">
        <Text color="muted">Esta prova ainda nao tem questoes. Sem questoes, o curso nao pode ser publicado.</Text>
      </CardBase>

      <draggable
        v-model="questoes"
        item-key="id"
        handle=".handle-questao"
        :animation="200"
        class="space-y-3"
        @end="persistirOrdem"
      >
        <template #item="{ element: questao, index }">
          <CardBase>
            <div class="flex items-start gap-3">
              <span class="handle-questao cursor-grab select-none text-slate-400" title="Arraste para reordenar" aria-hidden="true">::</span>
              <div class="min-w-0 flex-1">
                <p class="whitespace-pre-line text-sm font-medium">{{ index + 1 }}. {{ questao.enunciado }}</p>
                <ul class="mt-2 space-y-1">
                  <li v-for="alternativa in questao.alternativas" :key="alternativa.id" class="flex items-start gap-2 text-sm">
                    <Badge v-if="alternativa.correta" variant="success" size="sm">Correta</Badge>
                    <span v-else class="w-[4.5rem] shrink-0" />
                    <span class="min-w-0">{{ alternativa.texto }}</span>
                  </li>
                </ul>
              </div>
              <div class="flex flex-wrap justify-end gap-2">
                <Button size="sm" variant="outline" :disabled="index === 0" :aria-label="`Subir a questao ${index + 1}`" @click="mover(index, -1)">Subir</Button>
                <Button size="sm" variant="outline" :disabled="index === questoes.length - 1" :aria-label="`Descer a questao ${index + 1}`" @click="mover(index, 1)">Descer</Button>
                <Button size="sm" variant="outline" @click="abrirQuestao(questao)">Editar</Button>
                <Button size="sm" variant="danger" @click="excluirQuestao(questao)">Excluir</Button>
              </div>
            </div>
          </CardBase>
        </template>
      </draggable>
    </template>

    <QuestaoFormModal
      :show="modalAberto"
      :questao="questaoEmEdicao"
      :salvar="salvarQuestao"
      @fechar="modalAberto = false"
    />
  </div>
</template>
