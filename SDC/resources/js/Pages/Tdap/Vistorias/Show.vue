<template>
  <Head :title="`TDAP — Vistoria #${v.id}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Vistoria #${v.id}`"
      :description="`${v.caminhao?.placa ?? ''} — ${fmtDate(v.data)}`"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link :href="route('tdap.vistorias.index')">
          <SecondaryButton>Voltar</SecondaryButton>
        </Link>
        <Link v-if="canEdit" :href="route('tdap.vistorias.edit', v.id)">
          <PrimaryButton>Editar</PrimaryButton>
        </Link>
        <DangerButton v-if="canDelete" @click="excluir">Excluir</DangerButton>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Identificação</h3>
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><dt class="text-slate-500">Vistoriador</dt><dd>{{ v.nome }}</dd></div>
            <div><dt class="text-slate-500">Data</dt><dd>{{ fmtDate(v.data) }}</dd></div>
            <div><dt class="text-slate-500">Ficha</dt><dd class="font-mono">{{ v.ficha || '—' }}</dd></div>
            <div><dt class="text-slate-500">Edital</dt><dd>{{ v.edital || '—' }}</dd></div>
            <!-- Lacre do tanque no lugar do antigo "Lote" (que repetia o lote da ata). -->
            <div><dt class="text-slate-500">Número do lacre</dt><dd class="font-mono">{{ v.lacre || '—' }}</dd></div>
          </dl>
        </div>

        <div v-if="v.caminhao" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Caminhão vistoriado</h3>
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <dt class="text-slate-500">Placa</dt>
              <dd>
                <Link :href="route('tdap.caminhoes.show', v.caminhao.id)" class="font-mono font-semibold text-blue-600 hover:text-blue-800">
                  {{ v.caminhao.placa }}
                </Link>
              </dd>
            </div>
            <div><dt class="text-slate-500">Modelo</dt><dd>{{ v.modelo || v.caminhao.modelo || '—' }}</dd></div>
            <div><dt class="text-slate-500">Capacidade</dt><dd class="font-mono">{{ Number(v.capacidade).toFixed(2) }} m³</dd></div>
            <div><dt class="text-slate-500">Cor</dt><dd>{{ v.cor || '—' }}</dd></div>
            <div><dt class="text-slate-500">Ano</dt><dd>{{ v.ano || '—' }}</dd></div>
            <div v-if="v.caminhao.prestador">
              <dt class="text-slate-500">Prestador</dt>
              <dd>
                <Link :href="route('tdap.prestadores.show', v.caminhao.prestador.id)" class="text-blue-600 hover:text-blue-800">
                  {{ v.caminhao.prestador.nome }}
                </Link>
              </dd>
            </div>
          </dl>
        </div>

        <VistoriaChecklistGroup
          titulo="Condições Estruturais"
          :descricao="`${countOk(itensEstruturais)}/${itensEstruturais.length} itens conformes`"
          :itens="itensEstruturais"
          :form="v"
          readonly
        />

        <VistoriaChecklistGroup
          titulo="Condições do Tanque"
          :descricao="`${countOk(itensTanque)}/${itensTanque.length} itens conformes`"
          :itens="itensTanque"
          :form="v"
          readonly
        />

        <div v-if="v.observacoes" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Observações</h3>
          <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ v.observacoes }}</p>
        </div>
      </div>

      <aside class="space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500 mb-2">Parecer</p>
          <span :class="v.parecer === 'aprovada'
            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'"
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold">
            {{ v.parecer_label }}
          </span>
          <div class="mt-3">
            <span v-if="v.esta_vigente" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
              <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-blue-500"></span>
              Vigente (≤ 12 meses)
            </span>
            <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
              Expirada
            </span>
          </div>
        </div>
        <div v-if="v.user" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500">Registrada por</p>
          <p class="text-slate-900 dark:text-slate-100 font-medium mt-1">{{ v.user.name }}</p>
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
import VistoriaChecklistGroup from '@/Components/Organisms/Tdap/VistoriaChecklistGroup.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  vistoria: { type: Object, required: true },
  itensEstruturais: { type: Array, default: () => [] },
  itensTanque: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

// `computed(...).value` congelava o payload no setup: apos um reload parcial do
// Inertia a tela continuava mostrando a versao antiga da vistoria.
const v = computed(() => props.vistoria.data ?? props.vistoria);

function countOk(itens) {
  return itens.filter(i => !!v.value[i]).length;
}

function excluir() {
  if (!confirm(`Excluir vistoria #${v.value.id}?`)) return;
  router.delete(route('tdap.vistorias.destroy', v.value.id));
}

// Datas vem como 'YYYY-MM-DD'. `new Date('2026-05-01')` e meia-noite UTC e, no
// fuso do Brasil, exibia o dia anterior.
function fmtDate(d) {
  if (!d) return '—';
  const [ano, mes, dia] = String(d).slice(0, 10).split('-');

  return ano && mes && dia ? `${dia}/${mes}/${ano}` : '—';
}
</script>
