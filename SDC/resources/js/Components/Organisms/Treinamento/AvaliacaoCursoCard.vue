<script setup>
/**
 * Card "Avaliacao do curso": a etapa final obrigatoria (na sala de aula do
 * EAD e em "Minhas inscricoes"/"Meus cursos" nos eventos). Titulo e
 * subtitulo, um card interno com o convite, a barra de satisfacao de 1
 * (vermelho) a 5 (verde), o comentario e o botao Enviar. Enviar conclui o
 * curso e emite o certificado; quem pode avaliar e quando e decidido no
 * backend (AvaliacaoCursoService). `url` vem de fora porque a rota difere
 * entre o portal e a area do servidor; sem ela (pre-visualizacao do
 * professor) o formulario fica so de exibicao.
 */
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
  /** Opcoes da barra (NotaAvaliacaoCurso::toSelectArray). */
  notas: { type: Array, required: true },
  titulo: { type: String, default: 'Avaliação do curso' },
  subtitulo: { type: String, default: 'Última etapa: avalie o curso para concluí-lo e receber o certificado.' },
});

const emit = defineEmits(['enviada']);

const form = useForm({ nota: null, comentario: '' });

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
      <Heading :level="5" class="text-slate-800 dark:text-slate-100">Avaliar com a barra de satisfação</Heading>
      <Text size="sm" color="muted" class="mt-1">
        Sua opinião é muito importante para nós! Reserve um momento para avaliar sua experiência no curso e nos ajudar a melhorá-lo.
      </Text>

      <form class="mt-5 space-y-5" @submit.prevent="enviar">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-5 dark:border-slate-700 dark:bg-slate-900/40">
          <BarraDeSatisfacao
            v-model="form.nota"
            :opcoes="notas"
            legenda="Qual o seu nível de satisfação com o curso?"
            :disabled="form.processing || !url"
            :error="form.errors.nota"
          />
        </div>

        <FormTextarea
          v-model="form.comentario"
          label="Comentário (opcional)"
          placeholder="Conte-nos sobre sua experiência"
          :rows="4"
          maxlength="2000"
          :disabled="form.processing || !url"
          :error="form.errors.comentario"
        />

        <div class="flex justify-end">
          <Button type="submit" variant="success" :icon="PaperAirplaneIcon" :disabled="form.nota === null || !url" :loading="form.processing">
            Enviar
          </Button>
        </div>
      </form>
    </CardBase>
  </CardBase>
</template>
