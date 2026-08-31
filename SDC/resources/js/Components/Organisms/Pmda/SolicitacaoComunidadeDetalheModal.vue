<script setup>
/**
 * Detalhamento de uma solicitacao de inclusao de comunidade para a fila da CEDEC.
 *
 * O card da Central de Analises mostra so nome, municipio e data -- pouco para
 * decidir. Aqui o operador ve coordenadas, quem pediu e de qual PMDA veio antes
 * de aprovar ou rejeitar; por isso as acoes ficam neste modal, e nao no card.
 */
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';
import { CheckIcon, XMarkIcon, MapPinIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  show: { type: Boolean, default: false },
  solicitacao: { type: Object, default: null },
  podeDecidir: { type: Boolean, default: false },
  processando: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'aprovar', 'rejeitar']);

const fmtDataHora = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
};

const campos = computed(() => {
  const s = props.solicitacao;
  if (!s) return [];

  return [
    { label: 'Município', valor: s.municipio ?? '—' },
    { label: 'Solicitante', valor: s.solicitante?.nome ?? '—', hint: s.solicitante?.email },
    { label: 'Solicitada em', valor: fmtDataHora(s.created_at) },
    { label: 'PMDA de origem', valor: s.plano_protocolo ?? '—', mono: true },
  ];
});

const temCoordenadas = computed(() =>
  Boolean(props.solicitacao?.latitude && props.solicitacao?.longitude)
);

function abrirNoMapa() {
  const { latitude, longitude } = props.solicitacao;
  window.open(`https://www.google.com/maps?q=${latitude},${longitude}`, '_blank');
}
</script>

<template>
  <Modal :show="show" max-width="lg" @close="emit('close')">
    <div v-if="solicitacao" class="space-y-4 p-5">
      <header class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ solicitacao.nome }}</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">Solicitação de inclusão de comunidade</p>
        </div>
        <PmdaStatusBadge :label="solicitacao.status_label" :cor="solicitacao.status_cor" />
      </header>

      <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
        <div v-for="campo in campos" :key="campo.label">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ campo.label }}</dt>
          <dd class="text-sm text-slate-800 dark:text-slate-200" :class="campo.mono ? 'font-mono' : ''">
            {{ campo.valor }}
          </dd>
          <dd v-if="campo.hint" class="text-xs text-slate-400">{{ campo.hint }}</dd>
        </div>
      </dl>

      <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700/50">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Coordenadas</p>
            <p v-if="temCoordenadas" class="font-mono text-sm text-slate-800 dark:text-slate-200">
              {{ solicitacao.latitude }}, {{ solicitacao.longitude }}
            </p>
            <p v-else class="text-sm text-amber-600 dark:text-amber-500">
              Não informadas pelo município.
            </p>
          </div>
          <Button v-if="temCoordenadas" variant="secondary" size="sm" @click="abrirNoMapa">
            <MapPinIcon class="mr-1 h-4 w-4" /> Abrir no mapa
          </Button>
        </div>
      </div>

      <p v-if="solicitacao.motivo_rejeicao" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300">
        Motivo da rejeição: {{ solicitacao.motivo_rejeicao }}
      </p>

      <div class="flex flex-wrap justify-end gap-2 pt-1">
        <Button variant="secondary" size="sm" @click="emit('close')">Fechar</Button>
        <template v-if="podeDecidir">
          <Button variant="danger" size="sm" :disabled="processando" @click="emit('rejeitar', solicitacao)">
            <XMarkIcon class="mr-1 h-4 w-4" /> Rejeitar
          </Button>
          <Button variant="success" size="sm" :disabled="processando" :loading="processando" @click="emit('aprovar', solicitacao)">
            <CheckIcon class="mr-1 h-4 w-4" /> Aprovar
          </Button>
        </template>
      </div>
    </div>
  </Modal>
</template>
