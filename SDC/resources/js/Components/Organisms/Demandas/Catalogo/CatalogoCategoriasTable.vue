<template>
  <!--
    Tabela a partir de lg; abaixo disso, bloco. Mesmo corte de
    Organisms/Cedec/PrefeituraTable.vue: useMobile().isDesktop le a mesma media
    query do Tailwind (min-width: 1024px), entao a tabela e o bloco trocam na
    mesma largura que o resto do sistema.
  -->
  <div
    v-if="isDesktop"
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
  >
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th scope="col" :class="TH">Nome</th>
            <th scope="col" :class="TH">Categoria principal</th>
            <th scope="col" :class="TH">Descrição</th>
            <th scope="col" :class="TH">Status</th>
            <th scope="col" class="table-actions-head w-28 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              Ações
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <tr v-for="categoria in categoriasComPai" :key="categoria.id" class="table-row-solid transition-colors">
            <td :class="TD_FORTE">
              <span v-if="categoria.parent_id" class="text-slate-400 dark:text-slate-500">↳ </span>{{ categoria.nome }}
            </td>
            <td :class="TD">{{ categoria.paiNome ?? '—' }}</td>
            <td class="px-3 py-2 text-sm text-slate-700 dark:text-slate-200">
              <span class="block max-w-xs truncate" :title="categoria.descricao ?? ''">{{ categoria.descricao ?? '—' }}</span>
            </td>
            <td :class="TD">
              <Badge :variant="categoria.ativo ? 'success' : 'default'">{{ categoria.ativo ? 'Ativa' : 'Inativa' }}</Badge>
            </td>
            <td class="table-actions-cell w-28 whitespace-nowrap px-3 py-2 text-right">
              <div class="flex items-center justify-end gap-1">
                <ActionButton
                  action="edit"
                  module="demandas"
                  resource="chamados"
                  alias-override="manage"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Editar categoria"
                  @click="$emit('editar', categoria)"
                />
                <ButtonIcon
                  :icon="categoria.ativo ? XCircleIcon : CheckCircleIcon"
                  :variant="categoria.ativo ? 'danger' : 'success'"
                  size="sm"
                  :title="categoria.ativo ? 'Desativar categoria' : 'Ativar categoria'"
                  @click="$emit('alternar-status', categoria)"
                />
              </div>
            </td>
          </tr>

          <tr v-if="categoriasComPai.length === 0">
            <td colspan="5" class="p-0">
              <ListEmptyState title="Nenhuma categoria cadastrada" helper='Use o botão "Nova categoria" para começar.' />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Abaixo de lg: um bloco por categoria. -->
  <div v-else class="space-y-3">
    <article
      v-for="categoria in categoriasComPai"
      :key="categoria.id"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">
            <span v-if="categoria.parent_id" class="text-slate-400 dark:text-slate-500">↳ </span>{{ categoria.nome }}
          </h3>
          <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
            Categoria principal: {{ categoria.paiNome ?? '—' }}
          </p>
        </div>
        <Badge :variant="categoria.ativo ? 'success' : 'default'" class="shrink-0">
          {{ categoria.ativo ? 'Ativa' : 'Inativa' }}
        </Badge>
      </header>

      <p v-if="categoria.descricao" class="mt-3 min-w-0 truncate text-sm text-slate-700 dark:text-slate-200">
        {{ categoria.descricao }}
      </p>

      <footer class="mt-3 flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <ActionButton
          action="edit"
          module="demandas"
          resource="chamados"
          alias-override="manage"
          label="Editar"
          size="sm"
          @click="$emit('editar', categoria)"
        />
        <Button :variant="categoria.ativo ? 'danger' : 'success'" size="sm" @click="$emit('alternar-status', categoria)">
          {{ categoria.ativo ? 'Desativar' : 'Ativar' }}
        </Button>
      </footer>
    </article>

    <div
      v-if="categoriasComPai.length === 0"
      class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <ListEmptyState title="Nenhuma categoria cadastrada" helper='Use o botão "Nova categoria" para começar.' />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { XCircleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { useMobile } from '@/Composables/useMobile';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

const props = defineProps({
  categorias: { type: Array, default: () => [] },
});

defineEmits(['editar', 'alternar-status']);

const { isDesktop } = useMobile();

// Resolve o nome da categoria pai a partir do proprio array, sem round-trip:
// o backend ja entrega parent_id, so falta o nome para exibir na coluna.
const categoriasComPai = computed(() => {
  const porId = new Map(props.categorias.map((c) => [c.id, c]));

  return props.categorias.map((categoria) => ({
    ...categoria,
    paiNome: categoria.parent_id ? porId.get(categoria.parent_id)?.nome ?? null : null,
  }));
});

const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-100';
</script>
