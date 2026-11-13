<template>
  <nav :aria-label="`Pasta do protocolo ${protocolo.num_protocolo}`" class="min-w-0">
    <!-- Mobile: a pasta vira uma faixa rolavel; quem transborda rola dentro de si. -->
    <div v-if="isMobile" class="min-w-0 overflow-x-auto hide-scrollbar rounded-xl bg-slate-100 p-1.5 dark:bg-slate-800/30">
      <ul v-if="versoes.length > 1" class="mb-1 flex w-max gap-1 border-b border-slate-200 pb-1 dark:border-slate-700">
        <li v-for="versao in versoes" :key="versao.id">
          <Link
            :href="versao.href"
            class="flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors"
            :class="classeItem(versao.atual)"
            :aria-current="versao.atual ? 'true' : undefined"
          >
            Versão {{ versao.sufixo }}
          </Link>
        </li>
      </ul>
      <ul class="flex w-max gap-1">
        <li v-for="item in itens" :key="item.id">
          <Link
            :href="item.href"
            preserve-scroll
            class="flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition-colors"
            :class="classeItem(item.id === atual)"
            :aria-current="item.id === atual ? 'page' : undefined"
            :title="item.label"
          >
            <component :is="item.icon" class="h-4 w-4 flex-shrink-0" />
            {{ item.label }}
          </Link>
        </li>
      </ul>
      <ul v-if="subitens.length" class="mt-1 flex w-max gap-1 border-t border-slate-200 pt-1 dark:border-slate-700">
        <li v-for="sub in subitens" :key="sub.id">
          <button
            type="button"
            class="flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
            :class="classeItem(sub.id === subitemAtivo)"
            :title="sub.label"
            @click="$emit('subitem', sub.id)"
          >
            {{ sub.label }}
            <span class="rounded-full bg-slate-200 px-1.5 text-[11px] dark:bg-slate-700">{{ sub.badge }}</span>
          </button>
        </li>
      </ul>
    </div>

    <!-- Desktop: arvore lateral, como o explorador de arquivos do protocolo. -->
    <div v-else class="sticky top-4 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/50">
      <p class="px-3 pb-2 pt-1 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
        Pasta do protocolo
      </p>
      <ul class="space-y-0.5">
        <li v-for="item in itens" :key="item.id">
          <Link
            :href="item.href"
            preserve-scroll
            class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
            :class="classeItem(item.id === atual)"
            :aria-current="item.id === atual ? 'page' : undefined"
            :title="item.label"
          >
            <component :is="item.id === atual ? FolderOpenIcon : item.icon" class="h-4 w-4 flex-shrink-0" />
            <span class="min-w-0 truncate">{{ item.label }}</span>
          </Link>

          <ul v-if="item.id === atual && subitens.length" class="mb-1 mt-0.5 space-y-0.5 border-l border-slate-200 pl-2 ml-5 dark:border-slate-700">
            <li v-for="sub in subitens" :key="sub.id">
              <button
                type="button"
                class="flex w-full items-center justify-between gap-2 rounded-md py-1.5 pr-2.5 text-left text-xs font-medium transition-colors"
                :class="[classeItem(sub.id === subitemAtivo), sub.filho ? 'pl-6' : 'pl-2.5']"
                :title="sub.label"
                @click="$emit('subitem', sub.id)"
              >
                <span class="min-w-0 truncate">{{ sub.label }}</span>
                <span class="flex-shrink-0 rounded-full bg-slate-200 px-1.5 text-[11px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ sub.badge }}</span>
              </button>
            </li>
          </ul>
        </li>
      </ul>

      <template v-if="versoes.length > 1">
        <p class="mt-3 border-t border-slate-200 px-3 pb-2 pt-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:text-slate-400">
          Versões do processo
        </p>
        <ul class="space-y-0.5">
          <li v-for="versao in versoes" :key="versao.id">
            <Link
              :href="versao.href"
              class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 transition-colors"
              :class="classeItem(versao.atual)"
              :aria-current="versao.atual ? 'true' : undefined"
              :title="versao.atual ? `${versao.num_protocolo} (versão aberta)` : `Abrir ${versao.num_protocolo}`"
            >
              <span class="min-w-0 truncate text-[13px] font-semibold tabular-nums tracking-tight">{{ versao.num_protocolo }}</span>
              <span
                class="flex-shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="versao.atual ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
              >
                {{ versao.arquivado ? 'Arquivado' : versao.status_label }}
              </span>
            </Link>
          </li>
        </ul>
      </template>
    </div>
  </nav>
</template>

<script setup>
/**
 * Pasta do protocolo PAE: navegacao unica entre as telas que pertencem a um
 * protocolo (relatorio de analise, ficha do Anexo B, DCO, evacuacao, simulados
 * e anexos). Cada item e uma rota propria; a pasta so liga as telas, sem
 * mudar regra de negocio nem estado do protocolo.
 */
import { useMobile } from '@/Composables/useMobile';
import {
  ClipboardDocumentCheckIcon,
  DocumentTextIcon,
  FlagIcon,
  FolderOpenIcon,
  IdentificationIcon,
  PaperClipIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  protocolo: { type: Object, required: true },
  atual: { type: String, required: true },
  subitens: { type: Array, default: () => [] },
  subitemAtivo: { type: String, default: null },
});

defineEmits(['subitem']);

const { isMobile } = useMobile();
const page = usePage();

// O modo consulta (?readonly=1) acompanha a navegacao pela pasta; as rotas que
// nao o usam ignoram o parametro.
const somenteLeitura = computed(() => new URL(page.url, window.location.origin).searchParams.get('readonly') === '1');

const itens = computed(() => itensDe(props.protocolo.id));

function hrefDoItem(itemId, protocoloId) {
  return itensDe(protocoloId).find((item) => item.id === itemId)?.href;
}

function itensDe(id) {
  const consulta = somenteLeitura.value ? { readonly: 1 } : {};

  return [
    { id: 'relatorio', label: 'Relatório de análise', icon: DocumentTextIcon, href: route('pae.index', { protocolo_id: id, ...consulta }) },
    { id: 'ficha', label: 'Ficha cadastral (Anexo B)', icon: IdentificationIcon, href: route('pae.protocolo.ficha-anexo-b.show', { paeProtocolo: id, ...consulta }) },
    { id: 'dco', label: 'DCO', icon: ClipboardDocumentCheckIcon, href: route('pae.protocolo.dco.show', { paeProtocolo: id, ...consulta }) },
    { id: 'evacuacao', label: 'Evacuação (Anexo E)', icon: UserGroupIcon, href: route('pae.protocolo.evacuacao.show', { paeProtocolo: id, ...consulta }) },
    { id: 'simulados', label: 'Simulados (Anexo C)', icon: FlagIcon, href: route('pae.protocolo.simulados.show', { paeProtocolo: id, ...consulta }) },
    { id: 'anexos', label: 'Anexos', icon: PaperClipIcon, href: route('pae.index', { protocolo_id: id, secao: 'anexos', ...consulta }) },
  ];
}

// Versoes do mesmo processo (-001, -002...): cada uma abre na MESMA tela em
// que o analista esta, para comparar sem voltar a listagem.
const versoes = computed(() => (props.protocolo.versoes ?? []).map((versao) => ({
  ...versao,
  sufixo: versao.num_protocolo.split('-').pop(),
  href: hrefDoItem(props.atual, versao.id),
})));

function classeItem(ativo) {
  return ativo
    ? 'bg-blue-600 text-white shadow-sm'
    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700/60 dark:hover:text-white';
}
</script>
