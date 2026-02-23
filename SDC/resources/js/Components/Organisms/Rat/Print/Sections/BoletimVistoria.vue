<script setup>
import { computed } from 'vue';

const props = defineProps({
  vistoria: {
    type: Object,
    default: null,
  },
});

const patologias = computed(() => {
  if (!props.vistoria) return [];
  const list = [];
  const v = props.vistoria;

  if (v.v_patologia_rachaduras) list.push('Rachaduras');
  if (v.v_patologia_trincas) list.push('Trincas');
  if (v.v_patologia_fissuras_estruturais) list.push('Fissuras em Elementos Estruturais (pilar, viga, laje, etc.)');
  if (v.v_patologia_deformacoes_estruturais) list.push('Deformacoes ou Deslocamentos Estruturais');
  if (v.v_patologia_infiltracoes) list.push('Infiltracoes / Umidade Ascendente');
  if (v.v_patologia_corrosao_armaduras) list.push('Corrosao de Armaduras');
  if (v.v_patologia_desagregacao) list.push('Desagregacao de Reboco / Argamassa');
  if (v.v_patologia_eflorescencia) list.push('Eflorescencia / Bolor / Mofo');
  if (v.v_patologia_desplacamento) list.push('Desplacamento de Revestimento');
  if (v.v_patologia_fundacoes) list.push('Comprometimento das Fundacoes');
  if (v.v_patologia_instabilidade_talude) list.push('Instabilidade de Talude / Contencoes');
  if (v.v_patologia_movimentacao_solo) list.push('Indicios de Movimentacao de Solo');
  if (v.v_patologia_tombamento_muralhas) list.push('Risco de Tombamento de Muralhas de Vedacao');
  if (v.v_patologia_inundacoes) list.push('Inundacoes');
  if (v.v_patologia_alagamentos) list.push('Alagamentos');
  if (v.v_patologia_enxurradas) list.push('Enxurradas');
  if (v.v_patologia_madeira) list.push('Patologia em Elementos de Madeira (cupins, apodrecimento, etc.)');
  if (v.v_patologia_elementos_nao_estruturais) list.push('Comprometimento de Elementos Nao Estruturais (janelas, portas, etc.)');
  if (v.v_patologia_falha_drenagem) list.push('Falha em Drenagem Pluvial ou Esgoto');
  if (v.v_patologia_queda_arvores) list.push('Risco de Queda de Arvores e/ou Troncos e Galhos');
  if (v.v_patologia_outros) {
    let texto = 'Outros';
    if (v.v_patologia_outros_descricao) texto += ` (${v.v_patologia_outros_descricao})`;
    list.push(texto);
  }

  return list;
});

const bensAfetados = computed(() => {
  if (!props.vistoria) return [];
  const list = [];
  const v = props.vistoria;

  if (v.v_bens_residencia) list.push('Residencia');
  if (v.v_bens_muros) list.push('Muros');
  if (v.v_bens_vias_publicas) list.push('Vias Publicas');
  if (v.v_bens_pontes) list.push('Pontes');
  if (v.v_bens_viadutos) list.push('Viadutos');
  if (v.v_bens_comercios) list.push('Comercios');
  if (v.v_bens_galpoes) list.push('Galpoes');
  if (v.v_bens_predios_publicos) list.push('Predios Publicos');
  if (v.v_bens_edificios_publicos) list.push('Edificios Publicos');
  if (v.v_bens_outros) {
    let texto = 'Outros';
    if (v.v_bens_outros_descricao) texto += ` (${v.v_bens_outros_descricao})`;
    list.push(texto);
  }

  return list;
});

const encaminhamentos = computed(() => {
  if (!props.vistoria) return [];
  const list = [];
  const v = props.vistoria;

  if (v.v_enc_interdicao_parcial) list.push('Interdicao Parcial');
  if (v.v_enc_interdicao_total) list.push('Interdicao Total');
  if (v.v_enc_remocao_temporaria) list.push('Remocao Temporaria');
  if (v.v_enc_remocao_definitiva) list.push('Remocao Definitiva');
  if (v.v_enc_isolamento_area) list.push('Isolamento da Area');
  if (v.v_enc_desocupacao_abrigo) list.push('Desocupacao / Abrigo');
  if (v.v_enc_notificacao_responsavel) list.push('Notificacao ao Responsavel');
  if (v.v_enc_contratacao_responsavel) list.push('Contratacao de Responsavel Tecnico');
  if (v.v_enc_comunicacao_orgaos) list.push('Comunicacao a Orgaos Competentes');
  if (v.v_enc_apoio_social) list.push('Apoio Social');
  if (v.v_enc_outros) {
    let texto = 'Outros';
    if (v.v_enc_outros_descricao) texto += ` (${v.v_enc_outros_descricao})`;
    list.push(texto);
  }

  return list;
});

