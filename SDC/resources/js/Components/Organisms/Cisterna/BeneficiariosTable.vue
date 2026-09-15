<template>
  <!--
    Onze colunas com `whitespace-nowrap`: no telefone a tabela rolava de lado e
    a coluna fixa de acoes flutuava sobre conteudo cortado -- o usuario nao
    sabia de qual beneficiario eram os botoes que estava tocando.
    Regra 9 de `.claude/skills/frontend/04 - Responsividade`.

    O ResponsiveTable fica AQUI, dentro da tabela, e nao na pagina: assim toda
    tela que use este componente ganha os cards sem alteracao propria.
  -->
  <ResponsiveTable
    :items="beneficiarios"
    :mobile-fields="CAMPOS_MOBILE"
    :get-item-title="(b) => b.nome"
    :get-item-subtitle="(b) => formatarCpf(b.cpf)"
    :get-item-key="(b) => b.id"
    empty-message="Nenhum beneficiario encontrado"
  >
    <!-- Badges nao sobrevivem a interpolacao de texto: cada um vem por slot. -->
    <template #mobile-situacao_analise="{ item }">
      <SituacaoAnaliseBadge :valor="item.situacao_analise.valor" :rotulo="item.situacao_analise.rotulo" />
    </template>

    <template #mobile-situacao_obra="{ item }">
      <SituacaoObraBadge :valor="item.situacao_obra.valor" :rotulo="item.situacao_obra.rotulo" />
    </template>

    <template #mobile-etapas="{ item }">
      <div class="flex items-center gap-1">
        <EtapaVistoriaBadge
          v-for="etapa in ETAPAS"
          :key="etapa"
          :etapa="etapa"
          :concluida="(item.etapas_concluidas ?? []).includes(etapa)"
        />
      </div>
    </template>

    <!-- Mesmas acoes da linha, pela mesma funcao. -->
    <template #mobile-actions="{ item }">
      <ActionButton module="cisternas" resource="beneficiarios" size="sm" :actions="acoesDe(item)" />
    </template>

    <template #table>
  <!-- rounded-xl para esquadrar com os stat cards e o painel de filtros, que
     tambem sao 12px. Com rounded-lg (8px) a pagina empilhava tres raios
     diferentes: 16px no PageHeader, 12px nos cards/filtros e 8px aqui. -->
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th v-if="selecionavel" scope="col" class="w-10 px-3 py-2">
              <input
                type="checkbox"
                class="rounded border-slate-300 dark:border-slate-600"
                :checked="todosMarcados"
                :indeterminate="algunsMarcados"
                title="Marcar os registros desta pagina"
                @change="alternarTodos"
              >
            </th>
            <!-- Coluna sem `coluna` preenchida vira cabecalho de texto puro: e o
                 caso de lote, numero de instalacao, etapas e ranqueamento, que
                 saem de relacao a dois saltos ou de valor derivado e nao existem
                 como coluna ordenavel no backend. -->
            <SortableHeader
              v-for="c in COLUNAS"
              :key="c.chave"
              :coluna="c.ordenavel ? c.chave : ''"
              :direcao-inicial="c.direcaoInicial ?? 'asc'"
              v-bind="ordenacao"
              @ordenar="emit('ordenar', $event)"
            >
              {{ c.titulo }}
            </SortableHeader>
            <SortableHeader classe="table-actions-head w-44 min-w-44 text-right">Opcoes</SortableHeader>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <tr
            v-for="b in beneficiarios"
            :key="b.id"
            class="table-row-solid transition-colors"
          >
            <td v-if="selecionavel" class="px-3 py-2">
              <input
                type="checkbox"
                class="rounded border-slate-300 dark:border-slate-600"
                :checked="marcados.includes(b.id)"
                @change="alternar(b.id)"
              >
            </td>

            <td :class="TD_FORTE">{{ b.nome }}</td>
            <td :class="TD_MONO">{{ formatarCpf(b.cpf) }}</td>
            <td :class="TD">{{ b.municipio ?? '—' }}</td>
            <td :class="TD">{{ b.comunidade ?? '—' }}</td>
            <td :class="TD">
              <span v-if="b.lote || b.ordem_servico">{{ [b.lote, b.ordem_servico].filter(Boolean).join(' / ') }}</span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td :class="TD_MONO">{{ b.numero_instalacao ?? '—' }}</td>
            <td :class="TD">
              <SituacaoAnaliseBadge :valor="b.situacao_analise.valor" :rotulo="b.situacao_analise.rotulo" />
            </td>
            <td :class="TD">
              <SituacaoObraBadge :valor="b.situacao_obra.valor" :rotulo="b.situacao_obra.rotulo" />
            </td>
            <td :class="TD">
              <!--
                As tres etapas sempre aparecem, pendentes so com contorno: o
                usuario precisa ver o que FALTA, nao apenas o que foi feito. No
                legado isso custava tres whereHas por linha.
              -->
              <div class="flex items-center gap-1">
                <EtapaVistoriaBadge
                  v-for="etapa in ETAPAS"
                  :key="etapa"
                  :etapa="etapa"
                  :concluida="(b.etapas_concluidas ?? []).includes(etapa)"
                />
              </div>
            </td>
            <td :class="TD_MONO">{{ b.ranqueamento_ordem ?? '—' }}</td>

            <!-- Coluna fixa no canto direito: com 11 colunas a tabela passa a
                 area util entre ~768px e ~1300px e as acoes saiam da tela. Depende
                 de .table-row-solid na <tr> para o fundo opaco. -->
            <td class="table-actions-cell w-44 min-w-44 whitespace-nowrap px-3 py-2 text-right">
              <div class="flex items-center justify-end">
                <ActionButton
                  module="cisternas"
                  resource="beneficiarios"
                  :actions="acoesDe(b)"
                />
              </div>
            </td>
          </tr>

          <tr v-if="beneficiarios.length === 0">
            <td :colspan="COLUNAS.length + (selecionavel ? 2 : 1)" class="px-3 py-10">
              <ListEmptyState
                title="Nenhum beneficiario encontrado"
                helper="Ajuste os filtros ou clique no card Beneficiarios para limpar a pesquisa."
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
    </template>
  </ResponsiveTable>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import SortableHeader from '@/Components/Molecules/Table/SortableHeader.vue';
