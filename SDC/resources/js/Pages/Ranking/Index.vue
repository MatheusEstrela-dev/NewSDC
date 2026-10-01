<template>
  <Head title="Meu placar" />

  <!--
    Raiz sem calha horizontal propria: a calha e do <main> do AuthenticatedLayout.
    O ritmo vertical vem do mb-6 de cada filho (nunca space-y-6 no pai junto).
  -->
  <div class="pb-6">
    <PageHeader
      title="Meu placar"
      description="Cada entrega conta. Pontos reconhecem entregas; a conformidade municipal é avaliada separadamente pelo IPCM."
      :icon="ClipboardDocumentListIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex w-full flex-wrap items-center justify-end gap-2">
          <Button
            v-if="regras.length && !indisponivel"
            variant="outline"
            :icon="BookOpenIcon"
            aria-haspopup="dialog"
            :aria-expanded="regrasAbertas"
            @click="regrasAbertas = true"
          >
            <span class="text-left">
              <span class="block">Regras do placar</span>
              <span class="block text-xs font-normal opacity-75">{{ numero(regras.length) }} regras · pontos e bônus</span>
            </span>
          </Button>
          <Link
            v-if="podeVerCarteira && !indisponivel"
            :href="route('resgate.carteira')"
            class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
            data-link-carteira
          >
            <span class="text-left">
              <span class="block">Carteira de resgate</span>
              <span class="block text-xs font-normal opacity-75">saldo do município</span>
            </span>
          </Link>

        </div>
      </template>
    </PageHeader>

    <!-- Catalogo em homologacao: o controller responde sem tocar na projecao. -->
    <div
      v-if="preview"
      role="status"
      class="mb-6 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-500/30 dark:bg-sky-950/40 dark:text-sky-100"
    >
      Pré-visualização do catálogo de regras. A simulação não grava pontos nem altera regras.
    </div>

    <div
      v-if="indisponivel"
      role="status"
      class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100"
    >
      O placar está temporariamente indisponível. Tente novamente mais tarde.
    </div>

    <template v-else>
      <p
        v-if="semContexto"
        role="status"
        class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100"
      >
        Não há vínculo institucional atual para esta visão. Consulte Meu placar.
      </p>

      <!--
        Cards do topo tambem sao filtros rapidos (padrao do projeto): o saldo volta
        ao recorte consolidado e a posicao leva a pagina do placar onde voce esta.
        Nao ha anel no card ativo.
      -->
      <StatCardsGrid v-if="resumo" :colunas="4">
        <StatCard
          title="Saldo confirmado"
          :value="resumo.pontos ?? 0"
          variant="info"
          subtitle="Pontos do recorte selecionado"
          :icon="BoltIcon"
          clickable
          @click="filtrarPorModulo('all')"
        />
        <StatCard
          title="Posição no período"
          :value="posicaoTexto"
          :format-number="false"
          variant="success"
          :subtitle="resumo.posicao ? 'Empates compartilham a posição' : 'Sem classificação neste recorte'"
          :icon="ClipboardDocumentListIcon"
          :clickable="podeIrParaMinhaPosicao"
          @click="irParaMinhaPosicao"
        />
        <StatCard
          title="Faixa de atividade"
          :value="faixaTexto"
          :format-number="false"
          variant="warning"
          subtitle="Calculada a partir do saldo confirmado"
          :icon="CheckBadgeIcon"
        />
        <StatCard
          title="Atualização do saldo"
          :value="dataHora(resumo.atualizado_em)"
          :format-number="false"
          variant="info"
          subtitle="Última materialização da projeção"
          :icon="ClockIcon"
        />
      </StatCardsGrid>

      <FilterSection
        title="Filtros do placar"
        :columns="4"
        :default-collapsed="false"
        class="mb-6"
      >
        <FilterField
          label="Visão"
          type="select"
          :model-value="local.escopo"
          :options="escopoOpcoes"
          @update:model-value="local.escopo = $event"
        />

        <FilterField
          label="Período"
          type="select"
          :model-value="local.tipoPeriodo"
          :options="tipoPeriodoOpcoes"
          @update:model-value="trocarTipoPeriodo($event)"
        />

        <!-- Select de valor no lugar do campo de texto com regex (mes:2026-09). -->
        <FilterField
          v-if="local.tipoPeriodo === 'trimestre'"
          label="Temporada"
          type="select"
          :model-value="local.trimestre"
          :options="trimestreOpcoes"
          @update:model-value="local.trimestre = $event"
        />
        <FilterField
          v-else-if="local.tipoPeriodo === 'mes'"
          label="Mês de competência"
          type="select"
          :model-value="local.mes"
          :options="mesOpcoes"
          @update:model-value="local.mes = $event"
        />
        <FilterField
          v-else-if="local.tipoPeriodo === 'ano'"
          label="Ano de competência"
          type="select"
          :model-value="local.ano"
          :options="anoOpcoes"
          @update:model-value="local.ano = $event"
        />

        <FilterField
          label="Módulo do placar"
          type="select"
          :model-value="local.modulo"
          :options="moduloOpcoes"
          @update:model-value="local.modulo = $event"
        />

        <div class="flex items-end justify-end pt-1 md:col-span-2 lg:col-span-4">
          <FilterActions @search="aplicar" @clear="limpar" />
        </div>
      </FilterSection>

      <!-- Virada de temporada: bandeirada de largada, depois o aviso. -->
      <RankingBandeirada :show="bandeiradaAberta" :subtitulo="temporada ? rotuloTrimestre(temporada.chave, { comMeses: true }) : ''" @fim="bandeiradaAberta = false" />
      <RankingNovaTemporada v-if="temporadaExibida" :show="novaTemporadaAberta && !bandeiradaAberta" :temporada="temporadaExibida" :comecou-agora="estreiaTemporada" @fechar="novaTemporadaAberta = false" />
      <!-- A celebracao de faixa espera o aviso de temporada fechar: um por vez. -->
      <RankingConquistaFaixa :show="conquistaFaixa !== null && !novaTemporadaAberta && !bandeiradaAberta" :faixa="conquistaFaixa ?? 'diamante'" @fechar="fecharConquistaFaixa" />

      <p v-if="temporada" class="mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500 dark:text-slate-400" data-temporada-selo>
        <span class="rounded-full bg-amber-100 px-2.5 py-1 font-bold uppercase tracking-wider text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">Temporada</span>
        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ rotuloTrimestre(temporada.chave, { comMeses: true }) }}</span>
        <span>· {{ diaMes(temporada.inicio) }} a {{ diaMes(temporada.fim) }} · faltam {{ numero(temporada.dias_restantes) }} dias</span>
      </p>

      <RankingPodio
        v-if="placar"
        class="mb-6"
        :linhas="podioLinhas"
        :escopo="filtros.escopo"
        @celebrar="conquistaFaixa = 'diamante'"
      />

      <section v-if="placar" class="mb-6" aria-label="Classificação estadual">
        <ListContainer
          class="hidden md:block"
          title="Classificação estadual"
          subtitle="Empates compartilham a posição. Quando o nome não puder ser consultado, o participante aparece pelo código."
          :count="placar.total ?? 0"
          :icon="ClipboardDocumentListIcon"
        >
          <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
              <tr>
                <th class="w-24 px-4 py-3 text-left">Posição</th>
                <th class="px-4 py-3 text-left">Participante</th>
                <th v-for="kpi in KPIS_PLACAR" :key="kpi.chave" class="whitespace-nowrap px-4 py-3" :class="kpi.alinhar" :title="kpi.ajuda">{{ kpi.titulo }}</th>
                <th class="px-4 py-3 text-right">Pontos</th>
                <th class="px-4 py-3 text-left">Faixa</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <template v-for="linha in placarLinhas" :key="linha.entidade_id">
              <tr v-if="linha.corteFaixa" class="bg-slate-50 dark:bg-slate-800/60" data-corte-faixa>
                <td :colspan="COLUNAS_PLACAR" class="px-4 py-2">
                  <RankingFaixaCorte :faixa="linha.faixa" :limiares="faixas" alinhar-colunas />
                </td>
              </tr>
              <tr class="table-row-solid transition">
                <td class="px-4 py-4">
                  <RankingPosicaoCell :posicao="linha.posicao" :medalha="linha.medalha" destacar-topo />
                </td>
                <td class="px-4 py-4" :class="corDoNome(linha)">
                  {{ rotuloParticipante(linha) }}
                </td>
                <td v-for="kpi in KPIS_PLACAR" :key="kpi.chave" class="whitespace-nowrap px-4 py-4 tabular-nums text-slate-600 dark:text-slate-300" :class="kpi.alinhar">
                  {{ kpi.valor(linha) }}
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold text-slate-900 dark:text-slate-100">
                  {{ numero(linha.pontos) }}
                </td>
                <td class="px-4 py-4">
                  <RankingFaixaBadge :faixa="linha.faixa" />
                </td>
              </tr>
              <tr v-if="linha.fimDoPodio" aria-hidden="true" data-fim-podio>
                <td :colspan="COLUNAS_PLACAR" class="px-4 py-1.5">
                  <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                    <span class="h-px flex-1 bg-gradient-to-r from-transparent to-slate-300 dark:to-slate-600" />
                    Demais colocados
                    <span class="h-px flex-1 bg-gradient-to-l from-transparent to-slate-300 dark:to-slate-600" />
                  </div>
                </td>
              </tr>
              </template>

              <tr v-if="placarLinhas.length === 0">
                <td :colspan="COLUNAS_PLACAR" class="px-4 py-12 text-center">
                  <ClipboardDocumentListIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
                  <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhum participante neste recorte</p>
                  <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajuste a visão, o período ou o módulo.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </ListContainer>

        <!-- Mobile: a tabela vira cards (a pagina nao rola de lado em 375px). -->
        <div class="space-y-3 md:hidden">
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">
            Classificação estadual
            <span class="font-normal text-slate-500 dark:text-slate-400">({{ numero(placar.total ?? 0) }})</span>
          </h3>

          <template v-for="linha in placarLinhas" :key="`card-${linha.entidade_id}`">
          <div v-if="linha.corteFaixa" class="border-b border-slate-200 pb-2 pt-2 dark:border-slate-700/50" data-corte-faixa>
            <RankingFaixaCorte :faixa="linha.faixa" :limiares="faixas" />
          </div>
          <article
            class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/60"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <RankingPosicaoCell :posicao="linha.posicao" :medalha="linha.medalha" destacar-topo />
                <p class="mt-2 truncate text-sm" :class="corDoNome(linha)">{{ rotuloParticipante(linha) }}</p>
              </div>
              <div class="shrink-0 text-right">
                <p class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ numero(linha.pontos) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">pontos</p>
              </div>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
              <div v-for="kpi in KPIS_PLACAR" :key="kpi.chave">
                <dt class="text-slate-500 dark:text-slate-400">{{ kpi.titulo }}</dt>
                <dd class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ kpi.valor(linha) }}</dd>
              </div>
            </dl>
            <div class="mt-3">
              <RankingFaixaBadge :faixa="linha.faixa" />
            </div>
          </article>
          </template>

          <p
            v-if="placarLinhas.length === 0"
            class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500 dark:border-slate-700/50 dark:bg-slate-900/60 dark:text-slate-400"
          >
            Nenhum participante neste recorte.
          </p>
        </div>

        <Pagination :pagination="paginacaoPlacar" @page-change="irParaPaginaPlacar" />
      </section>

      <section v-if="extrato" class="mb-6" aria-label="Meu extrato">
        <ListContainer
          class="hidden md:block"
          title="Meu extrato"
          subtitle="Entregas pessoais de todos os módulos no período. Pendências não compõem o saldo confirmado."
          :count="extrato.total ?? 0"
          :icon="DocumentTextIcon"
        >
          <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
              <tr>
                <th class="px-4 py-3 text-left">Competência</th>
                <th class="px-4 py-3 text-left">Entrega</th>
                <th class="px-4 py-3 text-left">Decisão</th>
                <th class="px-4 py-3 text-right">Base</th>
                <th class="px-4 py-3 text-right">Bônus</th>
                <th class="px-4 py-3 text-left">Regra</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr
                v-for="(item, index) in extratoLinhas"
                :key="`${item.id}-${index}`"
                class="table-row-solid transition"
              >
                <td class="whitespace-nowrap px-4 py-4 text-slate-500 dark:text-slate-400">{{ dataHora(item.competencia_em) }}</td>
                <td class="px-4 py-4 text-slate-700 dark:text-slate-300">
                  {{ item.familia }}
                  <span v-if="item.modulo" class="block text-xs text-slate-500 dark:text-slate-400">{{ item.modulo }}</span>
                </td>
                <td class="px-4 py-4 text-slate-700 dark:text-slate-300">
                  {{ rotuloDecisao(item.decisao) }}
                  <span v-if="item.motivo" class="block text-xs text-slate-500 dark:text-slate-400">{{ item.motivo }}</span>
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-right text-slate-700 dark:text-slate-300">{{ numero(item.pontos_base) }}</td>
                <td class="whitespace-nowrap px-4 py-4 text-right text-slate-700 dark:text-slate-300">{{ numero(item.pontos_bonus) }}</td>
                <td class="whitespace-nowrap px-4 py-4 text-slate-500 dark:text-slate-400">{{ item.regra_versao ? `v${item.regra_versao}` : '—' }}</td>
              </tr>

              <tr v-if="extratoLinhas.length === 0">
                <td colspan="6" class="px-4 py-12 text-center">
                  <DocumentTextIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
                  <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhuma entrega registrada neste período</p>
                  <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Troque o período para consultar outro recorte.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </ListContainer>

        <div class="space-y-3 md:hidden">
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">
            Meu extrato
            <span class="font-normal text-slate-500 dark:text-slate-400">({{ numero(extrato.total ?? 0) }})</span>
          </h3>

          <article
            v-for="(item, index) in extratoLinhas"
            :key="`extrato-card-${item.id}-${index}`"
            class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/60"
          >
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ item.familia }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ dataHora(item.competencia_em) }}</p>

            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
              <div class="col-span-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">Decisão</dt>
                <dd class="text-slate-700 dark:text-slate-300">
                  {{ rotuloDecisao(item.decisao) }}
                  <span v-if="item.motivo" class="block text-xs text-slate-500 dark:text-slate-400">{{ item.motivo }}</span>
                </dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500 dark:text-slate-400">Base</dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ numero(item.pontos_base) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500 dark:text-slate-400">Bônus</dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ numero(item.pontos_bonus) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500 dark:text-slate-400">Regra</dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ item.regra_versao ? `v${item.regra_versao}` : '—' }}</dd>
              </div>
            </dl>
          </article>

          <p
            v-if="extratoLinhas.length === 0"
            class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500 dark:border-slate-700/50 dark:bg-slate-900/60 dark:text-slate-400"
          >
            Nenhuma entrega registrada neste período.
          </p>
        </div>

        <Pagination :pagination="paginacaoExtrato" @page-change="irParaPaginaExtrato" />
      </section>

    </template>

    <RankingRegrasModal :show="regrasAbertas" :regras="regras" :pode-gerenciar="podeGerenciarRegras" :salvando="regraSalvando" :erros="regraErros" :teto="tetoLancamento" @alternar="alternarRegra" @salvar-regra="atualizarRegra" @close="regrasAbertas = false" />

    <footer class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 dark:border-slate-700/50 dark:bg-slate-800/60 dark:text-slate-300">
      <p>{{ cobertura }}</p>
      <p class="mt-1">IPCM: em apuração. Pontuação de atividade não comprova regularidade municipal.</p>
    </footer>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { BookOpenIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';
import RankingPosicaoCell from '@/Components/Molecules/Ranking/RankingPosicaoCell.vue';
import RankingPodio from '@/Components/Molecules/Ranking/RankingPodio.vue';
import RankingFaixaCorte from '@/Components/Molecules/Ranking/RankingFaixaCorte.vue';
import RankingRegrasModal from '@/Components/Organisms/Ranking/RankingRegrasModal.vue';
import RankingConquistaFaixa from '@/Components/Organisms/Ranking/RankingConquistaFaixa.vue';
import RankingNovaTemporada from '@/Components/Organisms/Ranking/RankingNovaTemporada.vue';
import RankingBandeirada from '@/Components/Organisms/Ranking/RankingBandeirada.vue';
import { chaveTrimestre, diaMes, partesTrimestre, rotuloTrimestre, trimestresRecentes } from '@/Support/rankingTemporada';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  resumo: { type: Object, default: null },
  placar: { type: Object, default: null },
  podio: { type: Array, default: () => [] },
  extrato: { type: Object, default: null },
  regras: { type: Array, default: () => [] },
  filtros: { type: Object, required: true },
  indisponivel: { type: Boolean, default: false },
  semContexto: { type: Boolean, default: false },
  podeVerInstitucional: { type: Boolean, default: false },
  estadual: { type: Boolean, default: false },
  cobertura: { type: String, default: '' },
  // Catalogo em homologacao (RankingAccess::preview): o controller responde sem
  // resumo, placar nem extrato.
  preview: { type: Boolean, default: false },
  // Limiares de config('ranking.faixas'); sem ela o corte usa o fallback local.
  faixas: { type: Object, default: null },
  // Temporada corrente (trimestre) e o resultado do usuario na anterior.
  temporada: { type: Object, default: null },
  tetoLancamento: { type: Number, default: 500 },
  podeGerenciarRegras: { type: Boolean, default: false },
  podeVerCarteira: { type: Boolean, default: false },
});

