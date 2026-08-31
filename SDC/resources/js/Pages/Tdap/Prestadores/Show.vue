<template>
  <Head :title="`TDAP — ${p.nome}`" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      :title="p.nome"
      :description="p.cnpj_formatado"
      :icon="BuildingIcon"
    >
      <template #actions>
        <Link v-if="canEdit" :href="route('tdap.prestadores.edit', p.id)">
          <PrimaryButton>Editar</PrimaryButton>
        </Link>
        <DangerButton v-if="canDelete" :disabled="temCaminhoes" :title="tituloExcluir" @click="excluir">
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
            <div><dt class="text-slate-500">Telefone 1</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.tel1_formatado || '—' }}</dd></div>
            <div><dt class="text-slate-500">Telefone 2</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.tel2_formatado || '—' }}</dd></div>
          </dl>
        </div>

        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Endereço</h3>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="md:col-span-2"><dt class="text-slate-500">Logradouro</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.endereco || '—' }}</dd></div>
            <div><dt class="text-slate-500">Bairro</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.bairro || '—' }}</dd></div>
            <div><dt class="text-slate-500">CEP</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.cep_formatado || '—' }}</dd></div>
            <div><dt class="text-slate-500">Cidade</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.cidade || '—' }}</dd></div>
            <div><dt class="text-slate-500">UF</dt><dd class="text-slate-900 dark:text-slate-100">{{ p.uf || '—' }}</dd></div>
          </dl>
        </div>

        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Caminhões-tanque</h3>
            <Link
              v-if="canEdit"
              :href="route('tdap.caminhoes.create', { prestador_id: p.id })"
              class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400"
            >
              + Cadastrar caminhão
            </Link>
          </div>

          <ul v-if="caminhoes.length" class="divide-y divide-slate-200 dark:divide-slate-800 text-sm">
            <li v-for="caminhao in caminhoes" :key="caminhao.id" class="flex items-center justify-between gap-3 py-2">
              <Link
                :href="route('tdap.caminhoes.show', caminhao.id)"
                class="font-mono font-semibold text-slate-900 hover:text-blue-600 dark:text-slate-100"
              >
                {{ caminhao.placa }}
              </Link>
              <span class="truncate text-slate-500 dark:text-slate-400">{{ caminhao.marca_modelo || '—' }}</span>
              <span class="whitespace-nowrap text-slate-600 dark:text-slate-300">{{ caminhao.capacidade_m3 }} m³</span>
              <TdapStatusBadge :active="caminhao.ativo" />
            </li>
          </ul>

          <!-- Sem caminhao o prestador nao pode entrar em cronograma: a regra
               de ativacao exige vistoria vigente por caminhao alocado. -->
          <p v-else class="text-sm text-slate-500 dark:text-slate-400">
            Nenhum caminhão cadastrado. O prestador só pode ser alocado em cronograma depois de
            cadastrar ao menos um caminhão-tanque com vistoria aprovada.
          </p>
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
          <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100 mt-1">{{ totalCaminhoes }}</p>
          <Link
            v-if="temCaminhoes"
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
import TdapStatusBadge from '@/Components/Atoms/Tdap/TdapStatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  prestador: { type: Object, required: true },
  canEdit:   { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

const p = computed(() => props.prestador.data ?? props.prestador);
const caminhoes = computed(() => p.value.caminhoes ?? []);
const totalCaminhoes = computed(() => p.value.caminhoes_count ?? caminhoes.value.length);
const temCaminhoes = computed(() => totalCaminhoes.value > 0);

/**
 * O botao aparece desabilitado, e nao escondido: antes ele simplesmente
 * desaparecia quando havia caminhao vinculado e ninguem entendia por que a
 * exclusao "sumiu". O guard de verdade continua no PrestadorService.
 */
const tituloExcluir = computed(() => (temCaminhoes.value
  ? 'Remova os caminhões vinculados antes de excluir o prestador.'
  : 'Excluir prestador'));

function excluir() {
  if (temCaminhoes.value) return;
  if (!confirm(`Excluir o prestador ${p.value.nome}? Esta ação não pode ser desfeita.`)) return;

  router.delete(route('tdap.prestadores.destroy', p.value.id));
}
</script>
