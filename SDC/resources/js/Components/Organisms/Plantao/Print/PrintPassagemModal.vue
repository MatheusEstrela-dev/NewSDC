<script setup>
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  // Item da listagem (PlantaoListDTO): so usado para o cabecalho do
  // documento (data/periodo/plantonista). O texto em si vem sempre da rota
  // de relatorio, nunca deste objeto.
  plantao: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const texto = ref('');
const carregando = ref(false);
const erro = ref('');

// Busca sob demanda, escopada ao turno selecionado: troca de plantao (ou
// reabertura do modal) sempre limpa o texto anterior antes de buscar de
// novo, para nunca mostrar a passagem de um turno enquanto carrega a de
// outro. O guard `plantaoId !== props.plantao?.id` no fim descarta uma
// resposta que chegue atrasada depois que o usuario ja trocou de turno
// (dois cliques rapidos em linhas diferentes) - sem ele o texto do turno
// antigo poderia sobrescrever o do turno atual.
async function carregar(plantaoId) {
  texto.value = '';
  erro.value = '';

  if (!plantaoId) return;

  carregando.value = true;
  try {
    const { data } = await axios.get(route('plantao.passagem.relatorio', plantaoId));
    if (plantaoId !== props.plantao?.id) return;
    texto.value = data.texto;
  } catch {
    if (plantaoId !== props.plantao?.id) return;
    erro.value = 'Nao foi possivel carregar o relatorio de passagem.';
  } finally {
    if (plantaoId === props.plantao?.id) carregando.value = false;
  }
}

watch(
  () => [props.show, props.plantao?.id],
  ([show, plantaoId]) => {
    if (show) carregar(plantaoId);
  },
  { immediate: true },
);

const documentTitle = computed(() => {
  return `Passagem de Servico - ${props.plantao?.data || 'N/A'}`;
});

const subtitulo = computed(() => {
  const periodo = props.plantao?.periodo ? ` (${props.plantao.periodo})` : '';
  return `PASSAGEM DE SERVICO${periodo}`;
});
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Passagem de Servico"
    :document-title="documentTitle"
    :loading="carregando"
    @close="$emit('close')"
  >
    <div v-if="erro" class="text-center py-12 text-red-600 dark:text-red-400">
      {{ erro }}
    </div>

    <div v-else-if="plantao" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          :subtitulo="subtitulo"
          :numero="plantao.data"
          label-numero="DATA"
        />

        <div class="card-body p-0">
          <PrintSection titulo="DADOS DO TURNO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">PLANTONISTA</td>
                <td class="field-value" width="30%">{{ plantao.plantonista_nome || '' }}</td>
                <td class="field-label" width="20%">PERIODO</td>
                <td class="field-value" width="30%">{{ plantao.periodo || '' }}</td>
              </tr>
              <tr>
                <td class="field-label">DATA</td>
                <td class="field-value">{{ plantao.data || '' }}</td>
                <td class="field-label">STATUS</td>
                <td class="field-value">{{ plantao.status || '' }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="RELATORIO DE PASSAGEM">
            <table class="bos-table">
              <tr>
                <td class="field-value historico-text">{{ texto || 'Sem conteudo.' }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="CONFERENCIA">
            <table class="bos-table">
              <tr>
                <td class="field-value" width="50%" style="text-align: center; padding-top: 25px;">
                  ___________________________________________________<br>
                  Plantonista que registra
                </td>
                <td class="field-value" width="50%" style="text-align: center; padding-top: 25px;">
                  ___________________________________________________<br>
                  Responsavel que confere
                </td>
              </tr>
            </table>
          </PrintSection>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-gray-600 dark:text-gray-400">
      Nenhum turno selecionado
    </div>
  </BasePrintModal>
</template>

<style scoped>
.historico-text {
  min-height: 100px;
  padding: 8px;
  text-align: justify;
  white-space: pre-wrap;
  font-size: 9px;
  line-height: 1.3;
}
</style>
