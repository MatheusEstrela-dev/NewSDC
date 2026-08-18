<template>
  <AuthenticatedLayout>
    <Head :title="`Cisternas — ${beneficiario.nome}`" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        :title="beneficiario.nome"
        :description="cpfFormatado"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
      >
        <template #actions>
          <Link :href="route('cisternas.beneficiarios.index')" :class="BOTAO_SEC">Beneficiarios</Link>
          <Link :href="route('cisternas.vistorias.index', beneficiario.id)" :class="BOTAO_SEC">Vistorias</Link>
          <Link v-if="permissoes.editar" :href="route('cisternas.beneficiarios.edit', beneficiario.id)" :class="BOTAO">Editar</Link>
        </template>
      </PageHeader>

      <!-- As situacoes ficam fora do header: sao estado do registro, nao acao. -->
      <div class="-mt-2">
          <div class="flex flex-wrap items-center gap-2">
            <SituacaoAnaliseBadge
              :valor="beneficiario.situacao_analise.valor"
              :rotulo="beneficiario.situacao_analise.rotulo"
            />
            <SituacaoObraBadge
              :valor="beneficiario.situacao_obra.valor"
              :rotulo="beneficiario.situacao_obra.rotulo"
            />
            <EtapaVistoriaBadge
              v-for="etapa in ETAPAS"
              :key="etapa"
              :etapa="etapa"
              :concluida="etapaConcluida(etapa)"
            />
          </div>
      </div>

      <!--
        Observacao da analise em destaque, e nao numa aba: quando ha ressalva ou
        reprovacao, e o motivo que a pessoa precisa ler primeiro. Nos cadastros
        importados este campo diz por que o registro foi marcado duplicado.
      -->
      <div
        v-if="beneficiario.situacao_analise.observacao"
        class="rounded-md border-l-4 border-blue-500 bg-blue-50 p-3 text-sm text-blue-900 dark:bg-blue-500/10 dark:text-blue-200"
      >
        <strong class="font-semibold">Analise:</strong> {{ beneficiario.situacao_analise.observacao }}
      </div>

      <DadosBloco titulo="Identificacao" :itens="itensIdentificacao" />
      <DadosBloco titulo="Localizacao" :itens="itensLocalizacao" />
      <DadosBloco titulo="Familia e renda" :itens="itensSociais" />
      <DadosBloco titulo="Moradia e telhado" :itens="itensTecnicos" />
      <DadosBloco titulo="Atendimento por carro-pipa" :itens="itensPipa" />
      <DadosBloco titulo="Responsaveis tecnicos" :itens="itensResponsaveis" />

      <FotosImovelGallery :fotos="beneficiario.fotos_imovel ?? []" :do-legado="doLegado" />

      <section v-if="(beneficiario.comprovantes ?? []).length > 0" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
        <h3 class="mb-3 text-sm font-bold text-slate-900 dark:text-slate-100">Comprovantes</h3>
        <ul class="space-y-1 text-sm">
          <li v-for="c in beneficiario.comprovantes" :key="c.id">
            <a :href="c.url" target="_blank" rel="noopener" class="text-blue-700 hover:underline dark:text-blue-300">
              {{ c.nome || c.tipo }}
            </a>
            <span class="text-slate-400"> — {{ c.tipo }}</span>
          </li>
        </ul>
      </section>

      <section v-if="beneficiario.observacoes" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
        <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-slate-100">Observacoes</h3>
        <p class="whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ beneficiario.observacoes }}</p>
      </section>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import SituacaoAnaliseBadge from '@/Components/Atoms/Cisterna/SituacaoAnaliseBadge.vue';
import SituacaoObraBadge from '@/Components/Atoms/Cisterna/SituacaoObraBadge.vue';
import EtapaVistoriaBadge from '@/Components/Atoms/Cisterna/EtapaVistoriaBadge.vue';
import FotosImovelGallery from '@/Components/Organisms/Cisterna/FotosImovelGallery.vue';
import DadosBloco from '@/Components/Molecules/Cisterna/DadosBloco.vue';

const props = defineProps({
  beneficiario: { type: Object, required: true },
  etapaDisponivel: { type: String, default: null },
  permissoes: { type: Object, default: () => ({}) },
});

const ETAPAS = ['fornecedor', 'compdec', 'cedec'];

const BOTAO = 'rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700';
const BOTAO_SEC = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';

const social = computed(() => props.beneficiario.criterios_sociais ?? {});
const tecnica = computed(() => props.beneficiario.avaliacao_tecnica ?? {});
const pipa = computed(() => props.beneficiario.atendimento_pipa ?? {});
const responsaveis = computed(() => props.beneficiario.responsaveis_cadastro ?? {});

const doLegado = computed(() => (props.beneficiario.fotos_imovel ?? []).length === 0);

const cpfFormatado = computed(() => {
  const digitos = String(props.beneficiario.cpf ?? '').replace(/\D/g, '');

  return digitos.length === 11
    ? digitos.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
    : props.beneficiario.cpf;
});

