<template>

  <Head title="Integridade de Usuários" />
  <div>

    <PageHeader
      title="Integridade de Usuários"
      description="Diagnóstico do cadastro entre contas, equipes COMPDEC e autorizações"
      :icon-image="moduleIcon('permissionamento')"
      variant="gradient"
      class="mb-6 md:mb-8"
    >
      <template #actions>
        <a
          :href="route('admin.permissions.integridade.exportar', { regra: regraSelecionada })"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm hover:shadow"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Exportar {{ regraSelecionada }}
        </a>
      </template>
    </PageHeader>

    <!-- Tabs Navigation -->
    <div class="border-b border-slate-200 dark:border-slate-700 mb-6 md:mb-8 overflow-x-auto scrollbar-hide">
      <div class="flex space-x-1 min-w-max">
        <Link
          :href="route('admin.permissions.users.index')"
          class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 border-b-2 border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Usuários
        </Link>
        <Link
          :href="route('admin.permissions.roles.index')"
          class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 border-b-2 border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Cargos
        </Link>
        <Link
          :href="route('admin.permissions.permissions.index')"
          class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 border-b-2 border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Permissões
        </Link>
        <Link
          :href="route('admin.permissions.integridade.index')"
          class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400 bg-blue-50/50 dark:bg-blue-900/10 rounded-t-lg"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
          Integridade
        </Link>
      </div>
    </div>

    <!-- Totais por nivel de confianca -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/60 dark:bg-rose-900/10 p-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">Confirmado</div>
        <div class="mt-1 text-3xl font-bold text-rose-700 dark:text-rose-300">{{ resumo.totais.confirmado }}</div>
        <p class="mt-1 text-xs text-rose-700/80 dark:text-rose-300/70">Chave forte apenas — CPF de 11 dígitos, FK presente ou ausente.</p>
      </div>
      <div class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-900/10 p-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Suspeito</div>
        <div class="mt-1 text-3xl font-bold text-amber-700 dark:text-amber-300">{{ resumo.totais.suspeito }}</div>
        <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/70">Casado por nome. Exige leitura humana e fica fora do total.</p>
      </div>
      <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 p-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Informativo</div>
        <div class="mt-1 text-3xl font-bold text-slate-700 dark:text-slate-300">{{ resumo.totais.informativo }}</div>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Estado legítimo do fluxo. Contexto, não pendência.</p>
      </div>
    </div>

    <!-- Regras por eixo -->
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 mb-6 overflow-hidden">
      <button
        type="button"
        @click="alternarRegras"
        class="w-full flex items-center justify-between gap-3 px-4 lg:px-6 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
      >
        <div class="min-w-0">
          <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">Regras de verificação</div>
          <p v-if="regrasAbertas" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            {{ totalDeRegras }} regras em {{ Object.keys(resumo.eixos).length }} eixos. Clique em uma para listar as ocorrências.
          </p>
          <p v-else class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            Exibindo <span class="font-mono font-semibold">{{ regraSelecionada }}</span> — {{ regra.titulo }}
          </p>
        </div>
        <span class="flex items-center gap-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 shrink-0">
          {{ regrasAbertas ? 'Recolher' : 'Expandir' }}
          <svg class="w-4 h-4 transition-transform" :class="regrasAbertas ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </span>
      </button>

      <div v-show="regrasAbertas" class="px-4 lg:px-6 pb-5 space-y-4 border-t border-slate-200 dark:border-slate-700 pt-4">
      <div v-for="(regras, eixo) in resumo.eixos" :key="eixo">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">{{ eixo }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
          <Link
            v-for="item in regras"
            :key="item.id"
            :href="route('admin.permissions.integridade.index', { regra: item.id })"
            preserve-scroll
            class="rounded-xl border p-4 transition-colors"
            :class="item.id === regraSelecionada
              ? 'border-blue-500 dark:border-blue-400 bg-blue-50/60 dark:bg-blue-900/20'
              : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 hover:border-slate-300 dark:hover:border-slate-600'"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-mono font-semibold text-slate-400">{{ item.id }}</span>
                  <span class="text-[10px] font-semibold uppercase px-1.5 py-0.5 rounded" :class="classeNivel(item.nivel)">{{ item.nivel }}</span>
                </div>
                <div class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100">{{ item.titulo }}</div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ item.descricao }}</p>
              </div>
              <div class="text-2xl font-bold shrink-0" :class="item.quantidade === 0 ? 'text-slate-300 dark:text-slate-600' : 'text-slate-700 dark:text-slate-200'">
                {{ item.quantidade }}
              </div>
            </div>
          </Link>
        </div>
      </div>
      </div>
    </div>

    <!-- Aviso de leitura -->
    <div class="rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/60 dark:bg-blue-900/10 p-4 mb-6 text-sm text-blue-800 dark:text-blue-200">
      Este painel apenas diagnostica: nenhuma linha daqui altera o banco. A correção continua sendo feita conta a conta na tela de edição de usuário.
    </div>

    <!-- Filtros -->
    <div class="flex flex-col md:flex-row gap-4 mb-6">
      <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          v-model="form.search"
          @input="debouncedSearch"
          placeholder="Buscar por nome..."
          class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <select
        v-model="form.orgao_id"
        @change="filter"
        class="px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 md:w-72"
      >
        <option value="">Todos os órgãos</option>
        <option v-for="orgao in orgaos" :key="orgao.id" :value="orgao.id">{{ orgao.nome }}</option>
      </select>
    </div>

    <!-- Tabela -->
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 overflow-hidden">
      <div class="px-4 lg:px-6 py-4 border-b border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-2">
          <span class="text-xs font-mono font-semibold text-slate-400">{{ regraSelecionada }}</span>
          <span class="text-[10px] font-semibold uppercase px-1.5 py-0.5 rounded" :class="classeNivel(regra.nivel)">{{ regra.nivel }}</span>
          <span class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ regra.titulo }}</span>
        </div>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ regra.descricao }}</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900/40">
            <tr class="text-left">
              <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Município</th>
              <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nome</th>
              <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">CPF</th>
              <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">E-mail</th>
              <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Evidência</th>
              <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Ação</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
            <tr v-for="(linha, indice) in linhas.data" :key="`${linha.escopo}-${linha.ref_id}-${linha.orgao_id}-${indice}`" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
              <td class="px-4 lg:px-6 py-4 text-sm text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ linha.municipio }}</td>
              <td class="px-4 lg:px-6 py-4 text-sm font-medium text-slate-800 dark:text-slate-100">{{ linha.nome }}</td>
              <td class="px-4 lg:px-6 py-4 text-sm font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ linha.documento || '—' }}</td>
              <td class="px-4 lg:px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ linha.email || '—' }}</td>
              <td class="px-4 lg:px-6 py-4 text-xs text-slate-500 dark:text-slate-400">{{ linha.evidencia }}</td>
              <td class="px-4 lg:px-6 py-4">
                <div class="flex justify-end">
                  <ActionButton :actions="acoes(linha)" size="sm" />
                </div>
              </td>
            </tr>
            <tr v-if="linhas.data.length === 0">
              <td colspan="6" class="px-4 lg:px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                Nenhuma ocorrência para esta regra com os filtros atuais.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="linhas.data.length > 0" class="px-4 lg:px-6 py-4 border-t border-slate-200 dark:border-slate-700">
        <Pagination :links="linhas.links" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';

