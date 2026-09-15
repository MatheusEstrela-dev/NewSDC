<template>
  <Head :title="`TDAP — Lote ${l.numero}`" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      :title="`Lote ${l.numero}`"
      :description="descricao"
      :icon="MapIcon"
    >
      <template #actions>
        <Link v-if="canEdit" :href="route('tdap.lotes.edit', l.id)">
          <PrimaryButton>Editar</PrimaryButton>
        </Link>
        <!--
          Lote com cronograma nao pode ser excluido (FK restrict + guard no
          LoteService). O botao some para nao oferecer uma acao que falharia.
        -->
        <DangerButton v-if="canDelete && !temCronograma" @click="excluir">Excluir</DangerButton>
        <span v-else-if="canDelete" class="text-xs text-slate-500 self-center">
          {{ l.cronogramas_count }} cronograma(s) vinculado(s): exclusão bloqueada.
        </span>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Identificação</h3>
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><dt class="text-slate-500">Número</dt><dd class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ l.numero }}</dd></div>
            <div><dt class="text-slate-500">Nome</dt><dd class="text-slate-900 dark:text-slate-100">{{ l.nome || '—' }}</dd></div>
            <div><dt class="text-slate-500">Contrato</dt><dd class="font-mono text-slate-900 dark:text-slate-100">{{ l.contrato || '—' }}</dd></div>
          </dl>
        </div>

        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Vínculos</h3>
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <dt class="text-slate-500">Ata</dt>
              <dd v-if="l.ata">
                <Link :href="route('tdap.atas.show', l.ata.id)" class="font-mono font-semibold text-blue-600 hover:text-blue-800">{{ l.ata.numero }}</Link>
                <p class="text-xs text-slate-500">{{ formatDate(l.ata.dt_inicio) }} — {{ formatDate(l.ata.dt_final) }}</p>
              </dd>
            </div>
            <div>
              <dt class="text-slate-500">Municípios ({{ municipios.length }})</dt>
              <dd class="mt-1 flex flex-wrap gap-1">
                <span
                  v-for="m in municipios"
                  :key="m.id"
                  class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-300"
                >
                  {{ m.nome }}<span v-if="m.uf" class="text-slate-400">/{{ m.uf }}</span>
                </span>
                <span v-if="municipios.length === 0" class="text-slate-400">—</span>
              </dd>
            </div>
            <div>
              <dt class="text-slate-500">Prestador</dt>
              <dd v-if="l.prestador">
                <Link :href="route('tdap.prestadores.show', l.prestador.id)" class="text-blue-600 hover:text-blue-800">{{ l.prestador.nome }}</Link>
                <p class="text-xs text-slate-500 font-mono">{{ l.prestador.cnpj }}</p>
              </dd>
            </div>
          </dl>
        </div>

        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Quantidade e Valor</h3>
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><dt class="text-slate-500">Volume contratado</dt><dd class="font-mono text-lg text-slate-900 dark:text-slate-100">{{ Number(l.qtd_agua_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }} m³</dd></div>
            <div><dt class="text-slate-500">Valor unitário</dt><dd class="font-mono text-lg text-slate-900 dark:text-slate-100">R$ {{ Number(l.valor_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}/m³</dd></div>
            <div><dt class="text-slate-500">Valor total</dt><dd class="font-mono text-lg font-semibold text-blue-600">R$ {{ Number(l.valor_total).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</dd></div>
          </dl>
        </div>

        <div v-if="l.observacoes" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Observações</h3>
          <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ l.observacoes }}</p>
        </div>
      </div>

      <aside class="space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500">Status</p>
          <span
            :class="l.ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
            class="mt-2 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium"
          >
            {{ l.ativo ? 'Ativo' : 'Inativo' }}
          </span>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  lote:      { type: Object, required: true },
  canEdit:   { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

// `computed(...).value` congelava o payload no setup: depois de um reload
// parcial do Inertia a tela seguia mostrando a versao antiga do lote.
const l = computed(() => props.lote.data ?? props.lote);

const municipios = computed(() => (Array.isArray(l.value.municipios) ? l.value.municipios : []));

// Cabecalho: o nome do lote quando existe, senao um resumo dos municipios. A
// lista inteira (ha lote com mais de 30) estourava a linha do header.
const descricao = computed(() => {
  if (l.value.nome) return l.value.nome;

  const nomes = municipios.value.map(m => m.nome);

  if (nomes.length === 0) return 'Nenhum município vinculado';
  if (nomes.length <= 3) return nomes.join(', ');

  return `${nomes.slice(0, 3).join(', ')} e mais ${nomes.length - 3}`;
});

const temCronograma = computed(() => (l.value.cronogramas_count ?? 0) > 0);

function excluir() {
  if (!confirm(`Excluir o lote ${l.value.numero}?`)) return;
  router.delete(route('tdap.lotes.destroy', l.value.id));
}

// Datas vem como 'YYYY-MM-DD'. `new Date('2026-05-01')` e meia-noite UTC e, no
// fuso do Brasil, exibia o dia anterior.
function formatDate(d) {
  if (!d) return '—';
  const [ano, mes, dia] = String(d).slice(0, 10).split('-');

  return ano && mes && dia ? `${dia}/${mes}/${ano}` : '—';
}
</script>
