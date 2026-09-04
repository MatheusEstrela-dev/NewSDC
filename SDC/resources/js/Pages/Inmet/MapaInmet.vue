<template>

    <div class="inmet-container">
      <!-- Header -->
      <div class="header-section">
        <h1 class="page-title">
          Meteorologia
        </h1>
        <!--
          O input era decorativo: nao tinha v-model, entao digitar nele nao
          filtrava nada nem mandava nada para o mapa.
        -->
        <div class="search-bar">
          <input
            v-model="busca"
            type="text"
            placeholder="Buscar estacao ou municipio..."
            class="search-input"
            aria-label="Buscar estacao ou municipio"
            @keyup.enter="focarPrimeiroResultado"
          >
          <p v-if="busca && estacoesFiltradas.length === 0" class="search-aviso">
            Nenhuma estacao encontrada.
          </p>
        </div>
      </div>

      <!--
        Seletor de rede. As duas redes medem a mesma grandeza com as mesmas
        faixas; o que muda e a cadencia e a densidade. Por isso filtro na mesma
        pagina, e nao duas paginas.
      -->
      <div class="rede-filtro">
        <button
          v-for="opcao in opcoesDeRede"
          :key="opcao.valor"
          type="button"
          class="rede-chip"
          :class="{ 'is-ativo': redeSelecionada === opcao.valor }"
          @click="selecionarRede(opcao.valor)"
        >
          {{ opcao.rotulo }}
          <span class="rede-contagem">{{ opcao.total }}</span>
        </button>
      </div>

      <!--
        Camada de risco desenhada por cima das estacoes. Fica ao lado do filtro
        de rede porque as duas perguntas se respondem juntas: onde choveu e
        sobre qual area de alerta.
      -->
      <div class="rede-filtro">
        <select v-model="camadaGeoSelecionada" class="camada-select" @change="trocarCamadaGeo">
          <option :value="null">Sem camada de risco</option>
          <option v-for="camada in camadasGeo" :key="camada.id" :value="camada.id">
            {{ camada.nome }} ({{ camada.nivel }})
          </option>
        </select>
      </div>

      <!-- Mapa Container -->
      <div class="map-wrapper">
        <MapaLeaflet ref="mapaRef" :pontos="pontosDoMapa" :poligonos="poligonosGeo" :bbox="bbox" class="mapa-area" />

        <!-- Overlay: Estatísticas -->
        <div class="map-overlay stats-overlay">
          <h3 class="overlay-title">Estatísticas</h3>
          <div class="stat-row">
            <span>Média:</span>
            <strong>{{ resumo.media.toFixed(2) }} mm</strong>
          </div>
          <div class="stat-row">
            <span>Máxima:</span>
            <strong>{{ resumo.maxima.toFixed(2) }} mm</strong>
          </div>
          <div class="stat-row">
            <span>Com chuva:</span>
            <strong>{{ resumo.comChuva }}</strong>
          </div>
          <div class="stat-row">
            <span>Estações:</span>
            <strong>{{ resumo.total }}</strong>
          </div>
          <!--
            Estacao sem telemetria nao e estacao sem chuva. Sem este numero, a
            maioria dos 830 pontos do CEMADEN em cinza pareceria perda de dado.
          -->
          <div v-if="resumo.semLeitura > 0" class="stat-row">
            <span>Sem leitura:</span>
            <strong>{{ resumo.semLeitura }}</strong>
          </div>
          <!--
            Um horario por rede, e nao um so: e exatamente aqui que a diferenca
            de cadencia aparece. O INMET anda de hora em hora porque o HR_MEDICAO
            da API e horario; o CEMADEN anda a cada ~10 minutos.
          -->
          <!--
            O nome da fonte E o indicador. Antes as duas linhas eram vermelhas
            por estilo fixo, o que sugeria alarme permanente sem significar
            nada. Agora verde so quando o coletor respondeu dentro da janela.
          -->
          <div class="fonte-status" :class="inmetAtivo ? 'is-ao-vivo' : 'is-sem-resposta'">
            <span class="dot-indicator"></span>
            <strong class="fonte-nome">INMET</strong>
            <span class="fonte-estado">{{ inmetRotulo }} &middot; {{ inmetDesde }}</span>
          </div>
          <div class="fonte-dado">dado de {{ formatarDataHora(estatisticas.inmet?.ultima_atualizacao) }}</div>

          <div class="fonte-status" :class="cemadenAtivo ? 'is-ao-vivo' : 'is-sem-resposta'">
            <span class="dot-indicator"></span>
            <strong class="fonte-nome">CEMADEN</strong>
            <span class="fonte-estado">{{ cemadenRotulo }} &middot; {{ cemadenDesde }}</span>
          </div>
          <div class="fonte-dado">dado de {{ formatarDataHora(estatisticas.cemaden?.ultima_atualizacao) }}</div>
        </div>

        <!-- Overlay: Legenda -->
        <div class="map-overlay legend-overlay">
          <h4 class="legend-title">Níveis de Precipitação (24h)</h4>
          <div class="legend-grid">
            <div v-for="item in legenda" :key="item.classe" class="legend-item">
              <span class="color-box" :style="{ background: item.cor }"></span> {{ item.rotulo }}
            </div>
          </div>
          <p class="legend-footer">*Adaptado do sistema LHASA_RIO para MG</p>
        </div>
      </div>

      <!-- Tabela de Dados -->
      <div class="table-container">
        <table class="dados-table">
          <thead>
            <tr>
              <th class="hidden md:table-cell">Código</th>
              <th>Estação / Município</th>
              <th class="hidden sm:table-cell">Rede</th>
              <th>Chuva (mm)</th>
              <th>Nível</th>
              <th class="hidden sm:table-cell">Data/Hora</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="estacao in estacoesDaPagina" :key="estacao.id">
              <td class="code-cell hidden md:table-cell">{{ estacao.codigo_estacao }}<br><span class="sub-text">{{ estacao.tipo }}</span></td>
              <td>
                <div class="station-name">{{ estacao.nome_estacao }}</div>
                <div class="municipio-name">{{ estacao.municipio }}</div>
              </td>
              <td class="hidden sm:table-cell">
                <span class="rede-badge" :class="`rede-badge-${estacao.rede.toLowerCase()}`">{{ estacao.rede }}</span>
              </td>
              <td class="value-cell" :style="{ color: corDaClasse(estacao.classe_precipitacao) }">
                {{ formatarMm(estacao.precipitacao) }}
              </td>
              <td>
                <span class="status-badge" :style="{ borderColor: corDaClasse(estacao.classe_precipitacao), color: corDaClasse(estacao.classe_precipitacao) }">
                  {{ rotuloDaClasse(estacao.classe_precipitacao) }}
                </span>
              </td>
              <td class="time-cell hidden sm:table-cell">{{ formatarDataHora(estacao.medido_em) }}</td>
            </tr>
            <tr v-if="estacoesFiltradas.length === 0">
              <td colspan="6" class="empty-cell">Nenhuma estacao com leitura</td>
            </tr>
          </tbody>
        </table>

        <Pagination :pagination="paginacao" @page-change="irParaPagina" />
      </div>
    </div>

