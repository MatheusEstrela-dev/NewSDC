<template>
  <aside aria-label="Informações da nota" class="flex h-full min-h-0 flex-col">
    <header class="flex shrink-0 items-center justify-between border-b border-slate-200 px-3 py-2.5 dark:border-slate-700/50">
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Informações</h2>
      <slot name="acoes" />
    </header>

    <div v-if="nota" class="min-h-0 flex-1 space-y-4 overflow-y-auto p-3">
      <div class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-semibold tabular-nums text-slate-500 dark:text-slate-400">{{ nota.codigo }}</span>
          <Badge :variant="nota.status_variante" size="sm">{{ nota.status_label }}</Badge>
        </div>
        <h3 class="text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100">{{ nota.titulo }}</h3>
        <p v-if="nota.resumo" class="whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ nota.resumo }}</p>
      </div>

      <dl class="grid grid-cols-1 gap-3">
        <CampoDeLeitura rotulo="Pasta" :valor="nota.categoria" />
        <CampoDeLeitura rotulo="Vigência a partir de" :valor="dataLonga(nota.vigencia_inicio)" />
        <CampoDeLeitura rotulo="Elaborada por" :valor="nota.elaborado_por" />
        <CampoDeLeitura rotulo="Aprovada por" :valor="nota.aprovado_por" />
      </dl>

      <section v-if="nota.versao" class="space-y-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700/50">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          Versão {{ nota.versao.numero }}
        </h4>
        <dl class="grid grid-cols-1 gap-3">
          <CampoDeLeitura rotulo="Publicada em" :valor="dataLonga(nota.versao.publicada_em)" />
          <CampoDeLeitura rotulo="Publicada por" :valor="nota.versao.publicada_por" />
          <CampoDeLeitura rotulo="Arquivo" :valor="arquivo" />
        </dl>
        <div v-if="nota.versao.changelog">
          <p class="text-xs font-medium uppercase text-slate-500 dark:text-slate-400">O que mudou</p>
          <p class="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ nota.versao.changelog }}</p>
        </div>
        <slot name="ciencia" />
      </section>

      <section v-if="nota.rotas_relacionadas.length" class="space-y-2">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Telas relacionadas</h4>
        <ul class="space-y-1">
          <li v-for="rota in nota.rotas_relacionadas" :key="rota.url">
            <Link
              :href="rota.url"
              class="inline-flex min-h-[2.5rem] items-center gap-2 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
            >
              <ArrowRightCircleIcon class="h-4 w-4 shrink-0" />
              {{ rota.rotulo }}
            </Link>
          </li>
        </ul>
      </section>
    </div>

    <div v-else class="flex flex-1 items-center justify-center p-4 text-center text-sm text-slate-500 dark:text-slate-400">
      As informações da nota aparecem aqui.
    </div>
  </aside>
</template>

<script setup>
/**
 * Sidebar direita: resumo, responsaveis, vigencia, a versao aberta e as telas
 * do sistema ligadas ao fluxo. O slot `ciencia` recebe o "Li e estou ciente".
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRightCircleIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CampoDeLeitura from '@/Components/Molecules/Cedec/CampoDeLeitura.vue';

const props = defineProps({
  nota: { type: Object, default: null },
});

const arquivo = computed(() => {
  const versao = props.nota?.versao;
  if (!versao) return null;

  const mb = versao.arquivo_tamanho / (1024 * 1024);
  const tamanho = mb >= 1 ? `${mb.toFixed(1).replace('.', ',')} MB` : `${Math.max(1, Math.round(versao.arquivo_tamanho / 1024))} KB`;

  return `${versao.arquivo_nome} (${tamanho})`;
});

function dataLonga(iso) {
  if (!iso) return null;
  // Data pura (AAAA-MM-DD) e lida como meia-noite UTC; em Brasilia isso vira o dia anterior.
  const data = iso.length === 10 ? new Date(`${iso}T12:00:00`) : new Date(iso);

  return data.toLocaleDateString('pt-BR');
}
</script>
