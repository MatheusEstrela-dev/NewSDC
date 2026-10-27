<script setup>
import { computed, ref, watch } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import { useToast } from '@/Composables/useToast';
import { formatarDia } from '@/Support/dataLocal';

/**
 * Ficha de uma vistoria, salva em PDF pela impressao do navegador.
 *
 * O botao da linha aponta o id da vistoria daquele caminhao. O checklist
 * nao vem na listagem, entao a ficha e buscada so quando o modal abre.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  vistoriaId: { type: Number, default: null },
});

defineEmits(['close']);

const { show: toast } = useToast();
const dados = ref(null);
const carregando = ref(false);

const ROTULOS = {
  documento: 'Documentação do veículo',
  para_choque_d: 'Para-choque dianteiro',
  para_choque_t: 'Para-choque traseiro',
  placa_d: 'Placa dianteira',
  placa_t: 'Placa traseira',
  selo_placa_t: 'Selo da placa traseira',
  espel_ret_ex: 'Espelho retrovisor externo',
  motor_arr_bat: 'Motor, arranque e bateria',
  para_brisa: 'Para-brisa',
  bancos_fixos: 'Bancos fixos',
  farois_d: 'Faróis dianteiros',
  faroletes: 'Faroletes',
  trans_farois: 'Transparência dos faróis',
  lant_set_d: 'Lanterna e seta direita',
  lant_set_e: 'Lanterna e seta esquerda',
  pisca_alerta: 'Pisca-alerta',
  luz_re: 'Luz de ré',
  luz_freio: 'Luz de freio',
  freio_est: 'Freio de estacionamento',
  cond_pneus: 'Condição dos pneus',
  cond_pneus_e: 'Condição dos pneus (estepe)',
  buzina: 'Buzina',
  extintor: 'Extintor',
  cinto_seg: 'Cinto de segurança',
  macanetas: 'Maçanetas',
  tri_rod_mac: 'Triângulo, roda e macaco',
  aus_vaz_comb: 'Ausência de vazamento de combustível',
  aus_prop_pol: 'Ausência de propagação de poluentes',
  pintura_ext: 'Pintura externa do tanque',
  pintura_int: 'Pintura interna do tanque',
  vazamento: 'Vazamento',
  mangote: 'Mangote',
  valv_expul: 'Válvula de expulsão',
  tampa_ved: 'Tampa de vedação',
  agua_pot: 'Água potável',
};

const v = computed(() => dados.value?.vistoria ?? null);
const caminhao = computed(() => v.value?.caminhao ?? null);

const tituloDocumento = computed(() => {
  const placa = caminhao.value?.placa ?? '';
  return placa ? `Vistoria TDAP — ${placa}` : 'Vistoria TDAP';
});

watch(() => [props.show, props.vistoriaId], ([aberto, id]) => {
  if (aberto && id) carregar(id);
  else dados.value = null;
});

async function carregar(id) {
  carregando.value = true;
  try {
    const res = await fetch(route('tdap.frota.vistorias.impressao', id), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    dados.value = await res.json();
  } catch (_) {
    toast('Não foi possível gerar a ficha desta vistoria.', 'error');
  } finally {
    carregando.value = false;
  }
}

function rotulo(chave) {
  return ROTULOS[chave] ?? chave.replace(/_/g, ' ');
}

function situacao(chave) {
  return v.value?.[chave] ? 'Conforme' : 'Não conforme';
}

function observacao(chave) {
  return v.value?.[`${chave}_obs`] || '—';
}

function texto(valor) {
  return valor === null || valor === undefined || valor === '' ? '—' : valor;
}
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Ficha da Vistoria (PDF)"
    :document-title="tituloDocumento"
    :loading="carregando && !dados"
    @close="$emit('close')"
  >
    <template #toolbar>
      <p class="text-xs text-gray-500 dark:text-gray-400">Use “Imprimir” e escolha “Salvar como PDF”.</p>
    </template>

    <div v-if="v" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="FICHA DE VISTORIA — TDAP"
          :numero="caminhao?.placa"
          label-numero="PLACA"
        />

        <div class="card-body p-0">
          <PrintSection titulo="Identificação">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="16%">VISTORIADOR</td>
                <td class="field-value" width="34%">{{ texto(v.nome) }}</td>
                <td class="field-label" width="16%">DATA</td>
                <td class="field-value" width="34%">{{ formatarDia(v.data) || '—' }}</td>
              </tr>
              <tr>
                <td class="field-label">FICHA</td>
                <td class="field-value">{{ texto(v.ficha) }}</td>
                <td class="field-label">EDITAL</td>
                <td class="field-value">{{ texto(v.edital) }}</td>
              </tr>
              <tr>
                <td class="field-label">LACRE</td>
                <td class="field-value">{{ texto(v.lacre) }}</td>
                <td class="field-label">PARECER</td>
                <td class="field-value">
                  {{ texto(v.parecer_label) }}
                  — {{ v.esta_vigente ? 'Vigente' : 'Expirada' }}
                  <template v-if="v.valido_ate"> (até {{ formatarDia(v.valido_ate) }})</template>
                </td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="Caminhão vistoriado">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="16%">PLACA</td>
                <td class="field-value" width="34%">{{ texto(caminhao?.placa) }}</td>
                <td class="field-label" width="16%">PRESTADOR</td>
                <td class="field-value" width="34%">
                  {{ texto(caminhao?.prestador?.nome) }}
                  <template v-if="caminhao?.prestador?.cnpj"><br>{{ caminhao.prestador.cnpj }}</template>
                </td>
              </tr>
              <tr>
                <td class="field-label">MARCA / MODELO</td>
                <td class="field-value">{{ texto([caminhao?.marca, v.modelo || caminhao?.modelo].filter(Boolean).join(' ')) }}</td>
                <td class="field-label">COR / ANO</td>
                <td class="field-value">{{ texto(v.cor) }} / {{ texto(v.ano) }}</td>
              </tr>
              <tr>
                <td class="field-label">CAPACIDADE</td>
                <td class="field-value" colspan="3">{{ Number(v.capacidade || 0).toFixed(2) }} m³</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="Condições estruturais">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="46%">ITEM</td>
                <td class="field-label" width="18%">SITUAÇÃO</td>
                <td class="field-label" width="36%">OBSERVAÇÃO</td>
              </tr>
              <tr v-for="chave in dados.estruturais" :key="chave">
                <td class="field-value">{{ rotulo(chave) }}</td>
                <td class="field-value">{{ situacao(chave) }}</td>
                <td class="field-value">{{ observacao(chave) }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="Condições do tanque">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="46%">ITEM</td>
                <td class="field-label" width="18%">SITUAÇÃO</td>
                <td class="field-label" width="36%">OBSERVAÇÃO</td>
              </tr>
              <tr v-for="chave in dados.tanque" :key="chave">
                <td class="field-value">{{ rotulo(chave) }}</td>
                <td class="field-value">{{ situacao(chave) }}</td>
                <td class="field-value">{{ observacao(chave) }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="Observações">
            <table class="bos-table">
              <tr>
                <td class="field-value historico-text">{{ texto(v.observacoes) }}</td>
              </tr>
            </table>
          </PrintSection>

          <table class="bos-table">
            <tr>
              <td class="field-label" width="20%">REGISTRADA POR</td>
              <td class="field-value" width="50%">{{ texto(v.user?.name) }}</td>
              <td class="field-label" width="15%">GERADO EM</td>
              <td class="field-value" width="15%">{{ dados.gerado_em }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </BasePrintModal>
</template>