const itensIdentificacao = computed(() => [
  { rotulo: 'Telefone', valor: props.beneficiario.telefone },
  { rotulo: 'Nascimento', valor: data(props.beneficiario.data_nascimento) },
  { rotulo: 'Cadastro Unico', valor: props.beneficiario.cadastro_unico },
]);

const itensLocalizacao = computed(() => [
  { rotulo: 'Municipio', valor: nomeUf(props.beneficiario.municipio) },
  { rotulo: 'Comunidade', valor: props.beneficiario.comunidade?.nome },
  { rotulo: 'Endereco', valor: props.beneficiario.endereco },
  { rotulo: 'Coordenada', valor: coordenada.value },
  { rotulo: 'Lote / Ordem', valor: loteOrdem.value },
  { rotulo: 'Ranqueamento', valor: props.beneficiario.ranqueamento_ordem },
]);

const itensSociais = computed(() => [
  { rotulo: 'Pessoas na residencia', valor: social.value.qtd_pessoas },
  { rotulo: 'Renda familiar', valor: moeda(social.value.renda) },
  { rotulo: 'Renda per capita', valor: moeda(social.value.renda_per_capita) },
  { rotulo: 'Pessoa com deficiencia', valor: booleano(social.value.possui_deficiencia) },
  { rotulo: 'Crianca', valor: booleano(social.value.possui_crianca) },
  { rotulo: 'Nascimento da crianca', valor: data(social.value.data_nascimento_crianca) },
  { rotulo: 'Idoso', valor: booleano(social.value.possui_idoso) },
  { rotulo: 'Chefiada por mulher', valor: booleano(social.value.chefiada_mulher) },
]);

const itensTecnicos = computed(() => [
  { rotulo: 'Regime de posse', valor: tecnica.value.tipo_moradia_outro || tecnica.value.tipo_moradia },
  { rotulo: 'Cobertura', valor: tecnica.value.cobertura_outro || tecnica.value.cobertura_telhado },
  { rotulo: 'Comprimento do telhado', valor: metros(tecnica.value.comprimento_telhado) },
  { rotulo: 'Largura do telhado', valor: metros(tecnica.value.largura_telhado) },
  { rotulo: 'Area do telhado', valor: metrosQuadrados(tecnica.value.area_telhado) },
  { rotulo: 'Comprimento da testada', valor: metros(tecnica.value.comprimento_testada) },
  { rotulo: 'Caidas do telhado', valor: tecnica.value.num_caidas_telhado },
  { rotulo: 'Fogao a lenha', valor: booleano(tecnica.value.possui_fogao_lenha) },
  { rotulo: 'Telhado na area do fogao', valor: metrosQuadrados(tecnica.value.medida_telhado_area_fogao) },
  { rotulo: 'Testada nessa parte', valor: metros(tecnica.value.testada_disp_parte_fogao) },
]);

const itensPipa = computed(() => [
  { rotulo: 'Atendido', valor: booleano(pipa.value.atendido) },
  {
    rotulo: 'Responsaveis',
    valor: (pipa.value.responsaveis ?? []).map((r) => r.rotulo ?? r.valor).join(', '),
  },
  { rotulo: 'Outro responsavel', valor: pipa.value.descricao },
]);

const itensResponsaveis = computed(() => [
  { rotulo: 'Agente', valor: responsaveis.value.agente_nome },
  { rotulo: 'CPF do agente', valor: responsaveis.value.agente_cpf },
  { rotulo: 'Engenheiro', valor: responsaveis.value.engenheiro_nome },
  { rotulo: 'CREA', valor: responsaveis.value.engenheiro_crea },
]);

const coordenada = computed(() => {
  const { latitude, longitude } = props.beneficiario;

  return latitude && longitude ? `${latitude}, ${longitude}` : null;
});

const loteOrdem = computed(() => {
  const os = props.beneficiario.ordem_servico;

  if (!os) return null;

  return [os.lote, os.nome].filter(Boolean).join(' / ');
});

function etapaConcluida(etapa) {
  return (props.beneficiario.vistorias ?? []).some(
    (v) => (v.etapa?.valor ?? v.etapa) === etapa && Boolean(v.concluida_em),
  );
}

function nomeUf(municipio) {
  if (!municipio) return null;

  return municipio.uf ? `${municipio.nome} / ${municipio.uf}` : municipio.nome;
}

function booleano(valor) {
  if (valor === null || valor === undefined) return null;

  return valor ? 'Sim' : 'Nao';
}

function data(valor) {
  if (!valor) return null;

  const [ano, mes, dia] = String(valor).slice(0, 10).split('-');

  return dia ? `${dia}/${mes}/${ano}` : valor;
}

function moeda(valor) {
  if (valor === null || valor === undefined || valor === '') return null;

  return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function metros(valor) {
  return valor === null || valor === undefined || valor === '' ? null : `${valor} m`;
}

function metrosQuadrados(valor) {
  return valor === null || valor === undefined || valor === '' ? null : `${valor} m2`;
}
</script>