import SituacaoAnaliseBadge from '@/Components/Atoms/Cisterna/SituacaoAnaliseBadge.vue';
import SituacaoObraBadge from '@/Components/Atoms/Cisterna/SituacaoObraBadge.vue';
import EtapaVistoriaBadge from '@/Components/Atoms/Cisterna/EtapaVistoriaBadge.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';

const props = defineProps({
  beneficiarios: { type: Array, default: () => [] },
  marcados: { type: Array, default: () => [] },
  selecionavel: { type: Boolean, default: false },
  permissoes: { type: Object, default: () => ({}) },

  /** Coluna ordenada no momento, vinda da URL. */
  ordenadoPor: { type: String, default: 'nome' },

  /** Direcao atual da ordenacao: 'asc' ou 'desc'. */
  direcao: { type: String, default: 'asc' },
});

const emit = defineEmits(['update:marcados', 'excluir', 'ordenar', 'historico', 'imprimir', 'pdf', 'qrcode']);

/**
 * PDF e QR foram para o menu de tres pontos, como no PAE, e a impressora inline
 * saiu: eram DOIS caminhos para a mesma ficha, e o icone repetido roubava
 * espaco das acoes do dia a dia. O QR aparece so quando ja existe numero de
 * instalacao -- sem numero nao ha adesivo para colar.
 *
 * Impressao so com a CADEIA COMPLETA: a ficha e o documento que fecha a
 * instalacao e vai para prestacao de contas, e emitir com etapa em aberto
 * produziria papel afirmando conferencia que ninguem fez. O servidor recusa de
 * novo -- esconder o icone evita o clique, nao a chamada direta.
 */
function cadeiaCompleta(beneficiario) {
  return ETAPAS.every((e) => (beneficiario.etapas_concluidas ?? []).includes(e));
}

// Repassa o par coluna/direcao para todos os cabecalhos de uma vez, em vez de
// declarar as duas props em cada um.
const ordenacao = computed(() => ({
  ordenadoPor: props.ordenadoPor,
  direcao: props.direcao,
}));

const ETAPAS = ['fornecedor', 'compdec', 'cedec'];

// `ordenavel` espelha BeneficiarioService::colunasOrdenaveis(). Manter os dois
// lados juntos e proposital: cabecalho clicavel sem coluna na whitelist do
// backend cai silenciosamente na ordenacao padrao, e o usuario clica sem
// entender por que a lista nao muda.
/**
 * Acoes da linha, definidas UMA vez.
 *
 * Extraidas do template porque agora servem a dois lugares: a celula da tabela
 * no desktop e o rodape do card no mobile. Duplicar quarenta linhas de
 * permissao e `aliasOverride` entre os dois sairia de sincronia na primeira
 * acao nova -- e divergencia aqui significa acao existindo num modo e nao no
 * outro, sem erro nenhum.
 */
