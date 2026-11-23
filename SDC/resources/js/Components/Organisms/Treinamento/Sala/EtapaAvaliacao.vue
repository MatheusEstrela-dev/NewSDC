<script setup>
/**
 * Etapa final da trilha na sala de aula: a avaliacao do curso, gerada pelo
 * sistema depois da ultima pasta (nao e um item cadastrado pelo professor).
 *
 * BLOQUEADA  cadeado: a avaliacao so abre com a trilha completa.
 * LIBERADA   card de avaliacao; enviar conclui o curso e emite o certificado.
 *            No legado (concluido antes da regra) a avaliacao e opcional.
 * ENVIADA    parabens, as respostas dadas e o certificado.
 */
import { computed } from 'vue';
import { CheckBadgeIcon, LockClosedIcon, PrinterIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import AvaliacaoCursoCard from '@/Components/Organisms/Treinamento/AvaliacaoCursoCard.vue';
import { CORES_SELO } from '@/Components/Atoms/Input/BarraDeSatisfacao.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

const props = defineProps({
  situacao: { type: String, required: true, validator: (v) => ['BLOQUEADA', 'LIBERADA', 'ENVIADA'].includes(v) },
  /** SalaDeAulaProps::avaliacaoFinal (null na pre-visualizacao). */
  avaliacaoFinal: { type: Object, default: null },
  notas: { type: Array, required: true },
  /** Opcoes da pergunta de relevancia (NotaRelevanciaCurso::toSelectArray). */
  relevancias: { type: Array, default: () => [] },
  /** Itens obrigatorios que faltam para liberar a avaliacao. */
  faltam: { type: Number, default: 0 },
  urlAvaliar: { type: String, default: null },
  /** Recebe o id do certificado e devolve a URL de impressao do escopo. */
  urlCertificado: { type: Function, required: true },
  previa: { type: Boolean, default: false },
});

const avaliacao = computed(() => props.avaliacaoFinal?.avaliacao ?? null);
const certificado = computed(() => props.avaliacaoFinal?.certificado ?? null);
const legado = computed(() => props.avaliacaoFinal?.concluido === true && props.situacao === 'LIBERADA');
</script>

<template>
  <section class="p-5 sm:p-6" aria-labelledby="etapa-avaliacao-titulo">
    <!-- Bloqueada: a avaliacao so abre no final do curso -->
    <div v-if="situacao === 'BLOQUEADA'" class="mx-auto flex max-w-lg flex-col items-center py-10 text-center">
      <span class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-700/60 dark:text-slate-400">
        <LockClosedIcon class="h-7 w-7" aria-hidden="true" />
      </span>
      <Heading id="etapa-avaliacao-titulo" :level="4" class="mt-4 text-slate-800 dark:text-slate-100">Avaliação do curso</Heading>
      <Text size="sm" color="muted" class="mt-2">
        A avaliação é a última etapa e é liberada quando você concluir todos os módulos.
        <template v-if="faltam > 0">Falta{{ faltam > 1 ? 'm' : '' }} {{ faltam }} {{ faltam > 1 ? 'itens obrigatórios' : 'item obrigatório' }}.</template>
      </Text>
      <Text size="sm" color="muted" class="mt-1">Depois de avaliar, o curso é concluído e o certificado é emitido.</Text>
    </div>

    <!-- Liberada: falta so avaliar -->
    <div v-else-if="situacao === 'LIBERADA'" class="space-y-4">
      <div
        class="flex items-start gap-3 rounded-xl border px-4 py-3"
        :class="legado
          ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10'
          : 'border-amber-200 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10'"
      >
        <component
          :is="legado ? CheckBadgeIcon : SparklesIcon"
          class="mt-0.5 h-5 w-5 shrink-0"
          :class="legado ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
          aria-hidden="true"
        />
        <div class="min-w-0">
          <Heading id="etapa-avaliacao-titulo" :level="6" class="text-slate-800 dark:text-slate-100">
            <template v-if="previa">Pré-visualização da etapa final</template>
            <template v-else-if="legado">Curso concluído</template>
            <template v-else>Você concluiu todos os módulos!</template>
          </Heading>
          <Text size="sm" color="muted">
            <template v-if="previa">Todo curso termina com esta avaliação, gerada pelo sistema. O aluno só conclui o curso depois de enviá-la.</template>
            <template v-else-if="legado">Seu curso foi concluído antes da avaliação obrigatória. Se quiser, conte como foi a sua experiência.</template>
            <template v-else>Falta só avaliar o curso para concluí-lo e receber o certificado.</template>
          </Text>
        </div>
      </div>

      <AvaliacaoCursoCard
        :url="previa ? null : urlAvaliar"
        :notas="notas"
        :relevancias="relevancias"
        :subtitulo="legado ? 'Sua opinião ajuda a melhorar os próximos cursos.' : undefined"
      />

      <div v-if="legado && certificado?.disponivel" class="flex justify-center">
        <a
          :href="urlCertificado(certificado.id)"
          target="_blank"
          rel="noopener"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
        >
          <PrinterIcon class="h-4 w-4" aria-hidden="true" />
          Ver / Imprimir certificado
        </a>
      </div>
    </div>

    <!-- Enviada: curso concluido -->
    <div v-else class="mx-auto flex max-w-lg flex-col items-center py-8 text-center">
      <span class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
        <CheckBadgeIcon class="h-8 w-8" aria-hidden="true" />
      </span>
      <Heading id="etapa-avaliacao-titulo" :level="4" class="mt-4 text-slate-800 dark:text-slate-100">
        {{ avaliacaoFinal?.concluido ? 'Parabéns, você concluiu o curso!' : 'Avaliação enviada' }}
      </Heading>
      <Text size="sm" color="muted" class="mt-1">Obrigado por avaliar o curso.</Text>

      <span
        v-if="avaliacao"
        class="mt-4 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
        :class="CORES_SELO[avaliacao.nota_color] ?? CORES_SELO.slate"
      >
        Sua avaliação: {{ avaliacao.nota }} · {{ avaliacao.nota_label }}
      </span>
      <span
        v-if="avaliacao?.nota_relevancia"
        class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
        :class="CORES_SELO[avaliacao.nota_relevancia_color] ?? CORES_SELO.slate"
      >
        Relevância do conteúdo: {{ avaliacao.nota_relevancia }} · {{ avaliacao.nota_relevancia_label }}
      </span>

      <div class="mt-5">
        <a
          v-if="certificado?.disponivel"
          :href="urlCertificado(certificado.id)"
          target="_blank"
          rel="noopener"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
        >
          <PrinterIcon class="h-4 w-4" aria-hidden="true" />
          Ver / Imprimir certificado
        </a>
        <Text v-else-if="certificado?.cancelado" size="sm" color="muted">Certificado cancelado.</Text>
        <Text v-else-if="avaliacaoFinal?.concluido" size="sm" color="muted">
          Seu certificado está sendo emitido e aparecerá em "Meus certificados" em instantes.
        </Text>
      </div>
    </div>
  </section>
</template>