// Quem esconde acao sem permissao e o ActionButton: ele monta o slug
// {module}.{resource}.{action} e consulta o RBAC sozinho, com fallback 'hide'.
// Por isso `acoes()` abaixo nao filtra nada -- repetir a checagem aqui criaria
// duas fontes de verdade que podem divergir em silencio.

const props = defineProps({
  resumo: Object,
  regraSelecionada: String,
  regra: Object,
  linhas: Object,
  filtros: Object,
  orgaos: Array,
});

const form = reactive({
  search: props.filtros.search || '',
  orgao_id: props.filtros.orgao_id || '',
});

const totalDeRegras = computed(() =>
  Object.values(props.resumo.eixos).reduce((soma, regras) => soma + regras.length, 0),
);

// A grade ocupa a tela inteira e so serve para escolher a regra; depois da
// escolha ela atrapalha a leitura da lista. A preferencia fica no navegador
// porque cada clique em regra e uma navegacao Inertia nova.
const PREFERENCIA_REGRAS = 'integridade.regras-abertas';

const lerPreferencia = () => {
  try {
    return localStorage.getItem(PREFERENCIA_REGRAS) !== 'false';
  } catch {
    return true;
  }
};

const regrasAbertas = ref(lerPreferencia());

const alternarRegras = () => {
  regrasAbertas.value = !regrasAbertas.value;

  try {
    localStorage.setItem(PREFERENCIA_REGRAS, String(regrasAbertas.value));
  } catch {
    // Navegador com storage bloqueado: a preferencia nao sobrevive ao reload,
    // mas a tela continua funcionando.
  }
};