const POR_PAGINA_PLACAR = 25;
const regrasAbertas = ref(false);
const regraSalvando = ref('');
// Erros de validacao por rule_key: cada card mostra so os seus.
const regraErros = ref({});

// Sempre a versao vigente: o backend publica a nova versao a cada alteracao.
function atualizarRegra(regra, corpo, aoConcluir = null) {
  if (regraSalvando.value || !props.podeGerenciarRegras) return;
  regraSalvando.value = regra.rule_key;
  regraErros.value = { ...regraErros.value, [regra.rule_key]: {} };
  router.patch(route('ranking.regras.atualizar', regra.rule_key), corpo, {
    preserveState: true,
    preserveScroll: true,
    only: ['regras'],
    onSuccess: () => { aoConcluir?.(); },
    onError: (erros) => { regraErros.value = { ...regraErros.value, [regra.rule_key]: erros }; },
    onFinish: () => { regraSalvando.value = ''; },
  });
}

function alternarRegra(regra) {
  atualizarRegra(regra, { habilitada: !regra.habilitada });
}

const DECISOES = {
  confirmada: 'Confirmada',
  pendente: 'Pendente de validação',
  zero: 'Sem pontuação',
  em_apuracao: 'Em apuração',
  estornada: 'Estornada',
};

const FAIXAS = {
  bronze: 'Bronze',
  prata: 'Prata',
  ouro: 'Ouro',
  diamante: 'Diamante',
};

