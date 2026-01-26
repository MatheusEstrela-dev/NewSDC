<script setup>
defineProps({
  envolvidos: {
    type: Array,
    default: () => [],
  },
});

function formatDate(date) {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString('pt-BR');
}
</script>

<template>
  <div>
    <div class="section-title">ENVOLVIDOS NA OCORRENCIA</div>

    <template v-if="envolvidos.length > 0">
      <template v-for="(envolvido, index) in envolvidos" :key="index">
        <div v-if="envolvidos.length > 1" class="subsection-title">
          ENVOLVIDO No {{ index + 1 }}
        </div>

        <table class="bos-table">
          <tr>
            <td class="field-label" width="20%">TIPO DE PESSOA</td>
            <td class="field-value" width="30%">{{ envolvido?.g_tipo_pessoa || '' }}</td>
            <td class="field-label" width="20%"></td>
            <td class="field-value" width="30%"></td>
          </tr>
          <tr>
            <td class="field-label">NOME COMPLETO / RAZAO SOCIAL</td>
            <td class="field-value" colspan="3">{{ envolvido?.p_nome_completo || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">APELIDO / NOME FANTASIA</td>
            <td class="field-value">{{ envolvido?.p_nome_fantasia || '' }}</td>
            <td class="field-label">NOME SOCIAL</td>
            <td class="field-value">{{ envolvido?.p_nome_social || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">DATA DE NASCIMENTO</td>
            <td class="field-value">{{ formatDate(envolvido?.p_data_nascimento) }}</td>
            <td class="field-label">IDADE APARENTE</td>
            <td class="field-value">{{ envolvido?.p_idade_aparente || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">CPF</td>
            <td class="field-value">{{ envolvido?.p_cpf || envolvido?.cpf || '' }}</td>
            <td class="field-label">SEXO</td>
            <td class="field-value">{{ (envolvido?.p_sexo || envolvido?.sexo || '').toUpperCase() }}</td>
          </tr>
          <tr>
            <td class="field-label">NOME DA MAE</td>
            <td class="field-value" colspan="3">{{ envolvido?.p_nome_mae || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">NOME DO PAI</td>
            <td class="field-value" colspan="3">{{ envolvido?.p_nome_pai || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">OCUPACAO ATUAL</td>
            <td class="field-value">{{ envolvido?.p_ocupacao_atual || '' }}</td>
            <td class="field-label">ESCOLARIDADE</td>
            <td class="field-value">{{ envolvido?.p_escolaridade || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">ESTADO CIVIL</td>
            <td class="field-value">{{ envolvido?.p_estado_civil || '' }}</td>
            <td class="field-label">COR/RACA</td>
            <td class="field-value">{{ envolvido?.p_cor_raca || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">NACIONALIDADE</td>
            <td class="field-value" colspan="3">{{ envolvido?.p_nacionalidade || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">NATURALIDADE/UF</td>
            <td class="field-value">{{ envolvido?.p_naturalidade_uf || '' }}</td>
            <td class="field-label">PAIS DE ORIGEM</td>
            <td class="field-value">{{ envolvido?.p_pais_origem || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">ORIENTACAO SEXUAL</td>
            <td class="field-value">{{ envolvido?.p_orientacao_sexual || '' }}</td>
            <td class="field-label">IDENTIDADE DE GENERO</td>
            <td class="field-value">{{ envolvido?.p_identidade_genero || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">E TURISTA?</td>
            <td class="field-value">{{ envolvido?.p_turista ? 'SIM' : 'NAO' }}</td>
            <td class="field-label">PESSOA EM SITUACAO DE RUA?</td>
            <td class="field-value">
              {{ envolvido?.p_situacao_rua === null ? 'NAO INFORMADO' : (envolvido?.p_situacao_rua ? 'SIM' : 'NAO') }}
            </td>
          </tr>
          <tr>
            <td class="field-label">TIPO DE DOCUMENTO</td>
            <td class="field-value">{{ envolvido?.p_tipo || '' }}</td>
            <td class="field-label">NUMERO DO DOCUMENTO</td>
            <td class="field-value">{{ envolvido?.p_numero || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">ORGAO EXPEDIDOR</td>
            <td class="field-value">{{ envolvido?.p_orgao_expedidor || '' }}</td>
            <td class="field-label">UF</td>
            <td class="field-value">{{ envolvido?.p_uf || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">TELEFONE RESIDENCIAL/CELULAR</td>
            <td class="field-value">{{ envolvido?.p_telefone_residencial || '' }}</td>
            <td class="field-label">TELEFONE COMERCIAL/CELULAR</td>
            <td class="field-value">{{ envolvido?.p_telefone_comercial || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">E-MAIL</td>
            <td class="field-value" colspan="3">{{ envolvido?.p_email || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">MOTIVO AUSENCIA TELEFONE/E-MAIL</td>
            <td class="field-value" colspan="3">{{ envolvido?.p_motivo_ausencia_contato || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">E MILITAR/POLICIAL/AGENTE?</td>
            <td class="field-value" colspan="3">{{ envolvido?.g_envolvido_presenca ? 'SIM' : 'NAO' }}</td>
          </tr>
          <template v-if="envolvido?.g_envolvido_presenca">
            <tr>
              <td class="field-label">TIPO</td>
              <td class="field-value">{{ envolvido?.g_envolvido_tipo || '' }}</td>
              <td class="field-label">ORGAO</td>
              <td class="field-value">{{ envolvido?.g_envolvido_orgao || '' }}</td>
            </tr>
            <tr>
              <td class="field-label">UF</td>
              <td class="field-value">{{ envolvido?.g_envolvido_uf || '' }}</td>
              <td class="field-label">MATRICULA/NR</td>
              <td class="field-value">{{ envolvido?.g_envolvido_matricula || '' }}</td>
            </tr>
            <tr>
              <td class="field-label">EM SERVICO?</td>
              <td class="field-value" colspan="3">{{ envolvido?.g_envolvido_servico || '' }}</td>
            </tr>
          </template>
        </table>
      </template>
    </template>

    <table v-else class="bos-table">
      <tr>
        <td class="field-value text-center" style="padding: 20px;">
          Nenhum envolvido cadastrado
        </td>
      </tr>
    </table>
  </div>
</template>
