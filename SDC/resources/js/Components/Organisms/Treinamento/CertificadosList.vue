<script setup>
import { markRaw } from 'vue';
import { ArrowPathIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { route } from 'ziggy-js';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CertificadoSituacaoBadge from '@/Components/Molecules/Treinamento/CertificadoSituacaoBadge.vue';
import CodigoVerificacao from '@/Components/Molecules/Treinamento/CodigoVerificacao.vue';

const props = defineProps({
  certificados: {
    type: Array,
    default: () => [],
  },
  canReemitir: { type: Boolean, default: false },
  canCancelar: { type: Boolean, default: false },
});

const emit = defineEmits(['reemitir', 'cancelar']);

const ReemitirIcon = markRaw(ArrowPathIcon);
const CancelarIcon = markRaw(XCircleIcon);

// Permissao ja decidida pelo back (disponivel / canReemitir / canCancelar):
// `allowed` explicito sem module/resource, para o ActionButton nao montar um
// slug que nao existe. Certificado cancelado nao reemite nem cancela de novo.
const acoes = (certificado) => [
  {
    action: 'print',
    label: 'Ver / Imprimir certificado',
    allowed: Boolean(certificado.disponivel),
    handler: () => window.open(route('treinamentos.certificados.imprimir', certificado.id), '_blank', 'noopener'),
  },
  {
    action: 'edit',
    label: 'Reemitir certificado',
    icon: ReemitirIcon,
    allowed: props.canReemitir && !certificado.cancelado,
    handler: () => emit('reemitir', certificado),
  },
  {
    action: 'undo',
    label: 'Cancelar certificado',
    icon: CancelarIcon,
    allowed: props.canCancelar && !certificado.cancelado,
    handler: () => emit('cancelar', certificado),
  },
];

const formatarAproveitamento = (valor) => (
  valor === null || valor === undefined ? '—' : `${Number(valor).toLocaleString('pt-BR')}%`
);
</script>

<template>
  <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
      <thead class="bg-slate-50 dark:bg-slate-800/60">
        <tr class="text-left text-slate-500 dark:text-slate-400">
          <th class="px-4 py-2 font-medium">Participante</th>
          <th class="px-4 py-2 font-medium">Código de verificação</th>
          <th class="px-4 py-2 font-medium">Aproveitamento</th>
          <th class="px-4 py-2 font-medium">Situação</th>
          <th class="px-4 py-2 font-medium">Emitido em</th>
          <th class="px-4 py-2 text-right font-medium">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <tr v-for="certificado in certificados" :key="certificado.id" class="text-slate-700 dark:text-slate-300">
          <td class="px-4 py-2.5 font-medium">{{ certificado.inscrito_nome || '—' }}</td>
          <td class="px-4 py-2.5">
            <CodigoVerificacao v-if="certificado.codigo_verificacao" :codigo="certificado.codigo_verificacao" :url-validacao="certificado.url_validacao" />
            <span v-else>—</span>
          </td>
          <td class="px-4 py-2.5">{{ formatarAproveitamento(certificado.aproveitamento) }}</td>
          <td class="px-4 py-2.5"><CertificadoSituacaoBadge :certificado="certificado" /></td>
          <td class="px-4 py-2.5">{{ certificado.emitido_em ? new Date(certificado.emitido_em).toLocaleDateString('pt-BR') : '—' }}</td>
          <td class="px-4 py-2.5">
            <div class="flex justify-end">
              <ActionButton size="sm" :actions="acoes(certificado)" />
            </div>
          </td>
        </tr>
        <tr v-if="certificados.length === 0">
          <td colspan="6" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">Nenhum certificado emitido ainda.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
