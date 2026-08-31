<template>
  <Head :title="`TDAP — ${c.placa}`" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      :title="c.placa"
      :description="`${c.marca || ''} ${c.modelo || ''}`.trim() || 'Caminhão-tanque'"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link v-if="canEdit" :href="route('tdap.caminhoes.edit', c.id)">
          <PrimaryButton>Editar</PrimaryButton>
        </Link>
        <DangerButton v-if="canDelete" @click="excluir">
          Excluir
        </DangerButton>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Veículo</h3>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Placa</dt><dd class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ c.placa }}</dd></div>
            <div><dt class="text-slate-500">Capacidade do Tanque</dt><dd class="text-slate-900 dark:text-slate-100">{{ Number(c.capacidade_m3).toFixed(2) }} m³</dd></div>
            <div><dt class="text-slate-500">Marca</dt><dd class="text-slate-900 dark:text-slate-100">{{ c.marca || '—' }}</dd></div>
            <div><dt class="text-slate-500">Modelo</dt><dd class="text-slate-900 dark:text-slate-100">{{ c.modelo || '—' }}</dd></div>
            <div><dt class="text-slate-500">Cor</dt><dd class="text-slate-900 dark:text-slate-100">{{ c.cor || '—' }}</dd></div>
            <div><dt class="text-slate-500">Ano</dt><dd class="text-slate-900 dark:text-slate-100">{{ c.ano || '—' }}</dd></div>
          </dl>
        </div>

        <div v-if="c.observacoes" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Observações</h3>
          <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ c.observacoes }}</p>
        </div>
      </div>

      <aside class="space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500">Status</p>
          <span
            :class="c.ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
            class="mt-2 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium"
          >
            {{ c.ativo ? 'Ativo' : 'Inativo' }}
          </span>
        </div>
        <div v-if="c.prestador" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500 mb-2">Prestador</p>
          <Link :href="route('tdap.prestadores.show', c.prestador.id)" class="text-sm font-medium text-blue-600 hover:text-blue-800">
            {{ c.prestador.nome }}
          </Link>
          <p class="text-xs text-slate-500 font-mono mt-1">{{ c.prestador.cnpj }}</p>
          <p v-if="c.prestador.email" class="text-xs text-slate-500 mt-1">{{ c.prestador.email }}</p>
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
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  caminhao:  { type: Object, required: true },
  canEdit:   { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

const c = computed(() => props.caminhao.data ?? props.caminhao);

function excluir() {
  if (!confirm(`Excluir o caminhão ${c.value.placa}? Esta ação não pode ser desfeita.`)) return;
  router.delete(route('tdap.caminhoes.destroy', c.value.id));
}
</script>
