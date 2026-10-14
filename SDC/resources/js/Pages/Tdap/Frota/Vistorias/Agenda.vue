<template>
  <Head title="TDAP — Agenda do vistoriador" />
  <div class="w-full space-y-6 pb-8">
    <PageHeader variant="gradient"
      title="Agenda do vistoriador"
      description="Vistorias feitas, vencimentos para revistoria e os seus turnos no Plantão, num calendário só."
      :icon="TruckIcon"
      :icon-image="moduleIcon('tdap')"
      :espaco-inferior="false"
    >
      <template #actions>
        <ActionButton action="view" :allowed="true" variant="secondary" label="Histórico de vistorias" @click="router.visit(route('tdap.frota.vistorias.index'))" />
        <ActionButton action="view" :allowed="true" variant="secondary" label="Frota e Vistorias" @click="router.visit(route('tdap.frota.index'))" />
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <section class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow p-4 space-y-3">
        <div class="flex flex-wrap items-center gap-3 text-xs">
          <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 mr-auto">
            <input type="checkbox" :checked="minhas" class="rounded" @change="recarregar({ minhas: $event.target.checked })" />
            Só as vistorias que eu fiz
          </label>
          <span v-for="l in LEGENDA" :key="l.rotulo" class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-300">
            <span class="w-3 h-3 rounded-sm inline-block" :style="{ background: l.cor }"></span>{{ l.rotulo }}
          </span>
        </div>
        <EscalaCalendario
          :eventos="eventos"
          :data-inicial="dataInicial"
          :com-dia-inteiro="true"
          texto-vazio="Nenhuma vistoria, vencimento ou plantão neste período."
          @mudar-mes="aoMudarMes"
          @selecionar-vaga="abrirEvento"
        />
      </section>

      <aside class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Revistoria</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
          Caminhões ativos com a vistoria vencida, a vencer em {{ diasAlerta }} dias ou nunca vistoriados.
        </p>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          <li v-for="r in revistorias" :key="r.caminhao.id" class="py-2 flex items-center gap-2">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ r.caminhao.placa }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ r.prestador || '—' }}</p>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded" :class="classeRevistoria(r)">{{ rotuloRevistoria(r) }}</span>
            <button type="button" class="text-xs text-sky-600 hover:underline" @click="abrirHistorico(r.caminhao)">Histórico</button>
            <Link v-if="canCreate" :href="route('tdap.frota.vistorias.create', r.caminhao.id)" class="text-xs text-emerald-600 hover:underline">Vistoriar</Link>
          </li>
          <li v-if="revistorias.length === 0" class="py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
            Nenhum caminhão precisa de revistoria agora.
          </li>
        </ul>
      </aside>
    </div>

    <VistoriaHistoricoModal
      :open="historicoCaminhao !== null"
      :caminhao="historicoCaminhao"
      :pode-ver-vistoria="true"
      @close="historicoCaminhao = null"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import EscalaCalendario from '@/Components/Organisms/Plantao/EscalaCalendario.vue';
import VistoriaHistoricoModal from '@/Components/Organisms/Tdap/VistoriaHistoricoModal.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  dataInicial: { type: String, required: true },
  minhas: { type: Boolean, default: false },
  eventos: { type: Array, default: () => [] },
  revistorias: { type: Array, default: () => [] },
  diasAlerta: { type: Number, default: 30 },
  canCreate: { type: Boolean, default: false },
});

const LEGENDA = [
  { rotulo: 'Aprovada', cor: '#16a34a' },
  { rotulo: 'Reprovada', cor: '#dc2626' },
  { rotulo: 'A vencer', cor: '#d97706' },
  { rotulo: 'Vencida', cor: '#991b1b' },
  { rotulo: 'Meu plantão', cor: '#2563eb' },
];

const historicoCaminhao = ref(null);
let competencia = null;

function recarregar(extra = {}) {
  const [ano, mes] = props.dataInicial.split('-').map(Number);
  router.get(route('tdap.frota.vistorias.agenda'), {
    ano: competencia?.ano ?? ano,
    mes: competencia?.mes ?? mes,
    minhas: props.minhas ? 1 : 0,
    ...extra,
    ...(extra.minhas !== undefined ? { minhas: extra.minhas ? 1 : 0 } : {}),
  }, { preserveScroll: true, preserveState: true, only: ['eventos', 'minhas', 'dataInicial'] });
}

// O calendario emite a competencia a cada navegacao (inclusive a primeira
// montagem): so recarrega quando o mes muda de fato.
function aoMudarMes({ ano, mes }) {
  const [anoAtual, mesAtual] = props.dataInicial.split('-').map(Number);
  competencia = { ano, mes };
  if (ano !== anoAtual || mes !== mesAtual) recarregar({ ano, mes });
}

function abrirEvento(ext) {
  if (ext?.tipo === 'vistoria' && ext.vistoria_id) {
    router.visit(route('tdap.frota.vistorias.show', ext.vistoria_id));
  } else if (ext?.caminhao) {
    abrirHistorico(ext.caminhao);
  }
}

function abrirHistorico(caminhao) {
  historicoCaminhao.value = caminhao;
}

function rotuloRevistoria(r) {
  if (r.dias === null) return 'Nunca vistoriado';
  if (r.dias < 0) return `Vencida há ${-r.dias} d`;
  if (r.dias === 0) return 'Vence hoje';
  return `Vence em ${r.dias} d`;
}

function classeRevistoria(r) {
  if (r.dias === null || r.dias < 0) return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200';
  return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
}
</script>
