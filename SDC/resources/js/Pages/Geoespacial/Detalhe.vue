<template>
  <div class="detalhe-container">
    <Link :href="route('geoespacial.index')" class="voltar">&larr; Camadas de Risco</Link>

    <!--
      O quadradinho de cor do dominio nao cabe no PageHeader, que recebe titulo
      como string. Ele desceu para a descricao, em texto: "Geologico" ja diz o
      que a cor dizia, e a cor continua no mapa e na legenda, que e onde ela
      distingue uma area da outra.
    -->
    <PageHeader
      :title="camada.nome"
      :description="descricaoDoCabecalho"
      :icon="MapIcon"
      :icon-image="moduleIcon('geoespacial')"
      variant="gradient"
    >
      <template #actions>
        <ActionButton
          module="geoespacial"
          resource="camadas"
          :actions="acoesDe(camadaComAcoes, { verDetalhe: false })"
        />
      </template>
    </PageHeader>

    <div v-if="$page.props.flash?.sucesso" class="aviso aviso-sucesso">{{ $page.props.flash.sucesso }}</div>
    <div v-if="erros.camada" class="aviso aviso-erro">{{ erros.camada }}</div>

    <!--
      A situacao vem antes de tudo, e escrita por extenso.

      Numa tela que abre camada em quatro estados diferentes, "o que esta
      acontecendo com esta camada" e a primeira pergunta -- e a resposta muda o
      que a geometria abaixo significa: desenho publicado no plantao, rascunho
      esperando aprovacao ou area que ja saiu do ar.
    -->
    <div class="situacao" :class="`is-${camada.status}`">
      <strong>{{ SITUACAO[camada.status]?.titulo ?? camada.status }}</strong>
      <span>{{ SITUACAO[camada.status]?.texto ?? '' }}</span>

      <span v-if="camada.motivo_recusa" class="situacao-motivo">
        Motivo da recusa: {{ camada.motivo_recusa }}
      </span>
      <span v-if="camada.motivo_arquivamento" class="situacao-motivo">
        {{ camada.motivo_arquivamento }}
      </span>
      <!--
        Vencida com status aprovada nao deveria durar: geoespacial:arquivar-vencidas
        roda as 05:00. Se aparece, e a janela entre o vencimento e a varredura --
        e o plantao precisa saber disso ao olhar a area no mapa.
      -->
      <span v-if="camada.vencida && camada.status === 'aprovada'" class="situacao-motivo">
        Validade venceu em {{ formatarData(camada.valido_ate) }}: sera arquivada na
        proxima varredura automatica.
      </span>
    </div>

    <div class="conteudo-grid">
      <div class="map-wrapper">
        <MapaLeaflet :poligonos="poligonosDoMapa" :bbox="bbox" class="mapa-area" />

        <div class="map-overlay cruzamento">
          <h3 class="overlay-title">Cruzamento</h3>

          <div class="cruzamento-linha">
            <span>Municipios atingidos</span>
            <strong>{{ cruzamento.municipios }}</strong>
          </div>
          <!--
            A ressalva e obrigatoria: o cruzamento e por CENTROIDE do municipio,
            porque a tabela municipios guarda latitude/longitude e nao geometria
            de territorio. Municipio cujo centroide cai fora mas cujo territorio
            e atingido NAO entra na conta. Apresentar como exato seria mentira
            operacional num sistema de Defesa Civil.
          -->
          <p class="cruzamento-nota">Contagem por centroide do municipio: e piso, nao total.</p>

          <div class="cruzamento-linha">
            <span>Estacoes na area</span>
            <strong>{{ cruzamento.estacoes }}</strong>
          </div>

          <div class="cruzamento-linha">
            <span>Chuva 24h na area</span>
            <strong>
              {{ formatarMm(cruzamento.chuva_media) }} mm
              (max {{ formatarMm(cruzamento.chuva_maxima) }} mm)
            </strong>
          </div>
          <p class="cruzamento-nota">De {{ cruzamento.estacoes_com_leitura }} estacoes com leitura.</p>

          <!--
            Altimetria por AMOSTRAGEM das estacoes, e nao relevo do terreno.
            Com poucos pontos medidos numa area de milhares de km2, apresentar a
            faixa como a cota da area seria afirmar mais do que o dado sustenta.
          -->
          <div v-if="cruzamento.altimetria?.minima !== null" class="cruzamento-linha">
            <span>Altitude na area</span>
            <strong>
              {{ Math.round(cruzamento.altimetria.minima) }} a
              {{ Math.round(cruzamento.altimetria.maxima) }} m
            </strong>
          </div>
          <p v-if="cruzamento.altimetria?.minima !== null" class="cruzamento-nota">
            Amostrada em {{ cruzamento.altimetria.estacoes_com_cota }}
            {{ cruzamento.altimetria.estacoes_com_cota === 1 ? 'estacao' : 'estacoes' }}:
            e amostra, nao o relevo do terreno.
          </p>
        </div>
      </div>

      <div class="ficha-card">
        <h2 class="card-title">Ficha</h2>

        <div class="ficha-linha"><span>Feicoes</span><strong>{{ camada.feicoes }}</strong></div>
        <div class="ficha-linha"><span>Area total</span><strong>{{ formatarArea(camada.area_km2) }}</strong></div>
        <div class="ficha-linha"><span>Emitida em</span><strong>{{ formatarData(camada.emitido_em) }}</strong></div>
        <div class="ficha-linha">
          <span>Valida ate</span>
          <strong>{{ camada.valido_ate ? formatarData(camada.valido_ate) : 'sem prazo' }}</strong>
        </div>
        <div class="ficha-linha"><span>Arquivo</span><strong class="ficha-arquivo">{{ camada.arquivo_nome }}</strong></div>
        <div class="ficha-linha"><span>Enviada por</span><strong>{{ camada.enviado_por_nome ?? '-' }}</strong></div>
        <div class="ficha-linha"><span>Importada em</span><strong>{{ formatarData(camada.created_at) }}</strong></div>
        <div v-if="camada.revisado_por_nome" class="ficha-linha">
          <span>Revisada por</span><strong>{{ camada.revisado_por_nome }}</strong>
        </div>
        <div v-if="camada.arquivado_em" class="ficha-linha">
          <span>Arquivada por</span>
          <!--
            arquivado_por nulo com arquivado_em preenchido e a marca do
            arquivamento AUTOMATICO por validade vencida. Mostrar "-" ali
            faria parecer dado faltando, quando na verdade e a informacao.
          -->
          <strong>{{ camada.arquivado_por_nome ?? 'automatico (validade vencida)' }}</strong>
        </div>

        <h3 class="ficha-titulo">Municipios atingidos ({{ municipios.length }})</h3>
        <p v-if="municipios.length === 0" class="ficha-vazio">
          Nenhum centroide municipal cai dentro desta area.
        </p>
        <ul v-else class="municipios-lista">
          <li v-for="m in municipios" :key="m.id">{{ m.nome }}<span>{{ m.uf }}</span></li>
        </ul>
      </div>
    </div>

    <div class="table-container">
      <h2 class="card-title">Feicoes</h2>

      <table class="dados-table">
        <thead>
          <tr>
            <th>Feicao</th>
            <th class="hidden sm:table-cell">Geometria</th>
            <th class="hidden md:table-cell">Vertices</th>
            <th>Area</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="feicao in feicoesDaPagina" :key="feicao.id">
            <td class="code-cell">{{ feicao.feicao_nome }}</td>
            <td class="hidden sm:table-cell sub-text">{{ feicao.tipo_geometria }}</td>
            <td class="hidden md:table-cell value-cell">{{ feicao.vertices }}</td>
            <td class="value-cell">{{ formatarArea(feicao.area_km2) }}</td>
          </tr>
          <tr v-if="feicoes.length === 0">
            <td colspan="4" class="empty-cell">Esta camada nao tem geometria.</td>
          </tr>
        </tbody>
      </table>

      <Pagination :pagination="paginacao" @page-change="irParaPagina" />
    </div>

    <EditarCamadaModal
      :show="emEdicao !== null"
      :camada="emEdicao"
      :dominios="dominios"
      @close="fecharEdicao"
    />

    <ConfirmDialog
      :is-open="confirmacao.aberto"
      v-bind="confirmacao.opcoes"
      @confirm="confirmar"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import EditarCamadaModal from './Partials/EditarCamadaModal.vue';
