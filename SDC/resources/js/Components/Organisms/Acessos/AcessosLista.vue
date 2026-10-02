<template>
  <ListaResponsiva :itens="cadastros" :colunas="COLUNAS" rotulo-acoes="Ação" acoes-fixas :vazio="VAZIO">
    <template #linha="{ item, td, tdForte }">
      <tr class="table-row-solid transition-colors">
        <td :class="tdForte"><span class="block max-w-[14rem] truncate" :title="item.nome">{{ item.nome }}</span></td>
        <td :class="td"><span class="block max-w-[8rem] truncate" :title="item.login_ad">{{ item.login_ad || '—' }}</span></td>
        <td :class="td">•••{{ item.cpf_final }}</td>
        <td :class="td"><span class="block max-w-[8rem] truncate" :title="item.setor">{{ item.setor || '—' }}</span></td>
        <td :class="td"><span class="block max-w-[8rem] truncate" :title="item.cargo">{{ item.cargo || '—' }}</span></td>
        <td :class="td"><StatusAcessoBadge :status="item.status" /></td>
        <td class="table-actions-cell px-3 py-2 text-right">
          <Button :href="`/acessos/${item.id}`" :variant="ActionVariants.view" size="sm" :title="`Abrir cadastro de ${item.nome}`">Abrir</Button>
        </td>
      </tr>
    </template>

    <template #cartao="{ item }">
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100" :title="item.nome">{{ item.nome }}</h3>
          <p class="truncate text-xs text-slate-500 dark:text-slate-400" :title="item.login_ad">{{ item.login_ad || 'Sem login AD' }}</p>
        </div>
        <StatusAcessoBadge class="shrink-0" :status="item.status" />
      </header>
      <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
        <div class="min-w-0">
          <dt class="text-slate-500 dark:text-slate-400">CPF final</dt>
          <dd class="text-slate-700 dark:text-slate-200">•••{{ item.cpf_final }}</dd>
        </div>
        <div class="min-w-0">
          <dt class="text-slate-500 dark:text-slate-400">Setor</dt>
          <dd class="truncate text-slate-700 dark:text-slate-200" :title="item.setor">{{ item.setor || '—' }}</dd>
        </div>
        <div class="col-span-2 min-w-0">
          <dt class="text-slate-500 dark:text-slate-400">Cargo</dt>
          <dd class="truncate text-slate-700 dark:text-slate-200" :title="item.cargo">{{ item.cargo || '—' }}</dd>
        </div>
      </dl>
      <footer class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <!-- Alvo de 40px no toque, com rotulo visivel. -->
        <Button :href="`/acessos/${item.id}`" :variant="ActionVariants.view" size="md" full-width class="min-h-10">Abrir cadastro</Button>
      </footer>
    </template>
  </ListaResponsiva>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import { ActionVariants } from '@/Components/Atoms/Button/ActionButton.vue';
import ListaResponsiva from '@/Components/Molecules/List/ListaResponsiva.vue';
import StatusAcessoBadge from '@/Components/Atoms/Acessos/StatusAcessoBadge.vue';

defineProps({
  cadastros: { type: Array, required: true },
});

const COLUNAS = ['Nome', 'Login AD', 'CPF final', 'Setor', 'Cargo', 'Status'];
const VAZIO = { titulo: 'Nenhum cadastro encontrado', ajuda: 'Ajuste os filtros ou cadastre um novo acesso.' };
</script>
