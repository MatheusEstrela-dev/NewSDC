<template>
  <div class="geoespacial-container">
    <!--
      PageHeader do projeto, e nao header proprio. O modulo inteiro reimplementava
      cabecalho em CSS local, que e a divergencia que ja custou caro no RAT
      (RatPageHeader e RatCollapsibleSection sao copias, e a segunda renderiza sem
      estilo fora do modulo).

      icon-image devolve null hoje: nao existe chave 'geoespacial' em
      MODULE_ICONS, e aquele mapa e mantido pelo dono do projeto ("confirmado
      pelo usuario", diz o arquivo). O PageHeader entao cai no :icon, e no dia
      em que a arte for cadastrada ela aparece sozinha, sem tocar nesta tela.
    -->
    <PageHeader
      title="Camadas de Risco"
      :description="descricaoDoCabecalho"
      :icon="MapIcon"
      :icon-image="moduleIcon('geoespacial')"
      variant="gradient"
    >
      <template #actions>
        <Link :href="route('geoespacial.enviar')" class="atalho-link">Enviar camada de risco</Link>
      </template>
    </PageHeader>

    <!--
      Upload primeiro, e nao no fim da pagina: quem abre esta tela quase sempre
      chega com um arquivo do CEMADEN na mao. A lista e o mapa sao a consulta,
      que vem depois do ato.
    -->
    <!--
      O formulario saiu daqui. Esta tela e de CONSULTA: mapa do estado,
      lista de camadas e cruzamento. Quem envia e a COMPDEC, que quer
      tratar do proprio municipio, e a tela de envio explica o processo --
      ver Geoespacial/Enviar.vue.
    -->
    <div class="atalho-envio">
      <span class="atalho-nota">Envio de KML ou KMZ, com o processo explicado passo a passo.</span>

      <!--
        Arquivada fica fora por padrao: ela nao tem geometria no Gold, entao
        seleciona-la mostraria um mapa vazio sem explicar por que. Quem precisa
        achar uma para reativar liga o filtro.
      -->
      <label class="atalho-filtro">
        <input type="checkbox" :checked="comArquivadas" @change="alternarArquivadas($event)">
        Mostrar arquivadas
      </label>
    </div>

    <div class="conteudo-grid">
      <div class="lista-card">
        <h2 class="card-title">Camadas</h2>

        <button
          type="button"
          class="camada-item"
          :class="{ 'is-ativo': camadaSelecionada === null }"
          @click="selecionarCamada(null)"
        >
          <span class="camada-nome">Todas as camadas</span>
          <span class="camada-meta">sem cruzamento</span>
        </button>

        <!--
          <div> com botao interno, e nao <button> na linha inteira: o
          ActionButton abre um dropdown, e botao dentro de botao e HTML invalido
          -- o clique na acao borbulharia e trocaria a camada selecionada junto.
        -->
        <div
          v-for="camada in camadas"
          :key="camada.id"
          class="camada-item"
          :class="{ 'is-ativo': camadaSelecionada === camada.id }"
        >
          <button type="button" class="camada-alvo" @click="selecionarCamada(camada.id)">
          <span class="camada-nome">
            <span class="camada-cor" :style="{ backgroundColor: corDoDominio(camada.dominio) }"></span>
            {{ camada.nome }}
          </span>
          <span class="camada-meta">
            {{ rotularDominio(camada.dominio) }}
            &middot; {{ rotularNivel(camada.nivel) }}
            &middot; {{ formatarData(camada.emitido_em) }}
            <template v-if="camada.origem === 'municipal'">&middot; municipal</template>
          </span>
          <!--
            Status so aparece quando NAO e a aprovada estadual: marcar as
            aprovadas com "aprovada" seria ruido em toda a lista.
          -->
          <span v-if="camada.status !== 'aprovada'" class="camada-status" :class="`is-${camada.status}`">
            {{ ROTULO_STATUS[camada.status] ?? camada.status }}
          </span>
          <!--
            O motivo da recusa fica na tela do municipio, e nao so na
            notificacao: sem ele o remetente reenvia o mesmo arquivo, o dedup
            recusa por hash igual, e ninguem entende o que aconteceu.
          -->
          <span v-if="camada.motivo_recusa" class="camada-motivo">{{ camada.motivo_recusa }}</span>
          <span v-if="camada.motivo_arquivamento" class="camada-motivo">{{ camada.motivo_arquivamento }}</span>
          </button>

          <div class="camada-acoes">
            <ActionButton
              module="geoespacial"
              resource="camadas"
              size="sm"
              :actions="acoesDe(camada)"
            />
          </div>
        </div>

        <p v-if="camadas.length === 0" class="lista-vazia">
          Nenhuma camada importada ate agora.
        </p>
      </div>

      <div class="map-wrapper">
        <MapaLeaflet :poligonos="poligonosDoMapa" :bbox="bbox" class="mapa-area" />

        <div v-if="cruzamento" class="map-overlay cruzamento">
          <h3 class="overlay-title">Cruzamento</h3>

          <div class="cruzamento-linha">
            <span>Municipios atingidos</span>
            <strong>{{ cruzamento.municipios }}</strong>
          </div>
          <!--
            A ressalva e obrigatoria: gold.geo_camada_municipios cruza por
            CENTROIDE, porque a tabela municipios guarda latitude/longitude e
            nao geometria de territorio. Municipio cujo centroide cai fora mas
            cujo territorio e atingido NAO entra na conta. Apresentar o numero
            como exato seria mentira operacional num sistema de Defesa Civil.
          -->
          <p class="cruzamento-nota">
            Contagem por centroide do municipio: e piso, nao total.
          </p>

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
          <p class="cruzamento-nota">
            De {{ cruzamento.estacoes_com_leitura }} estacoes com leitura.
          </p>

          <!--
            Altimetria por AMOSTRAGEM das estacoes, e nao relevo do terreno.
            Dizer isso na tela importa: com 5 pontos medidos numa area de
            milhares de km2, apresentar "719 a 1200 m" como a cota da area
            seria afirmar mais do que o dado sustenta. O relevo de verdade
            depende do MDE por raster.
          -->
          <div v-if="cruzamento.altimetria?.minima !== null" class="cruzamento-linha">
            <span>Altitude na area</span>
            <strong>
              {{ Math.round(cruzamento.altimetria.minima) }} a
              {{ Math.round(cruzamento.altimetria.maxima) }} m
            </strong>
          </div>
          <p v-if="cruzamento.altimetria?.minima !== null" class="cruzamento-nota">
            Media {{ Math.round(cruzamento.altimetria.media) }} m, amostrada em
            {{ cruzamento.altimetria.estacoes_com_cota }}
            {{ cruzamento.altimetria.estacoes_com_cota === 1 ? 'estacao' : 'estacoes' }}
            com cota: e amostra, nao o relevo do terreno.
          </p>
          <p v-else class="cruzamento-nota">
            Sem altitude: nenhuma estacao com cota dentro da area. So a rede do
            INMET publica cota.
          </p>
        </div>

        <div class="map-overlay legend-overlay">
          <h4 class="legend-title">Dominios</h4>
          <div v-for="(config, chave) in dominios" :key="chave" class="legend-row">
            <span class="legend-dot" :style="{ backgroundColor: config.cor }"></span>
            <span>{{ config.rotulo }}</span>
          </div>
          <p v-if="feicoes.length === 0" class="legend-footer">
            Nenhuma area desenhada na selecao atual.
          </p>
        </div>
      </div>
    </div>

    <div class="table-container">
      <table class="dados-table">
        <thead>
          <tr>
            <th>Camada</th>
            <th class="hidden md:table-cell">Feicao</th>
            <th class="hidden sm:table-cell">Dominio</th>
            <th>Nivel</th>
            <th>Area</th>
            <th class="hidden sm:table-cell">Emissao</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="feicao in feicoesDaPagina" :key="feicao.id">
            <td class="code-cell">
              {{ feicao.camada_nome }}
              <div class="municipio-name md:hidden">{{ feicao.feicao_nome }}</div>
            </td>
            <td class="hidden md:table-cell">
              {{ feicao.feicao_nome }}
              <span class="sub-text">{{ feicao.tipo_geometria }}</span>
            </td>
            <td class="hidden sm:table-cell">
              <span
                class="status-badge"
                :style="{ borderColor: corDoDominio(feicao.dominio), color: corDoDominio(feicao.dominio) }"
              >
                {{ rotularDominio(feicao.dominio) }}
              </span>
            </td>
            <td>{{ rotularNivel(feicao.nivel) }}</td>
            <td class="value-cell">{{ formatarArea(feicao.area_km2) }}</td>
            <td class="time-cell hidden sm:table-cell">{{ formatarData(feicao.emitido_em) }}</td>
          </tr>
          <tr v-if="feicoes.length === 0">
            <td colspan="6" class="empty-cell">Nenhuma area na selecao atual</td>
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

import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import EditarCamadaModal from './Partials/EditarCamadaModal.vue';
import { useAcoesDeCamada } from '@/Composables/useAcoesDeCamada';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  camadas: { type: Array, default: () => [] },
  feicoes: { type: Array, default: () => [] },
  // null quando nenhuma camada esta selecionada: o cruzamento so faz sentido
  // para UMA camada, entao "todas" nao tem painel.
  cruzamento: { type: Object, default: null },
  camadaSelecionada: { type: Number, default: null },
  dominios: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
  comArquivadas: { type: Boolean, default: false },
});