import { useAcoesDeCamada } from '@/Composables/useAcoesDeCamada';

const props = defineProps({
  camada: { type: Object, required: true },
  feicoes: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
  cruzamento: { type: Object, required: true },
  acoes: { type: Object, default: () => ({}) },
  dominios: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
  // Precisa ser DECLARADA: sem isto props.errors e undefined e nenhum erro de
  // conflito -- "foi decidida por outra pessoa" -- chega a tela.
  errors: { type: Object, default: () => ({}) },
});

const { acoesDe, emEdicao, fecharEdicao, confirmacao, confirmar, cancelar } = useAcoesDeCamada();

const erros = computed(() => props.errors ?? {});

const SITUACAO = {
  pendente: {
    titulo: 'Aguardando aprovacao da CEDEC',
    texto: 'A geometria abaixo NAO esta no mapa operacional enquanto a camada nao for aprovada.',
  },
  aprovada: {
    titulo: 'Publicada no mapa',
    texto: 'A area aparece no mapa operacional do estado.',
  },
  recusada: {
    titulo: 'Recusada',
    texto: 'A camada nao foi publicada. Corrija o apontamento e envie um arquivo novo.',
  },
  arquivada: {
    titulo: 'Arquivada',
    texto: 'A area foi retirada do mapa operacional. O historico esta mantido e ela pode ser reativada.',
  },
};

