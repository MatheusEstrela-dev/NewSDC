<script setup>
import { computed, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { mensagensDeErro } from '@/Composables/treinamento/useProvaApi';

/**
 * Criar/editar uma questao: enunciado e 2 a 10 alternativas, exatamente uma correta.
 * `salvar` recebe o corpo pronto e devolve uma promise; erros 422 aparecem aqui.
 */
const MIN_ALTERNATIVAS = 2;
const MAX_ALTERNATIVAS = 10;

const props = defineProps({
  show: { type: Boolean, default: false },
  questao: { type: Object, default: null },
  salvar: { type: Function, required: true },
});
const emit = defineEmits(['fechar']);

let sequencia = 0;
const novaChave = () => `a${++sequencia}`;

const enunciado = ref('');
const alternativas = ref([]);
const corretaChave = ref(null);
const erros = ref([]);
const processando = ref(false);

function iniciar() {
  erros.value = [];
  const origem = props.questao?.alternativas ?? [{}, {}];
  alternativas.value = origem.map((a) => ({ chave: novaChave(), id: a.id ?? null, texto: a.texto ?? '' }));
  corretaChave.value = props.questao
    ? alternativas.value.find((_, i) => origem[i].correta)?.chave ?? null
    : null;
  enunciado.value = props.questao?.enunciado ?? '';
}

watch(() => props.show, (aberto) => { if (aberto) iniciar(); });

const podeAdicionar = computed(() => alternativas.value.length < MAX_ALTERNATIVAS);
const podeRemover = computed(() => alternativas.value.length > MIN_ALTERNATIVAS);

function adicionar() {
  if (podeAdicionar.value) alternativas.value.push({ chave: novaChave(), id: null, texto: '' });
}

function remover(alternativa) {
  if (!podeRemover.value) return;
  alternativas.value = alternativas.value.filter((a) => a.chave !== alternativa.chave);
  if (corretaChave.value === alternativa.chave) corretaChave.value = null;
}

function validar() {
  const problemas = [];
  if (!enunciado.value.trim()) problemas.push('Informe o enunciado da questao.');
  if (alternativas.value.some((a) => !a.texto.trim())) problemas.push('Preencha o texto de todas as alternativas.');
  if (alternativas.value.length < MIN_ALTERNATIVAS) problemas.push(`Use pelo menos ${MIN_ALTERNATIVAS} alternativas.`);
  if (corretaChave.value === null) problemas.push('Marque exatamente uma alternativa como correta.');
  return problemas;
}

async function enviar() {
  erros.value = validar();
  if (erros.value.length) return;

  processando.value = true;
  try {
    await props.salvar({
      enunciado: enunciado.value.trim(),
      alternativas: alternativas.value.map((a) => ({
        ...(a.id ? { id: a.id } : {}),
        texto: a.texto.trim(),
        correta: a.chave === corretaChave.value,
      })),
    });
    emit('fechar');
  } catch (e) {
    erros.value = mensagensDeErro(e);
  } finally {
    processando.value = false;
  }
}

const campo = 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800';
</script>

<template>
  <Modal :show="show" max-width="2xl" @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="enviar">
      <Heading :level="3" class="text-lg font-semibold">{{ questao ? 'Editar questao' : 'Nova questao' }}</Heading>

      <div
        v-if="questao"
        class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200"
      >
        Se alunos ja responderam esta questao, alterar o gabarito nao reavalia as tentativas ja enviadas.
        Alternativas removidas continuam registradas nas respostas antigas.
      </div>

      <ul v-if="erros.length" role="alert" class="list-disc space-y-1 pl-5 text-sm text-red-600">
        <li v-for="(erro, indice) in erros" :key="indice">{{ erro }}</li>
      </ul>

      <div>
        <label for="questao-enunciado" class="mb-1 block text-sm font-medium">Enunciado</label>
        <textarea id="questao-enunciado" v-model="enunciado" rows="3" :class="campo" />
      </div>

      <fieldset class="space-y-2">
        <legend class="mb-1 text-sm font-medium">Alternativas (marque a correta)</legend>
        <div v-for="(alternativa, indice) in alternativas" :key="alternativa.chave" class="flex items-center gap-2">
          <input
            v-model="corretaChave"
            type="radio"
            name="alternativa-correta"
            :value="alternativa.chave"
            :aria-label="`Alternativa ${indice + 1} e a correta`"
            class="h-4 w-4 shrink-0"
          >
          <input
            v-model="alternativa.texto"
            type="text"
            :aria-label="`Texto da alternativa ${indice + 1}`"
            :class="campo"
          >
          <Button type="button" size="sm" variant="outline" :disabled="!podeRemover" :aria-label="`Remover alternativa ${indice + 1}`" @click="remover(alternativa)">
            Remover
          </Button>
        </div>
        <Button type="button" size="sm" variant="outline" :disabled="!podeAdicionar" @click="adicionar">+ Alternativa</Button>
        <Text size="xs" color="muted">De {{ MIN_ALTERNATIVAS }} a {{ MAX_ALTERNATIVAS }} alternativas, uma correta.</Text>
      </fieldset>

      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="emit('fechar')">Cancelar</Button>
        <Button type="submit" :loading="processando">Salvar</Button>
      </div>
    </form>
  </Modal>
</template>
