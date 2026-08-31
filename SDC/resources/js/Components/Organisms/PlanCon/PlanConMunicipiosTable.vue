<template>
  <ListContainer
    :title="title"
    :subtitle="subtitle"
    :count="total"
    :icon="BuildingOffice2Icon"
    icon-class="text-blue-500"
  >
    <table class="w-full text-left text-sm">
      <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50">
        <tr>
          <th v-if="showPlano" class="hidden px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 sm:table-cell">Cód. Mun.</th>
          <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Município</th>
          <th class="hidden px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 lg:table-cell">Código IBGE</th>
          <th v-if="showPlano" class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Plano</th>
          <th v-if="showSituacao" class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Situação</th>
          <th v-if="showDataAtualizacao" class="hidden px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 md:table-cell">Atualização</th>
          <th class="table-actions-head w-20 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
        <tr v-for="municipio in municipios" :key="municipio.planoId ?? municipio.id" class="table-row-solid transition-colors">
          <td v-if="showPlano" class="hidden px-4 py-3 font-mono text-sm text-slate-600 dark:text-slate-300 sm:table-cell">{{ municipio.codigoMunicipio ?? '—' }}</td>
          <td class="px-4 py-3">
            <p class="font-medium text-slate-800 dark:text-slate-100">{{ municipio.nome }}</p>
            <p class="mt-0.5 text-xs text-slate-500 lg:hidden">IBGE {{ municipio.codigoIbge || '—' }}</p>
          </td>
          <td class="hidden px-4 py-3 font-mono text-sm text-slate-600 dark:text-slate-300 lg:table-cell">{{ municipio.codigoIbge || '—' }}</td>
          <td v-if="showPlano" class="px-4 py-3">
            <p class="break-all text-sm text-slate-700 dark:text-slate-200">{{ municipio.arquivo || '—' }}</p>
            <p v-if="municipio.versao" class="mt-0.5 text-xs text-slate-500">versão {{ municipio.versao }}</p>
          </td>
          <td v-if="showSituacao" class="px-4 py-3"><PlanConStatusBadge :situacao="municipio.situacaoPlano" /></td>
          <td v-if="showDataAtualizacao" class="hidden px-4 py-3 text-slate-600 dark:text-slate-300 md:table-cell">{{ formatDate(municipio.dataUltimaAtualizacao) }}</td>
          <td class="table-actions-cell px-4 py-3" @click.stop>
            <div class="flex items-center justify-end gap-2">
              <ActionButton
                module="plancon"
                resource="municipios"
                size="sm"
                :actions="acoesDaLinha(municipio)"
              />
            </div>
          </td>
        </tr>
        <tr v-if="municipios.length === 0">
          <td :colspan="columnCount" class="p-0">
            <ListEmptyState :icon="BuildingOffice2Icon" :title="emptyTitle" helper="Não há registros para os filtros informados." />
          </td>
        </tr>
      </tbody>
    </table>
  </ListContainer>
</template>

<script setup>
import { computed } from 'vue';
import { BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PlanConStatusBadge from '@/Components/Molecules/PlanCon/PlanConStatusBadge.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';

const props = defineProps({
  municipios: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  title: { type: String, default: 'Municípios' },
  subtitle: { type: String, default: '' },
  emptyTitle: { type: String, default: 'Nenhum município encontrado' },
  showSituacao: { type: Boolean, default: false },
  showDataAtualizacao: { type: Boolean, default: false },
  // Colunas de plano (codigo do municipio no legado e nome do arquivo), como
  // na tela antiga. So fazem sentido na lista "com plano".
  showPlano: { type: Boolean, default: false },
});
const emit = defineEmits(['view']);

/**
 * "Visualizar" abre o plano em outra aba. O backend resolve de onde vem o
 * arquivo: da media, quando ja foi importado, ou direto do disco do legado.
 */
// window.open dentro de handler passado por prop nem sempre conta como gesto
// do usuario e o navegador bloqueia como popup. Um <a target=_blank> clicado
// e sempre aceito.
function abrirEmNovaAba(url) {
  const a = document.createElement('a');
  a.href = url;
  a.target = '_blank';
  a.rel = 'noopener';
  document.body.appendChild(a);
  a.click();
  a.remove();
}

function acoesDaLinha(municipio) {
  if (municipio.planoId) {
    return [{
      action: 'view',
      handler: () => abrirEmNovaAba(route('plancon.planos.download', municipio.planoId)),
    }];
  }

  return [{ action: 'view', handler: () => emit('view', municipio) }];
}
const columnCount = computed(() => 3 + Number(props.showSituacao) + Number(props.showDataAtualizacao) + 2 * Number(props.showPlano));
function formatDate(value) { return value ? new Date(value).toLocaleDateString('pt-BR') : '—'; }
</script>