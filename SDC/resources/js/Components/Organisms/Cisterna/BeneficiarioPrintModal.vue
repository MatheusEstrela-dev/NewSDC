<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Ficha da Cisterna"
    :document-title="tituloDocumento"
    :loading="carregando"
    @close="$emit('close')"
  >
    <p v-if="erro" class="p-6 text-center text-sm text-red-600">{{ erro }}</p>

    <div v-else-if="dados" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="FICHA DE INSTALACAO - PROGRAMA DE CISTERNAS"
          :numero="numeroDoDocumento"
          :label-numero="dados.numero_instalacao ? 'Nº INSTALACAO' : 'ID'"
        />

        <div class="card-body p-0">
          <PrintSection titulo="IDENTIFICACAO DO BENEFICIARIO">
            <PrintFieldsTable :fields="identificacao" />
          </PrintSection>

          <PrintSection titulo="LOCALIZACAO DO IMOVEL">
            <PrintFieldsTable :fields="localizacao" />
          </PrintSection>

          <PrintSection titulo="COMPOSICAO FAMILIAR E RENDA">
            <PrintFieldsTable :fields="social" />
          </PrintSection>

          <PrintSection titulo="MORADIA E TELHADO">
            <PrintFieldsTable :fields="tecnica" />
          </PrintSection>

          <!--
            A cadeia inteira no documento, e nao so a etapa final: a ficha
            comprova que as TRES conferencias aconteceram, com o responsavel
            tecnico e a data de cada uma. E o que a prestacao de contas verifica.
          -->
          <PrintSection titulo="CADEIA DE FISCALIZACAO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="6%">#</td>
                <td class="field-label" width="30%">ETAPA</td>
                <td class="field-label" width="14%">CONCLUSAO</td>
                <td class="field-label" width="32%">RESPONSAVEL TECNICO</td>
                <td class="field-label" width="18%">CREA</td>
              </tr>
              <tr v-for="(v, i) in vistorias" :key="v.id">
                <td class="field-value">{{ i + 1 }}</td>
                <td class="field-value">{{ v.etapa?.rotulo ?? v.etapa }}</td>
                <td class="field-value">{{ dataBr(v.concluida_em) }}</td>
                <td class="field-value">{{ v.engenheiro?.nome || '—' }}</td>
                <td class="field-value">{{ v.engenheiro?.crea || '—' }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="ATENDIMENTO POR CARRO-PIPA">
            <div class="p-2 text-xs">
              {{ resumoPipa }}
            </div>
          </PrintSection>

          <PrintSection v-if="dados.observacoes" titulo="OBSERVACOES">
            <div class="whitespace-pre-line p-2 text-xs">{{ dados.observacoes }}</div>
          </PrintSection>

          <!--
            Assinatura do beneficiario: a ficha e o documento que atesta o
            recebimento da cisterna instalada, e sem campo de assinatura ela nao
            serve para o proposito que a faz existir.
          -->
          <PrintSection titulo="DECLARACAO DE RECEBIMENTO">
            <div class="p-3 text-xs">
              <p class="mb-6">
                Declaro que a cisterna identificada nesta ficha foi instalada no imovel acima
                e conferida nas tres etapas de fiscalizacao do programa.
              </p>

              <div class="flex justify-between gap-8 pt-8">
                <div class="flex-1 border-t border-black pt-1 text-center">
                  {{ dados.nome }}<br>
                  <span class="text-[10px]">Beneficiario — CPF {{ cpfFormatado }}</span>
                </div>
                <div class="flex-1 border-t border-black pt-1 text-center">
                  <span class="text-[10px]">Responsavel pela fiscalizacao — CEDEC / MG</span>
                </div>
              </div>
            </div>
          </PrintSection>
        </div>
      </div>
    </div>
  </BasePrintModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import PrintFieldsTable from '@/Components/Organisms/Print/Sections/PrintFieldsTable.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  /** Precisa de `id`; o resto vem do servidor ao abrir. */
  beneficiario: { type: Object, default: null },
});

defineEmits(['close']);

const carregando = ref(false);
const erro = ref('');
const dados = ref(null);

const vistorias = computed(
  () => (dados.value?.vistorias ?? []).filter((v) => v.concluida),
);

const cpfFormatado = computed(() => {
  const d = String(dados.value?.cpf ?? '').replace(/\D/g, '');

  return d.length === 11 ? d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : (dados.value?.cpf ?? '—');
});

const numeroDoDocumento = computed(() => {
  const n = dados.value?.numero_instalacao;

  return n ? `#${n}` : `#${dados.value?.id ?? ''}`;
});

const tituloDocumento = computed(
  () => `ficha-cisterna-${dados.value?.numero_instalacao ?? dados.value?.id ?? 's-n'}`,
);