</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';
import { usePrecipitacao } from '@/Composables/usePrecipitacao';
import { useMonitorFonte } from '@/Composables/useMonitorFonte';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  // Lista ja unificada pelo controller: cada item traz 'rede' e o campo de
  // chuva sob o nome unico 'precipitacao'.
  estacoes: { type: Array, default: () => [] },
  // Uma entrada por rede: { inmet: {...}, cemaden: {...} }.
  estatisticas: { type: Object, default: () => ({}) },
  // Idem, mas com o instante da ultima consulta a fonte, mesmo sem novidade.
  verificado_em: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
  // Cabecalho das camadas de risco, so o que o seletor mostra.
  camadasGeo: { type: Array, default: () => [] },
  // Feicoes da camada escolhida. Chega vazia ate o operador escolher uma.
  feicoesGeo: { type: Array, default: () => [] },
});

/*
 * Um assinante por rede, porque cada pipeline avisa no seu proprio canal e as
 * duas alimentam esta mesma tela. As chamadas nao conflitam: cada uma mantem a
 * propria assinatura no closure.
 *
 * `bbox` fica de fora do only: e config, nao muda com a coleta.
 */
useAtualizacaoAoVivo({
  canal: 'medalhao.inmet',
  evento: '.GoldAtualizado',
  props: ['estacoes', 'estatisticas', 'verificado_em'],
});

useAtualizacaoAoVivo({
  canal: 'medalhao.cemaden',
  evento: '.GoldAtualizado',
  props: ['estacoes', 'estatisticas', 'verificado_em'],
});