const ESCOPO_SINGULAR = {
  usuario: 'Participante',
  orgao: 'Órgão',
  municipio: 'Município',
};

const formatadorNumero = new Intl.NumberFormat('pt-BR');
const formatadorDataHora = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' });

function numero(valor) {
  return formatadorNumero.format(Number(valor ?? 0));
}

function dataHora(valor) {
  if (!valor) return '—';
  const data = new Date(valor);
  return Number.isNaN(data.getTime()) ? '—' : formatadorDataHora.format(data);
}

function rotuloDecisao(decisao) {
  return DECISOES[decisao] ?? decisao ?? '—';
}

// Nome do medalhista na cor do metal da medalha; demais no tom neutro.
const CORES_DO_NOME = {
  1: 'font-semibold text-amber-600 dark:text-amber-400',
  2: 'font-semibold text-slate-500 dark:text-slate-300',
  3: 'font-semibold text-orange-700 dark:text-orange-400',
};
function corDoNome(linha) {
  return CORES_DO_NOME[linha?.medalha] ?? 'text-slate-700 dark:text-slate-300';
}

function rotuloParticipante(linha) {
  if (linha?.rotulo) return linha.rotulo;
  const prefixo = ESCOPO_SINGULAR[props.filtros?.escopo] ?? 'Participante';
  return `${prefixo} #${linha?.entidade_id ?? '—'}`;
}

