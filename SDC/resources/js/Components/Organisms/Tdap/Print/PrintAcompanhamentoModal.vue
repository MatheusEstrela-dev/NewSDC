<script setup>
import { computed } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';

/**
 * Relatorio do Acompanhamento de cronogramas, salvo em PDF pela impressao do
 * navegador (mesmo caminho do relatorio do cronograma e das fichas do modulo).
 *
 * `fases` e o que esta NA TELA: cada item { label, responsavel, total, linhas },
 * onde `linhas` ja sai com o recorte e os filtros aplicados. O relatorio diz o
 * que foi filtrado, para quem le o papel saber que nao e o acervo inteiro.
 */
const props = defineProps({
  show:        { type: Boolean, default: false },
  fases:       { type: Array, default: () => [] },
  indicadores: { type: Array, default: () => [] },
  // Texto livre com o recorte aplicado ("Em atraso", "Minha fila"...).
  filtros:     { type: String, default: '' },
});

defineEmits(['close']);

const geradoEm = computed(() => new Date().toLocaleString('pt-BR'));
const documentTitle = computed(() => `Acompanhamento TDAP - ${new Date().toISOString().slice(0, 10)}`);
const totalExibido = computed(() => props.fases.reduce((n, f) => n + f.linhas.length, 0));

const dash = (v) => (v === null || v === undefined || v === '' ? '—' : v);

function data(iso) {
  return iso ? new Date(`${iso}T00:00:00`).toLocaleDateString('pt-BR') : '—';
}
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Relatório do Acompanhamento"
    :document-title="documentTitle"
    @close="$emit('close')"
  >
    <div class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="ACOMPANHAMENTO DE CRONOGRAMAS TDAP"
          :numero="geradoEm"
          label-numero="GERADO EM"
        />

        <div class="card-body p-0">
          <PrintSection titulo="RESUMO">
            <table class="bos-table">
              <tr>
                <td v-for="ind in indicadores" :key="ind.titulo" class="field-label" width="25%">{{ ind.titulo.toUpperCase() }}</td>
              </tr>
              <tr>
                <td v-for="ind in indicadores" :key="ind.titulo" class="field-value text-center" style="font-size: 14px; font-weight: bold;">{{ ind.valor }}</td>
              </tr>
              <tr>
                <td class="field-label" :colspan="Math.max(indicadores.length, 1)">
                  EXIBIDOS: {{ totalExibido }} CRONOGRAMA(S) · FILTROS: {{ filtros ? filtros.toUpperCase() : 'NENHUM' }}
                </td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection v-for="f in fases" :key="f.value" :titulo="`${f.ordem}. ${f.label.toUpperCase()} (${f.linhas.length}${f.total > f.linhas.length ? ' DE ' + f.total : ''})`">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="14%">CRONOGRAMA</td>
                <td class="field-label" width="20%">MUNICIPIO</td>
                <td class="field-label" width="24%">PRESTADOR</td>
                <td class="field-label" width="12%">ATA / LOTE</td>
                <td class="field-label" width="8%">CAMINHOES</td>
                <td class="field-label" width="14%">VIGENCIA</td>
                <td class="field-label" width="8%">NA FASE</td>
              </tr>
              <tr v-for="c in f.linhas" :key="c.id">
                <td class="field-value">{{ dash(c.numero) }}</td>
                <td class="field-value">{{ dash(c.municipio) }}</td>
                <td class="field-value">{{ dash(c.prestador) }}</td>
                <td class="field-value">{{ dash(c.ata) }} / {{ dash(c.lote) }}</td>
                <td class="field-value text-center">{{ c.caminhoes }}</td>
                <td class="field-value">{{ data(c.dt_inicio) }} a {{ data(c.dt_final) }}</td>
                <td class="field-value" :style="c.atraso ? 'color: #b91c1c; font-weight: bold;' : ''">
                  {{ c.dias_na_fase === null ? '—' : (c.dias_na_fase === 0 ? 'Hoje' : `${c.dias_na_fase} d`) }}
                </td>
              </tr>
              <tr v-if="f.linhas.length === 0">
                <td class="field-value text-center" colspan="7">Nenhum cronograma nesta fase.</td>
              </tr>
            </table>
          </PrintSection>
        </div>
      </div>
    </div>
  </BasePrintModal>
</template>