/*
 * Terceiro canal, so para a LISTA de camadas.
 *
 * Sem ele, uma area de risco recem-importada so aparecia no seletor depois de
 * um F5 -- justamente o comportamento que o tempo real veio eliminar. E o
 * cenario nao e raro: quem sobe o KML costuma ir direto olhar a chuva sobre a
 * area.
 *
 * `feicoesGeo` fica de fora do only de proposito: elas dependem da camada que o
 * operador escolheu, e rebusca-las aqui trocaria a geometria em tela por conta
 * propria enquanto ele olha.
 */
useAtualizacaoAoVivo({
  canal: 'medalhao.geoespacial',
  evento: '.GoldAtualizado',
  props: ['camadasGeo'],
});

// Faixas, paletas e formatadores vivem no composable: as mesmas faixas
// alimentam os CASE das matviews gold.inmet_mapa e gold.cemaden_mapa.
const { legenda, corDaClasse, rotuloDaClasse, formatarMm, formatarDataHora } = usePrecipitacao();

/*
 * Tolerancia de 25 minutos: as duas redes sao coletadas a cada 10 min, entao
 * isso permite perder dois ciclos antes de acusar queda. Apertar mais faria a
 * etiqueta piscar vermelho a cada atraso de fila; afrouxar faria uma fonte
 * morta parecer viva por tempo demais.
 */
const TOLERANCIA_MIN = 25;

// Desestruturado para o template receber refs de topo, que o Vue desembrulha
// sozinho: acessar .value dentro do template funciona mas nao e idiomatico.
const {
  ativo: inmetAtivo, rotulo: inmetRotulo, desde: inmetDesde,
} = useMonitorFonte(() => props.verificado_em.inmet, TOLERANCIA_MIN);

const {
  ativo: cemadenAtivo, rotulo: cemadenRotulo, desde: cemadenDesde,
} = useMonitorFonte(() => props.verificado_em.cemaden, TOLERANCIA_MIN);

const redeSelecionada = ref('TODAS');

const busca = ref('');
const mapaRef = ref(null);

/**
 * Normaliza para comparar sem acento e sem caixa: o catalogo traz "CAETE" e
 * "CAETÉ", e o operador digita dos dois jeitos. Sem isto, "francisco sa" nao
 * acha "FRANCISCO SÁ".
 */
function normalizar(texto) {
  return String(texto ?? '')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .trim();
}

const estacoesFiltradas = computed(() => {
  const porRede = redeSelecionada.value === 'TODAS'
    ? props.estacoes
    : props.estacoes.filter((estacao) => estacao.rede === redeSelecionada.value);

  const termo = normalizar(busca.value);

  if (termo === '') {
    return porRede;
  }

  // Nome da estacao E municipio: a tabela mostra os dois juntos, e o operador
  // as vezes sabe a cidade e nao o nome do pluviometro.
  return porRede.filter((estacao) => normalizar(estacao.nome_estacao).includes(termo)
    || normalizar(estacao.municipio).includes(termo));
});

/**
 * Leva o mapa ate a primeira estacao que casa com a busca.
 *
 * Chamado no Enter e tambem quando a busca fica com um unico resultado -- ai
 * nao ha ambiguidade sobre qual estacao o operador quer ver.
 */
function focarPrimeiroResultado() {
  const alvo = estacoesFiltradas.value[0];

  if (!alvo) {
    return;
  }

  mapaRef.value?.focarPonto(alvo.id);
}

const opcoesDeRede = computed(() => {
  const porRede = (rede) => props.estacoes.filter((estacao) => estacao.rede === rede).length;

  return [
    { valor: 'TODAS', rotulo: 'Todas', total: props.estacoes.length },
    { valor: 'INMET', rotulo: 'INMET', total: porRede('INMET') },
    { valor: 'CEMADEN', rotulo: 'CEMADEN', total: porRede('CEMADEN') },
  ];
});

function selecionarRede(valor) {
  redeSelecionada.value = valor;
}

/*
 * Resumo derivado do que esta EM TELA, e nao das matviews de estatistica.
 *
 * As matviews agregam cada rede isoladamente, e nao existe uma terceira para o
 * conjunto filtrado. Derivar aqui garante que o numero mostrado corresponda ao
 * filtro ativo -- ler a matview com o filtro em CEMADEN mostraria a media das
 * duas redes. As matviews continuam servindo o 'ultima_atualizacao' por rede,
 * que e dado de origem e nao agregacao.
 */