// A chave do periodo e o contrato do recorte ('acumulado', 'mes:2026-09',
// 'ano:2026'). Na tela ela vira dois selects e volta a ser string no envio.
function separarPeriodo(chave) {
  const valor = String(chave ?? '');
  if (partesTrimestre(valor)) return { tipo: 'trimestre', trimestre: valor, mes: '', ano: valor.slice(10, 14) };
  if (valor.startsWith('mes:')) return { tipo: 'mes', mes: valor.slice(4), ano: valor.slice(4, 8) };
  if (valor.startsWith('ano:')) return { tipo: 'ano', mes: '', ano: valor.slice(4) };
  return { tipo: 'acumulado', mes: '', ano: '' };
}

function juntarPeriodo(estado) {
  if (estado.tipoPeriodo === 'trimestre' && estado.trimestre) return estado.trimestre;
  if (estado.tipoPeriodo === 'mes' && estado.mes) return `mes:${estado.mes}`;
  if (estado.tipoPeriodo === 'ano' && estado.ano) return `ano:${estado.ano}`;
  return 'acumulado';
}

const agora = new Date();
const mesCorrente = `${agora.getFullYear()}-${String(agora.getMonth() + 1).padStart(2, '0')}`;

function rotuloMes(chave) {
  const [ano, mes] = chave.split('-').map(Number);
  const texto = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric', timeZone: 'UTC' })
    .format(new Date(Date.UTC(ano, mes - 1, 1)));
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

const inicial = separarPeriodo(props.filtros?.periodo);

const local = reactive({
  escopo: props.filtros?.escopo ?? 'usuario',
  modulo: props.filtros?.modulo ?? 'all',
  tipoPeriodo: inicial.tipo,
  mes: inicial.mes || mesCorrente,
  ano: inicial.ano || String(agora.getFullYear()),
  trimestre: inicial.trimestre || chaveTrimestre(agora),
});

// Volta do servidor com preserveState: o estado local precisa refletir o recorte
// que de fato foi aplicado (inclusive quando o filtro chega por card ou pagina).
watch(
  () => props.filtros,
  (valor) => {
    if (!valor) return;
    const partes = separarPeriodo(valor.periodo);
    local.escopo = valor.escopo ?? 'usuario';
    local.modulo = valor.modulo ?? 'all';
    local.tipoPeriodo = partes.tipo;
    if (partes.mes) local.mes = partes.mes;
    if (partes.ano) local.ano = partes.ano;
    if (partes.trimestre) local.trimestre = partes.trimestre;
  },
  { deep: true },
);

const escopoOpcoes = computed(() => {
  const opcoes = [{ value: 'usuario', label: 'Meu placar' }];
  if (props.podeVerInstitucional) {
    opcoes.push({ value: 'orgao', label: 'Meu órgão' });
    opcoes.push({ value: 'municipio', label: 'Meu município' });
  }
  return opcoes;
});

const tipoPeriodoOpcoes = [
  { value: 'trimestre', label: 'Temporada (trimestre)' },
  { value: 'mes', label: 'Mês' },
  { value: 'ano', label: 'Ano' },
  { value: 'acumulado', label: 'Acumulado' },
];

const moduloOpcoes = [
  { value: 'all', label: 'Todos os módulos' },
  { value: 'rat', label: 'RAT' },
  { value: 'pae', label: 'PAE' },
];

// 24 meses para tras a partir do mes corrente; o mes que veio do servidor entra
// na lista mesmo quando cai fora dessa janela, senao o select perderia o valor.
const mesOpcoes = computed(() => {
  const chaves = [];
  for (let i = 0; i < 24; i += 1) {
    const referencia = new Date(Date.UTC(agora.getFullYear(), agora.getMonth() - i, 1));
    chaves.push(`${referencia.getUTCFullYear()}-${String(referencia.getUTCMonth() + 1).padStart(2, '0')}`);
  }
  if (local.mes && !chaves.includes(local.mes)) chaves.unshift(local.mes);
  return chaves.map((chave) => ({ value: chave, label: rotuloMes(chave) }));
});

// 8 temporadas para tras; a que veio do servidor entra mesmo fora da janela.
const trimestreOpcoes = computed(() => {
  const chaves = trimestresRecentes(8, agora);
  if (local.trimestre && !chaves.includes(local.trimestre)) chaves.unshift(local.trimestre);
  return chaves.map((chave) => ({ value: chave, label: rotuloTrimestre(chave, { comMeses: true }) }));
});

const anoOpcoes = computed(() => {
  const anos = [];
  for (let i = 0; i < 6; i += 1) anos.push(String(agora.getFullYear() - i));
  if (local.ano && !anos.includes(local.ano)) anos.unshift(local.ano);
  return anos.map((ano) => ({ value: ano, label: ano }));
});

const posicaoTexto = computed(() => (props.resumo?.posicao ? `${numero(props.resumo.posicao)}º` : '—'));
const faixaTexto = computed(() => FAIXAS[props.resumo?.faixa] ?? 'Em apuração');

// corteFaixa marca a primeira linha de cada faixa na pagina: a tela desenha o
// cabecalho do grupo (Diamante > Ouro > Prata > Bronze) antes dela.
// fimDoPodio marca a ultima linha medalhada seguida de nao medalhada na mesma
// faixa: a tela separa os medalhistas dos demais colocados.
const placarLinhas = computed(() => {
  const linhas = props.placar?.linhas ?? [];
  const medalhas = linhas.map((linha, indice) => medalhaDaLinha(linha, indice, linhas));
  return linhas.map((linha, indice) => ({
    ...linha,
    corteFaixa: (indice === 0 || linhas[indice - 1]?.faixa !== linha.faixa) && String(linha.faixa || '').toLowerCase() !== 'bronze',
    medalha: medalhas[indice],
    fimDoPodio: medalhas[indice] !== null && indice + 1 < linhas.length
      && medalhas[indice + 1] === null && linhas[indice + 1].faixa === linha.faixa,
  }));
});

// Medalha pela POSICAO (1, 2 e 3), nao pela ordem da linha: empatado divide a
// medalha em vez de um 2o levar bronze. Mesmo limite do podio - no maximo tres
// por degrau - para um empate largo nao encher a tabela de medalhas.
const MEDALHAS_POR_DEGRAU = 3;
function medalhaDaLinha(linha, indice, linhas) {
  const posicao = Number(linha.posicao);
  if ((props.placar?.pagina ?? 1) !== 1 || posicao < 1 || posicao > 3) return null;
  const anteriores = linhas.slice(0, indice).filter((outra) => Number(outra.posicao) === posicao).length;
  return anteriores < MEDALHAS_POR_DEGRAU ? posicao : null;
}

// Colunas de indicadores da classificacao: uma definicao para tabela e card.
const formatadorData = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short' });
const KPIS_PLACAR = [
  { chave: 'dias', titulo: 'Dias ativos', alinhar: 'text-right', ajuda: 'Dias distintos com acesso ao sistema no período; desempata pontos iguais.', valor: (l) => numero(l.dias_ativos) },
  { chave: 'entregas', titulo: 'Entregas', alinhar: 'text-right', ajuda: 'Entregas pontuadas no período.', valor: (l) => numero(l.entregas) },
  { chave: 'prazo', titulo: 'No prazo', alinhar: 'text-right', ajuda: 'Parcela das entregas feitas dentro do prazo (com bônus).', valor: (l) => (Number(l.entregas) > 0 ? `${Math.round((Number(l.entregas_no_prazo) / Number(l.entregas)) * 100)}%` : '—') },
  { chave: 'ultima', titulo: 'Última entrega', alinhar: 'text-left', ajuda: 'Data da entrega pontuada mais recente no período.', valor: (l) => dataCurta(l.ultima_entrega_em) },
];
// Posicao + participante + indicadores + pontos + faixa.
const COLUNAS_PLACAR = KPIS_PLACAR.length + 4;

function dataCurta(valor) {
  if (!valor) return '—';
  const data = new Date(valor);
  return Number.isNaN(data.getTime()) ? '—' : formatadorData.format(data);
}
const extratoLinhas = computed(() => props.extrato?.data ?? []);

// PREVIA TEMPORARIA (remover apos validacao do design): ?previa=<faixa>, so
// para admin, encena a celebracao da faixa (bronze|prata|ouro|diamante); com
// diamante poe tambem o lider do podio na faixa Diamante.
const ORDEM_FAIXAS = ['bronze', 'prata', 'ouro', 'diamante'];
const previaFaixa = (() => {
  if (typeof window === 'undefined' || !props.podeGerenciarRegras) return null;
  const pedida = new URLSearchParams(window.location.search).get('previa');
  return ORDEM_FAIXAS.includes(pedida) ? pedida : null;
})();
const previaTemporada = typeof window !== 'undefined' && props.podeGerenciarRegras
  && new URLSearchParams(window.location.search).get('previa') === 'temporada';

// Aviso de nova temporada: uma vez por usuario e temporada, marcado ao
// aparecer. So quando ha o que contar - resultado na anterior - ou nas duas
// primeiras semanas da rodada; fora disso seria um pop-up sem novidade.
const DIAS_DE_ESTREIA = 15;
const novaTemporadaAberta = ref(false);
const bandeiradaAberta = ref(false);
// Estreia = primeiras duas semanas da rodada. A previa simula a virada.
const estreiaTemporada = (() => {
  if (previaTemporada) return true;
  const inicio = new Date(`${props.temporada?.inicio}T00:00:00`);
  return !Number.isNaN(inicio.getTime()) && (Date.now() - inicio.getTime()) / 86400000 <= DIAS_DE_ESTREIA;
})();
// Na previa, sem resultado real anterior, usa o resumo atual como exemplo.
const temporadaExibida = computed(() => {
  if (!props.temporada) return null;
  if (!previaTemporada || props.temporada.anterior) return props.temporada;
  return { ...props.temporada, anterior: { chave: 'trimestre:2026-T2', pontos: props.resumo?.pontos ?? 1789, posicao: props.resumo?.posicao ?? 10, faixa: props.resumo?.faixa ?? 'ouro' } };
});
(() => {
  const temporada = props.temporada;
  if (!temporada) return;
  if (previaTemporada) {
    bandeiradaAberta.value = true;
    novaTemporadaAberta.value = true;
    return;
  }
  const chave = `ranking:temporada-vista:${usePage().props.auth?.user?.id ?? 'anon'}:${temporada.chave}`;
  if (!temporada.anterior && !estreiaTemporada) return;
  try {
    if (window.localStorage.getItem(chave) === '1') return;
    window.localStorage.setItem(chave, '1');
  } catch { /* sem storage: repete na proxima visita */ }
  // A bandeirada so larga na estreia; no meio da rodada vai direto ao aviso.
  bandeiradaAberta.value = estreiaTemporada;
  novaTemporadaAberta.value = true;
})();

const podioLinhas = computed(() => props.podio.map((linha, indice) => ({
  ...linha,
  rotulo: rotuloParticipante(linha),
  ...(previaFaixa === 'diamante' && indice === 0 ? { faixa: 'diamante' } : {}),
})));

// Celebracao de subida de faixa: so quando o PROPRIO usuario sobe (visao "Meu
// placar"; em orgao/municipio a faixa e da entidade, nao dele), uma vez por
// faixa e periodo. Bronze so conta com pontos: saldo zero tambem e "bronze" e
// nao e conquista. Quem ja chega numa faixa alta ve so ela, e as de baixo ficam
// marcadas: subir de Prata para Ouro nao reapresenta a Prata.
//
// Marcada como vista ao APARECER, nao ao fechar: recarregar no meio da animacao
// nao a repete. Lembrada neste navegador; storage indisponivel (aba anonima,
// bloqueio) apenas faz repetir - nunca quebra a pagina.
const conquistaFaixa = ref(null);
const prefixoConquista = computed(() => `ranking:faixa-vista:${usePage().props.auth?.user?.id ?? 'anon'}:${props.filtros?.periodo}`);
function conquistaJaVista(faixa) {
  try {
    if (window.localStorage.getItem(`${prefixoConquista.value}:${faixa}`) === '1') return true;
    // Chave da versao so-Diamante: quem ja viu nao ve de novo.
    return faixa === 'diamante'
      && window.localStorage.getItem(`ranking:diamante-visto:${usePage().props.auth?.user?.id ?? 'anon'}:${props.filtros?.periodo}`) === '1';
  } catch { return false; }
}
function marcarAte(faixa) {
  try {
    ORDEM_FAIXAS.slice(0, ORDEM_FAIXAS.indexOf(faixa) + 1)
      .forEach((f) => window.localStorage.setItem(`${prefixoConquista.value}:${f}`, '1'));
  } catch { /* sem storage: repete na proxima visita */ }
}
function fecharConquistaFaixa() {
  conquistaFaixa.value = null;
}
watch(() => [props.resumo?.faixa, props.resumo?.pontos, props.filtros?.escopo, prefixoConquista.value], ([faixa, pontos, escopo]) => {
  if (previaFaixa) {
    conquistaFaixa.value = previaFaixa;
    return;
  }
  if (escopo !== 'usuario' || !ORDEM_FAIXAS.includes(faixa)) return;
  if (faixa === 'bronze' && Number(pontos ?? 0) <= 0) return;
  if (conquistaJaVista(faixa)) return;
  marcarAte(faixa);
  conquistaFaixa.value = faixa;
}, { immediate: true });

const paginacaoPlacar = computed(() => {
  if (!props.placar) return null;
  const porPagina = props.placar.por_pagina ?? POR_PAGINA_PLACAR;
  const pagina = props.placar.pagina ?? 1;
  const total = props.placar.total ?? 0;
  return {
    current_page: pagina,
    last_page: Math.max(1, props.placar.total_paginas ?? 1),
    per_page: porPagina,
    total,
    from: total === 0 ? null : (pagina - 1) * porPagina + 1,
    to: total === 0 ? null : Math.min(pagina * porPagina, total),
  };
});

const paginacaoExtrato = computed(() => {
  if (!props.extrato) return null;
  return {
    current_page: props.extrato.current_page ?? 1,
    last_page: Math.max(1, props.extrato.last_page ?? 1),
    per_page: props.extrato.per_page ?? 25,
    total: props.extrato.total ?? 0,
    from: props.extrato.from ?? null,
    to: props.extrato.to ?? null,
  };
});

const podeIrParaMinhaPosicao = computed(
  () => Boolean(props.placar) && Boolean(props.resumo?.posicao),
);

function navegar(parametros = {}) {
  router.get(
    route('ranking.index'),
    {
      escopo: local.escopo,
      periodo: juntarPeriodo(local),
      modulo: local.modulo,
      pagina: props.placar?.pagina ?? props.filtros?.pagina ?? 1,
      extrato_pagina: props.extrato?.current_page ?? 1,
      ...parametros,
    },
    { preserveState: true, replace: true },
  );
}

function aplicar() {
  navegar({ pagina: 1, extrato_pagina: 1 });
}

function limpar() {
  local.escopo = 'usuario';
  local.modulo = 'all';
  local.tipoPeriodo = 'trimestre';
  local.trimestre = chaveTrimestre(agora);
  local.mes = mesCorrente;
  local.ano = String(agora.getFullYear());
  navegar({ pagina: 1, extrato_pagina: 1 });
}


function trocarTipoPeriodo(tipo) {
  local.tipoPeriodo = tipo;
}

// Card do saldo como filtro rapido: devolve o recorte consolidado.
function filtrarPorModulo(modulo) {
  local.modulo = modulo;
  navegar({ modulo, pagina: 1 });
}

// Card da posicao como filtro rapido: abre a pagina do placar onde voce esta.
function irParaMinhaPosicao() {
  if (!podeIrParaMinhaPosicao.value) return;
  const porPagina = props.placar?.por_pagina ?? POR_PAGINA_PLACAR;
  navegar({ pagina: Math.max(1, Math.ceil(props.resumo.posicao / porPagina)) });
}

function irParaPaginaPlacar(pagina) {
  navegar({ pagina });
}

function irParaPaginaExtrato(pagina) {
  navegar({ extrato_pagina: pagina });
}
</script>
