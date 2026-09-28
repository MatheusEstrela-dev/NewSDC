<template>
  <!-- Mesmo corte de CatalogoCategoriasTable: tabela a partir de lg, bloco abaixo disso. -->
  <div
    v-if="isDesktop"
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
  >
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th scope="col" :class="TH">Nome</th>
            <th scope="col" :class="TH">Categoria</th>
            <th scope="col" :class="TH">Campos</th>
            <th scope="col" :class="TH">Automação</th>
            <th scope="col" :class="TH">Status</th>
            <th scope="col" class="table-actions-head w-36 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              Ações
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <template v-for="assunto in assuntos" :key="assunto.id">
            <tr class="table-row-solid transition-colors">
              <td :class="TD_FORTE">{{ assunto.nome }}</td>
              <td :class="TD">{{ assunto.categoria_nome ?? '—' }}</td>
              <td :class="TD">{{ contarCampos(assunto) }}</td>
              <td :class="TD">
                <Badge v-if="acaoLabel(assunto)" variant="info">{{ acaoLabel(assunto) }}</Badge>
                <span v-else>—</span>
              </td>
              <td :class="TD">
                <Badge :variant="assunto.ativo ? 'success' : 'default'">{{ assunto.ativo ? 'Ativo' : 'Inativo' }}</Badge>
              </td>
              <td class="table-actions-cell w-36 whitespace-nowrap px-3 py-2 text-right">
                <div class="flex items-center justify-end gap-1">
                  <ActionButton
                    action="edit"
                    module="demandas"
                    resource="chamados"
                    alias-override="manage"
                    :show-label="false"
                    size="sm"
                    tooltip-text="Editar assunto"
                    @click="$emit('editar', assunto)"
                  />
                  <ButtonIcon
                    :icon="assunto.ativo ? XCircleIcon : CheckCircleIcon"
                    :variant="assunto.ativo ? 'danger' : 'success'"
                    size="sm"
                    :title="assunto.ativo ? 'Desativar assunto' : 'Ativar assunto'"
                    @click="$emit('alternar-status', assunto)"
                  />
                  <ButtonIcon
                    :icon="ChevronDownIcon"
                    variant="secondary"
                    size="sm"
                    class="transition-transform"
                    :class="{ 'rotate-180': estaExpandido(assunto.id) }"
                    title="Campos e automação"
                    @click="alternarExpandido(assunto.id)"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="estaExpandido(assunto.id)">
              <td colspan="6" class="border-t border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700/50 dark:bg-slate-900/40">
                <AssuntoCamposEditor :assunto="assunto" />
              </td>
            </tr>
          </template>

          <tr v-if="assuntos.length === 0">
            <td colspan="6" class="p-0">
              <ListEmptyState title="Nenhum assunto cadastrado" helper='Use o botão "Novo assunto" para começar.' />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Abaixo de lg: um bloco por assunto. -->
  <div v-else class="space-y-3">
    <article
      v-for="assunto in assuntos"
      :key="assunto.id"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ assunto.nome }}</h3>
          <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
            Categoria: {{ assunto.categoria_nome ?? '—' }}
          </p>
        </div>
        <Badge :variant="assunto.ativo ? 'success' : 'default'" class="shrink-0">
          {{ assunto.ativo ? 'Ativo' : 'Inativo' }}
        </Badge>
      </header>

      <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2">
        <div class="min-w-0">
          <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">Campos</dt>
          <dd class="text-sm text-slate-700 dark:text-slate-200">{{ contarCampos(assunto) }}</dd>
        </div>
        <div class="min-w-0">
          <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">Automação</dt>
          <dd class="text-sm text-slate-700 dark:text-slate-200">{{ acaoLabel(assunto) ?? '—' }}</dd>
        </div>
      </dl>

      <footer class="mt-3 flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <ActionButton
          action="edit"
          module="demandas"
          resource="chamados"
          alias-override="manage"
          label="Editar"
          size="sm"
          @click="$emit('editar', assunto)"
        />
        <Button :variant="assunto.ativo ? 'danger' : 'success'" size="sm" @click="$emit('alternar-status', assunto)">
          {{ assunto.ativo ? 'Desativar' : 'Ativar' }}
        </Button>
        <Button variant="secondary" size="sm" @click="alternarExpandido(assunto.id)">
          Campos e automação
        </Button>
      </footer>

      <div v-if="estaExpandido(assunto.id)" class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <AssuntoCamposEditor :assunto="assunto" />
      </div>
    </article>

    <div
      v-if="assuntos.length === 0"
      class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <ListEmptyState title="Nenhum assunto cadastrado" helper='Use o botão "Novo assunto" para começar.' />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { XCircleIcon, CheckCircleIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import { useMobile } from '@/Composables/useMobile';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import AssuntoCamposEditor from '@/Components/Organisms/Demandas/Catalogo/AssuntoCamposEditor.vue';

defineProps({
  assuntos: { type: Array, default: () => [] },
});

defineEmits(['editar', 'alternar-status']);

const { isDesktop } = useMobile();

// Rotulo da acao de automacao AD; sem automacao configurada, mostra travessao.
const ACAO_LABELS = { desbloquear: 'Desbloquear', ativar: 'Ativar', resetar: 'Resetar' };

function acaoLabel(assunto) {
  return ACAO_LABELS[assunto.form_automacao?.acao] ?? null;
}

function contarCampos(assunto) {
  return (assunto.campos_dinamicos ?? []).length;
}

// Linhas expandidas (Campos e automacao). Guardado por id, nao por indice: a
// lista pode reordenar apos salvar sem derrubar o que esta aberto.
const expandidos = ref([]);

function estaExpandido(id) {
  return expandidos.value.includes(id);
}

function alternarExpandido(id) {
  expandidos.value = estaExpandido(id)
    ? expandidos.value.filter((item) => item !== id)
    : [...expandidos.value, id];
}

const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-100';
</script>
