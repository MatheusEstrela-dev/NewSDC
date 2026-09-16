<template>
  <span
    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
    :class="CLASSES[situacao] ?? CLASSES.sem_vistoria"
    :title="titulo"
  >
    <span class="w-1.5 h-1.5 rounded-full" :class="PONTO[situacao] ?? PONTO.sem_vistoria"></span>
    {{ rotulo }}
  </span>
</template>

<script setup>
/**
 * Situacao de vistoria de um caminhao, em tres estados.
 *
 * Nao sao dois. "Sem vistoria" e "vistoria vencida" parecem a mesma coisa --
 * o veiculo nao pode rodar -- mas pedem acoes diferentes: cadastrar a primeira
 * vistoria ou renovar a que expirou. Achatar em "nao apto" tira do operador
 * justamente a informacao que diz o que fazer.
 *
 * O criterio de apto e o mesmo que CronogramaService::podeAtivar aplica:
 * vistoria aprovada nos ultimos 12 meses.
 */
import { computed } from 'vue';

const props = defineProps({
  /** 'apto' | 'vencida' | 'sem_vistoria' */
  situacao: { type: String, default: 'sem_vistoria' },
  /** Dias ate o fim da vigencia; negativo = ja venceu. */
  diasRestantes: { type: Number, default: null },
});

// Classes por extenso: string dinamica (`bg-${cor}-100`) some no purge do
// Tailwind. Mesma razao documentada em Atas/Index.vue.
const CLASSES = {
  apto: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  vencida: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  sem_vistoria: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
};

const PONTO = {
  apto: 'bg-emerald-500',
  vencida: 'bg-amber-500',
  sem_vistoria: 'bg-red-500',
};

const rotulo = computed(() => {
  if (props.situacao === 'sem_vistoria') return 'Sem vistoria';

  if (props.situacao === 'apto') {
    const dias = props.diasRestantes;

    // Vigente mas perto do fim: avisar antes vale mais que dizer "apto".
    if (dias !== null && dias >= 0 && dias <= 30) {
      return dias === 0 ? 'Vence hoje' : `Vence em ${dias}d`;
    }

    return 'Apto';
  }

  return props.diasRestantes !== null && props.diasRestantes < 0
    ? `Vencida há ${Math.abs(props.diasRestantes)}d`
    : 'Vencida';
});

const titulo = computed(() => ({
  apto: 'Vistoria aprovada e dentro dos 12 meses de vigência',
  vencida: 'A última vistoria passou dos 12 meses — o caminhão não pode operar',
  sem_vistoria: 'Este caminhão nunca foi vistoriado',
}[props.situacao] ?? ''));
</script>
