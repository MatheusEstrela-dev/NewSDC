<script setup>
import { computed } from 'vue';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

/*
 * Lista do catalogo: tabela no desktop, bloco por curso no mobile. As acoes
 * (editar, ativar/inativar) sobem por evento; quem grava e a pagina.
 */
const props = defineProps({
  cursos: { type: Array, default: () => [] },
});
defineEmits(['editar', 'alternar']);

const linhas = computed(() => props.cursos.map((c) => ({
  ...c,
  sigla_exibida: c.sigla || '—',
  situacao: c.ativo ? 'Ativo' : 'Inativo',
})));

const camposMobile = [
  { key: 'sigla_exibida', label: 'Sigla' },
  { key: 'turmas_count', label: 'Turmas' },
  { key: 'situacao', label: 'Situação' },
];
</script>

<template>
  <ListEmptyState
    v-if="cursos.length === 0"
    title="Nenhum curso encontrado"
    helper="Cadastre um curso ou ajuste a busca."
  />

  <ResponsiveTable
    v-else
    :items="linhas"
    :mobile-fields="camposMobile"
    :get-item-title="(c) => c.nome"
    :get-item-key="(c) => c.id"
  >
    <template #table>
      <div class="min-w-0 overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
            <tr>
              <th class="px-4 py-2.5 font-semibold">Nome</th>
              <th class="px-3 py-2.5 font-semibold">Sigla</th>
              <th class="px-3 py-2.5 text-right font-semibold">Turmas</th>
              <th class="px-3 py-2.5 font-semibold">Situação</th>
              <th class="px-4 py-2.5 text-right font-semibold">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700/40">
            <tr v-for="c in linhas" :key="c.id" class="text-slate-700 dark:text-slate-200">
              <td class="max-w-[24rem] px-4 py-2.5 font-medium text-slate-900 dark:text-slate-100">
                <span class="line-clamp-2" :title="c.nome">{{ c.nome }}</span>
              </td>
              <td class="px-3 py-2.5">{{ c.sigla_exibida }}</td>
              <td class="px-3 py-2.5 text-right tabular-nums">{{ c.turmas_count }}</td>
              <td class="px-3 py-2.5">
                <Badge :variant="c.ativo ? 'success' : 'neutral'">{{ c.situacao }}</Badge>
              </td>
              <td class="px-4 py-2.5">
                <div class="flex justify-end gap-2">
                  <Button variant="secondary" size="sm" @click="$emit('editar', c)">Editar</Button>
                  <Button variant="outline" size="sm" @click="$emit('alternar', c)">{{ c.ativo ? 'Inativar' : 'Ativar' }}</Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <template #mobile-actions="{ item }">
      <div class="flex gap-2">
        <Button variant="secondary" size="sm" class="min-h-[44px] flex-1" @click="$emit('editar', item)">Editar</Button>
        <Button variant="outline" size="sm" class="min-h-[44px] flex-1" @click="$emit('alternar', item)">{{ item.ativo ? 'Inativar' : 'Ativar' }}</Button>
      </div>
    </template>
  </ResponsiveTable>
</template>
