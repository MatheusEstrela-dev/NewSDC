<script setup>
/**
 * Cabecalho em destaque da tela do curso: selo de modalidade/publicacao,
 * titulo, descricao curta, periodo e local, e o emblema (ou a capa do EAD)
 * a direita. As abas da pagina entram no slot #tabs, no rodape do cartao.
 */
import { computed } from 'vue';
import { AcademicCapIcon, CalendarIcon, MapPinIcon } from '@heroicons/vue/24/outline';
import TreinamentoStatusBadge from '@/Components/Molecules/Treinamento/TreinamentoStatusBadge.vue';
import CapaCursoUpload from '@/Components/Molecules/Treinamento/CapaCursoUpload.vue';

const props = defineProps({
  treinamento: { type: Object, required: true },
  // Quem configura o curso troca a capa do EAD direto no cabecalho.
  podeTrocarCapa: { type: Boolean, default: false },
});

const ehEad = computed(() => props.treinamento.tipo === 'EAD');

const selo = computed(() => {
  const partes = [props.treinamento.tipo_label];
  if (props.treinamento.categoria === 'EVENTO') partes.push('Evento');
  partes.push(props.treinamento.esta_publicado ? 'Publicado' : 'Não publicado');

  return partes.filter(Boolean).join(' · ');
});

// "14 dez. 2026": datas do curso sao dias (sem fuso), dai o UTC.
function dataCurta(valor) {
  if (!valor) return null;
  const texto = String(valor).trim();
  if (texto.includes('/')) return texto;
  const d = new Date(valor);
  if (Number.isNaN(d.getTime())) return texto;

  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
}

const periodo = computed(() => {
  const inicio = dataCurta(props.treinamento.data_inicio);
  const fim = dataCurta(props.treinamento.data_fim);
  if (inicio && fim && inicio !== fim) return `${inicio} — ${fim}`;

  return inicio || fim;
});

const lugar = computed(() => props.treinamento.municipio_nome
  ? `${props.treinamento.municipio_nome}, MG`
  : (ehEad.value ? 'Online (EAD)' : props.treinamento.local));
</script>

<template>
  <section class="mb-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60 sm:mb-6">
    <div class="curso-hero relative flex flex-col gap-6 bg-gradient-to-br from-white via-blue-50/40 to-blue-100/50 px-5 py-6 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/80 sm:px-8 sm:py-8 md:flex-row md:items-center">
      <div class="relative min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
            <span
              class="h-2 w-2 shrink-0 rounded-full"
              :class="treinamento.esta_publicado ? 'bg-emerald-500' : 'bg-slate-400'"
              aria-hidden="true"
            ></span>
            {{ selo }}
          </span>
          <TreinamentoStatusBadge :status="treinamento.status" :label="treinamento.status_label" size="sm" />
        </div>

        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl lg:text-4xl">
          {{ treinamento.titulo }}
        </h1>

        <p v-if="treinamento.descricao" class="mt-3 line-clamp-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300 sm:text-base">
          {{ treinamento.descricao }}
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-medium text-slate-700 dark:text-slate-200">
          <span v-if="periodo" class="inline-flex items-center gap-2">
            <CalendarIcon class="h-5 w-5 shrink-0 text-orange-500" aria-hidden="true" />
            {{ periodo }}
          </span>
          <span v-if="lugar" class="inline-flex min-w-0 items-center gap-2">
            <MapPinIcon class="h-5 w-5 shrink-0 text-orange-500" aria-hidden="true" />
            <span class="truncate">{{ lugar }}</span>
          </span>
          <span class="inline-flex items-center gap-2 text-slate-500 dark:text-slate-400">
            {{ treinamento.carga_horaria }}h · {{ treinamento.categoria_label }}
          </span>
        </div>
      </div>

      <!-- Capa do EAD ou emblema da Defesa Civil -->
      <div v-if="ehEad && (treinamento.capa_url || podeTrocarCapa)" class="relative w-full shrink-0 md:w-72">
        <div class="relative aspect-[16/9] overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
          <img
            v-if="treinamento.capa_url"
            :src="treinamento.capa_url"
            :alt="`Capa do curso: ${treinamento.titulo}`"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full w-full items-center justify-center px-4 text-center text-sm text-slate-500 dark:text-slate-400">
            Curso sem imagem de capa
          </div>
          <div v-if="podeTrocarCapa" class="absolute right-2 top-2">
            <CapaCursoUpload :curso="treinamento" />
          </div>
        </div>
      </div>
      <div v-else class="relative hidden shrink-0 flex-col items-end md:flex" aria-hidden="true">
        <div class="relative flex h-44 w-44 items-center justify-center">
          <span class="absolute inset-0 rounded-full border border-slate-300/60 dark:border-slate-600/40"></span>
          <span class="absolute inset-6 rounded-full border border-slate-300/60 dark:border-slate-600/40"></span>
          <div class="relative flex h-24 w-24 rotate-[-4deg] items-center justify-center rounded-3xl border border-orange-400/70 bg-white shadow-lg shadow-orange-500/20 dark:bg-slate-800">
            <AcademicCapIcon class="h-11 w-11 text-orange-500" />
          </div>
        </div>
        <span class="mt-2 text-right text-[10px] font-semibold uppercase leading-tight tracking-widest text-slate-500 dark:text-slate-400">
          Defesa Civil<br />Minas Gerais
        </span>
      </div>
    </div>

    <!-- As abas (ModuleTabs) trazem margem inferior propria; aqui dentro do cartao ela sai. -->
    <div v-if="$slots.tabs" class="border-t border-slate-200 px-3 py-3 dark:border-slate-700/50 sm:px-5 [&_.module-tabs-wrapper]:mb-0">
      <slot name="tabs" />
    </div>
  </section>
</template>

<style scoped>
/* Pontilhado sutil de fundo, como no layout de referencia. */
.curso-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image: radial-gradient(rgb(148 163 184 / 0.25) 1px, transparent 1px);
  background-size: 18px 18px;
  mask-image: linear-gradient(to left, black, transparent 70%);
}
</style>
