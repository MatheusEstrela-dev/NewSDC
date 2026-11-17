<script setup>
/**
 * Cronograma do curso (Area do Gestor, issue #86) no calendario que o
 * Plantao ja tem (EscalaCalendario) - o mesmo componente que a agenda de
 * vistorias da TDAP reaproveita, entao a area do gestor nao cria um terceiro.
 *
 * Os itens vem por axios (GestaoCursoConsulta::cronograma, JSON por mes),
 * nao por prop Inertia: o calendario pede de novo a cada mes navegado.
 */
import { computed, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import SecaoRecolhivel from '@/Components/Molecules/Treinamento/SecaoRecolhivel.vue';
import EscalaCalendario from '@/Components/Organisms/Plantao/EscalaCalendario.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import CronogramaItemModal from '@/Components/Organisms/Treinamento/Gestao/CronogramaItemModal.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  treinamentoId: { type: [Number, String], required: true },
  turmas: { type: Array, default: () => [] },
  tipoOptions: { type: Array, default: () => [] },
  podeConfigurar: { type: Boolean, default: false },
});
const emit = defineEmits(['alterado']);

const { show: toast } = useToast();

// Cores literais (FullCalendar nao le classe Tailwind), do mesmo mapa que o
// select de tipo ja usa (`tipoOptions`, vindo de TipoEventoCronograma::toSelectArray()).
const HEX_POR_COR = { blue: '#3b82f6', purple: '#a855f7', yellow: '#eab308', green: '#22c55e', gray: '#64748b' };

function corDoTipo(tipo) {
  const cor = props.tipoOptions.find((o) => o.value === tipo)?.color;
  return HEX_POR_COR[cor] ?? '#64748b';
}

const carregando = ref(false);
const itens = ref([]);
const turmaFiltro = ref('');
const competencia = ref({ ano: new Date().getFullYear(), mes: new Date().getMonth() + 1 });

const opcoesFiltroTurma = computed(() => [
  { value: '', label: 'Todas as turmas' },
  ...props.turmas.map((t) => ({ value: String(t.id), label: t.nome })),
]);

async function carregar({ ano, mes }) {
  competencia.value = { ano, mes };
  carregando.value = true;

  const de = `${ano}-${String(mes).padStart(2, '0')}-01`;
  const ate = new Date(ano, mes, 0).toISOString().slice(0, 10);

  try {
    const { data } = await axios.get(route('treinamentos.gestor.cronograma.index', props.treinamentoId), {
      params: { de, ate, turma_id: turmaFiltro.value || undefined },
    });

    itens.value = (data ?? []).map((item) => ({
      id: item.id,
      title: item.turma_nome ? `${item.titulo} (${item.turma_nome})` : item.titulo,
      start: item.inicio,
      end: item.fim ?? undefined,
      backgroundColor: corDoTipo(item.tipo),
      borderColor: corDoTipo(item.tipo),
      extendedProps: item,
    }));
  } catch (e) {
    toast('Não foi possível carregar o cronograma.', 'error');
  } finally {
    carregando.value = false;
  }
}

watch(turmaFiltro, () => carregar(competencia.value));

onMounted(() => carregar(competencia.value));

const modalAberto = ref(false);
const itemEmEdicao = ref(null);
const dataParaNovoItem = ref(null);

function novoItem(dia = null) {
  if (!props.podeConfigurar) return;
  itemEmEdicao.value = null;
  dataParaNovoItem.value = dia;
  modalAberto.value = true;
}

function editarItem(evento) {
  if (!props.podeConfigurar) return;
  itemEmEdicao.value = evento;
  dataParaNovoItem.value = null;
  modalAberto.value = true;
}

function aoSalvar() {
  carregar(competencia.value);
  emit('alterado');
}

function removerItem(item) {
  if (!confirm(`Remover "${item.titulo}" do cronograma?`)) return;

  router.delete(route('treinamentos.gestor.cronograma.destroy', [props.treinamentoId, item.id]), {
    preserveScroll: true,
    onSuccess: () => {
      toast('Item removido.', 'success');
      carregar(competencia.value);
    },
  });
}
</script>

<template>
  <SecaoRecolhivel titulo="Cronograma" namespace="treinamento-gestor" secao-id="cronograma">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <div class="w-full max-w-xs sm:w-56">
        <SelectInput v-if="turmas.length" v-model="turmaFiltro" :options="opcoesFiltroTurma" placeholder="" />
      </div>
      <Button v-if="podeConfigurar" variant="primary" size="sm" @click="novoItem()">Novo item</Button>
    </div>

    <div :class="{ 'opacity-60 transition-opacity': carregando }">
      <EscalaCalendario
        :eventos="itens"
        :data-inicial="`${competencia.ano}-${String(competencia.mes).padStart(2, '0')}-01`"
        :pode-montar="podeConfigurar"
        texto-vazio="Nenhum item de cronograma neste período."
        @mudar-mes="carregar"
        @selecionar-dia="novoItem"
        @selecionar-vaga="editarItem"
      />
    </div>

    <!-- Remover fica de fora do calendario (o clique no evento abre edicao);
         a lista textual abaixo cobre a acao de remover sem precisar de um
         segundo clique dentro do FullCalendar. -->
    <ul v-if="podeConfigurar && itens.length" class="mt-3 space-y-1">
      <li v-for="evento in itens" :key="evento.id" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-1.5 text-xs dark:border-slate-700/60">
        <span class="truncate text-slate-600 dark:text-slate-300">{{ evento.title }}</span>
        <button type="button" class="shrink-0 text-red-600 hover:underline dark:text-red-400" @click="removerItem(evento.extendedProps)">
          Remover
        </button>
      </li>
    </ul>

    <CronogramaItemModal
      :show="modalAberto"
      :treinamento-id="treinamentoId"
      :item="itemEmEdicao"
      :turmas="turmas"
      :tipo-options="tipoOptions"
      :data-inicial="dataParaNovoItem"
      @fechar="modalAberto = false"
      @salvo="aoSalvar"
    />
  </SecaoRecolhivel>
</template>
