<script setup>
/**
 * Os proximos turnos do usuario logado.
 *
 * Existe para o telefone. No celular o calendario mensal nao serve para "quando
 * eu trabalho?" -- o plantonista teria que procurar o proprio nome entre os dos
 * colegas numa grade de trinta dias. Aqui a resposta esta na primeira linha.
 *
 * No desktop a lista continua util como coluna lateral, ao lado do calendario
 * da equipe: uma visao pessoal e uma coletiva, sem trocar de tela.
 */
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

defineProps({
  vagas: {
    type: Array,
    default: () => [],
  },
  // Assumir turno exige a permissao de abrir plantao, resolvida no servidor.
  podeAssumir: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['assumir']);
</script>

<template>
  <section class="rounded-lg border border-slate-200 bg-white dark:border-slate-700/50 dark:bg-slate-800/50">
    <header class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-700/50">
      <ClockIcon class="h-4 w-4 text-slate-400" />
      <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Meus proximos plantoes</h2>
    </header>

    <ListEmptyState
      v-if="!vagas.length"
      title="Nenhum plantao a frente"
      helper="Voce nao tem turnos escalados neste mes."
      class="py-8"
    />

    <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700/50">
      <li
        v-for="vaga in vagas"
        :key="vaga.itemId"
        class="flex items-center gap-3 px-4 py-3"
      >
        <!--
          Cor vem do banco e vai inline: classe Tailwind montada em tempo de
          execucao seria purgada do bundle, porque o Tailwind so escaneia o
          codigo-fonte.
        -->
        <span
          class="h-9 w-1 shrink-0 rounded-full"
          :style="{ backgroundColor: vaga.cor }"
          aria-hidden="true"
        ></span>

        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ vaga.diaSemana }}, {{ vaga.dataLabel }}
          </p>
          <p class="truncate text-xs text-slate-500 dark:text-slate-400">
            {{ vaga.tipoLabel }}
          </p>
        </div>

        <span
          v-if="vaga.jaAssumida"
          class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
        >
          Assumido
        </span>

        <button
          v-else-if="podeAssumir && vaga.podeAssumir"
          type="button"
          class="min-h-[2.25rem] shrink-0 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
          @click="emit('assumir', vaga.itemId)"
        >
          Assumir
        </button>
      </li>
    </ul>
  </section>
</template>
