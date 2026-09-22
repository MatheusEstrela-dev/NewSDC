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
          <ActionButton
            action="history"
            :allowed="true"
            variant="secondary"
            label="Atualizar"
            tooltip-text="Recarregar o recorte atual do placar"
            @click="atualizar"
          />
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
          v-if="local.tipoPeriodo === 'mes'"
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

      <RankingPodio
        v-if="podioLinhas.length"
        class="mb-6"
        :linhas="podioLinhas"
        :escopo="filtros.escopo"
      />

      <section v-if="placar" class="mb-6" aria-label="Classificação estadual">
        <ListContainer
          class="hidden md:block"
          title="Classificação estadual"
          subtitle="Empates compartilham a posição. Participantes são identificados pelo código nesta versão do piloto."
          :count="placar.total ?? 0"
          :icon="ClipboardDocumentListIcon"
        >
          <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
              <tr>
                <th class="w-24 px-4 py-3 text-left">Posição</th>
                <th class="px-4 py-3 text-left">Participante</th>
                <th class="px-4 py-3 text-right">Pontos</th>
                <th class="px-4 py-3 text-left">Faixa</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr
                v-for="linha in placarLinhas"
                :key="linha.entidade_id"
                class="table-row-solid transition"
              >
                <td class="px-4 py-4">
                  <RankingPosicaoCell :posicao="linha.posicao" destacar-topo />
                </td>
                <td class="px-4 py-4 text-slate-700 dark:text-slate-300">
                  {{ rotuloParticipante(linha) }}
                </td>
                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold text-slate-900 dark:text-slate-100">
                  {{ numero(linha.pontos) }}
                </td>
                <td class="px-4 py-4">
                  <RankingFaixaBadge :faixa="linha.faixa" />
                </td>
              </tr>

              <tr v-if="placarLinhas.length === 0">
                <td colspan="4" class="px-4 py-12 text-center">
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

          <article
            v-for="linha in placarLinhas"
            :key="`card-${linha.entidade_id}`"
            class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/60"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <RankingPosicaoCell :posicao="linha.posicao" destacar-topo />
                <p class="mt-2 truncate text-sm text-slate-700 dark:text-slate-300">{{ rotuloParticipante(linha) }}</p>
              </div>
              <div class="shrink-0 text-right">
                <p class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ numero(linha.pontos) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">pontos</p>
              </div>
            </div>
            <div class="mt-3">
              <RankingFaixaBadge :faixa="linha.faixa" />
            </div>
          </article>

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

      <details
        v-if="regras.length"
        class="mb-6 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/60"
      >
        <summary class="cursor-pointer text-sm font-bold text-slate-900 dark:text-slate-100">
          Regras publicadas
          <span class="font-normal text-slate-500 dark:text-slate-400">({{ numero(regras.length) }})</span>
        </summary>

        <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <li
            v-for="regra in regras"
            :key="`${regra.rule_key}-${regra.versao}`"
            class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-700/50 dark:bg-slate-800/60"
          >
            <p class="break-words font-semibold text-slate-900 dark:text-slate-100">
              {{ String(regra.modulo).toUpperCase() }} · {{ regra.familia }} · v{{ regra.versao }}
            </p>
            <p class="mt-1 text-slate-600 dark:text-slate-300">
              {{ numero(regra.pontos_base) }} pontos de base ·
              {{ regra.aceita_bonus ? `${numero(regra.bonus_percentual)}% de bônus quando elegível` : 'Sem bônus' }}
            </p>
            <p class="mt-1 text-slate-600 dark:text-slate-300">
              {{ regra.habilitada ? 'Habilitada' : `Desabilitada: ${regra.motivo_desabilitada ?? 'aguardando homologação'}` }}
            </p>
          </li>
        </ul>
      </details>
    </template>

    <footer class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 dark:border-slate-700/50 dark:bg-slate-800/60 dark:text-slate-300">
      <p>{{ cobertura }}</p>
      <p class="mt-1">IPCM: em apuração. Pontuação de atividade não comprova regularidade municipal.</p>
    </footer>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
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
});

const POR_PAGINA_PLACAR = 25;

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

function rotuloParticipante(linha) {
  if (linha?.rotulo) return linha.rotulo;
  const prefixo = ESCOPO_SINGULAR[props.filtros?.escopo] ?? 'Participante';
  return `${prefixo} #${linha?.entidade_id ?? '—'}`;
}

// A chave do periodo e o contrato do recorte ('acumulado', 'mes:2026-09',
// 'ano:2026'). Na tela ela vira dois selects e volta a ser string no envio.
function separarPeriodo(chave) {
  const valor = String(chave ?? '');
  if (valor.startsWith('mes:')) return { tipo: 'mes', mes: valor.slice(4), ano: valor.slice(4, 8) };
  if (valor.startsWith('ano:')) return { tipo: 'ano', mes: '', ano: valor.slice(4) };
  return { tipo: 'acumulado', mes: '', ano: '' };
}

function juntarPeriodo(estado) {
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

const anoOpcoes = computed(() => {
  const anos = [];
  for (let i = 0; i < 6; i += 1) anos.push(String(agora.getFullYear() - i));
  if (local.ano && !anos.includes(local.ano)) anos.unshift(local.ano);
  return anos.map((ano) => ({ value: ano, label: ano }));
});

const posicaoTexto = computed(() => (props.resumo?.posicao ? `${numero(props.resumo.posicao)}º` : '—'));
const faixaTexto = computed(() => FAIXAS[props.resumo?.faixa] ?? 'Em apuração');

const placarLinhas = computed(() => props.placar?.linhas ?? []);
const extratoLinhas = computed(() => props.extrato?.data ?? []);

// Podio so na primeira pagina: fora dela nao existe top 3 na tela.
const podioLinhas = computed(() => {
  if ((props.placar?.pagina ?? 1) !== 1) return [];
  return placarLinhas.value
    .filter((linha) => linha.posicao <= 3)
    .map((linha) => ({ ...linha, rotulo: rotuloParticipante(linha) }));
});

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
  local.tipoPeriodo = 'mes';
  local.mes = mesCorrente;
  local.ano = String(agora.getFullYear());
  navegar({ pagina: 1, extrato_pagina: 1 });
}

function atualizar() {
  navegar();
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