const { acoesDe, emEdicao, fecharEdicao, confirmacao, confirmar, cancelar } = useAcoesDeCamada();

const ROTULO_STATUS = {
  pendente: 'aguardando aprovacao',
  recusada: 'recusada',
  arquivada: 'arquivada',
};

/*
 * O filtro vai na URL, e nao em estado local.
 *
 * Arquivar recarrega a pagina pelo Inertia; com o filtro so na memoria, a
 * camada que a pessoa acabou de arquivar sumiria da lista no mesmo instante e
 * ela nao teria como conferir o resultado nem desfazer.
 */
function alternarArquivadas(evento) {
  router.get(route('geoespacial.index'), {
    ...(props.camadaSelecionada !== null ? { camada: props.camadaSelecionada } : {}),
    ...(evento.target.checked ? { arquivadas: 1 } : {}),
  }, { preserveScroll: true, preserveState: true });
}

// O aviso do Gold chega vazio, so dizendo que mudou; quem rebusca e o Inertia
// pelo controller. `dominios` e `bbox` ficam de fora do only: sao config e nao
// mudam com a importacao.
useAtualizacaoAoVivo({
  canal: 'medalhao.geoespacial',
  evento: '.GoldAtualizado',
  props: ['camadas', 'feicoes', 'cruzamento'],
});