const orgaosAcionados = computed(() => {
  if (!props.vistoria) return [];
  const list = [];
  const v = props.vistoria;

  if (v.v_orgao_copasa) list.push('COPASA');
  if (v.v_orgao_cemig) list.push('CEMIG');
  if (v.v_orgao_secretaria_municipal) list.push('Secretaria Municipal');
  if (v.v_orgao_defesa_civil_estadual) list.push('Defesa Civil Estadual');
  if (v.v_orgao_dnit) list.push('DNIT');
  if (v.v_orgao_pm) list.push('Policia Militar');
  if (v.v_orgao_bm) list.push('Bombeiros Militares');
  if (v.v_orgao_crea) list.push('CREA');
  if (v.v_orgao_emater) list.push('EMATER');
  if (v.v_orgao_seapa) list.push('SEAPA');
  if (v.v_orgao_outros) {
    let texto = 'Outros';
    if (v.v_orgao_outros_descricao) texto += ` (${v.v_orgao_outros_descricao})`;
    list.push(texto);
  }

  return list;
});
</script>

<template>
  <div v-if="vistoria">
    <div class="section-title">VISTORIA TECNICA</div>

    <table class="bos-table">
      <tr>
        <td class="field-label" width="20%">TIPO DE IMOVEL</td>
        <td class="field-value" width="30%">{{ (vistoria.v_tipo_imovel || '').toUpperCase() }}</td>
        <td class="field-label" width="20%">PROPRIETARIO/MORADOR</td>
        <td class="field-value" width="30%">{{ vistoria.v_proprietario_morador || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">TELEFONE DE CONTATO</td>
        <td class="field-value">{{ vistoria.v_contato_telefone || '' }}</td>
        <td class="field-label">No DE MORADORES</td>
        <td class="field-value">{{ vistoria.v_numero_moradores || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">HA IDOSOS?</td>
        <td class="field-value">{{ (vistoria.v_ha_idosos || '').toUpperCase() }}</td>
        <td class="field-label">HA CRIANCAS?</td>
        <td class="field-value">{{ (vistoria.v_ha_criancas || '').toUpperCase() }}</td>
      </tr>
      <tr>
        <td class="field-label">HA PESSOAS COM DIFICULDADE DE LOCOMOCAO?</td>
        <td class="field-value" colspan="3">{{ (vistoria.v_ha_dificuldade_locomocao || '').toUpperCase() }}</td>
      </tr>
      <tr>
        <td class="field-label">ENDERECO DO IMOVEL</td>
        <td class="field-value" colspan="3">{{ vistoria.v_endereco_imovel || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">BAIRRO</td>
        <td class="field-value">{{ vistoria.v_bairro || '' }}</td>
        <td class="field-label">CEP</td>
        <td class="field-value">{{ vistoria.v_cep || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">LATITUDE</td>
        <td class="field-value">{{ vistoria.v_latitude || '' }}</td>
        <td class="field-label">LONGITUDE</td>
        <td class="field-value">{{ vistoria.v_longitude || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">ABASTECIMENTO DE AGUA</td>
        <td class="field-value">{{ (vistoria.v_abastecimento_agua || '').toUpperCase() }}</td>
        <td class="field-label">ESGOTAMENTO SANITARIO</td>
        <td class="field-value">{{ (vistoria.v_esgotamento_sanitario || '').toUpperCase() }}</td>
      </tr>
      <tr>
        <td class="field-label">DRENAGEM SUPERFICIAL</td>
        <td class="field-value">{{ (vistoria.v_drenagem_superficial || '').toUpperCase() }}</td>
        <td class="field-label">TIPO DE REVESTIMENTO</td>
        <td class="field-value">{{ (vistoria.v_tipo_revestimento || '').toUpperCase() }}</td>
      </tr>
      <tr>
        <td class="field-label">SISTEMA VIARIO DE ACESSO</td>
        <td class="field-value" colspan="3">{{ vistoria.v_sistema_viario_acesso || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">CONDICOES DE ACESSO</td>
        <td class="field-value" colspan="3">{{ vistoria.v_condicoes_acesso || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">No DE MORADIAS NO TERRENO</td>
        <td class="field-value">{{ vistoria.v_numero_moradias_terreno || '' }}</td>
        <td class="field-label">DISTANCIA DA ENCOSTA</td>
        <td class="field-value">{{ vistoria.v_distancia_encosta || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">MATERIAL CONSTRUTIVO</td>
        <td class="field-value" colspan="3">{{ vistoria.v_material_construtivo || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">CONSERVACAO ESTRUTURAL</td>
        <td class="field-value" colspan="3">{{ vistoria.v_conservacao_estrutural || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">ELEMENTOS ESTRUTURAIS</td>
        <td class="field-value" colspan="3">{{ vistoria.v_elementos_estruturais || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">ELEMENTOS CONSTRUTIVOS</td>
        <td class="field-value" colspan="3">{{ vistoria.v_elementos_construtivos || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">AGENTES POTENCIALIZADORES</td>
        <td class="field-value" colspan="3">{{ vistoria.v_agentes_potencializadores || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">PROCESSOS GEODINAMICOS</td>
        <td class="field-value" colspan="3">{{ vistoria.v_processos_geodinamicos || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">BENS E INFRAESTRUTURAS AFETADOS</td>
        <td class="field-value" colspan="3">{{ vistoria.v_bens_afetados || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">ENCAMINHAMENTOS NECESSARIOS</td>
        <td class="field-value" colspan="3">{{ vistoria.v_encaminhamentos || '' }}</td>
      </tr>
      <tr v-if="vistoria.v_encaminhamentos_outros">
        <td class="field-label">OUTROS ENCAMINHAMENTOS</td>
        <td class="field-value" colspan="3">{{ vistoria.v_encaminhamentos_outros }}</td>
      </tr>
      <tr>
        <td class="field-label">ORGAOS/ENTIDADES ACIONADOS</td>
        <td class="field-value" colspan="3">{{ vistoria.v_orgaos_acionados || '' }}</td>
      </tr>
      <tr v-if="vistoria.v_orgaos_acionados_outros">
        <td class="field-label">OUTROS ORGAOS/ENTIDADES</td>
        <td class="field-value" colspan="3">{{ vistoria.v_orgaos_acionados_outros }}</td>
      </tr>
      <tr v-if="vistoria.v_historico">
        <td class="field-label">HISTORICO / OBSERVACOES</td>
        <td class="field-value" colspan="3" style="white-space: pre-wrap;">{{ vistoria.v_historico }}</td>
      </tr>
    </table>

    <template v-if="patologias.length > 0">
      <div class="subsection-title">TIPIFICACAO DA OCORRENCIA / PATOLOGIAS IDENTIFICADAS</div>
      <table class="bos-table">
        <tr>
          <td class="field-value" style="padding: 10px;">
            <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
              <li v-for="patologia in patologias" :key="patologia" style="margin-bottom: 5px;">
                {{ patologia }}
              </li>
            </ul>
          </td>
        </tr>
      </table>
    </template>

    <template v-if="bensAfetados.length > 0">
      <div class="subsection-title">BENS E INFRAESTRUTURAS AFETADOS</div>
      <table class="bos-table">
        <tr>
          <td class="field-value" style="padding: 10px;">
            <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
              <li v-for="bem in bensAfetados" :key="bem" style="margin-bottom: 5px;">
                {{ bem }}
              </li>
            </ul>
          </td>
        </tr>
      </table>
    </template>

    <template v-if="encaminhamentos.length > 0">
      <div class="subsection-title">ANALISE DE VULNERABILIDADE / ENCAMINHAMENTOS</div>
      <table class="bos-table">
        <tr>
          <td class="field-value" style="padding: 10px;">
            <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
              <li v-for="enc in encaminhamentos" :key="enc" style="margin-bottom: 5px;">
                {{ enc }}
              </li>
            </ul>
          </td>
        </tr>
      </table>
    </template>

    <template v-if="orgaosAcionados.length > 0">
      <div class="subsection-title">ORGAOS / ENTIDADES ACIONADAS</div>
      <table class="bos-table">
        <tr>
          <td class="field-value" style="padding: 10px;">
            <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
              <li v-for="orgao in orgaosAcionados" :key="orgao" style="margin-bottom: 5px;">
                {{ orgao }}
              </li>
            </ul>
          </td>
        </tr>
      </table>
    </template>
  </div>
</template>
