<template>
  <div>
    <ListEmptyState
      v-if="linhas.length === 0"
      :icon="UsersIcon"
      title="Nenhuma pessoa encontrada"
      helper="Ninguém concluiu os cursos escolhidos para o recorte informado."
    />
    <template v-else>
      <ResponsiveTable
        :items="linhas"
        :mobile-fields="camposMobile"
        :get-item-title="(l) => l.nome"
        :get-item-subtitle="(l) => l.cpf"
        :get-item-key="(l) => l.identidade"
      >
        <template #table>
          <div class="min-w-0 overflow-x-auto">
            <table class="w-full text-left text-xs lg:text-sm">
              <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                <tr>
                  <th class="px-4 py-2.5 font-semibold">Nome</th>
                  <th class="px-3 py-2.5 font-semibold">CPF</th>
                  <th class="px-3 py-2.5 font-semibold">Tipo</th>
                  <th class="px-3 py-2.5 font-semibold">Órgão</th>
                  <th class="px-3 py-2.5 font-semibold">Município</th>
                  <th class="px-3 py-2.5 font-semibold">REDEC</th>
                  <th class="px-4 py-2.5 font-semibold">Conclusão</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700/40">
                <tr v-for="l in linhas" :key="l.identidade" class="align-top text-slate-700 dark:text-slate-200">
                  <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-slate-100">{{ l.nome }}</td>
                  <td class="whitespace-nowrap px-3 py-2.5 tabular-nums">{{ l.cpf }}</td>
                  <td class="px-3 py-2.5">{{ l.tipo }}</td>
                  <td class="px-3 py-2.5">{{ l.orgao }}</td>
                  <td class="px-3 py-2.5">{{ l.municipio }}</td>
                  <td class="px-3 py-2.5">{{ l.redec }}</td>
                  <td class="px-4 py-2.5">
                    <p v-for="c in l.cursos" :key="c.curso_id" class="whitespace-nowrap">{{ c.nome }}: {{ c.data }}</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </ResponsiveTable>

      <div v-if="paginacao && paginacao.last_page > 1" class="border-t border-slate-200 px-5 py-4 dark:border-slate-700/40">
        <Pagination class="!mt-0" :pagination="paginacao" @page-change="$emit('pagina', $event)" />
      </div>
    </template>
  </div>
</template>

<script setup>
/**
 * Aba "Pessoas": quem concluiu os cursos, 20 por pagina (paginacao do Laravel
 * em `meta`). Ate `md` cada pessoa vira um bloco (ResponsiveTable).
 */
import { computed } from 'vue';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';

const props = defineProps({
  pessoas: { type: Object, default: () => ({ data: [], meta: null }) },
});

defineEmits(['pagina']);

const formatarData = (iso) => (iso ? iso.split('-').reverse().join('/') : '');

const linhas = computed(() => (props.pessoas?.data ?? []).map((p) => ({
  ...p,
  orgao: p.orgao ?? '—',
  municipio: p.municipio ?? '—',
  redec: p.redec ?? '—',
  cursos: (p.cursos ?? []).map((c) => ({ ...c, data: formatarData(c.concluido_em) })),
  conclusoes: (p.cursos ?? []).map((c) => `${c.nome}: ${formatarData(c.concluido_em)}`).join(' | '),
})));

const camposMobile = [
  { key: 'tipo', label: 'Tipo' },
  { key: 'orgao', label: 'Órgão' },
  { key: 'municipio', label: 'Município' },
  { key: 'redec', label: 'REDEC' },
  { key: 'conclusoes', label: 'Conclusão', fullWidth: true },
];

const paginacao = computed(() => {
  const m = props.pessoas?.meta ?? props.pessoas;
  if (!m || m.current_page === undefined) return null;

  return { current_page: m.current_page, last_page: m.last_page ?? 1, per_page: m.per_page ?? 20, total: m.total ?? 0 };
});
</script>
