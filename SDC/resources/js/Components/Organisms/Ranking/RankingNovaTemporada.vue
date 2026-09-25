<template>
  <Modal :show="show" max-width="md" centralizado @close="$emit('fechar')">
    <div class="temporada p-6 text-center" data-nova-temporada>
      <p class="text-xs font-bold uppercase tracking-[0.25em] text-amber-600 dark:text-amber-400">{{ comecouAgora ? 'Nova rodada' : 'Temporada em andamento' }}</p>
      <h2 class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white">
        {{ comecouAgora ? 'Nova temporada começou!' : `Faltam ${numero(temporada.dias_restantes)} dias` }}
      </h2>
      <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
        {{ rotuloTrimestre(temporada.chave, { comMeses: true }) }} · {{ diaMes(temporada.inicio) }} a {{ diaMes(temporada.fim) }}
      </p>

      <section v-if="temporada.anterior" class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left dark:border-slate-700 dark:bg-slate-900/60" data-temporada-anterior>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Como você terminou o {{ rotuloTrimestre(temporada.anterior.chave) }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-3">
          <div>
            <p class="text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ temporada.anterior.posicao ? `${numero(temporada.anterior.posicao)}º` : '—' }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">posição final</p>
          </div>
          <div>
            <p class="text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ numero(temporada.anterior.pontos) }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">pontos</p>
          </div>
          <div>
            <RankingFaixaBadge :faixa="temporada.anterior.faixa" />
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">faixa alcançada</p>
          </div>
        </div>
      </section>

      <p class="mt-5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
        O placar da temporada recomeça do zero para todos a cada trimestre. Seus pontos acumulados e o histórico continuam guardados.
      </p>

      <Button variant="primary" class="mt-6" data-temporada-fechar @click="$emit('fechar')">
        {{ comecouAgora ? 'Começar a temporada' : 'Entendi' }}
      </Button>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Aviso de virada de temporada (trimestre): explica que o placar recomecou,
 * mostra o resultado do usuario na temporada anterior e lembra que o
 * acumulado continua. Na estreia (`comecouAgora`) o titulo e "Nova temporada
 * comecou"; para quem so abre o ranking no meio da rodada, vira "Faltam N
 * dias".
 *
 * Quem decide QUANDO mostrar e a pagina (uma vez por temporada).
 */
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';
import { diaMes, rotuloTrimestre } from '@/Support/rankingTemporada';

defineProps({
  show: { type: Boolean, default: false },
  temporada: { type: Object, required: true },
  // Primeiras semanas da rodada; decidido pela pagina, que tambem larga a
  // bandeirada no mesmo criterio.
  comecouAgora: { type: Boolean, default: false },
});
defineEmits(['fechar']);

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
</script>
