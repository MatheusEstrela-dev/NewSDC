<template>
  <div class="geoespacial-container">
    <div class="header-section">
      <h1 class="page-title">Camadas de Risco</h1>
      <p class="page-subtitle">
        Areas de alerta importadas de KML/KMZ, cruzadas com os municipios e as
        estacoes que o sistema ja monitora
        <span v-if="camadaAtual" class="update-note">
          &middot; exibindo {{ camadaAtual.nome }}
        </span>
      </p>
    </div>

    <!--
      Upload primeiro, e nao no fim da pagina: quem abre esta tela quase sempre
      chega com um arquivo do CEMADEN na mao. A lista e o mapa sao a consulta,
      que vem depois do ato.
    -->
    <form class="upload-card" enctype="multipart/form-data" @submit.prevent="enviar">
      <h2 class="card-title">Enviar camada</h2>

      <div class="upload-grid">
        <label class="campo campo-largo">
          <span class="campo-rotulo">Arquivo (.kml ou .kmz)</span>
          <!--
            :key forca um input novo a cada envio bem-sucedido. form.reset() nao
            limpa input de arquivo: o valor dele e do DOM e o navegador nao
            deixa reescrever, entao sem isto o nome do arquivo antigo ficaria na
            tela sugerindo que ele ainda seria enviado.
          -->
          <input
            :key="chaveArquivo"
            type="file"
            accept=".kml,.kmz"
            class="campo-input campo-arquivo"
            @change="selecionarArquivo"
          >
          <span v-if="form.errors.arquivo" class="campo-erro">{{ form.errors.arquivo }}</span>
        </label>

        <label class="campo">
          <span class="campo-rotulo">Dominio</span>
          <select v-model="form.dominio" class="campo-input" @change="trocarDominio">
            <option v-for="(config, chave) in dominios" :key="chave" :value="chave">
              {{ config.rotulo }}
            </option>
          </select>
          <span v-if="form.errors.dominio" class="campo-erro">{{ form.errors.dominio }}</span>
        </label>

        <label class="campo">
          <span class="campo-rotulo">Nivel</span>
          <select v-model="form.nivel" class="campo-input">
            <option v-for="nivel in niveisDoDominio" :key="nivel" :value="nivel">
              {{ rotularNivel(nivel) }}
            </option>
          </select>
          <span v-if="form.errors.nivel" class="campo-erro">{{ form.errors.nivel }}</span>
        </label>

        <label class="campo campo-largo">
          <span class="campo-rotulo">Nome da camada</span>
          <input
            v-model="form.nome"
            type="text"
            maxlength="255"
            placeholder="ALERTA MODERADO 28/02"
            class="campo-input"
          >
          <span v-if="form.errors.nome" class="campo-erro">{{ form.errors.nome }}</span>
        </label>

        <label class="campo">
          <!--
            Emissao, validade e nivel NAO estao dentro do KML: so no nome do
            arquivo, que e contrato que ninguem garante. Por isso o operador
            informa em vez de a tela adivinhar.
          -->
          <span class="campo-rotulo">Emitido em</span>
          <input v-model="form.emitido_em" type="date" class="campo-input">
          <span v-if="form.errors.emitido_em" class="campo-erro">{{ form.errors.emitido_em }}</span>
        </label>

        <label class="campo">
          <span class="campo-rotulo">Valido ate (opcional)</span>
          <input v-model="form.valido_ate" type="date" class="campo-input">
          <span v-if="form.errors.valido_ate" class="campo-erro">{{ form.errors.valido_ate }}</span>
        </label>
      </div>

      <div class="upload-acoes">
        <button type="submit" class="botao-primario" :disabled="form.processing">
          {{ form.processing ? 'Enviando...' : 'Enviar camada' }}
        </button>
        <!--
          O processamento e assincrono: o request so grava o cru e despacha o
          job. Dizer "enviada" e nao "importada" e o que evita o operador achar
          que a area ja deveria estar no mapa no mesmo instante.
        -->
        <span v-if="form.recentlySuccessful" class="aviso-sucesso">
          Camada enviada. O desenho aparece quando a fila terminar.
        </span>
      </div>
    </form>

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

        <button
          v-for="camada in camadas"
          :key="camada.id"
          type="button"
          class="camada-item"
          :class="{ 'is-ativo': camadaSelecionada === camada.id }"
          @click="selecionarCamada(camada.id)"
        >
          <span class="camada-nome">
            <span class="camada-cor" :style="{ backgroundColor: corDoDominio(camada.dominio) }"></span>
            {{ camada.nome }}
          </span>
          <span class="camada-meta">
            {{ rotularDominio(camada.dominio) }}
            &middot; {{ rotularNivel(camada.nivel) }}
            &middot; {{ formatarData(camada.emitido_em) }}
          </span>
        </button>

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
          <tr v-for="feicao in feicoes" :key="feicao.id">
            <td class="code-cell">
              {{ feicao.camada_nome }}
              <div class="municipio-name md:hidden">{{ feicao.feicao_nome ?? 'sem nome' }}</div>
            </td>
            <td class="hidden md:table-cell">
              {{ feicao.feicao_nome ?? 'sem nome' }}
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
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  camadas: { type: Array, default: () => [] },
  feicoes: { type: Array, default: () => [] },
  // null quando nenhuma camada esta selecionada: o cruzamento so faz sentido
  // para UMA camada, entao "todas" nao tem painel.
  cruzamento: { type: Object, default: null },
  camadaSelecionada: { type: Number, default: null },
  dominios: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
});