/**
 * A equipe NAO tem tela propria: compdec.equipe.index e compdec.equipe.show
 * sao endpoints JSON que a aba consome via Inertia.lazy. Apontar um <Link>
 * para eles quebra com "a plain JSON response was received".
 *
 * A tela e sempre compdec.show; a aba entra por ?tab=equipe, que OrgaoShow le
 * da query string na montagem.
 */
const telaDoOrgao = (orgaoId, aba = null) =>
  route('compdec.show', aba ? { orgao: orgaoId, tab: aba } : { orgao: orgaoId });

const irPara = (href) => router.visit(href);

/**
 * Para onde a linha leva. O painel nao corrige nada, entao "acao" aqui e
 * sempre navegar ate a tela do modulo que tem o poder de mexer naquele dado:
 * conta na edicao de usuario, membro na aba de equipe do orgao.
 *
 * "Ver equipe" usa o icone de `assign` com aliasOverride 'view': sem isso ela
 * disputaria o mesmo olho de "Abrir orgao" na mesma linha, e o slug checado
 * seria compdec.equipe.assign, que nao existe.
 */
const acoes = (linha) => {
  const lista = [];
  const temOrgao = Number(linha.orgao_id) > 0;

  const verEquipe = (orgaoId) => ({
    action: 'assign',
    module: 'compdec',
    resource: 'equipe',
    aliasOverride: 'view',
    label: 'Ver equipe',
    handler: () => irPara(telaDoOrgao(orgaoId, 'equipe')),
  });

  const abrirOrgao = (orgaoId) => ({
    action: 'view',
    module: 'compdec',
    resource: 'orgaos',
    label: 'Abrir órgão',
    handler: () => irPara(telaDoOrgao(orgaoId)),
  });

  if (linha.escopo === 'usuario') {
    lista.push({
      action: 'edit',
      module: 'users',
      resource: null,
      label: 'Abrir conta',
      handler: () => irPara(route('admin.permissions.users.edit', linha.ref_id)),
    });

    if (temOrgao) {
      lista.push(abrirOrgao(linha.orgao_id));
    }
  }

  if (linha.escopo === 'equipe' && temOrgao) {
    lista.push(verEquipe(linha.orgao_id), abrirOrgao(linha.orgao_id));
  }

  if (linha.escopo === 'orgao') {
    lista.push(verEquipe(linha.ref_id), abrirOrgao(linha.ref_id));
  }

  return lista;
};

// A cor do selo e o que separa o que da para agir do que precisa de olho
// humano: vermelho so aparece em regra de chave forte.
const classeNivel = (nivel) => ({
  confirmado: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
  suspeito: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
  informativo: 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300',
}[nivel] || 'bg-slate-100 text-slate-600');

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => filter(), 300);
};

const filter = () => {
  router.get(
    route('admin.permissions.integridade.index'),
    { ...form, regra: props.regraSelecionada },
    { preserveState: true, preserveScroll: true },
  );
};
</script>
