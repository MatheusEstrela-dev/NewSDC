<script setup>
import { computed } from 'vue';
import {
  AcademicCapIcon,
  ArrowRightIcon,
  BookOpenIcon,
  CalendarDaysIcon,
  ComputerDesktopIcon,
  Squares2X2Icon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import EnvioDeCapa from '@/Components/Molecules/Treinamento/EnvioDeCapa.vue';

/*
 * Pastas das categorias de oferta (Cursos livres, Com tutoria, Presencial,
 * Eventos, Integracao de Saberes) das vitrines de cursos. Cada pasta e um
 * banner no formato do card de curso: foto ocupando o card, painel de vidro
 * escurecido embaixo com selos (categoria, quantidade), nome, escopo e o botao
 * "Explorar categoria". Clicar emite `abrir` com o valor da categoria; a pagina
 * navega para ?categoria= (entra no historico). Os itens vem prontos do backend
 * (CategoriaOferta::mosaico; `resumo` opcional, ex.: "2 em andamento ·
 * 3 disponíveis"). Pasta "em breve" nao e clicavel; com `desabilitarVazias`,
 * a pasta ativa sem nenhuma capacitacao tambem nao (as vitrines de aluno e portal;
 * a Area do Gestor deixa aberta para gerenciar). Com `podeEditarCapa`, cada
 * pasta ganha o botao de enviar/trocar a foto (fora do botao da pasta: botao
 * dentro de botao nao e HTML valido).
 */
const props = defineProps({
  categorias: { type: Array, default: () => [] },
  // Banners mais baixos, para quando divide a tela com outras secoes.
  compacto: { type: Boolean, default: false },
  podeEditarCapa: { type: Boolean, default: false },
  // Pasta ativa com total 0 fica sem clique, com o aviso "Nenhuma capacitação por enquanto".
  desabilitarVazias: { type: Boolean, default: false },
  titulo: { type: String, default: 'Capacitações por categoria' },
  descricao: { type: String, default: 'Navegue pelas categorias para explorar o escopo completo das capacitações disponíveis.' },
  cabecalho: { type: Boolean, default: true },
});

const emit = defineEmits(['abrir']);

const ICONES = {
  'computer-desktop': ComputerDesktopIcon,
  'academic-cap': AcademicCapIcon,
  users: UsersIcon,
  calendar: CalendarDaysIcon,
  'book-open': BookOpenIcon,
};

// Fundo da pasta sem foto. As classes ficam aqui (e nao no PHP) para o Tailwind enxerga-las.
const GRADIENTES = {
  sky: 'from-sky-500 to-blue-800',
  indigo: 'from-indigo-500 to-violet-800',
  emerald: 'from-emerald-500 to-teal-800',
  amber: 'from-amber-500 to-orange-700',
  slate: 'from-slate-500 to-slate-800',
};

// Selo da quantidade na cor da categoria (o selo "Categoria" e neutro).
const SELOS = {
  sky: 'bg-sky-500/90',
  indigo: 'bg-indigo-500/90',
  emerald: 'bg-emerald-500/90',
  amber: 'bg-orange-500/90',
  slate: 'bg-slate-500/90',
};

const ativas = computed(() => props.categorias.filter((c) => !c.em_breve));
const totalDeCursos = computed(() => ativas.value.reduce((soma, c) => soma + Number(c.total ?? 0), 0));
const resumo = computed(() => {
  const n = ativas.value.length;
  return `${n} ${n === 1 ? 'categoria' : 'categorias'} · ${totalDeCursos.value} ${totalDeCursos.value === 1 ? 'capacitação' : 'capacitações'} no total`;
});

const vazia = (categoria) => props.desabilitarVazias && !categoria.em_breve && Number(categoria.total ?? 0) === 0;
const inativa = (categoria) => categoria.em_breve || vazia(categoria);

function rotuloDe(categoria) {
  if (categoria.em_breve) return `${categoria.rotulo} (em breve)`;
  if (vazia(categoria)) return `${categoria.rotulo}: nenhuma capacitação por enquanto`;
  return `Abrir ${categoria.rotulo}: ${categoria.total_rotulo}${categoria.resumo ? `, ${categoria.resumo}` : ''}`;
}

function abrir(categoria) {
  if (inativa(categoria)) return;
  emit('abrir', categoria.valor);
}
</script>

<template>
  <nav aria-label="Categorias de cursos">
    <header v-if="cabecalho" class="mb-5 flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-700/60">
      <div class="min-w-0">
        <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 sm:text-2xl">{{ titulo }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ descricao }}</p>
      </div>
      <p class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
        <Squares2X2Icon class="h-4 w-4 shrink-0" aria-hidden="true" />
        {{ resumo }}
      </p>
    </header>

    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <li v-for="categoria in categorias" :key="categoria.valor" class="relative min-w-0">
        <button
          type="button"
          :disabled="inativa(categoria)"
          :aria-disabled="inativa(categoria) ? 'true' : undefined"
          :aria-label="rotuloDe(categoria)"
          class="group relative block w-full overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br text-left shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:border-slate-700/60 dark:focus-visible:ring-offset-slate-900"
          :class="[
            GRADIENTES[categoria.cor] ?? GRADIENTES.slate,
            compacto ? 'h-64' : 'h-80 sm:h-96',
            inativa(categoria)
              ? 'cursor-not-allowed'
              : 'cursor-pointer hover:-translate-y-0.5 hover:shadow-lg hover:ring-2 hover:ring-blue-500/60',
          ]"
          @click="abrir(categoria)"
        >
          <!-- Foto ocupa o card inteiro; sem foto, o icone da categoria sobre o gradiente. -->
          <img
            v-if="categoria.imagem"
            :src="categoria.imagem"
            alt=""
            loading="lazy"
            class="pointer-events-none absolute inset-0 h-full w-full object-cover transition-transform duration-500"
            :class="inativa(categoria) ? 'grayscale-[40%]' : 'group-hover:scale-105'"
          />
          <component
            :is="ICONES[categoria.icone] ?? BookOpenIcon"
            v-else
            class="pointer-events-none absolute left-1/2 top-[28%] h-20 w-20 -translate-x-1/2 -translate-y-1/2 text-white/40 transition-transform duration-300"
            :class="inativa(categoria) ? '' : 'group-hover:scale-110'"
            aria-hidden="true"
          />

          <span
            v-if="categoria.em_breve"
            class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-700"
          >
            Em breve
          </span>

          <!-- Painel de vidro: selos, nome, escopo e o botao de explorar. -->
          <span class="absolute inset-x-0 bottom-0 block bg-slate-950/65 px-4 pb-4 pt-3 text-white backdrop-blur-md">
            <span class="flex flex-wrap items-center gap-1.5">
              <span class="rounded-md bg-blue-600/90 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">Categoria</span>
              <span
                class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                :class="SELOS[categoria.cor] ?? SELOS.slate"
              >
                {{ categoria.em_breve ? 'Em breve' : categoria.total_rotulo }}
              </span>
            </span>
            <span class="mt-2 block truncate text-lg font-bold leading-tight">{{ categoria.rotulo }}</span>
            <span v-if="categoria.resumo" class="mt-1 block truncate text-xs font-semibold text-white/95">{{ categoria.resumo }}</span>
            <span v-if="!compacto" class="mt-1 line-clamp-2 block text-xs leading-snug text-white/80">
              Escopo: {{ categoria.descricao }}
            </span>
            <span
              class="mt-3 flex items-center justify-between rounded-lg border border-white/25 bg-white/10 px-3 py-2 text-xs font-semibold transition-colors"
              :class="inativa(categoria) ? 'text-white/70' : 'group-hover:bg-white/20'"
            >
              <span class="truncate">{{ categoria.em_breve ? 'Disponível em breve' : vazia(categoria) ? 'Nenhuma capacitação por enquanto' : `Explorar categoria · Ver ${categoria.total_rotulo}` }}</span>
              <ArrowRightIcon v-if="!inativa(categoria)" class="h-4 w-4 shrink-0 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
            </span>
          </span>
        </button>

        <!-- Enviar/trocar a foto da pasta: so para quem administra cursos. -->
        <EnvioDeCapa
          v-if="podeEditarCapa"
          class="absolute right-3 top-3 z-10"
          :tem-capa="Boolean(categoria.capa_propria)"
          :url-enviar="route('treinamentos.categorias.capa.store', categoria.valor)"
          :url-remover="route('treinamentos.categorias.capa.destroy', categoria.valor)"
          :rotulo="categoria.rotulo"
        />
      </li>
    </ul>
  </nav>
</template>
