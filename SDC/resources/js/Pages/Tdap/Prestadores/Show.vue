<template>
  <Head :title="`TDAP — ${p.nome}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="p.nome"
      :description="p.cnpj_formatado"
      :icon="BuildingIcon"
    >
      <template #actions>
        <Link :href="route('tdap.prestadores.index')">
          <SecondaryButton>Voltar</SecondaryButton>
        </Link>
        <Link v-if="canEdit" :href="route('tdap.prestadores.edit', p.id)">
          <PrimaryButton>Editar</PrimaryButton>
        </Link>
        <DangerButton v-if="canDelete && (p.caminhoes_count ?? 0) === 0" @click="excluir">
          Excluir
        </DangerButton>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Identificação</h3>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">CNPJ</dt><dd class="font-mono text-slate-900 dark:text-slate-100">{{ p.cnpj_formatado }}</dd></div>
            <div><dt class="text-slate-500">Razão Social</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.nome }}</dd></div>
            <div><dt class="text-slate-500">Representante</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.representante || '—' }}</dd></div>
            <div><dt class="text-slate-500">E-mail</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.email }}</dd></div>
            <div><dt class="text-slate-500">Telefone 1</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.tel1 || '—' }}</dd></div>
            <div><dt class="text-slate-500">Telefone 2</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.tel2 || '—' }}</dd></div>
          </dl>
        </div>

        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Endereço</h3>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="md:col-span-2"><dt class="text-slate-500">Logradouro</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.endereco || '—' }}</dd></div>
            <div><dt class="text-slate-500">Bairro</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.bairro || '—' }}</dd></div>
            <div><dt class="text-slate-500">CEP</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.cep || '—' }}</dd></div>
            <div><dt class="text-slate-500">Cidade</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.cidade || '—' }}</dd></div>
            <div><dt class="text-slate-500">UF</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.uf || '—' }}</dd></div>
          </dl>
        </div>

        <div v-if="p.observacoes" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Observações</h3>
          <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ p.observacoes }}</p>
        </div>
      </div>

      <aside class="space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500">Status</p>
          <span
            :class="p.ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
            class="mt-2 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium"
          >
            {{ p.ativo ? 'Ativo' : 'Inativo' }}
          </span>
        </div>
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500">Caminhões vinculados</p>
          <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100 mt-1">{{ p.caminhoes_count ?? 0 }}</p>
          <Link
            v-if="(p.caminhoes_count ?? 0) > 0"
            :href="route('tdap.caminhoes.index', { prestador_id: p.id })"
            class="text-sm text-blue-600 hover:text-blue-800 mt-2 inline-block"
          >
            Ver caminhões
          </Link>
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
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  prestador: { type: Object, required: true },
  canEdit:   { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

const p = computed(() => props.prestador.data ?? props.prestador);

function excluir() {
  if (!confirm(`Excluir o prestador ${p.value.nome}? Esta ação não pode ser desfeita.`)) return;
  router.delete(route('tdap.prestadores.destroy', p.value.id));
}
</script>