// O aviso do Gold chega vazio, so dizendo que mudou; quem rebusca e o Inertia
// pelo controller. `dominios` e `bbox` ficam de fora do only: sao config e nao
// mudam com a importacao.
useAtualizacaoAoVivo({
  canal: 'medalhao.geoespacial',
  evento: '.GoldAtualizado',
  props: ['camadas', 'feicoes', 'cruzamento'],
});

const chavesDeDominio = Object.keys(props.dominios);

const form = useForm({
  arquivo: null,
  dominio: chavesDeDominio[0] ?? 'geologico',
  nome: '',
  emitido_em: '',
  valido_ate: '',
  nivel: props.dominios[chavesDeDominio[0]]?.niveis?.[0] ?? '',
});

const chaveArquivo = ref(0);

const niveisDoDominio = computed(() => props.dominios[form.dominio]?.niveis ?? []);

const camadaAtual = computed(
  () => props.camadas.find((camada) => camada.id === props.camadaSelecionada) ?? null,
);

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

const poligonosDoMapa = computed(() => props.feicoes.map((feicao) => ({
  id: feicao.id,
  geojson: decodificarGeojson(feicao.geojson),
  cor: corDoDominio(feicao.dominio),
  rotulo: `${feicao.camada_nome} - ${formatarArea(feicao.area_km2)}`,
})));

function selecionarArquivo(evento) {
  form.arquivo = evento.target.files?.[0] ?? null;
}

// Nivel e vocabulario do dominio: trocar de dominio sem trocar o nivel deixaria
// no formulario um valor que o select nem mostra mais.
function trocarDominio() {
  form.nivel = niveisDoDominio.value[0] ?? '';
}

function enviar() {
  // forceFormData obrigatorio: sem ele o Inertia serializa como JSON e o
  // arquivo simplesmente nao sobe, sem erro nenhum na tela.
  form.post(route('geoespacial.upload'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset('arquivo', 'nome');
      chaveArquivo.value += 1;
    },
  });
}

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

.header-section {
  margin-bottom: 1rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
}

.page-subtitle {
  font-size: 0.875rem;
  opacity: 0.7;
}

.update-note {
  opacity: 0.6;
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

.camada-item {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  width: 100%;
  text-align: left;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 6px;
  padding: 8px 10px;
  cursor: pointer;
  color: var(--texto);
  font-size: 0.8125rem;
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
.dark .geoespacial-container .campo-erro {
  color: #f87171;
}

.dark .geoespacial-container .aviso-sucesso {
  color: #4ade80;
}
</style>
