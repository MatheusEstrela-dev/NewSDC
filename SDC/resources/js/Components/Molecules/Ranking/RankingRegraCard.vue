<template>
  <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">{{ nomeModuloRanking(regra.modulo) }}</span>
      <div class="flex flex-wrap gap-1">
        <Badge v-if="regraDemonstracao(regra)" cor="violet" size="sm">Demonstração</Badge>
        <Badge :cor="regra.habilitada ? 'emerald' : 'slate'" size="sm">{{ regra.habilitada ? 'Habilitada' : 'Desabilitada' }}</Badge>
      </div>
    </div>
    <h3 class="mt-3 break-words text-base font-bold text-slate-900 dark:text-slate-100">{{ tituloRegraRanking(regra) }}</h3>
    <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
      <p class="text-slate-900 dark:text-white">
        <span class="text-3xl font-extrabold tabular-nums">{{ numero(regra.pontos_base) }}</span>
        <span class="ml-1 text-xs text-slate-500 dark:text-slate-400">pontos de base</span>
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <Badge :cor="regra.aceita_bonus ? 'amber' : 'slate'" size="sm">{{ rotuloBonus }}</Badge>
        <Button variant="outline" size="sm" aria-haspopup="dialog" :aria-label="`${podeGerenciar ? 'Gerenciar' : 'Ver detalhes da'} regra ${tituloRegraRanking(regra)}`" @click="aberto = true">
          {{ podeGerenciar ? 'Gerenciar' : 'Detalhes' }}
        </Button>
      </div>
    </div>

    <!-- Card enxuto: condicoes, vigencia e acoes ficam no mini modal, para a
         grade nao esticar quando um card e aberto.

         Montado so no clique (v-if), por dois motivos: o editor de bonus nasce
         limpo a cada abertura, sem herdar rascunho cancelado; e o modal e
         teleportado para o body DEPOIS do modal de regras - montado junto com o
         card, entraria antes e, com o mesmo z-index, ficaria atras dele. -->
    <Modal v-if="aberto" :show="true" max-width="lg" centralizado @close="aberto = false">
      <div class="space-y-5 p-5">
        <header>
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">{{ nomeModuloRanking(regra.modulo) }}</span>
            <div class="flex flex-wrap gap-1">
              <Badge v-if="regraDemonstracao(regra)" cor="violet" size="sm">Demonstração</Badge>
              <Badge :cor="regra.habilitada ? 'emerald' : 'slate'" size="sm">{{ regra.habilitada ? 'Habilitada' : 'Desabilitada' }}</Badge>
            </div>
          </div>
          <h3 class="mt-2 break-words text-lg font-bold text-slate-900 dark:text-slate-100">{{ tituloRegraRanking(regra) }}</h3>
          <p class="mt-1 text-sm text-slate-900 dark:text-white">
            <span class="font-extrabold tabular-nums">{{ numero(regra.pontos_base) }}</span>
            <span class="text-slate-500 dark:text-slate-400"> pontos de base · {{ rotuloBonus }}</span>
          </p>
        </header>

        <section class="space-y-3 text-xs leading-relaxed text-slate-600 dark:text-slate-300">
          <p>{{ disponibilidadeRegraRanking(regra) }}</p>
          <p>{{ regra.aceita_bonus ? `O bônus de ${numero(regra.bonus_percentual)}% só se aplica quando a entrega atende aos critérios de elegibilidade da regra. Não é automático.` : 'Esta regra não prevê bônus. O valor de base é a referência para a pontuação da entrega.' }}</p>
          <dl class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-900/60">
            <div><dt class="text-slate-500 dark:text-slate-400">Versão</dt><dd class="font-semibold">{{ regra.versao ?? '—' }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Vigência</dt><dd class="font-semibold">{{ data(regra.vigente_de) }}<template v-if="regra.vigente_ate"> até {{ data(regra.vigente_ate) }}</template><template v-else> · sem término definido</template></dd></div>
          </dl>
        </section>

        <template v-if="podeGerenciar">
          <section class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
            <div>
              <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Situação da regra</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">Regras desabilitadas não pontuam.</p>
            </div>
            <button type="button" :disabled="salvando" class="rounded-lg border border-blue-500 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500 disabled:cursor-wait disabled:opacity-50 dark:text-blue-300 dark:hover:bg-blue-500/10" :aria-label="`${regra.habilitada ? 'Desativar' : 'Habilitar'} regra ${tituloRegraRanking(regra)}`" @click="$emit('alternar', regra)">
              {{ salvando ? 'Salvando...' : regra.habilitada ? 'Desativar' : 'Habilitar' }}
            </button>
          </section>

          <section class="border-t border-slate-200 pt-4 dark:border-slate-700">
            <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Pontuação</p>
            <RankingPontuacaoEditor
              :pontos-base="Number(regra.pontos_base ?? 0)"
              :aceita-bonus="Boolean(regra.aceita_bonus)"
              :bonus-percentual="Number(regra.bonus_percentual ?? 0)"
              :teto="teto"
              :salvando="salvando"
              :erros="erros"
              @salvar="$emit('salvar-regra', regra, $event, () => { aberto = false; })"
              @cancelar="aberto = false"
            />
          </section>
        </template>

        <div v-else class="flex justify-end border-t border-slate-200 pt-4 dark:border-slate-700">
          <Button variant="outline" size="sm" @click="aberto = false">Fechar</Button>
        </div>
      </div>
    </Modal>
  </article>
</template>

<script setup>
import { computed, ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Modal from '@/Components/Modal.vue';
import RankingPontuacaoEditor from '@/Components/Molecules/Ranking/RankingPontuacaoEditor.vue';
import { nomeModuloRanking, tituloRegraRanking, regraDemonstracao, disponibilidadeRegraRanking } from '@/Support/rankingRegras';

const props = defineProps({ regra: { type: Object, required: true }, podeGerenciar: { type: Boolean, default: false }, salvando: { type: Boolean, default: false }, erros: { type: Object, default: () => ({}) }, teto: { type: Number, default: 500 } });
defineEmits(['alternar', 'salvar-regra']);
const aberto = ref(false);
const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
const rotuloBonus = computed(() => (props.regra.aceita_bonus ? `Bônus de ${numero(props.regra.bonus_percentual)}%` : 'Sem bônus'));
const data = (valor) => {
  const partes = String(valor ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
  return partes ? `${partes[3]}/${partes[2]}/${partes[1]}` : 'Não informada';
};
</script>
