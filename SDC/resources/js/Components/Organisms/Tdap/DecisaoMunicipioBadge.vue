<template>
  <span
    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
    :class="CLASSES[status] ?? CLASSES.pendente"
    :title="titulo"
  >
    {{ ROTULOS[status] ?? ROTULOS.pendente }}
  </span>
</template>

<script setup>
/**
 * Decisao do COMPDEC sobre a viagem, para a fila da CEDEC.
 *
 * Sem isso a CEDEC aprovava para pagamento sem saber se o municipio atestou
 * o recebimento -- ou se o reprovou.
 */
import { computed } from 'vue';

const props = defineProps({
  /** pendente | confirmada | reprovada */
  status: { type: String, default: 'pendente' },
  motivo: { type: String, default: null },
});

// Classes por extenso: string dinamica some no purge do Tailwind.
const CLASSES = {
  pendente: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
  confirmada: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  reprovada: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
};

const ROTULOS = {
  pendente: 'município: aguardando',
  confirmada: 'município: recebida',
  reprovada: 'município: reprovada',
};

const titulo = computed(() => (props.status === 'reprovada' && props.motivo
  ? `Motivo: ${props.motivo}`
  : ''));
</script>