const identificacao = computed(() => {
  const b = dados.value ?? {};

  return [
    [
      { label: 'Nome', value: b.nome, valueWidth: '80%', colspan: 3 },
    ],
    [
      { label: 'CPF', value: cpfFormatado.value },
      { label: 'Nascimento', value: dataBr(b.data_nascimento) },
    ],
    [
      { label: 'Telefone', value: b.telefone || '—' },
      { label: 'Cadastro Unico', value: b.cadastro_unico || '—' },
    ],
  ];
});

const localizacao = computed(() => {
  const b = dados.value ?? {};

  return [
    [
      { label: 'Municipio', value: [b.municipio?.nome, b.municipio?.uf].filter(Boolean).join(' / ') },
      { label: 'Comunidade', value: b.comunidade?.nome || '—' },
    ],
    [
      { label: 'Endereco', value: b.endereco || '—', valueWidth: '80%', colspan: 3 },
    ],
    [
      { label: 'Coordenada', value: b.latitude && b.longitude ? `${b.latitude}, ${b.longitude}` : '—' },
      { label: 'Lote / Ordem', value: [b.lote?.nome, b.ordem_servico?.nome].filter(Boolean).join(' / ') || '—' },
    ],
  ];
});

const social = computed(() => {
  const s = dados.value?.criterios_sociais ?? {};

  return [
    [
      { label: 'Pessoas na residencia', value: s.qtd_pessoas ?? '—' },
      { label: 'Renda familiar', value: moeda(s.renda) },
    ],
    [
      { label: 'Renda per capita', value: moeda(s.renda_per_capita) },
      { label: 'Chefiada por mulher', value: simNao(s.chefiada_mulher) },
    ],
    [
      { label: 'Pessoa com deficiencia', value: simNao(s.possui_deficiencia) },
      { label: 'Idoso na residencia', value: simNao(s.possui_idoso) },
    ],
  ];
});

const tecnica = computed(() => {
  const t = dados.value?.avaliacao_tecnica ?? {};

  return [
    [
      { label: 'Regime de posse', value: t.tipo_moradia || '—' },
      { label: 'Cobertura', value: t.cobertura_telhado || '—' },
    ],
    [
      { label: 'Comprimento (m)', value: t.comprimento_telhado ?? '—' },
      { label: 'Largura (m)', value: t.largura_telhado ?? '—' },
    ],
    [
      { label: 'Area do telhado (m2)', value: t.area_telhado ?? '—' },
      { label: 'Testada (m)', value: t.comprimento_testada ?? '—' },
    ],
    [
      { label: 'Caidas do telhado', value: t.num_caidas_telhado ?? '—' },
      { label: 'Fogao a lenha', value: simNao(t.possui_fogao_lenha) },
    ],
  ];
});

const resumoPipa = computed(() => {
  const p = dados.value?.atendimento_pipa ?? {};

  if (!p.atendido) return 'A familia nao recebe agua por carro-pipa.';

  const quem = (p.responsaveis ?? []).map((r) => r.rotulo ?? r.valor ?? r).join(', ');

  return `Atendida por carro-pipa${quem ? ` — ${quem}` : ''}${p.descricao ? ` (${p.descricao})` : ''}.`;
});

/**
 * Busca ao abrir, e nao por prop: a listagem so carrega o resumo de cada linha,
 * e a ficha precisa do cadastro inteiro com os tres relatorios. Mandar tudo isso
 * nas 25 linhas da pagina seria payload grande para um documento que se imprime
 * de vez em quando.
 */
async function carregar() {
  if (!props.beneficiario?.id) return;

  carregando.value = true;
  erro.value = '';
  dados.value = null;

  let url;

  try {
    url = route('cisternas.beneficiarios.impressao', props.beneficiario.id);
  } catch {
    erro.value = 'Esta aba foi carregada antes desta funcionalidade existir. Recarregue a pagina.';
    carregando.value = false;

    return;
  }

  try {
    const resposta = await fetch(url, { headers: { Accept: 'application/json' } });
    const corpo = await resposta.json().catch(() => null);

    if (!resposta.ok) {
      // O 422 traz a mensagem do dominio dizendo QUAL etapa falta. Trocar por
      // texto genErico esconderia justamente o que resolve o impedimento.
      erro.value = corpo?.message
        ?? (resposta.status === 403
          ? 'Voce nao tem alcance sobre este cadastro.'
          : `Nao foi possivel montar a ficha (HTTP ${resposta.status}).`);

      return;
    }

    dados.value = corpo.beneficiario;
  } catch {
    erro.value = 'Falha de rede ao montar a ficha.';
  } finally {
    carregando.value = false;
  }
}

watch(
  () => [props.show, props.beneficiario?.id],
  ([aberto]) => {
    if (aberto) carregar();
  },
);

function dataBr(iso) {
  if (!iso) return '—';

  const [ano, mes, dia] = String(iso).slice(0, 10).split('-');

  return dia && mes && ano ? `${dia}/${mes}/${ano}` : iso;
}

function moeda(valor) {
  if (valor === null || valor === undefined || valor === '') return '—';

  return `R$ ${Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function simNao(valor) {
  return valor ? 'Sim' : 'Nao';
}
</script>