const resumo = computed(() => {
  const lista = estacoesFiltradas.value;
  const comLeitura = lista
    .map((estacao) => Number(estacao.precipitacao))
    .filter((valor) => Number.isFinite(valor));

  const soma = comLeitura.reduce((acumulado, valor) => acumulado + valor, 0);

  return {
    total: lista.length,
    semLeitura: lista.length - comLeitura.length,
    comChuva: comLeitura.filter((valor) => valor > 0).length,
    media: comLeitura.length > 0 ? soma / comLeitura.length : 0,
    maxima: comLeitura.length > 0 ? Math.max(...comLeitura) : 0,
  };
});

/*
 * Paginacao no cliente, nao no servidor: o mapa precisa de TODAS as estacoes de
 * qualquer forma, entao paginar no backend exigiria uma segunda consulta.
 *
 * Com as duas redes sao 890 estacoes. Ainda cabe no cliente -- o controller
 * manda so os campos que a tela usa -- mas e o limite: se a rede dobrar, o mapa
 * deve passar a receber um agregado por municipio em vez de ponto por estacao.
 */
const POR_PAGINA = 10;
const pagina = ref(1);

// Trocar de rede ou buscar sem isto deixaria o operador numa pagina que nao
// existe mais no recorte novo, e a tabela apareceria vazia.
watch([redeSelecionada, busca], () => {
  pagina.value = 1;
});

const paginacao = computed(() => ({
  current_page: pagina.value,
  per_page: POR_PAGINA,
  total: estacoesFiltradas.value.length,
  last_page: Math.max(1, Math.ceil(estacoesFiltradas.value.length / POR_PAGINA)),
}));

const estacoesDaPagina = computed(() => {
  const inicio = (pagina.value - 1) * POR_PAGINA;

  return estacoesFiltradas.value.slice(inicio, inicio + POR_PAGINA);
});

function irParaPagina(numero) {
  pagina.value = Math.min(Math.max(1, numero), paginacao.value.last_page);
}

// A pagina traduz estacao -> ponto; a mecanica de Leaflet vive no componente.
// O popup vai estruturado: quem escapa e o componente, o que importa porque
// nome de estacao e municipio vem de API externa.
const pontosDoMapa = computed(() => estacoesFiltradas.value.map((estacao) => ({
  id: estacao.id,
  latitude: estacao.latitude,
  longitude: estacao.longitude,
  cor: corDaClasse(estacao.classe_precipitacao),
  // O CEMADEN tem 830 pontos contra 60 do INMET: raio menor evita que a mancha
  // do CEMADEN cubra o estado e esconda as estacoes do INMET embaixo.
  raio: estacao.rede === 'CEMADEN' ? 5 : 7,
  popup: {
    titulo: estacao.nome_estacao,
    linhas: [
      { rotulo: 'Rede', valor: estacao.rede },
      { rotulo: 'Municipio', valor: estacao.municipio },
      { rotulo: 'Chuva', valor: formatarMm(estacao.precipitacao) },
      { rotulo: 'Nivel', valor: rotuloDaClasse(estacao.classe_precipitacao) },
      { rotulo: 'Temperatura', valor: estacao.temperatura !== null ? `${estacao.temperatura} C` : '-' },
      // So o INMET publica cota; o CEMADEN vem null e a linha some, em vez de
      // mostrar um traco em 830 popups.
      ...(estacao.altitude !== null ? [{ rotulo: 'Altitude', valor: `${Number(estacao.altitude).toFixed(0)} m` }] : []),
      { rotulo: 'Medido em', valor: formatarDataHora(estacao.medido_em) },
    ],
  },
})));

const camadaGeoSelecionada = ref(null);

/*
 * only: ['feicoesGeo'] e o que evita rebuscar as 890 estacoes a cada troca de
 * camada. preserveState mantem a pagina da tabela e o filtro de rede, que o
 * operador nao mexeu.
 */
function trocarCamadaGeo() {
  router.get(
    route('inmet.index'),
    { camada_geo: camadaGeoSelecionada.value },
    { only: ['feicoesGeo'], preserveState: true, preserveScroll: true },
  );
}

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

