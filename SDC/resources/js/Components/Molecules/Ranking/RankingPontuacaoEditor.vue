<template>
  <div class="space-y-4" data-pontuacao-editor>
    <div class="rounded-lg border border-blue-200 bg-blue-50/60 p-3 dark:border-blue-500/30 dark:bg-blue-950/20">
      <label :for="`${idBase}-base`" class="text-xs font-semibold text-slate-800 dark:text-slate-100">Pontos de base</label>
      <p class="text-xs text-slate-500 dark:text-slate-400">Quanto vale cada entrega aceita desta regra.</p>
      <div class="mt-2">
        <NumberStepper
          :id="`${idBase}-base`"
          v-model="base"
          :min="0"
          :max="teto"
          :passo="PASSO_BASE"
          :disabled="salvando"
          rotulo="pontos"
          data-base
        />
      </div>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-3 dark:border-amber-500/30 dark:bg-amber-950/20" data-bonus-editor>
      <div class="flex flex-wrap items-center justify-between gap-2">
        <span :id="`${idBase}-rotulo`" class="text-xs font-semibold text-slate-800 dark:text-slate-100">Bônus da regra</span>
        <button
          type="button"
          role="switch"
          :aria-checked="ligado"
          :aria-labelledby="`${idBase}-rotulo`"
          :disabled="salvando"
          class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 disabled:opacity-50"
          :class="ligado ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-600'"
          data-bonus-switch
          @click="ligado = !ligado"
        >
          <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition" :class="ligado ? 'translate-x-5' : 'translate-x-0.5'" />
        </button>
      </div>
      <div class="mt-3" :class="ligado ? '' : 'opacity-50'">
        <NumberStepper
          :id="`${idBase}-pct`"
          v-model="percentual"
          :min="0"
          :max="100"
          :passo="PASSO_BONUS"
          :disabled="!ligado || salvando"
          rotulo="%"
          data-bonus
        />
      </div>
      <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">O bônus só é pago quando a entrega tem prazo comprovado e foi feita dentro dele.</p>
    </div>

    <p class="text-xs text-slate-700 dark:text-slate-200" aria-live="polite" data-pontuacao-preview>
      <template v-if="ligado">Cada entrega rende até {{ numero(total) }} pontos ({{ numero(base) }} de base + {{ numero(pontosBonus) }} de bônus)</template>
      <template v-else>Cada entrega rende {{ numero(base) }} pontos de base, sem bônus</template>
    </p>

    <p v-if="acimaDoTeto" role="alert" class="text-xs font-semibold text-red-700 dark:text-red-300" data-pontuacao-teto>
      O total passa do teto de {{ numero(teto) }} pontos por lançamento. Reduza a base ou o bônus.
    </p>
    <p v-else-if="mensagemErro" role="alert" class="text-xs font-semibold text-red-700 dark:text-red-300" data-pontuacao-erro>{{ mensagemErro }}</p>

    <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">Cada alteração publica uma nova versão da regra; entregas anteriores mantêm a versão em vigor na data delas.</p>

    <div class="flex flex-wrap justify-end gap-2">
      <Button variant="outline" size="sm" :disabled="salvando" data-pontuacao-cancelar @click="$emit('cancelar')">Cancelar</Button>
      <Button variant="primary" size="sm" :disabled="!mudou || acimaDoTeto" :loading="salvando" data-pontuacao-salvar @click="salvar">{{ salvando ? 'Salvando...' : 'Salvar' }}</Button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import NumberStepper from '@/Components/Molecules/Form/NumberStepper.vue';

const props = defineProps({
  pontosBase: { type: Number, default: 0 },
  aceitaBonus: { type: Boolean, default: false },
  bonusPercentual: { type: Number, default: 0 },
  teto: { type: Number, default: 500 },
  salvando: { type: Boolean, default: false },
  erros: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['salvar', 'cancelar']);

const PASSO_BASE = 5;
const PASSO_BONUS = 10;
const idBase = `pontuacao-${useId()}`;

const base = ref(Math.trunc(Number(props.pontosBase) || 0));
const ligado = ref(props.aceitaBonus);
const percentual = ref(Math.trunc(Number(props.bonusPercentual) || 0));

// Mesma conta do backend: divisao inteira (base 3 com 20% rende 0).
const pontosBonus = computed(() => (ligado.value ? Math.floor((base.value * percentual.value) / 100) : 0));
const total = computed(() => base.value + pontosBonus.value);
const acimaDoTeto = computed(() => total.value > props.teto);
const mudou = computed(() => base.value !== Math.trunc(Number(props.pontosBase) || 0)
  || ligado.value !== props.aceitaBonus
  || percentual.value !== Math.trunc(Number(props.bonusPercentual) || 0));
const mensagemErro = computed(() => props.erros?.pontos_base || props.erros?.bonus_percentual || props.erros?.aceita_bonus || '');

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));

// Envia so o que mudou: publicar a regra inteira apagaria a intencao da versao.
function salvar() {
  if (!mudou.value || acimaDoTeto.value || props.salvando) return;
  const corpo = {};
  if (base.value !== Math.trunc(Number(props.pontosBase) || 0)) corpo.pontos_base = base.value;
  if (ligado.value !== props.aceitaBonus) corpo.aceita_bonus = ligado.value;
  if (percentual.value !== Math.trunc(Number(props.bonusPercentual) || 0)) corpo.bonus_percentual = percentual.value;
  emit('salvar', corpo);
}
</script>
