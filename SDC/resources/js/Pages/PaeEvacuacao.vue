<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6">
    <Head :title="`Evacuação - ${protocolo.num_protocolo}`" />

    <header class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">PAE · Resolução GMG nº 83/2024 · Anexo E</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Conferência de evacuação</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Protocolo {{ protocolo.num_protocolo }}</p>
      </div>
      <a :href="route('pae.protocolos.index')" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Voltar aos protocolos</a>
    </header>

    <p v-if="historica" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
      Consultando a versão {{ conferencia.versao }}.
      <a :href="route('pae.protocolo.evacuacao.show', protocolo.id)" class="font-semibold underline">Ir para a versão atual ({{ versao_atual }})</a>
    </p>
    <p v-if="protocolo.arquivado" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Protocolo arquivado: conferência somente para consulta.</p>

    <EvacuacaoResultadoPainel :resultado="resultado" :simulado="simulado" />

    <EvacuacaoSetoresEditor :itens="form.setores" :erros="erros" :resultado="resultado" :somente-leitura="!can_edit" @adicionar="adicionar('setores')" @remover="remover('setores', $event)" />
    <EvacuacaoRotasEditor :itens="form.rotas" :erros="erros" :resultado="resultado" :setores-disponiveis="idsSetores" :somente-leitura="!can_edit" @adicionar="adicionar('rotas')" @remover="remover('rotas', $event)" />
    <EvacuacaoAcessosEditor :itens="form.acessos" :erros="erros" :resultado="resultado" :rotas-disponiveis="idsRotas" :somente-leitura="!can_edit" @adicionar="adicionar('acessos')" @remover="remover('acessos', $event)" />
    <EvacuacaoPontosEditor :itens="form.pontos_encontro" :erros="erros" :resultado="resultado" :somente-leitura="!can_edit" @adicionar="adicionar('pontos_encontro')" @remover="remover('pontos_encontro', $event)" />

    <section v-if="conferencia && !can_edit" class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Registro da versão {{ conferencia.versao }}</h2>
      <dl class="mt-3 grid gap-3 sm:grid-cols-3">
        <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Número SEI</dt><dd class="text-slate-800 dark:text-slate-100">{{ conferencia.num_sei || '—' }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Autor</dt><dd class="text-slate-800 dark:text-slate-100">{{ conferencia.autor || '—' }}</dd></div>
        <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Data</dt><dd class="text-slate-800 dark:text-slate-100">{{ dataLocal(conferencia.created_at) }}</dd></div>
        <div class="sm:col-span-3"><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Observação</dt><dd class="whitespace-pre-line text-slate-800 dark:text-slate-100">{{ conferencia.observacao || '—' }}</dd></div>
      </dl>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="grid gap-3 sm:grid-cols-3">
        <label class="block text-sm text-slate-700 dark:text-slate-200">Tempo total declarado pelo empreendedor (mm:ss)
          <input v-model.trim="form.tte_declarado" :disabled="!can_edit" placeholder="15:00" maxlength="6" :class="[CLASSE_CAMPO, 'mt-1']" />
          <InputError :message="erros.tte_declarado" />
        </label>
        <template v-if="can_edit">
          <label class="block text-sm text-slate-700 dark:text-slate-200">Número SEI
            <input v-model.trim="form.num_sei" maxlength="100" :class="[CLASSE_CAMPO, 'mt-1']" />
            <InputError :message="erros.num_sei" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Observação
            <textarea v-model="form.observacao" rows="1" maxlength="5000" :class="[CLASSE_CAMPO, 'mt-1']" />
            <InputError :message="erros.observacao" />
          </label>
        </template>
      </div>
      <p v-if="erros.chave_idempotencia || erros.protocolo" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ erros.chave_idempotencia || erros.protocolo }}</p>
      <div v-if="can_edit" class="mt-4 flex flex-wrap justify-end gap-3">
        <button type="button" :disabled="simulando" class="rounded-lg border border-blue-700 px-4 py-2 text-sm font-semibold text-blue-700 disabled:opacity-50 dark:border-blue-300 dark:text-blue-300" @click="simular">{{ simulando ? 'Simulando...' : 'Simular' }}</button>
        <button type="button" :disabled="!simulado || form.processing || !form.num_sei" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" @click="registrar">Registrar conferência</button>
      </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Histórico de conferências</h2>
      <p v-if="!historico.length" class="mt-2 text-slate-500 dark:text-slate-400">Nenhuma conferência registrada.</p>
      <ol v-else class="mt-3 space-y-2">
        <li v-for="item in historico" :key="item.versao" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
          <span class="text-slate-700 dark:text-slate-200">Versão {{ item.versao }} · {{ item.autor || '—' }} · {{ dataLocal(item.created_at) }} · SEI {{ item.num_sei }} · TTE {{ item.tte_fmt ?? '—' }}</span>
          <span class="flex items-center gap-3">
            <span :class="item.conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ item.conforme ? 'Conforme' : 'Não conforme' }}</span>
            <a :href="route('pae.protocolo.evacuacao.versao', [protocolo.id, item.versao])" class="font-semibold text-blue-700 underline dark:text-blue-300">Abrir</a>
          </span>
        </li>
      </ol>
    </section>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import EvacuacaoResultadoPainel from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoResultadoPainel.vue';
import EvacuacaoSetoresEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoSetoresEditor.vue';
import EvacuacaoRotasEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoRotasEditor.vue';
import EvacuacaoAcessosEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoAcessosEditor.vue';
import EvacuacaoPontosEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoPontosEditor.vue';
import { usePaeEvacuacaoForm } from '@/Composables/pae/usePaeEvacuacaoForm';
import { CLASSE_CAMPO } from '@/utils/paeEvacuacao';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  conferencia: { type: Object, default: null },
  historico: { type: Array, default: () => [] },
  historica: { type: Boolean, default: false },
  versao_atual: { type: Number, default: 0 },
  can_edit: { type: Boolean, default: false },
});

const { form, resultado, simulado, simulando, erros, simular, registrar, adicionar, remover } = usePaeEvacuacaoForm(props.protocolo.id, props.conferencia);

const idsSetores = computed(() => form.setores.map((s) => s.id).filter(Boolean));
const idsRotas = computed(() => form.rotas.map((r) => r.id).filter(Boolean));

function dataLocal(valor) {
  return valor ? new Date(valor).toLocaleDateString('pt-BR') : '—';
}
</script>