// A area e recorte de risco, nao medicao: o rotulo diz de que camada ela veio e
// o tamanho, porque no mapa uma mancha sozinha nao se identifica.
//
// Cor literal e nao var(--...): o Leaflet joga isto em atributo SVG, onde
// variavel CSS nao resolve e a area sairia preta.
const poligonosGeo = computed(() => props.feicoesGeo.map((feicao) => ({
  id: feicao.id,
  geojson: decodificarGeojson(feicao.geojson),
  cor: '#b45309',
  rotulo: `${feicao.camada_nome} — ${feicao.area_km2} km2`,
})));
</script>

<style scoped>
/* Dark Mode Base */
/*
 * Regras BASE = tema claro. O escuro vem em :global(.dark), classe que o
 * useTheme poe no <html>. Antes esta pagina tinha `dark` fixo no proprio
 * container e cores escuras aqui, entao ignorava o tema do site.
 */
.inmet-container {
  /*
   * Um token por papel, em vez de repetir cada regra duas vezes. Trocar o tema
   * troca so este bloco.
   */
  --sup: #ffffff;
  --sup-2: #f1f5f9;
  --borda: #e2e8f0;
  --texto: #1e293b;
  --texto-fraco: #64748b;
  --overlay: rgba(255, 255, 255, 0.94);
  --mapa-fallback: #e2e8f0;

  background-color: #f8fafc;
  min-height: calc(100vh - 64px); /* Account for topbar height */
  color: var(--texto);
  font-family: 'Inter', sans-serif;
  padding: 20px;
  width: 100%; /* Ensure it doesn't overflow */
  max-width: 100%; /* Prevent any overflow */
  box-sizing: border-box; /* Include padding in width calculation */
  margin: 0; /* Remove any default margins */
  position: relative; /* Establish positioning context */
}

/* Header */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--texto);
}

.search-input {
  background: var(--sup);
  border: 1px solid var(--borda);
  color: var(--texto);
  padding: 8px 16px;
  border-radius: 6px;
  width: 300px;
  font-size: 14px;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
}

/* Fora do fluxo: em fluxo normal ele empurraria o seletor de rede para baixo a
   cada busca sem resultado, e a pagina saltaria enquanto o operador digita. */
.search-aviso {
  position: absolute;
  margin-top: 4px;
  font-size: 12px;
  color: #f59e0b;
}

.search-bar {
  position: relative;
}

/* Map Wrapper */
/*
 * `isolation: isolate` conserta o mapa aparecendo SOBRE a sidebar.
 *
 * O Leaflet posiciona os proprios paines em z-index 200-700 e os controles
 * (o +/- de zoom) em 800-1000, e os overlays deste arquivo usam 1000. Sem um
 * contexto de empilhamento aqui, todos esses valores competiam no contexto
 * RAIZ -- e a sidebar e z-index 50. Resultado: no telefone, abrir o menu com o
 * mapa na tela deixava zoom e "Estatisticas" flutuando por cima do drawer.
 *
 * `isolation: isolate` cria o contexto sem depender de position/z-index, e o
 * `z-index: 0` garante o mesmo em navegador que ignore `isolation`. Dentro
 * dele o 1000 do overlay continua valendo sobre os paines do mapa; fora, o
 * wrapper inteiro vale 0 e fica abaixo de qualquer chrome do app.
 *
 * Regra geral: TODO container de mapa, modal ou dropdown de biblioteca externa
 * precisa isolar -- a biblioteca nao conhece a escala de z-index do SDC.
 */
.map-wrapper {
  position: relative;
  isolation: isolate;
  z-index: 0;
  height: 600px;
  width: 100%; /* Ensure it doesn't overflow container */
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--borda);
  margin-bottom: 24px;
  box-sizing: border-box; /* Include border in width calculation */
}


.mapa-area {
  height: 100%;
  width: 100%;
  background: var(--mapa-fallback); /* Fallback */
}

/* Overlays */
.map-overlay {
  position: absolute;
  background: var(--overlay);
  border: 1px solid var(--borda);
  border-radius: 8px;
  padding: 16px;
  z-index: 1000;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}

.stats-overlay {
  top: 20px;
  right: 20px;
  width: 280px;
}

.legend-overlay {
  bottom: 20px;
  left: 20px;
  width: 300px;
}

.overlay-title, .legend-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
  color: var(--texto);
  display: flex;
  align-items: center;
  gap: 8px;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 13px;
  color: var(--texto-fraco);
}

.stat-row strong {
  color: var(--texto);
}

/*
 * Status por fonte. A regra .stat-note saiu: pintava as duas linhas de #ef4444
 * fixo, o que deixava a tela em alarme permanente sem que a cor significasse
 * nada. Agora a cor e estado, e o nome da API e o proprio indicador.
 */
