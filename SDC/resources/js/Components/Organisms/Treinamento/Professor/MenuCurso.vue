<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { useToast } from '@/Composables/useToast';
import { pendenciasDe } from '@/Composables/treinamento/useProvaApi';

/**
 * Menu lateral do curso na area do professor (rascunho 3, P1). Compartilhado
 * por Conteudo, Alunos e Correcoes: so muda a entrada ativa.
 */
const props = defineProps({
  curso: { type: Object, required: true },
  ativo: { type: String, required: true },
  pendentes: { type: Number, default: 0 },
});

const entradas = computed(() => [
  { chave: 'conteudo', rotulo: 'Conteúdo', href: route('treinamentos.professor.conteudo', props.curso.id) },
  { chave: 'provas', rotulo: 'Provas', href: route('treinamentos.professor.provas', props.curso.id) },
  { chave: 'alunos', rotulo: 'Alunos', href: route('treinamentos.professor.alunos', props.curso.id) },
  { chave: 'correcoes', rotulo: 'Correções', href: route('treinamentos.professor.correcoes', props.curso.id), contador: props.pendentes },
  { chave: 'previa', rotulo: 'Prévia', href: route('treinamentos.professor.previa', props.curso.id) },
]);

const baseClasses = 'flex items-center justify-between gap-2 whitespace-nowrap rounded-md px-3 py-2 text-sm transition-colors';

function classes(chave) {
  return [
    baseClasses,
    chave === props.ativo
      ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-300'
      : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
  ];
}

const { show: toast } = useToast();

const urlPublicar = () => route('treinamentos.professor.publicar', props.curso.id);

// As pendencias so chegam na resposta do publicar e somem ao navegar: fora do
// Conteudo, e so quando o erro traz pendencias, leva o professor para la e repete a
// tentativa uma unica vez (bloqueada de novo) para a lista aparecer onde e exibida.
function publicar() {
  router.post(urlPublicar(), {}, {
    preserveScroll: true,
    onError: (erros) => {
      if (pendenciasDe(erros).length === 0) {
        toast(erros?.publicar ?? 'Nao foi possivel publicar o curso.', 'error');

        return;
      }

      toast('O curso nao pode ser publicado ainda. Veja as pendencias na aba Conteúdo.', 'error');
      if (props.ativo === 'conteudo') return;

      router.visit(route('treinamentos.professor.conteudo', props.curso.id), {
        onSuccess: () => router.post(urlPublicar(), {}, { preserveScroll: true }),
      });
    },
  });
}
</script>

<template>
  <nav class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible" aria-label="Menu do curso">
    <Link
      v-for="entrada in entradas"
      :key="entrada.chave"
      :href="entrada.href"
      :class="classes(entrada.chave)"
    >
      <span>{{ entrada.rotulo }}</span>
      <Badge v-if="entrada.contador > 0" variant="warning" size="sm">{{ entrada.contador }}</Badge>
    </Link>

    <button
      type="button"
      :class="[baseClasses, 'text-left', curso.publicado ? 'cursor-default text-slate-400' : 'text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-500/10']"
      :disabled="curso.publicado"
      @click="publicar"
    >
      <span>{{ curso.publicado ? 'Publicado' : 'Publicar' }}</span>
    </button>
  </nav>
</template>
