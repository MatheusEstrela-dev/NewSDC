<template>
  <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h3 class="mb-4 text-sm font-bold text-slate-900 dark:text-slate-100">Cadeia de fiscalizacao</h3>

    <ol class="space-y-0">
      <li v-for="(etapa, indice) in etapas" :key="etapa.valor" class="flex gap-3">
        <!-- Trilho: marcador + linha que liga a etapa seguinte -->
        <div class="flex flex-col items-center">
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
            :class="MARCADOR[etapa.estado]"
          >
            <CheckIcon v-if="etapa.estado === 'concluida'" class="h-4 w-4" />
            <span v-else>{{ indice + 1 }}</span>
          </span>
          <span
            v-if="indice < etapas.length - 1"
            class="my-1 w-0.5 flex-1"
            :class="etapa.estado === 'concluida' ? 'bg-emerald-400' : 'bg-slate-200 dark:bg-slate-700'"
          />
        </div>

        <div class="flex-1 pb-5">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ etapa.rotulo }}</span>
            <span :class="['rounded px-1.5 py-0.5 text-[10px] font-bold uppercase', ROTULO[etapa.estado]]">
              {{ TEXTO[etapa.estado] }}
            </span>
          </div>

          <p v-if="etapa.vistoria" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            {{ resumoDaVistoria(etapa.vistoria) }}
          </p>
          <p v-else class="mt-1 text-xs text-slate-400">
            {{ etapa.estado === 'disponivel' ? 'Pode ser preenchida agora' : 'Aguarda a etapa anterior' }}
          </p>

          <div class="mt-2 flex flex-wrap gap-2">
            <Link
              v-if="etapa.vistoria"
              :href="route('cisternas.vistorias.show', etapa.vistoria.id)"
              :class="ACAO"
            >
              Ver relatorio
            </Link>
            <button
              v-if="!etapa.vistoria && etapa.estado === 'disponivel' && podeCriar"
              type="button"
              :class="ACAO_PRIMARIA"
              @click="$emit('preencher', etapa.valor)"
            >
              Preencher
            </button>
            <!--
              Editar mora AQUI, junto das outras acoes da etapa, e nao no
              cabecalho da tela de detalhe: o relatorio nasce vazio e se completa
              em varias sessoes, entao continuar o preenchimento e acao rotineira
              da cadeia, no mesmo painel do "Preencher".

              Vale tambem para etapa concluida: corrigir relatorio fechado e caso
              real, e era o que a nota da tela ja mandava fazer.
            -->
            <button
              v-if="etapa.vistoria && podeEditar"
              type="button"
              :class="ACAO"
              @click="$emit('editar', etapa)"
            >
              {{ etapa.estado === 'concluida' ? 'Corrigir' : 'Continuar preenchimento' }}
            </button>
          </div>
        </div>
      </li>
    </ol>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CheckIcon } from '@heroicons/vue/24/outline';

/**
 * As tres etapas em ordem, com o estado de cada uma.
 *
 * A cadeia e SEQUENCIAL: o COMPDEC confere o que o fornecedor instalou, e a
 * CEDEC fiscaliza depois. Mostrar as tres sempre -- inclusive as que ainda nao
 * podem ser preenchidas -- e o que deixa claro o que falta. No legado cada etapa
 * era uma tela separada e nao havia lugar nenhum que mostrasse o todo.
 *
 * Quem decide qual etapa esta liberada e o servidor (`etapaDisponivel`), nao
 * esta tela: a regra e de dominio.
 */
const props = defineProps({
  /** VistoriaResource::collection do beneficiario. */
  vistorias: { type: Array, default: () => [] },
  /** EtapaVistoria::options() do controller. */
  opcoesEtapa: { type: Array, default: () => [] },
  /** Valor da etapa liberada agora, ou null quando a cadeia terminou. */
  etapaDisponivel: { type: String, default: null },
  podeCriar: { type: Boolean, default: false },
  podeEditar: { type: Boolean, default: false },
});

defineEmits(['preencher', 'editar']);

const MARCADOR = {
  concluida: 'bg-emerald-500 text-white',
  em_aberto: 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
  disponivel: 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
  bloqueada: 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500',
};

const ROTULO = {
  concluida: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
  em_aberto: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
  disponivel: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
  bloqueada: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
};

const TEXTO = {
  concluida: 'Concluida',
  em_aberto: 'Em aberto',
  disponivel: 'Liberada',
  bloqueada: 'Bloqueada',
};

const ACAO = 'rounded px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-500/10';
const ACAO_PRIMARIA = 'rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white hover:bg-blue-700';

const etapas = computed(() => props.opcoesEtapa.map((opcao) => {
  const vistoria = props.vistorias.find(
    (v) => (v.etapa?.valor ?? v.etapa) === opcao.value,
  ) ?? null;

  return {
    valor: opcao.value,
    rotulo: opcao.label,
    vistoria,
    estado: estadoDa(vistoria, opcao.value),
  };
}));

/**
 * Vistoria existente mas nao concluida e `em_aberto`, e nao `concluida`: no
 * legado o marcador de conclusao era o CREA do engenheiro estar preenchido, e
 * relatorio salvo pela metade e caso comum na carga real.
 */
function estadoDa(vistoria, valor) {
  if (vistoria) {
    return vistoria.concluida ? 'concluida' : 'em_aberto';
  }

  return valor === props.etapaDisponivel ? 'disponivel' : 'bloqueada';
}

function resumoDaVistoria(vistoria) {
  const partes = [];

  if (vistoria.numero_instalacao) partes.push(`Nº ${vistoria.numero_instalacao}`);
  if (vistoria.data_relatorio) partes.push(dataBr(vistoria.data_relatorio));
  if (vistoria.engenheiro?.nome) partes.push(vistoria.engenheiro.nome);

  return partes.length > 0 ? partes.join(' — ') : 'Relatorio sem dados preenchidos';
}

function dataBr(iso) {
  const [ano, mes, dia] = String(iso).slice(0, 10).split('-');

  return dia ? `${dia}/${mes}/${ano}` : iso;
}
</script>