.fonte-status {
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
}

.fonte-nome {
  font-weight: 700;
  letter-spacing: 0.03em;
}

.fonte-estado {
  margin-left: auto;
  opacity: 0.9;
  font-variant-numeric: tabular-nums;
}

.fonte-dado {
  margin-top: 2px;
  margin-left: 12px;
  font-size: 10px;
  color: var(--texto-fraco);
}

/* currentColor: o ponto herda a cor do estado, sem par de regras duplicado. */
.dot-indicator {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}

.fonte-status.is-ao-vivo {
  color: #15803d;
}

.fonte-status.is-sem-resposta {
  color: #dc2626;
}

/* Pulso so no verde: e o unico estado que precisa comunicar continuidade. */
.fonte-status.is-ao-vivo .dot-indicator {
  animation: pulso-ao-vivo 2s ease-in-out infinite;
}

@keyframes pulso-ao-vivo {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.45; }
}

/* Movimento continuo e gatilho vestibular; a cor sozinha ja informa o estado. */
@media (prefers-reduced-motion: reduce) {
  .fonte-status.is-ao-vivo .dot-indicator {
    animation: none;
  }
}

.legend-grid {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--texto-fraco);
}

.color-box {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.legend-footer {
  margin-top: 12px;
  font-size: 10px;
  color: var(--texto-fraco);
  font-style: italic;
}

/* Table */
.table-container {
  background: var(--sup);
  border-radius: 8px;
  border: 1px solid var(--borda);
  overflow: hidden;
  width: 100%; /* Ensure it doesn't overflow container */
  box-sizing: border-box; /* Include border in width calculation */
  overflow-x: auto; /* Allow horizontal scroll if needed */
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
  color: var(--texto);
}

.sub-text {
  font-size: 10px;
  color: var(--texto-fraco);
  font-weight: normal;
}

.station-name {
  font-weight: 500;
}

.municipio-name {
  font-size: 11px;
  color: var(--texto-fraco);
  text-transform: uppercase;
}

.value-cell {
  font-weight: 700;
}

.status-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  border: 1px solid currentColor;
  text-transform: uppercase;
}

.time-cell {
  color: var(--texto-fraco);
  font-size: 12px;
}

/* Base Responsive Container */
.inmet-container {
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
  box-sizing: border-box;
}

/* Responsive Adjustments */
.empty-cell {
  text-align: center;
  padding: 1.5rem;
  color: var(--texto-fraco);
}


@media (max-width: 768px) {
  .inmet-container {
    padding: 12px 8px;
    height: auto;
    min-height: calc(100dvh - 3rem - env(safe-area-inset-top, 0px));
    padding-bottom: 24px;
  }

  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .search-input {
    width: 100%;
    max-width: 100%;
    font-size: 16px; /* Prevent iOS auto-zoom on focus */
  }

  .map-wrapper {
    height: 55dvh; /* Dynamic height based on viewport */
    max-height: 450px;
    margin: 0 -8px 16px -8px;
    width: calc(100% + 16px);
    border-radius: 0;
    border-left: none;
    border-right: none;
  }

  /* Reposition Overlays for Mobile */
  .stats-overlay {
    top: auto;
    bottom: 10px;
    right: 10px;
    left: 10px;
    width: auto;
    transform: none;
    background: rgba(26, 29, 33, 0.98);
    /* Make it collapsible or compact */
    padding: 12px;
  }
  
  .stats-overlay .stat-row {
     display: inline-block;
     margin-right: 12px;
     margin-bottom: 4px;
  }

  .legend-overlay {
    position: static; /* Move out of map flow */
    width: 100%;
    margin-bottom: 20px;
    background: var(--sup);
    border: 1px solid var(--borda);
  }

  /* Table adjustments */
  .table-container {
    margin: 0 -12px;
    width: calc(100% + 24px);
    border-radius: 0;
    border-left: none;
    border-right: none;
  }
  
  .dados-table th, .dados-table td {
    padding: 8px 10px;
    font-size: 12px;
  }
  
  .station-name {
    font-size: 13px;
  }
}

/*
 * ESTE BLOCO FICA NO FIM DO ARQUIVO DE PROPOSITO.
 *
 * Media query NAO soma especificidade: `.legend-overlay` aqui dentro vale
 * 0,1,0, igual ao `.map-overlay { position: absolute }` das regras base. Com
 * o bloco no meio do arquivo, a regra base vinha DEPOIS e vencia -- a legenda
 * seguia sobreposta ao mapa no telefone, cobrindo os pontos que explica, sem
 * nenhum sintoma no CSS.
 */
