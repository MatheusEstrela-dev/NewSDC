<template>
  <Head :title="tituloPagina" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      :title="titulo"
      :description="descricao"
      :icon="TruckIcon"
    >
      <template #actions>
        <Button
          v-if="podeAbrirCadastro && plano"
          variant="outline"
          size="md"
          :href="route('pmda.planos.edit', plano.id)"
        >
          Abrir cadastro no PMDA
        </Button>
        <Button variant="outline" size="md" :href="hrefVoltar">
          {{ voltar.rotulo }}
        </Button>
      </template>
    </TdapPageHeader>

    <div
      v-if="plano && plano.excluido"
      class="rounded-md border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-300"
    >
      Este PMDA foi excluído depois de autorizar pontos de cronograma. A consulta mostra o que ficou registrado.
    </div>

    <div
      v-if="plano && !plano.vigente && plano.vigente_id"
      class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-900/20 dark:text-amber-200"
    >
      Este plano autorizou o cronograma.
      O PMDA vigente do município agora é
      <Link :href="hrefVigente" class="font-semibold text-amber-900 underline dark:text-amber-100">
        {{ plano.vigente_protocolo || `#${plano.vigente_id}` }}
      </Link>.
    </div>

    <div v-if="!plano" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-2">Nenhum PMDA aprovado</h3>
      <p class="text-sm text-slate-500 dark:text-slate-400">
        Este município não tem PMDA aprovado com ponto de captação ativo.
        Os pontos são cadastrados no PMDA do município.
      </p>
    </div>

    <template v-else>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Plano</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Protocolo</dt>
            <dd class="text-slate-900 dark:text-slate-100">{{ plano.protocolo || `#${plano.id}` }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Situação</dt>
            <dd class="mt-1">
              <PmdaStatusBadge :label="plano.status_label" :cor="plano.status_cor" />
            </dd>
          </div>
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Aprovação</dt>
            <dd class="text-slate-900 dark:text-slate-100">{{ fmtDate(plano.data_aprov) }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Município</dt>
            <dd class="text-slate-900 dark:text-slate-100">
              {{ municipio.nome }}<span v-if="municipio.uf">/{{ municipio.uf }}</span>
            </dd>
          </div>
        </dl>
      </div>

      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">
          Pontos de captação ativos ({{ plano.pontos.length }})
        </h3>
        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
          <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
              <tr class="text-left text-slate-500 dark:text-slate-400">
                <th class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ponto</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Tipo</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Capacidade</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="p in plano.pontos" :key="p.id" class="text-slate-700 dark:text-slate-300">
                <td class="px-4 py-2.5 font-medium">{{ p.nome }}</td>
                <td class="px-4 py-2.5">{{ p.tipo_nome }}</td>
                <td class="px-4 py-2.5 text-right">{{ fmtCapacidade(p.capacidade) }}</td>
              </tr>
              <tr v-if="plano.pontos.length === 0">
                <td colspan="3" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Nenhum ponto de captação ativo neste PMDA.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">
          Cronogramas deste PMDA ({{ cronogramas.length }})
        </h3>
        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
          <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
              <tr class="text-left text-slate-500 dark:text-slate-400">
                <th class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Número</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Acompanhamento</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Vigência</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="c in cronogramas" :key="c.id" class="text-slate-700 dark:text-slate-300">
                <td class="px-4 py-2.5">
                  <Link :href="route('tdap.cronogramas.show', c.id)" class="font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                    {{ c.numero }}
                  </Link>
                </td>
                <td class="px-4 py-2.5">
                  <span class="inline-flex items-center gap-2">
                    <FaseTramiteBadge v-if="c.fase_tramite" :fase="c.fase_tramite" :encerrado="c.estado === 'encerrado'" />
                    <span v-if="c.arquivado" class="text-xs text-slate-500">Arquivado</span>
                  </span>
                </td>
                <td class="px-4 py-2.5">{{ fmtDate(c.dt_inicio) }} — {{ fmtDate(c.dt_final) }}</td>
              </tr>
              <tr v-if="cronogramas.length === 0">
                <td colspan="3" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Nenhum cronograma deste município usa este PMDA.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import FaseTramiteBadge from '@/Components/Organisms/Tdap/FaseTramiteBadge.vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import { formatDate } from '@/utils/dateFormatter';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  municipio: { type: Object, required: true },
  plano: { type: Object, default: null },
  cronogramas: { type: Array, default: () => [] },
  podeAbrirCadastro: { type: Boolean, default: false },
  voltar: { type: Object, required: true },
  consulta: { type: Object, default: () => ({}) },
});

const titulo = computed(() => {
  if (props.plano?.protocolo) return `PMDA ${props.plano.protocolo}`;
  return `PMDA de ${props.municipio.nome}`;
});

const tituloPagina = computed(() => `TDAP — ${titulo.value}`);

const descricao = computed(() => {
  const local = props.municipio.uf
    ? `${props.municipio.nome}/${props.municipio.uf}`
    : props.municipio.nome;
  return `Plano que autoriza os pontos de captação em ${local}`;
});

const hrefVigente = computed(() => route('tdap.cronogramas.pmda', paramsDaConsulta({
  pmda_plano_id: props.plano?.vigente_id,
})));

const hrefVoltar = computed(() => {
  const params = props.voltar?.parametros;
  return params?.cronograma
    ? route(props.voltar.rota, params)
    : route(props.voltar.rota);
});

function paramsDaConsulta(extra = {}) {
  const params = { municipio_id: props.municipio.id, ...extra };
  if (props.consulta?.cronograma) params.cronograma = props.consulta.cronograma;
  if (props.consulta?.origem) params.origem = props.consulta.origem;
  return params;
}

function fmtDate(valor) {
  return formatDate(valor) || '—';
}

function fmtCapacidade(valor) {
  const n = Number(valor || 0);
  return n > 0 ? `${n.toLocaleString('pt-BR', { maximumFractionDigits: 2 })} m³` : '—';
}
</script>