/*
 * As acoes do backend chegam em props.acoes (esta tela mostra UMA camada), mas
 * o composable as procura em `camada.acoes` -- o formato das listagens.
 * Reempacotar num computed evita um segundo caminho no composable e nao mexe na
 * prop, que e do pai.
 */
const camadaComAcoes = computed(() => ({ ...props.camada, acoes: props.acoes }));

const descricaoDoCabecalho = computed(() => [
  rotularDominio(props.camada.dominio),
  `nivel ${rotularNivel(props.camada.nivel)}`,
  props.camada.origem === 'municipal'
    ? `enviada por ${props.camada.municipio_nome}`
    : 'camada estadual',
].join(' - '));

const POR_PAGINA = 15;
const pagina = ref(1);

watch(() => props.feicoes, () => { pagina.value = 1; });

const paginacao = computed(() => ({
  current_page: pagina.value,
  per_page: POR_PAGINA,
  total: props.feicoes.length,
  last_page: Math.max(1, Math.ceil(props.feicoes.length / POR_PAGINA)),
}));

const feicoesDaPagina = computed(() => {
  const inicio = (pagina.value - 1) * POR_PAGINA;

  return props.feicoes.slice(inicio, inicio + POR_PAGINA);
});

function irParaPagina(numero) {
  pagina.value = Math.min(Math.max(1, numero), paginacao.value.last_page);
}

/*
 * O geojson vem do banco como TEXTO: o PDO do Postgres entrega jsonb como
 * string, e o L.geoJSON exige objeto -- passar a string desenha nada e nao
 * levanta erro nenhum.
 */
function decodificarGeojson(valor) {
  if (! valor) {
    return null;
  }

  if (typeof valor !== 'string') {
    return valor;
  }

  try {
    return JSON.parse(valor);
  } catch {
    return null;
  }
}

// Cor literal e nao var(--...): o Leaflet joga isto em atributo SVG, onde
// variavel CSS nao resolve e a area sairia preta.
function corDoDominio(dominio) {
  return props.dominios[dominio]?.cor ?? '#b45309';
}

const poligonosDoMapa = computed(() => props.feicoes.map((feicao) => ({
  id: feicao.id,
  geojson: decodificarGeojson(feicao.geojson),
  cor: corDoDominio(props.camada.dominio),
  rotulo: `${feicao.feicao_nome} - ${formatarArea(feicao.area_km2)}`,
})));

function rotularDominio(dominio) {
  return props.dominios[dominio]?.rotulo ?? dominio;
}

function rotularNivel(nivel) {
  return String(nivel ?? '').replace(/_/g, ' ');
}

function formatarArea(valor) {
  const n = Number(valor);

  return Number.isFinite(n) ? `${n.toLocaleString('pt-BR')} km2` : '-';
}

function formatarMm(valor) {
  const n = Number(valor);

  return Number.isFinite(n) ? n.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) : '-';
}

function formatarData(valor) {
  if (! valor) {
    return '-';
  }

  const d = new Date(valor);

  return Number.isNaN(d.getTime()) ? String(valor) : d.toLocaleDateString('pt-BR');
}
</script>

<style scoped>
/*
 * Regras BASE = tema claro. O escuro vem no bloco NAO-scoped no fim do arquivo,
 * qualificado pelo container: :global(.dark) dentro de scoped compila para
 * `.dark` pelado e pinta o <html> inteiro.
 */
.detalhe-container {
  --sup: #ffffff;
  --sup-2: #f3f4f6;
  --borda: #e5e7eb;
  --texto: #111827;
  --texto-fraco: #6b7280;

  padding: 16px;
  background-color: #f9fafb;
  color: var(--texto);
  min-height: 100%;
}

.voltar {
  font-size: 0.75rem;
  color: var(--texto-fraco);
  text-decoration: none;
}

.voltar:hover {
  text-decoration: underline;
}

.aviso {
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 0.85rem;
  margin-bottom: 12px;
  border: 1px solid var(--borda);
  background: var(--sup);
}

