<script setup>
import { computed, markRaw } from 'vue';
import {
  AcademicCapIcon, BookOpenIcon, BuildingOffice2Icon, ClipboardDocumentListIcon,
  ClockIcon, ExclamationTriangleIcon, UserGroupIcon,
} from '@heroicons/vue/24/outline';
import Button from '@/Components/Atoms/Button/Button.vue';

/*
 * Card de curso EAD no estilo catalogo: capa, selo de situacao, categoria,
 * titulo, descricao e carga horaria. Sem `capaUrl`, a capa e gerada (degrade +
 * icone pelo tema do titulo) ate o curso ganhar imagem propria.
 */
const props = defineProps({
  curso: { type: Object, required: true },
  // 'inscrito': mostra progresso e Continuar; 'disponivel': mostra Inscrever-se.
  modo: { type: String, default: 'inscrito', validator: (v) => ['inscrito', 'disponivel'].includes(v) },
  capaUrl: { type: String, default: null },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['inscrever']);

const CAPAS = [
  'from-[#16305c] via-[#1e4079] to-[#0b1a33]',
  'from-[#0f4c5c] via-[#14697d] to-[#082a33]',
  'from-[#7a2e0e] via-[#c2541b] to-[#3d1707]',
  'from-[#312e81] via-[#4338ca] to-[#1e1b4b]',
  'from-[#064e3b] via-[#047857] to-[#022c22]',
];

const TEMAS = [
  { termos: ['risco', 'desastre', 'alerta'], icone: ExclamationTriangleIcon },
  { termos: ['plano', 'contingencia', 'pae', 'emergencia'], icone: ClipboardDocumentListIcon },
  { termos: ['barragem', 'municip', 'prefeitura'], icone: BuildingOffice2Icon },
  { termos: ['agente', 'formacao', 'equipe'], icone: UserGroupIcon },
  { termos: ['plantao', 'turno', 'servico'], icone: ClockIcon },
];

const semAcento = (texto) => String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const capaClasse = computed(() => CAPAS[Math.abs(Number(props.curso.id) || 0) % CAPAS.length]);

const icone = computed(() => {
  const titulo = semAcento(props.curso.titulo);
  const tema = TEMAS.find(({ termos }) => termos.some((t) => titulo.includes(t)));
  return markRaw(tema?.icone ?? AcademicCapIcon);
});

const concluido = computed(() => props.modo === 'inscrito' && props.curso.aprovada && Math.round(props.curso.percentual) >= 100);

const selo = computed(() => {
  if (concluido.value) return { texto: 'Concluído', classe: 'bg-emerald-500 text-white' };
  if (props.curso.situacao === 'PAUSADO') return { texto: 'Pausado', classe: 'bg-amber-400 text-slate-900' };
  if (props.curso.situacao === 'DESATIVADO') return { texto: 'Encerrado', classe: 'bg-slate-500 text-white' };
  if (props.modo === 'disponivel') return { texto: 'Inscrições abertas', classe: 'bg-emerald-500 text-white' };
  if (!props.curso.aprovada) return { texto: props.curso.status_label || 'Pendente', classe: 'bg-amber-400 text-slate-900' };
  return { texto: 'Em andamento', classe: 'bg-sky-500 text-white' };
});
</script>

<template>
  <article class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-700/50 dark:bg-slate-800/80">
    <!-- Capa -->
    <div class="relative h-36 overflow-hidden">
      <img v-if="capaUrl" :src="capaUrl" :alt="curso.titulo" class="h-full w-full object-cover" />
      <div v-else :class="['h-full w-full bg-gradient-to-br', capaClasse]">
        <div class="absolute inset-0 opacity-25 [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,.55)_1px,transparent_0)] [background-size:14px_14px]" />
        <div class="absolute -right-6 -bottom-8 h-36 w-36 rounded-full bg-white/10 blur-xl" />
        <component :is="icone" class="absolute right-5 top-1/2 h-20 w-20 -translate-y-1/2 text-white/80 drop-shadow-lg transition-transform duration-300 group-hover:scale-110" />
        <div class="absolute bottom-3 left-4 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-white/80">
          <span class="rounded bg-white/15 px-1.5 py-0.5">EAD</span>
          <span>SDC MG · CEDEC</span>
        </div>
      </div>
      <span :class="['absolute left-3 top-3 rounded px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide shadow', selo.classe]">
        {{ selo.texto }}
      </span>
    </div>

    <!-- Corpo -->
    <div class="flex flex-1 flex-col p-4">
      <p class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
        <BookOpenIcon class="h-4 w-4" />
        <span>Curso</span>
        <span class="text-slate-300 dark:text-slate-600">|</span>
        <span class="truncate">EAD{{ curso.instrutor ? `, conduzido por ${curso.instrutor}` : '' }}</span>
      </p>

      <h3 class="mt-2 text-base font-bold leading-snug text-slate-900 dark:text-white">{{ curso.titulo }}</h3>

      <p v-if="curso.descricao" class="mt-2 line-clamp-3 text-sm text-slate-600 dark:text-slate-400">
        {{ curso.descricao }}
      </p>

      <div class="mt-auto pt-4">
        <div v-if="modo === 'inscrito' && curso.aprovada" class="mb-3">
          <div class="h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div :class="['h-full rounded-full transition-all', concluido ? 'bg-emerald-500' : 'bg-amber-500']" :style="{ width: `${Math.min(100, curso.percentual)}%` }" />
          </div>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ Math.round(curso.percentual) }}% concluído</p>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-700/60">
          <span v-if="curso.carga_horaria" class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300">
            <ClockIcon class="h-4 w-4" />
            {{ curso.carga_horaria }} horas
          </span>
          <span v-else />

          <span v-if="modo === 'disponivel' && curso.pausado" class="text-xs text-slate-500 dark:text-slate-400">Inscrições suspensas</span>
          <Button v-else-if="modo === 'disponivel'" size="sm" :loading="loading" @click="emit('inscrever', curso)">Inscrever-se</Button>
          <span v-else-if="curso.aprovada && !curso.sala_url" class="text-xs text-slate-500 dark:text-slate-400">{{ curso.situacao === 'DESATIVADO' ? 'Curso encerrado' : 'Curso pausado pelo professor' }}</span>
          <Button v-else-if="curso.aprovada" size="sm" :href="curso.sala_url">{{ concluido ? 'Revisar' : 'Continuar' }}</Button>
          <span v-else class="text-xs text-slate-500 dark:text-slate-400">Aguardando aprovação</span>
        </div>
      </div>
    </div>
  </article>
</template>
