<template>
  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-800/70">
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
            <SortableHeader>Opcoes</SortableHeader>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <tr
            v-for="b in beneficiarios"
            :key="b.id"
            class="hover:bg-slate-50 dark:hover:bg-slate-800/40"
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

            <td class="whitespace-nowrap px-3 py-2 text-right">
              <TableActions
                module="cisternas"
                resource="beneficiarios"
                :show-view="true"
                :show-history="true"
                :show-edit="permissoes.editar"
                :show-delete="permissoes.excluir"
                :show-print="cadeiaCompleta(b)"
                :show-attachments="false"
                @view="ir('cisternas.beneficiarios.show', b.id)"
                @history="$emit('historico', b)"
                @print="$emit('imprimir', b)"
                @edit="ir('cisternas.beneficiarios.edit', b.id)"
                @delete="$emit('excluir', b)"
              />
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

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';
import SortableHeader from '@/Components/Molecules/Table/SortableHeader.vue';
import SituacaoAnaliseBadge from '@/Components/Atoms/Cisterna/SituacaoAnaliseBadge.vue';
import SituacaoObraBadge from '@/Components/Atoms/Cisterna/SituacaoObraBadge.vue';
import EtapaVistoriaBadge from '@/Components/Atoms/Cisterna/EtapaVistoriaBadge.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

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

const emit = defineEmits(['update:marcados', 'excluir', 'ordenar', 'historico', 'imprimir']);

/**
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