.aviso-sucesso {
  border-color: #15803d;
  color: #15803d;
}

.aviso-erro {
  border-color: #dc2626;
  color: #dc2626;
}

.situacao {
  display: flex;
  flex-direction: column;
  gap: 2px;
  border: 1px solid var(--borda);
  border-left-width: 4px;
  border-radius: 8px;
  background: var(--sup);
  padding: 10px 14px;
  margin-bottom: 12px;
  font-size: 0.8rem;
  color: var(--texto-fraco);
}

.situacao strong {
  color: var(--texto);
  font-size: 0.85rem;
}

.situacao.is-pendente { border-left-color: #c2410c; }
.situacao.is-aprovada { border-left-color: #15803d; }
.situacao.is-recusada { border-left-color: #dc2626; }
.situacao.is-arquivada { border-left-color: #6b7280; }

.situacao-motivo {
  margin-top: 4px;
  font-style: italic;
}

.conteudo-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

@media (min-width: 1024px) {
  .conteudo-grid {
    grid-template-columns: 1fr 320px;
  }
}

.map-wrapper {
  position: relative;
  border: 1px solid var(--borda);
  border-radius: 10px;
  overflow: hidden;
  background: var(--sup);
  min-height: 420px;
}

.mapa-area {
  height: 420px;
  width: 100%;
}

.map-overlay {
  position: absolute;
  z-index: 500;
  background: var(--sup);
  border: 1px solid var(--borda);
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.cruzamento {
  top: 10px;
  right: 10px;
  max-width: 260px;
}

.overlay-title {
  margin: 0 0 6px;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--texto-fraco);
}

.cruzamento-linha {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  padding: 2px 0;
}

.cruzamento-nota {
  margin: 0 0 6px;
  font-size: 0.66rem;
  color: var(--texto-fraco);
  line-height: 1.3;
}

.ficha-card {
  border: 1px solid var(--borda);
  border-radius: 10px;
  background: var(--sup);
  padding: 12px 14px;
}

.card-title {
  margin: 0 0 8px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--texto-fraco);
}

.ficha-linha {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  padding: 4px 0;
  font-size: 0.78rem;
  border-bottom: 1px solid var(--borda);
}

.ficha-linha span {
  color: var(--texto-fraco);
}

.ficha-arquivo {
  word-break: break-all;
  text-align: right;
}

.ficha-titulo {
  margin: 14px 0 6px;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--texto-fraco);
}

.ficha-vazio {
  margin: 0;
  font-size: 0.75rem;
  color: var(--texto-fraco);
}

.municipios-lista {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 220px;
  overflow-y: auto;
  font-size: 0.78rem;
}

.municipios-lista li {
  display: flex;
  justify-content: space-between;
  padding: 3px 0;
  border-bottom: 1px solid var(--borda);
}

.municipios-lista span {
  color: var(--texto-fraco);
}

.table-container {
  margin-top: 12px;
  border: 1px solid var(--borda);
  border-radius: 10px;
  background: var(--sup);
  padding: 12px 14px;
  /* Tabela larga rola dentro do proprio bloco: o corpo da pagina nunca rola na horizontal. */
  overflow-x: auto;
}

.dados-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.dados-table th {
  text-align: left;
  padding: 6px 8px;
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--texto-fraco);
  border-bottom: 1px solid var(--borda);
}

.dados-table td {
  padding: 6px 8px;
  border-bottom: 1px solid var(--borda);
}

.code-cell {
  font-weight: 600;
}

.value-cell {
  font-variant-numeric: tabular-nums;
}

.sub-text {
  color: var(--texto-fraco);
  font-size: 0.72rem;
}

.empty-cell {
  text-align: center;
  color: var(--texto-fraco);
  padding: 24px 8px;
}
</style>

<style>
/*
 * Tema escuro FORA do scoped e qualificado por .detalhe-container.
 *
 * :global(.dark) dentro de <style scoped> compila para `.dark` pelado, sem o
 * hash do componente: as regras vazariam para o sistema inteiro e pintariam
 * telas de outros modulos.
 */
.dark .detalhe-container {
  --sup: #1e293b;
  --sup-2: #0f172a;
  --borda: rgba(51, 65, 85, 0.6);
  --texto: #e2e8f0;
  --texto-fraco: #94a3b8;

  background-color: #0f172a;
}

.dark .detalhe-container .aviso-sucesso {
  color: #4ade80;
  border-color: #4ade80;
}

.dark .detalhe-container .aviso-erro {
  color: #f87171;
  border-color: #f87171;
}
</style>