@media (max-width: 767px) {
  .map-wrapper {
    height: 60vh;
    min-height: 320px;
    margin-bottom: 16px;
  }

  .map-overlay {
    padding: 10px 12px;
    font-size: 0.8125rem;
  }

  .stats-overlay {
    top: 8px;
    right: 8px;
    left: auto;
    width: auto;
    max-width: 60%;
  }

  /* A legenda vai para baixo do mapa: sobreposta, ela cobria o proprio dado
     que explica. */
  .legend-overlay {
    position: static;
    width: 100%;
    margin-top: 12px;
  }
}

/*
 * Seletor de rede e badge da tabela. Usam os mesmos tokens do container, entao
 * acompanham claro/escuro sem par de regras duplicado.
 */
.rede-filtro {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.rede-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border: 1px solid var(--borda);
  border-radius: 999px;
  background: var(--sup);
  color: var(--texto-fraco);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s, color 0.15s, border-color 0.15s;
}

.rede-chip:hover {
  color: var(--texto);
  border-color: var(--texto-fraco);
}

.rede-chip.is-ativo {
  background: var(--sup-2);
  border-color: #3b82f6;
  color: var(--texto);
}

.rede-contagem {
  padding: 1px 6px;
  border-radius: 999px;
  background: var(--sup-2);
  color: var(--texto-fraco);
  font-size: 0.7rem;
  font-variant-numeric: tabular-nums;
}

.rede-chip.is-ativo .rede-contagem {
  background: #3b82f6;
  color: #ffffff;
}

/*
 * Mesmos tokens do container, como o .rede-chip: sem eles o select herda o
 * branco do agente do navegador e nasce ilegivel no tema escuro.
 */
.camada-select {
  padding: 6px 12px;
  border: 1px solid var(--borda);
  border-radius: 999px;
  background: var(--sup);
  color: var(--texto);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  max-width: 100%;
}

.camada-select:hover {
  border-color: var(--texto-fraco);
}

/*
 * A lista aberta e desenhada pelo sistema operacional e nao herda a cor do
 * select, entao a option recebe o par de novo.
 */
.camada-select option {
  background: var(--sup);
  color: var(--texto);
}

.rede-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  border: 1px solid var(--borda);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  color: var(--texto-fraco);
}

/* Cor por rede para distinguir a origem num relance, sem depender da coluna. */
.rede-badge-inmet {
  border-color: #0e7490;
  color: #0e7490;
}

.rede-badge-cemaden {
  border-color: #7c3aed;
  color: #7c3aed;
}
</style>

<!--
  Bloco NAO-scoped de proposito.

  A variante escura depende da classe `dark` que o useTheme poe no <html>, ou
  seja, de um ancestral FORA deste componente. `:global(.dark) .inmet-container`
  dentro do <style scoped> nao serve: o compilador descarta tudo depois do
  :global() e emite apenas `.dark`, o que define os tokens no proprio <html> --
  onde a redefinicao local do container os sobrescreve, e o tema escuro nunca
  chega. Foi exatamente o defeito visto na tela.

  Qualificar por .inmet-container mantem o alcance na pagina, mesmo sem scope.
-->
<style>
.dark .inmet-container {
  --sup: #1a1d21;
  --sup-2: #25292f;
  --borda: #374151;
  --texto: #e5e7eb;
  --texto-fraco: #9ca3af;
  --overlay: rgba(26, 29, 33, 0.95);
  --mapa-fallback: #1a1d21;

  background-color: #111315;
}

/*
 * As cores dos badges de rede sao literais, nao tokens: distinguem origem e nao
 * papel de superficie. Sobre fundo escuro as versoes fechadas do tema claro
 * ficam ilegiveis, entao clareiam aqui.
 */
.dark .inmet-container .fonte-status.is-ao-vivo {
  color: #4ade80;
}

.dark .inmet-container .fonte-status.is-sem-resposta {
  color: #f87171;
}

.dark .inmet-container .rede-badge-inmet {
  border-color: #22d3ee;
  color: #22d3ee;
}

.dark .inmet-container .rede-badge-cemaden {
  border-color: #a78bfa;
  color: #a78bfa;
}
</style>
