<template>
  <section aria-label="Pódio do placar" class="podio rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800 sm:p-6">
    <header class="relative flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-xs font-bold uppercase tracking-widest text-amber-700 dark:text-amber-400">Cada entrega conta</p>
        <h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Pódio do placar</h2>
        <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
          Três primeiras posições por {{ rotuloDoEscopo }} no recorte atual. Empates compartilham o degrau.
        </p>
      </div>
      <span v-if="colocados.length" class="rounded-full border border-slate-200 bg-white/70 px-3 py-1.5 text-xs text-slate-500 dark:border-slate-600 dark:bg-slate-900/40 dark:text-slate-300">
        Selecione um destaque para comparar
      </span>
    </header>

    <p v-if="!colocados.length" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
      Ainda não há colocados neste recorte.
    </p>

    <template v-else>
      <ol class="podio__degraus" aria-label="Primeiras posições">
        <li v-for="grupo in grupos" :key="grupo.posicao" class="podio__degrau" :class="`podio__degrau--${grupo.posicao}`">
          <div class="podio__medalha"><RankingMedalha :posicao="grupo.posicao" /></div>
          <div class="podio__plataforma">
            <div class="mb-4 flex flex-wrap items-center justify-center gap-2">
              <h3 class="text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300">{{ grupo.posicao }}º lugar</h3>
              <span v-if="grupo.linhas.length > 1" class="rounded-full bg-white/70 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">Empate</span>
            </div>
            <ul class="space-y-2">
              <li v-for="colocado in grupo.linhas" :key="colocado.entidade_id">
                <button
                  type="button"
                  class="podio__participante w-full rounded-xl border p-4 text-center"
                  :class="selecionado === colocado ? 'podio__participante--ativo' : ''"
                  :aria-pressed="selecionado === colocado"
                  :aria-label="`Comparar ${identificacao(colocado)}, ${colocado.posicao}º lugar, ${pontos(colocado.pontos)} pontos`"
                  @click="selecionadoId = colocado.entidade_id"
                >
                  <span class="block break-words text-sm font-semibold text-slate-800 dark:text-slate-100">{{ identificacao(colocado) }}</span>
                  <span class="mt-2 block text-3xl font-extrabold tabular-nums tracking-tight text-slate-900 dark:text-white">{{ pontos(colocado.pontos) }}</span>
                  <span class="mb-3 block text-xs text-slate-500 dark:text-slate-400">pontos no período</span>
                  <RankingFaixaBadge :faixa="colocado.faixa" size="sm" />
                </button>
              </li>
            </ul>
          </div>
        </li>
      </ol>

      <div v-if="selecionado" class="podio__comparacao relative mt-5 rounded-xl border border-slate-200 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-900/50" role="status" aria-live="polite" aria-atomic="true">
        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
          <div class="min-w-0">
            <p class="text-xs text-slate-500 dark:text-slate-400">Destaque selecionado</p>
            <p class="mt-1 break-words text-sm font-semibold text-slate-800 dark:text-slate-100">{{ identificacao(selecionado) }}</p>
          </div>
          <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
            {{ distanciaDaLideranca === 0 ? 'Na liderança do recorte' : `${pontos(distanciaDaLideranca)} pontos até a liderança` }}
          </p>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" aria-hidden="true">
          <div class="podio__progresso h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-300" :style="{ width: `${percentualDaLideranca}%` }" />
        </div>
        <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">A medalha indica a posição. A faixa de atividade reflete o saldo de pontos.</p>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';
import RankingMedalha from '@/Components/Atoms/Ranking/RankingMedalha.vue';

const ROTULOS_DE_ESCOPO = { usuario: 'participante', orgao: 'órgão', municipio: 'município' };
const props = defineProps({
  linhas: { type: Array, default: () => [] },
  escopo: { type: String, default: 'usuario' },
});

const selecionadoId = ref(null);
const rotuloDoEscopo = computed(() => ROTULOS_DE_ESCOPO[props.escopo] ?? 'participante');
// A posição vem do backend. Agrupar por posição mantém todos os empates.
const colocados = computed(() => props.linhas
  .map((linha) => ({ ...linha, posicao: Number(linha?.posicao) }))
  .filter((linha) => Number.isInteger(linha.posicao) && linha.posicao >= 1 && linha.posicao <= 3)
  .sort((a, b) => a.posicao - b.posicao || Number(b.pontos ?? 0) - Number(a.pontos ?? 0)));