const camadaAtual = computed(
  () => props.camadas.find((camada) => camada.id === props.camadaSelecionada) ?? null,
);

// A descricao do PageHeader e uma string so: o subtitulo antigo tinha um <span>
// condicional dentro, que a prop `description` nao renderiza como markup.
const descricaoDoCabecalho = computed(() => {
  const base = 'Areas de alerta importadas de KML/KMZ, cruzadas com os municipios'
    + ' e as estacoes que o sistema ja monitora';

  return camadaAtual.value ? `${base} - exibindo ${camadaAtual.value.nome}` : base;
});

/*
 * O geojson vem do gold como TEXTO, e nao como objeto: o PDO do Postgres
 * entrega jsonb como string, e o L.geoJSON exige objeto -- passar a string
 * desenha nada e nao levanta erro nenhum. Aceita objeto tambem porque o dia em
 * que o repositorio decodificar, esta tela nao precisa mudar.
 */
function decodificarGeojson(valor) {
  if (!valor) {
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

/*
 * Paginacao no cliente, e nao no servidor: o mapa precisa de TODAS as feicoes
 * de qualquer forma para desenhar, entao paginar no backend exigiria uma
 * segunda consulta para ganhar nada. Mesmo desenho das telas de Meteorologia e
 * Sismos, com o mesmo componente.
 */
const POR_PAGINA = 15;
const pagina = ref(1);

// Trocar de camada com a pagina 4 aberta deixaria a tabela vazia.
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

const poligonosDoMapa = computed(() => props.feicoes.map((feicao) => ({
  id: feicao.id,
  geojson: decodificarGeojson(feicao.geojson),
  cor: corDoDominio(feicao.dominio),
  rotulo: `${feicao.camada_nome} - ${formatarArea(feicao.area_km2)}`,
})));

/*
 * preserveState mantem o formulario preenchido enquanto o operador compara
 * camadas; only limita o rebusca ao que a selecao muda -- dominios e bbox sao
 * config e viriam identicos.
 */
function selecionarCamada(id) {
  router.get(
    route('geoespacial.index'),
    id === null ? {} : { camada: id },
    {
      only: ['feicoes', 'cruzamento', 'camadaSelecionada'],
      preserveState: true,
      preserveScroll: true,
    },
  );
}

function rotularDominio(dominio) {
  return props.dominios[dominio]?.rotulo ?? dominio ?? '-';
}

function rotularNivel(nivel) {
  return nivel ? String(nivel).replace(/_/g, ' ') : '-';
}

// O area_km2 vem do numeric do Postgres, que o PDO entrega como string: sem o
// Number() a soma vira concatenacao e o toFixed nem existe.
function formatarArea(valor) {
  const numero = Number(valor);

  return Number.isFinite(numero) ? `${numero.toFixed(2)} km2` : '-';
}

function formatarMm(valor) {
  const numero = Number(valor);

  return Number.isFinite(numero) ? numero.toFixed(2) : '0.00';
}

function formatarData(valor) {
  if (!valor) {
    return '-';
  }

  const data = new Date(valor);

  return Number.isNaN(data.getTime()) ? String(valor) : data.toLocaleDateString('pt-BR');
}
</script>

<style scoped>
/*
 * Um token por papel: as regras abaixo nunca repetem cor por tema. As variantes
 * escuras vivem no <style> NAO-scoped no fim do arquivo, porque dependem da
 * classe `dark` que o useTheme poe no <html>, fora deste componente.
 */
.geoespacial-container {
  --sup: #ffffff;
  --sup-2: #f1f5f9;
  --borda: #e2e8f0;
  --texto: #1e293b;
  --texto-fraco: #64748b;
  --overlay: rgba(255, 255, 255, 0.94);
  --mapa-fallback: #e2e8f0;

  padding: 1.5rem;
  color: var(--texto);
  box-sizing: border-box;
  width: 100%;
  max-width: 100%;
}

.card-title {
  font-size: 0.9375rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

/* Formulario de upload */
.upload-card {
  background: var(--sup);
  border: 1px solid var(--borda);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.upload-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
}

.campo {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.campo-largo {
  grid-column: span 2;
}

.campo-rotulo {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--texto-fraco);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.campo-input {
  background: var(--sup);
  border: 1px solid var(--borda);
  color: var(--texto);
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 0.8125rem;
  width: 100%;
  box-sizing: border-box;
}

.campo-input:focus {
  outline: none;
  border-color: #3b82f6;
}

/* O seletor nativo de arquivo nasce com padding proprio; o do campo dobraria. */
.campo-arquivo {
  padding: 6px;
}

.campo-erro {
  font-size: 0.6875rem;
  color: #dc2626;
}

.upload-acoes {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.75rem;
  flex-wrap: wrap;
}

.botao-primario {
  background: #3b82f6;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  padding: 8px 18px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
}

.botao-primario:disabled {
  opacity: 0.6;
  cursor: progress;
}

.aviso-sucesso {
  font-size: 0.75rem;
  color: #15803d;
}

/* Lista e mapa lado a lado: a lista e o seletor do que o mapa desenha. */
.conteudo-grid {
  display: grid;
  grid-template-columns: minmax(220px, 300px) 1fr;
  gap: 1rem;
  align-items: start;
}

.lista-card {
  background: var(--sup);
  border: 1px solid var(--borda);
  border-radius: 8px;
  padding: 1rem;
  max-height: 600px;
  overflow-y: auto;
  box-sizing: border-box;
}

/*
 * A linha e um <div> em faixa: o alvo de selecao ocupa a largura toda e as
 * acoes ficam no canto. O empilhamento vertical do conteudo desceu para
 * .camada-alvo, que e o botao de verdade.
 */
.camada-item {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  width: 100%;
  border: 1px solid transparent;
  border-radius: 6px;
  padding: 8px 10px;
  color: var(--texto);
  font-size: 0.8125rem;
}

.camada-alvo {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  flex: 1 1 auto;
  min-width: 0;
  text-align: left;
  background: transparent;
  border: 0;
  padding: 0;
  cursor: pointer;
  color: inherit;
  font-size: inherit;
}

/*
 * As acoes so aparecem no hover e no foco-dentro em telas com ponteiro. Seis
 * camadas com o botao sempre visivel viravam uma coluna de tres pontinhos que
 * competia com o nome. Em telas de toque nao ha hover, entao la elas ficam
 * sempre visiveis -- caso contrario seriam inalcancaveis.
 */
.camada-acoes {
  flex: 0 0 auto;
  opacity: 1;
}

@media (hover: hover) {
  .camada-acoes {
    opacity: 0;
    transition: opacity 120ms ease;
  }

  .camada-item:hover .camada-acoes,
  .camada-item:focus-within .camada-acoes {
    opacity: 1;
  }
}

.atalho-filtro {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: var(--texto-fraco);
  cursor: pointer;
}

.camada-item:hover {
  background: var(--sup-2);
}

.camada-item.is-ativo {
  background: var(--sup-2);
  border-color: #3b82f6;
}

.camada-nome {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-weight: 600;
}

.camada-cor {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 2px;
  flex-shrink: 0;
}

.camada-meta {
  font-size: 0.6875rem;
  color: var(--texto-fraco);
}

.lista-vazia {
  font-size: 0.8125rem;
  color: var(--texto-fraco);
  padding: 0.5rem 0;
}

/*
 * `isolation: isolate` cria contexto de empilhamento proprio, como nas telas de
 * Sismos e Meteorologia: sem ele os paines do Leaflet (z-index 200-1000) e os
 * overlays desta pagina competem no contexto RAIZ e passam por cima da sidebar,
 * que e z-index 50.
 */
.map-wrapper {
  position: relative;
  isolation: isolate;
  z-index: 0;
  height: 600px;
  width: 100%;
  border-radius: 0.5rem;
  overflow: hidden;
  box-sizing: border-box;
}

.mapa-area {
  height: 100%;
  width: 100%;
  background: var(--mapa-fallback); /* fallback enquanto os tiles nao chegam */
}

.map-overlay {
  position: absolute;
  z-index: 500;
  background: var(--overlay);
  color: var(--texto);
  border: 1px solid var(--borda);
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  min-width: 190px;
  max-width: 280px;
}

.cruzamento {
  top: 1rem;
  right: 1rem;
}

.legend-overlay {
  bottom: 1rem;
  right: 1rem;
}

.overlay-title,
.legend-title {
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.cruzamento-linha,
.legend-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.125rem 0;
}

.legend-row {
  justify-content: flex-start;
}

/* A ressalva do centroide: pequena, mas nunca escondida atras de tooltip. */
.cruzamento-nota {
  font-size: 0.6875rem;
  color: var(--texto-fraco);
  line-height: 1.35;
  margin: 0 0 0.5rem;
}

.legend-dot {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 2px;
  display: inline-block;
  flex-shrink: 0;
}

.legend-footer {
  font-size: 0.6875rem;
  color: var(--texto-fraco);
  margin-top: 0.375rem;
}

/* Tabela das feicoes */
.table-container {
  margin-top: 1rem;
  background: var(--sup);
  border: 1px solid var(--borda);
  border-radius: 8px;
  overflow: hidden;
  overflow-x: auto;
  width: 100%;
  box-sizing: border-box;
}

.dados-table {
  width: 100%;
  border-collapse: collapse;
}

.dados-table th {
  background: var(--sup-2);
  color: var(--texto-fraco);
  font-weight: 500;
  font-size: 12px;
  text-align: left;
  padding: 12px 16px;
  text-transform: uppercase;
  border-bottom: 1px solid var(--borda);
  white-space: nowrap;
}

.dados-table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--borda);
  color: var(--texto);
  font-size: 13px;
}

.dados-table tbody tr:last-child td {
  border-bottom: none;
}

.dados-table tbody tr:hover {
  background: var(--sup-2);
}

.code-cell {
  font-weight: 600;
}

.sub-text {
  font-size: 10px;
  color: var(--texto-fraco);
  font-weight: normal;
  margin-left: 4px;
}

.municipio-name {
  font-size: 11px;
  color: var(--texto-fraco);
  text-transform: uppercase;
}

.value-cell {
  font-weight: 600;
  white-space: nowrap;
}

.status-badge {
  border: 1px solid currentColor;
  border-radius: 4px;
  padding: 2px 8px;
  font-size: 11px;
  white-space: nowrap;
}

.time-cell,
.empty-cell {
  color: var(--texto-fraco);
  white-space: nowrap;
}

.empty-cell {
  text-align: center;
  padding: 1.5rem;
}

/*
 * ESTE BLOCO FICA NO FIM DO <style scoped> DE PROPOSITO: media query nao soma
 * especificidade, entao regra base declarada depois venceria a daqui e o layout
 * de telefone nao aconteceria, sem nenhum sintoma no CSS.
 */
@media (max-width: 900px) {
  /* Em coluna unica a lista vira indice acima do mapa, e nao barra lateral. */
  .conteudo-grid {
    grid-template-columns: 1fr;
  }

  .lista-card {
    max-height: 240px;
  }
}

@media (max-width: 767px) {
  .campo-largo {
    grid-column: span 1;
  }

  .map-wrapper {
    height: 60vh;
    min-height: 320px;
  }

  .map-overlay {
    padding: 0.625rem 0.75rem;
    min-width: 0;
    max-width: 70%;
  }

  /* A legenda desce para baixo do mapa em vez de cobrir a area que explica. */
  .legend-overlay {
    position: static;
    width: 100%;
    max-width: 100%;
    margin-top: 0.75rem;
  }
}

.camada-status {
  display: block;
  margin-top: 4px;
  font-size: 0.66rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.camada-status.is-pendente {
  color: #c2410c;
}

.camada-status.is-recusada {
  color: #dc2626;
}

/* Cinza, e nao vermelho: arquivada nao e erro, e camada que cumpriu o prazo. */
.camada-status.is-arquivada {
  color: #6b7280;
}

.camada-motivo {
  display: block;
  margin-top: 3px;
  font-size: 0.7rem;
  color: var(--texto-fraco);
  white-space: normal;
}

.atalho-envio {
  display: flex;
  align-items: baseline;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.atalho-link {
  padding: 8px 14px;
  border-radius: 6px;
  background: #1d4ed8;
  color: #ffffff;
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
}

.atalho-nota {
  font-size: 0.75rem;
  color: var(--texto-fraco);
}
</style>

<!--
  Bloco NAO-scoped de proposito, mesma razao do MapaInmet e do MapaSismos: a
  variante escura depende da classe `dark` que o useTheme poe no <html>, que e
  ancestral FORA deste componente. `:global(.dark) .x` dentro do <style scoped>
  NAO serve -- o compilador descarta tudo depois do :global() e emite apenas
  `.dark` pelado, o que pinta o proprio <html> em vez da pagina.

  Qualificar por .geoespacial-container mantem o alcance na pagina, sem scope.
-->
<style>
.dark .geoespacial-container {
  --sup: #1a1d21;
  --sup-2: #25292f;
  --borda: #374151;
  --texto: #e5e7eb;
  --texto-fraco: #9ca3af;
  --overlay: rgba(26, 29, 33, 0.92);
  --mapa-fallback: #1a1d21;
}

/*
 * Erro e sucesso sao cores literais, e nao tokens: significam estado e nao
 * papel de superficie. Os tons fechados do tema claro ficam ilegiveis sobre
 * fundo escuro, entao clareiam aqui.
 */
.dark .geoespacial-container .camada-status.is-pendente {
  color: #fb923c;
}

.dark .geoespacial-container .camada-status.is-recusada {
  color: #f87171;
}

.dark .geoespacial-container .camada-status.is-arquivada {
  color: #9ca3af;
}

.dark .geoespacial-container .campo-erro {
  color: #f87171;
}

.dark .geoespacial-container .aviso-sucesso {
  color: #4ade80;
}
</style>
