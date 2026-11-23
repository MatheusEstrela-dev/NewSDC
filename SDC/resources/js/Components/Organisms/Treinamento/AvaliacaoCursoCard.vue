<script setup>
/**
 * Card "Avaliacao do curso": a etapa final obrigatoria (na sala de aula do
 * EAD e em "Minhas inscricoes"/"Meus cursos" nos eventos). Formulario de
 * avaliacao da CEDEC MG com tres perguntas obrigatorias:
 *   1. Como voce avalia a capacitacao? (barra 1..5, NotaAvaliacaoCurso)
 *   2. Em que medida o conteudo foi relevante...? (barra 1..5, NotaRelevanciaCurso)
 *   3. Sugestao, comentario ou tema para futuras palestras (texto longo)
 * Enviar conclui o curso e emite o certificado; quem pode avaliar e quando e
 * decidido no backend (AvaliacaoCursoService). `url` vem de fora porque a
 * rota difere entre o portal e a area do servidor; sem ela (pre-visualizacao
 * do professor) o formulario fica so de exibicao.
 */
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { PaperAirplaneIcon } from '@heroicons/vue/24/outline';
import BarraDeSatisfacao from '@/Components/Atoms/Input/BarraDeSatisfacao.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  url: { type: String, default: null },
  /** Opcoes da 1a pergunta (NotaAvaliacaoCurso::toSelectArray). */
  notas: { type: Array, required: true },
  /** Opcoes da 2a pergunta (NotaRelevanciaCurso::toSelectArray). */
  relevancias: { type: Array, default: () => [] },
  titulo: { type: String, default: 'Avaliação do curso' },
  subtitulo: { type: String, default: 'Última etapa: avalie o curso para concluí-lo e receber o certificado.' },
});

const LIMITE_COMENTARIO = 2000;

const emit = defineEmits(['enviada']);

const form = useForm({ nota: null, nota_relevancia: null, comentario: '' });

const bloqueado = computed(() => form.processing || !props.url);
const completo = computed(() => form.nota !== null && form.nota_relevancia !== null && form.comentario.trim() !== '');

const faltando = computed(() => [
  form.nota === null ? 'avaliação' : null,
  form.nota_relevancia === null ? 'relevância' : null,
  form.comentario.trim() === '' ? 'comentário' : null,
].filter(Boolean));

// O retorno (flash "success" do backend) e exibido pelo layout; a pagina
// recarrega as props com o curso concluido e o certificado.
function enviar() {
  if (!props.url) return;

  form.post(props.url, {
    preserveScroll: true,
    onSuccess: () => emit('enviada'),
  });
}
</script>

<template>
  <CardBase padding="lg">
    <header class="text-center">
      <Heading :level="4" class="text-slate-800 dark:text-slate-100">{{ titulo }}</Heading>
      <Text size="sm" color="muted" class="mt-1">{{ subtitulo }}</Text>
    </header>

    <CardBase padding="lg" class="mx-auto mt-5 max-w-2xl shadow-sm">
      <Heading :level="5" class="text-slate-800 dark:text-slate-100">Avaliação</Heading>
      <Text size="sm" color="muted" class="mt-1">
        Sua avaliação é muito importante para o mapeamento de melhorias e monitoramento do andamento das ações pedagógicas da CEDEC MG.
      </Text>

      <form class="mt-5 space-y-5" @submit.prevent="enviar">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-5 dark:border-slate-700 dark:bg-slate-900/40">
          <BarraDeSatisfacao
            v-model="form.nota"
            :opcoes="notas"
            nome="nota"
            legenda="Como você avalia a capacitação?"
            obrigatorio
            :disabled="bloqueado"
            :error="form.errors.nota"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-5 dark:border-slate-700 dark:bg-slate-900/40">
          <BarraDeSatisfacao
            v-model="form.nota_relevancia"
            :opcoes="relevancias"
            nome="nota_relevancia"
            legenda="Em que medida o conteúdo apresentado foi relevante para sua atuação ou contribuiu para ampliar seus conhecimentos sobre o tema?"
            obrigatorio
            :disabled="bloqueado"
            :error="form.errors.nota_relevancia"
          />
        </div>

        <FormTextarea
          v-model="form.comentario"
          label="Você tem alguma sugestão, comentário ou tema que gostaria de ver abordado em futuras palestras?"
          placeholder="Escreva sua resposta"
          :rows="4"
          :maxlength="LIMITE_COMENTARIO"
          required
          :disabled="bloqueado"
          :error="form.errors.comentario"
        />

        <p class="text-right text-xs text-slate-500 dark:text-slate-400" aria-live="polite">
          {{ form.comentario.length }}/{{ LIMITE_COMENTARIO }}
        </p>

        <div class="flex flex-col items-end gap-2">
          <Text v-if="url && faltando.length" size="xs" color="muted" aria-live="polite">Responda: {{ faltando.join(', ') }}</Text>
          <Button type="submit" variant="success" :icon="PaperAirplaneIcon" :disabled="!completo || !url" :loading="form.processing">
            Enviar
          </Button>
        </div>
      </form>
    </CardBase>
  </CardBase>
</template>