const grupos = computed(() => [1, 2, 3]
  .map((posicao) => ({ posicao, linhas: colocados.value.filter((linha) => linha.posicao === posicao) }))
  .filter((grupo) => grupo.linhas.length));
const selecionado = computed(() => colocados.value.find((linha) => linha.entidade_id === selecionadoId.value) ?? colocados.value[0]);
const pontosDaLideranca = computed(() => Number(colocados.value[0]?.pontos ?? 0));
const distanciaDaLideranca = computed(() => Math.max(0, pontosDaLideranca.value - Number(selecionado.value?.pontos ?? 0)));
const percentualDaLideranca = computed(() => pontosDaLideranca.value > 0
  ? Math.max(0, Math.min(100, Number(selecionado.value?.pontos ?? 0) / pontosDaLideranca.value * 100))
  : distanciaDaLideranca.value === 0 ? 100 : 0);
const formatador = new Intl.NumberFormat('pt-BR');
const pontos = (valor) => formatador.format(Number(valor ?? 0));
const identificacao = (colocado) => colocado.rotulo || `#${colocado.entidade_id}`;
</script>

<style scoped>
.podio { position: relative; isolation: isolate; overflow: hidden; }
.podio::before { content: ''; position: absolute; z-index: -1; inset: 0; pointer-events: none; background: radial-gradient(ellipse at 50% 0, rgb(245 158 11 / 12%), transparent 65%); }
.podio__degraus { display: grid; grid-template-columns: minmax(0, 1fr); gap: 24px; margin: 28px 0 0; padding: 0; list-style: none; }
.podio__degrau { --degrau-cor: 245 158 11; min-width: 0; }
.podio__degrau--2 { --degrau-cor: 148 163 184; }
.podio__degrau--3 { --degrau-cor: 194 120 67; }
.podio__medalha { position: relative; z-index: 1; display: flex; justify-content: center; margin-bottom: -15px; transition: transform .25s ease; }
.podio__degrau:focus-within .podio__medalha { transform: translateY(-5px) rotate(-3deg); }
.podio__plataforma { padding: 30px 12px 14px; border: 1px solid rgb(var(--degrau-cor) / 35%); border-top: 3px solid rgb(var(--degrau-cor) / 65%); border-radius: 18px; background: linear-gradient(180deg, rgb(var(--degrau-cor) / 12%), rgb(var(--degrau-cor) / 3%)); }
.podio__participante { border-color: transparent; background: rgb(255 255 255 / 55%); transition: background .2s ease, border-color .2s ease, transform .2s ease, box-shadow .2s ease; }
.dark .podio__participante { background: rgb(15 23 42 / 25%); }
.podio__participante--ativo { border-color: rgb(var(--degrau-cor) / 60%); box-shadow: 0 4px 18px rgb(var(--degrau-cor) / 10%); }
.podio__participante:focus-visible { outline: 3px solid #3b82f6; outline-offset: 3px; }
.podio__progresso { transition: width .35s ease; }
@media (hover: hover) {
  .podio__degrau:hover .podio__medalha { transform: translateY(-5px) rotate(-3deg); }
  .podio__participante:hover { transform: translateY(-2px); border-color: rgb(var(--degrau-cor) / 60%); background: rgb(var(--degrau-cor) / 10%); }
}
@media (min-width: 768px) {
  .podio__degraus { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; align-items: start; }
  .podio__degrau--1 { grid-column: 2; grid-row: 1; }
  .podio__degrau--2 { grid-column: 1; grid-row: 1; padding-top: 36px; }
  .podio__degrau--3 { grid-column: 3; grid-row: 1; padding-top: 60px; }
  .podio__degrau--1 .podio__plataforma { padding-bottom: 28px; }
}
@media (prefers-reduced-motion: reduce) {
  .podio__medalha, .podio__participante, .podio__progresso { transition: none; }
  .podio__degrau:hover .podio__medalha, .podio__degrau:focus-within .podio__medalha, .podio__participante:hover { transform: none; }
}
</style>
