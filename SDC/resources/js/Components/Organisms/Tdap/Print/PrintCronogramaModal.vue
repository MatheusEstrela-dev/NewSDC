<script setup>
import { computed } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import { formatDate } from '@/utils/dateFormatter';

/**
 * Documento impresso do cronograma TDAP.
 *
 * `dados` tem o formato de GET tdap.cronogramas.impressao:
 * { cronograma: CronogramaResource, pontos_captacao: [...] }. A ficha do
 * cronograma monta o mesmo objeto com as props que ja tem, sem nova requisicao.
 */
const props = defineProps({
  show:    { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  dados:   { type: Object, default: null },
});

defineEmits(['close']);

const c = computed(() => props.dados?.cronograma ?? {});
const pontos = computed(() => props.dados?.pontos_captacao ?? []);
const caminhoes = computed(() => c.value.caminhoes ?? []);

const documentTitle = computed(() => `Cronograma TDAP - ${c.value.numero ?? 'N/A'}`);

const ESTADOS = { rascunho: 'RASCUNHO', ativo: 'ATIVO', encerrado: 'ENCERRADO' };

const estadoLabel = computed(() => {
  const estado = ESTADOS[c.value.estado] ?? (c.value.estado ?? '').toUpperCase();
  return c.value.arquivado ? `${estado} (ARQUIVADO)` : estado;
});

const municipioLabel = computed(() => {
  const m = c.value.municipio;
  return m ? (m.uf ? `${m.nome} / ${m.uf}` : m.nome) : '—';
});

const totalViagens = computed(() => caminhoes.value.reduce((s, cc) => s + Number(cc.num_viagens || 0), 0));

/**
 * Dados-chave no cabecalho: quem le o impresso identifica a operacao (onde,
 * quem executa, ate quando e quanto) sem descer ate as secoes. Duas linhas de
 * quatro celulas para caber no cabecalho de uma folha A4.
 */
const resumoHeader = computed(() => {
  const prorrogado = Boolean(c.value.dt_inicio_prorrogacao);
  const pontosNomes = pontos.value.map((p) => p.nome).filter(Boolean);

  return [
    [
      { rotulo: 'Situacao', valor: dash(estadoLabel.value) },
      { rotulo: 'Municipio', valor: municipioLabel.value },
      { rotulo: 'Prestador', valor: dash(c.value.prestador?.nome), detalhe: c.value.prestador?.cnpj ? `CNPJ ${c.value.prestador.cnpj}` : null },
      { rotulo: 'Ata / Lote', valor: `${dash(c.value.ata?.numero)} / ${dash(c.value.lote?.numero)}` },
    ],
    [
      {
        rotulo: prorrogado ? 'Vigencia (prorrogada)' : 'Vigencia',
        valor: periodo(c.value.dt_inicio_efetiva ?? c.value.dt_inicio, c.value.dt_final_efetiva ?? c.value.dt_final),
        detalhe: prorrogado ? `Original: ${periodo(c.value.dt_inicio, c.value.dt_final)}` : `${dash(c.value.dias)} dias`,
      },
      {
        rotulo: 'Volume contratado',
        valor: `${num(c.value.volume_contratado_m3)} m³`,
        detalhe: `Entregue: ${num(c.value.volume_entregue_m3)} m³ (${num(c.value.execucao_percentual)}%)`,
      },
      { rotulo: 'Caminhoes / Viagens', valor: `${caminhoes.value.length} / ${totalViagens.value}` },
      {
        rotulo: 'Pontos de captacao',
        valor: String(pontosNomes.length),
        detalhe: pontosNomes.length ? pontosNomes.join(', ') : 'Nenhum vinculado',
      },
    ],
  ];
});

function periodo(inicio, fim) {
  return inicio || fim ? `${formatDate(inicio) || '—'} a ${formatDate(fim) || '—'}` : '—';
}

function num(v, casas = 2) {
  return Number(v ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
}

function dash(v) {
  return v === null || v === undefined || v === '' ? '—' : v;
}
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Cronograma"
    :document-title="documentTitle"
    :loading="loading"
    @close="$emit('close')"
  >
    <div v-if="dados" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="CRONOGRAMA TDAP - DISTRIBUICAO DE AGUA POTAVEL"
          :numero="c.numero"
          label-numero="CRONOGRAMA"
        >
          <!-- Estilo inline: a janela de impressao nao tem o CSS da aplicacao. -->
          <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <tr v-for="(linha, i) in resumoHeader" :key="i">
              <td
                v-for="item in linha"
                :key="item.rotulo"
                style="border: 1px solid rgba(255,255,255,0.35); padding: 4px 6px; vertical-align: top; color: #fff; background: rgba(0,0,0,0.18);"
              >
                <div style="font-size: 7px; font-weight: bold; text-transform: uppercase; opacity: 0.8; letter-spacing: 0.3px;">{{ item.rotulo }}</div>
                <div style="font-size: 10px; font-weight: bold; margin-top: 1px;">{{ item.valor }}</div>
                <div v-if="item.detalhe" style="font-size: 8px; opacity: 0.85; margin-top: 1px;">{{ item.detalhe }}</div>
              </td>
            </tr>
          </table>
        </PrintHeader>

        <div class="card-body p-0">
          <PrintSection titulo="IDENTIFICACAO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">NUMERO</td>
                <td class="field-value" width="30%">{{ dash(c.numero) }}</td>
                <td class="field-label" width="20%">SITUACAO</td>
                <td class="field-value" width="30%">{{ dash(estadoLabel) }}</td>
              </tr>
              <tr>
                <td class="field-label">EMPENHO</td>
                <td class="field-value">{{ dash(c.nota_empenho || c.empenho) }}</td>
                <td class="field-label">ATIVADO EM</td>
                <td class="field-value">{{ dash(formatDate(c.ativado_em)) }}</td>
              </tr>
              <tr>
                <td class="field-label">ATA</td>
                <td class="field-value">{{ dash(c.ata?.numero) }}</td>
                <td class="field-label">LOTE</td>
                <td class="field-value">{{ dash(c.lote ? [c.lote.numero, c.lote.nome].filter(Boolean).join(' - ') : null) }}</td>
              </tr>
              <tr>
                <td class="field-label">MUNICIPIO</td>
                <td class="field-value">{{ municipioLabel }}</td>
                <td class="field-label">PRESTADOR</td>
                <td class="field-value">{{ dash(c.prestador?.nome) }}</td>
              </tr>
              <tr>
                <td class="field-label">CNPJ DO PRESTADOR</td>
                <td class="field-value">{{ dash(c.prestador?.cnpj) }}</td>
                <td class="field-label">ENCERRADO EM</td>
                <td class="field-value">{{ dash(formatDate(c.encerrado_em)) }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="VIGENCIA E VOLUME">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">VIGENCIA</td>
                <td class="field-value" width="30%">{{ periodo(c.dt_inicio, c.dt_final) }}</td>
                <td class="field-label" width="20%">PRORROGACAO</td>
                <td class="field-value" width="30%">{{ c.dt_inicio_prorrogacao ? periodo(c.dt_inicio_prorrogacao, c.dt_final_prorrogacao) : '—' }}</td>
              </tr>
              <tr>
                <td class="field-label">DIAS</td>
                <td class="field-value">{{ dash(c.dias) }}</td>
                <td class="field-label">CONSUMO DIARIO (L)</td>
                <td class="field-value">{{ num(c.consumo_diario) }}</td>
              </tr>
              <tr>
                <td class="field-label">FATOR (M3)</td>
                <td class="field-value">{{ num(c.fator) }}<template v-if="c.usar_fator_manual"> (manual)</template></td>
                <td class="field-label">VIAGENS PREVISTAS</td>
                <td class="field-value">{{ totalViagens }}</td>
              </tr>
              <tr>
                <td class="field-label">VOLUME CONTRATADO (M3)</td>
                <td class="field-value">{{ num(c.volume_contratado_m3) }}</td>
                <td class="field-label">VOLUME ENTREGUE (M3)</td>
                <td class="field-value">{{ num(c.volume_entregue_m3) }} ({{ num(c.execucao_percentual) }}%)</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="PONTOS DE CAPTACAO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="4%">#</td>
                <td class="field-label" width="30%">PONTO</td>
                <td class="field-label" width="18%">TIPO</td>
                <td class="field-label" width="12%">CAPACIDADE (M3)</td>
                <td class="field-label" width="18%">COORDENADAS</td>
                <td class="field-label" width="18%">ORIGEM</td>
              </tr>
              <tr v-for="(p, i) in pontos" :key="p.id">
                <td class="field-value text-center">{{ i + 1 }}</td>
                <td class="field-value">{{ dash(p.nome) }}</td>
                <td class="field-value">{{ dash(p.tipo_nome) }}</td>
                <td class="field-value">{{ Number(p.capacidade) > 0 ? num(p.capacidade) : '—' }}</td>
                <td class="field-value">{{ p.latitude && p.longitude ? `${p.latitude}, ${p.longitude}` : '—' }}</td>
                <td class="field-value">{{ p.pmda_plano_id ? `PMDA ${p.protocolo || '#' + p.pmda_plano_id}` : 'Fora do PMDA aprovado' }}</td>
              </tr>
              <tr v-if="pontos.length === 0">
                <td class="field-value text-center" colspan="6">Nenhum ponto de captacao vinculado.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="CAMINHOES ALOCADOS">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="4%">#</td>
                <td class="field-label" width="14%">PLACA</td>
                <td class="field-label" width="26%">VEICULO</td>
                <td class="field-label" width="12%">CAPACIDADE (M3)</td>
                <td class="field-label" width="12%">PREVISTO (M3)</td>
                <td class="field-label" width="10%">VIAGENS</td>
                <td class="field-label" width="12%">ENTREGUE (M3)</td>
                <td class="field-label" width="10%">%</td>
              </tr>
              <tr v-for="(cc, i) in caminhoes" :key="cc.id">
                <td class="field-value text-center">{{ i + 1 }}</td>
                <td class="field-value">{{ dash(cc.placa) }}</td>
                <td class="field-value">{{ dash(cc.marca_modelo) }}</td>
                <td class="field-value">{{ num(cc.capacidade_m3) }}</td>
                <td class="field-value">{{ num(cc.agua_prevista) }}</td>
                <td class="field-value">{{ cc.num_viagens }}</td>
                <td class="field-value">{{ num(cc.agua_entregue) }}</td>
                <td class="field-value">{{ num(cc.percentual) }}</td>
              </tr>
              <tr v-if="caminhoes.length === 0">
                <td class="field-value text-center" colspan="8">Nenhum caminhao alocado.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="JUSTIFICATIVA E OBSERVACOES">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">JUSTIFICATIVA</td>
                <td class="field-value" style="white-space: pre-wrap;">{{ dash(c.justificativa) }}</td>
              </tr>
              <tr>
                <td class="field-label">OBSERVACAO</td>
                <td class="field-value" style="white-space: pre-wrap;">{{ dash(c.observacao) }}</td>
              </tr>
            </table>
          </PrintSection>

          <table class="bos-table">
            <tr>
              <td class="field-value" width="50%" style="height: 60px; text-align: center; vertical-align: bottom;">
                ______________________________________<br>
                RESPONSAVEL CEDEC-MG
              </td>
              <td class="field-value" width="50%" style="height: 60px; text-align: center; vertical-align: bottom;">
                ______________________________________<br>
                PRESTADOR DE SERVICO
              </td>
            </tr>
          </table>

          <div style="padding: 8px 10px; font-size: 8px; color: #555; border: 1px solid #000; border-top: none;">
            Documento gerado em {{ new Date().toLocaleString('pt-BR') }} pelo Sistema Integrado de Defesa Civil - CEDEC-MG.
          </div>
        </div>
      </div>
    </div>

    <div v-else class="py-12 text-center text-gray-600 dark:text-gray-400">
      Nenhum cronograma carregado.
    </div>
  </BasePrintModal>
</template>
