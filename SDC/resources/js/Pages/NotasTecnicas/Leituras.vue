<template>
  <Head :title="`Leituras de ${nota.codigo}`" />

  <div class="w-full space-y-6 pb-8">
    <PageHeader
      :title="`Leituras de ${nota.codigo}`"
      :description="nota.titulo"
      :icon-image="moduleIcon('notas-tecnicas') ?? ''"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <ActionButton
            v-if="versaoId"
            action="spreadsheet"
            module="notas-tecnicas"
            resource="leituras"
            label="Exportar CSV"
            :allowed="true"
            @click="exportar"
          />
        </div>
      </template>
    </PageHeader>

    <div
      v-if="!versaoId"
      class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-400"
    >
      Esta nota ainda não tem versão enviada.
    </div>

    <template v-else>
      <StatCardsGrid :colunas="3" :espaco-inferior="false">
        <StatCard
          title="Já leram"
          :value="resumo.lidas"
          subtitle="Ver quem deu ciência"
          variant="success"
          :icon="CheckCircleIcon"
          clickable
          @click="filtrar({ aba: 'lidas' })"
        />
        <StatCard
          title="Faltam"
          :value="resumo.pendentes"
          subtitle="Ver quem ainda não leu"
          variant="warning"
          :icon="ClockIcon"
          clickable
          @click="filtrar({ aba: 'pendentes' })"
        />
        <StatCard
          title="Público"
          :value="resumo.publico"
          :subtitle="`${percentual}% de leitura`"
          variant="info"
          :icon="UsersIcon"
        />
      </StatCardsGrid>

      <section class="rounded-xl border border-slate-200 bg-white dark:border-slate-700/50 dark:bg-slate-900/40">
        <header class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4 dark:border-slate-700/50">
          <div class="w-full sm:w-40">
            <FormSelect
              :model-value="versaoId"
              label="Versão"
              :options="versoes"
              @update:model-value="filtrar({ versao: $event })"
            />
          </div>
          <div class="min-w-0 flex-1">
            <SearchInput v-model="busca" placeholder="Buscar por nome ou e-mail" />
          </div>
        </header>

        <ModuleTabs :tabs="abas" :active-tab="filtros.aba" class="px-4" @tab-change="filtrar({ aba: $event })" />

        <ul v-if="linhas.length" class="divide-y divide-slate-100 dark:divide-slate-800">
          <li v-for="pessoa in linhas" :key="pessoa.id" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3">
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ pessoa.name }}</p>
              <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ pessoa.email }}</p>
            </div>
            <span v-if="pessoa.lida_em" class="shrink-0 text-xs tabular-nums text-emerald-700 dark:text-emerald-400">
              Ciente em {{ dataHora(pessoa.lida_em) }}
            </span>
          </li>
        </ul>
        <ListEmptyState
          v-else
          :icon="UsersIcon"
          :title="filtros.aba === 'lidas' ? 'Ninguém leu ainda' : 'Todo o público já leu'"
          helper="Ajuste a busca ou troque de aba."
        />

        <div v-if="paginacao.last_page > 1" class="px-4 pb-4">
          <Pagination :pagination="paginacao" @page-change="filtrar({ page: $event })" />
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircleIcon, ClockIcon, UsersIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import SearchInput from '@/Components/Atoms/Input/SearchInput.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  nota: { type: Object, required: true },
  versoes: { type: Array, default: () => [] },
  versaoId: { type: Number, default: null },
  filtros: { type: Object, default: () => ({ aba: 'pendentes', busca: null }) },
  resumo: { type: Object, default: () => ({ lidas: 0, publico: 0, pendentes: 0 }) },
  pessoas: { type: Object, default: null },
});

const abas = computed(() => [
  { id: 'pendentes', label: `Faltam (${props.resumo?.pendentes ?? 0})` },
  { id: 'lidas', label: `Já leram (${props.resumo?.lidas ?? 0})` },
]);

const linhas = computed(() => props.pessoas?.data ?? []);

const paginacao = computed(() => ({
  current_page: props.pessoas?.current_page ?? 1,
  last_page: props.pessoas?.last_page ?? 1,
  per_page: props.pessoas?.per_page ?? 25,
  total: props.pessoas?.total ?? 0,
  from: props.pessoas?.from ?? null,
  to: props.pessoas?.to ?? null,
}));

const percentual = computed(() => {
  const publico = props.resumo?.publico ?? 0;

  return publico === 0 ? 0 : Math.round(((publico - props.resumo.pendentes) / publico) * 100);
});

/** Troca de aba, versao, pagina ou busca: tudo na URL, como o resto do sistema. */
function filtrar(mudanca) {
  const query = { versao: props.versaoId, aba: props.filtros.aba, busca: props.filtros.busca, ...mudanca };
  if (!('page' in mudanca)) delete query.page;

  router.get(route('notas-tecnicas.leituras', props.nota.id), Object.fromEntries(
    Object.entries(query).filter(([, valor]) => valor !== null && valor !== undefined && valor !== ''),
  ), { preserveState: true, preserveScroll: true, replace: true });
}

const busca = ref(props.filtros.busca ?? '');
let temporizador = null;

watch(busca, (valor) => {
  clearTimeout(temporizador);
  if (valor.trim() === (props.filtros.busca ?? '')) return;
  temporizador = setTimeout(() => filtrar({ busca: valor.trim() }), 400);
});

onBeforeUnmount(() => clearTimeout(temporizador));

// Download e navegacao do navegador, nao visita Inertia.
function exportar() {
  window.location.href = route('notas-tecnicas.leituras.exportar', { nota: props.nota.id, versao: props.versaoId });
}

function dataHora(iso) {
  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>