function acoesDe(b) {
  // `props.permissoes`, nao `permissoes`: no TEMPLATE o Vue resolve a prop pelo
  // nome, mas dentro de <script setup> nao existe esse acucar. Ao mover estas
  // acoes do template para ca, `permissoes.editar` virou ReferenceError em
  // runtime -- e o build passa, porque nada disso e checado em tempo de
  // compilacao.
  return [
    { action: 'view',    handler: () => ir('cisternas.beneficiarios.show', b.id) },
    { action: 'edit',    handler: () => ir('cisternas.beneficiarios.edit', b.id), allowed: props.permissoes.editar },
    { action: 'history', handler: () => emit('historico', b), label: 'Serie Historica' },
    { action: 'delete',  handler: () => emit('excluir', b), allowed: props.permissoes.excluir },
    /*
      No menu de tres pontos, como no PAE: sao acoes de saida em
      documento, nao do dia a dia, e inline disputariam espaco
      com ver/editar/excluir numa coluna ja apertada.
      IMPRIMIR e PDF sao acoes SEPARADAS de proposito, e nao
      duas portas para a mesma coisa: imprimir manda a ficha
      para o papel pelo dialogo do navegador; PDF gera o
      arquivo. A regra de negocio de cada uma sera definida --
      ate la o PDF usa a mesma ficha, e gerar arquivo de
      verdade ainda depende de escolher biblioteca de PDF, que
      o NewSDC nao tem.
      `aliasOverride` reaproveita permissao existente em vez de
      inventar slug: o ActionButton monta {module}.{resource}.
      {action} e consulta can(), entao 'pdf' procuraria
      cisternas.beneficiarios.pdf -- que nao existe -- e o item
      sumiria para todo mundo menos super-admin. Mesmo defeito
      que `validar` teve nas notificacoes.
      QR aponta para a ficha PUBLICA, que abre sem login: exigir
      mais que `view` para mostrar o adesivo nao protegeria nada.
    */
    {
      action: 'print', placement: 'menu',
      aliasOverride: 'print', allowed: cadeiaCompleta(b),
      handler: () => emit('imprimir', b),
    },
    {
      action: 'pdf', placement: 'menu',
      aliasOverride: 'print', allowed: cadeiaCompleta(b),
      handler: () => emit('pdf', b),
    },
    {
      action: 'qrcode', placement: 'menu',
      aliasOverride: 'view', allowed: Boolean(b.numero_instalacao),
      handler: () => emit('qrcode', b),
    },
  ];
}


/**
 * O card do telefone mostra SEIS campos, nao os onze da tabela.
 *
 * Nome e CPF viram titulo e subtitulo. Do resto sobra o que identifica e o que
 * o usuario vem conferir: onde mora, em que lote esta e como andam analise,
 * obra e vistoria. Lote/ordem, numero de instalacao e ranqueamento ficam para
 * a ficha -- card com onze linhas nao e melhor que tabela rolando.
 */
const CAMPOS_MOBILE = [
  { key: 'municipio', label: 'Municipio' },
  { key: 'comunidade', label: 'Comunidade' },
  { key: 'situacao_analise', label: 'Analise' },
  { key: 'situacao_obra', label: 'Obra' },
  { key: 'etapas', label: 'Etapas', fullWidth: true },
];

const COLUNAS = [
  { chave: 'nome', titulo: 'Nome', ordenavel: true },
  { chave: 'cpf', titulo: 'CPF', ordenavel: true },
  { chave: 'municipio', titulo: 'Municipio', ordenavel: true },
  { chave: 'comunidade', titulo: 'Comunidade', ordenavel: true },
  { chave: 'lote', titulo: 'Lote / Ordem', ordenavel: false },
  { chave: 'numero_instalacao', titulo: 'Nº instalacao', ordenavel: false },
  { chave: 'situacao_analise', titulo: 'Analise', ordenavel: true },
  { chave: 'situacao_obra', titulo: 'Obra', ordenavel: true },
  // Ordena pelo numero de etapas concluidas (0 a 3), nao por texto: e o que os
  // selos F/C/CD desenham. Comeca em desc para a primeira tela mostrar quem
  // avancou -- em asc, as 7.106 sem vistoria nenhuma enchem a pagina de selos
  // vazios e parece que a ordenacao nao fez nada.
  { chave: 'etapas', titulo: 'Etapas', ordenavel: true, direcaoInicial: 'desc' },
  { chave: 'ranqueamento', titulo: 'Ranq.', ordenavel: false },
];

const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-100';
const TD_MONO = 'whitespace-nowrap px-3 py-2 font-mono text-sm text-slate-600 dark:text-slate-300';

const idsDaPagina = computed(() => props.beneficiarios.map((b) => b.id));

const todosMarcados = computed(
  () => idsDaPagina.value.length > 0 && idsDaPagina.value.every((id) => props.marcados.includes(id)),
);

const algunsMarcados = computed(
  () => !todosMarcados.value && idsDaPagina.value.some((id) => props.marcados.includes(id)),
);

function alternar(id) {
  const proximos = props.marcados.includes(id)
    ? props.marcados.filter((m) => m !== id)
    : [...props.marcados, id];

  emit('update:marcados', proximos);
}

/**
 * Marca ou desmarca so os IDs desta pagina, preservando o que ficou marcado em
 * outras. A acao em massa do backend valida o escopo territorial de novo, entao
 * selecao larga aqui nao vira alcance indevido.
 */
function alternarTodos() {
  if (todosMarcados.value) {
    emit('update:marcados', props.marcados.filter((id) => !idsDaPagina.value.includes(id)));

    return;
  }

  emit('update:marcados', [...new Set([...props.marcados, ...idsDaPagina.value])]);
}

function ir(rota, id) {
  router.visit(route(rota, id));
}

function formatarCpf(cpf) {
  const digitos = String(cpf ?? '').replace(/\D/g, '');

  return digitos.length === 11
    ? digitos.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
    : (cpf ?? '—');
}
</script>
