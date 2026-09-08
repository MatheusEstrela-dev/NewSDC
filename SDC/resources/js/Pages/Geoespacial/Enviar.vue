<template>
  <div class="enviar-container">
    <PageHeader
      title="Enviar camada de risco"
      description="Envie o mapeamento de risco do seu municipio em KML ou KMZ. A CEDEC analisa e, aprovada, a area passa a aparecer no mapa estadual cruzada com as estacoes de chuva."
      :icon="UploadIcon"
      :icon-image="moduleIcon('geoespacial')"
      variant="gradient"
    />

    <!--
      Procedencia ANTES do formulario, e nao depois do erro. O municipio nao e
      campo do formulario: ele sai do vinculo do usuario com a COMPDEC. Dizer
      isso na entrada evita a pessoa preencher tudo para descobrir no fim que o
      cadastro dela nao permite enviar.
    -->
    <div v-if="procedencia.permitido" class="procedencia">
      <span class="procedencia-rotulo">Enviando em nome de</span>
      <strong class="procedencia-nome">{{ procedencia.municipio }}</strong>
      <span class="procedencia-orgao">{{ procedencia.orgao }}</span>
    </div>
    <div v-else class="bloqueio">
      <strong>Nao e possivel enviar com este usuario.</strong>
      <p>{{ procedencia.motivo }}</p>
    </div>

    <div v-if="podePublicarDireto" class="aviso-cedec">
      Seu usuario tem permissao de revisao, entao o que voce enviar e publicado
      <strong>direto</strong>, sem passar pela fila. Camadas enviadas por
      municipios continuam exigindo aprovacao.
    </div>

    <div class="colunas">
      <section class="cartao">
        <h2 class="cartao-titulo">Como funciona</h2>
        <ol class="passos">
          <li>
            <strong>Voce envia o arquivo.</strong>
            Ele e guardado exatamente como chegou, e a area entra como
            <em>aguardando aprovacao</em>. Nada aparece no mapa ainda.
          </li>
          <li>
            <strong>A CEDEC analisa.</strong>
            Confere se a geometria corresponde ao municipio e se o nivel esta
            coerente. Voce recebe notificacao da decisao.
          </li>
          <li>
            <strong>Aprovada, entra no mapa.</strong>
            A area passa a ser cruzada com os municipios e com as 890 estacoes
            de chuva do INMET e do CEMADEN. Recusada, voce ve o motivo aqui
            mesmo e pode corrigir e reenviar.
          </li>
        </ol>
      </section>

      <section class="cartao">
        <h2 class="cartao-titulo">Como gerar o arquivo</h2>

        <p class="cartao-nota">
          Se voce ja tem o KML/KMZ do mapeamento, pule esta parte.
        </p>

        <h3 class="subtitulo">Google Earth Pro (gratuito)</h3>
        <ol class="passos-curtos">
          <li>Menu <em>Adicionar &rarr; Poligono</em> e desenhe a area de risco.</li>
          <li>De um nome a cada poligono na aba <em>Descricao</em>. Sem nome, o sistema numera como Area 1, Area 2.</li>
          <li>Repita para cada area. Varias areas podem ir no mesmo arquivo.</li>
          <li>Clique com o botao direito na pasta &rarr; <em>Salvar lugar como</em> &rarr; formato <em>.kmz</em>.</li>
        </ol>

        <h3 class="subtitulo">QGIS</h3>
        <ol class="passos-curtos">
          <li>Com a camada de poligonos aberta, clique nela com o botao direito.</li>
          <li><em>Exportar &rarr; Salvar objetos como</em>, formato <em>KML</em>.</li>
          <li>
            Confirme o SRC como <strong>EPSG:4326 (WGS 84)</strong>. Em outro
            sistema de coordenadas a area cai no lugar errado do mapa.
          </li>
        </ol>

        <p class="cartao-nota">
          Aceita <strong>.kml</strong> e <strong>.kmz</strong>, ate
          {{ limiteMb }} MB. Poligonos, linhas e pontos funcionam.
        </p>
      </section>
    </div>

    <section v-if="procedencia.permitido" class="cartao">
      <h2 class="cartao-titulo">Enviar</h2>

      <div v-if="$page.props.flash?.sucesso" class="aviso-sucesso">
        {{ $page.props.flash.sucesso }}
      </div>

      <form class="formulario" @submit.prevent="enviar">
        <div class="campo campo-largo">
          <label for="arquivo">Arquivo (.kml ou .kmz)</label>
          <input id="arquivo" type="file" accept=".kml,.kmz" @input="formulario.arquivo = $event.target.files[0]">
          <p v-if="formulario.errors.arquivo" class="campo-erro">{{ formulario.errors.arquivo }}</p>
        </div>

        <div class="campo campo-largo">
          <label for="nome">Nome da camada</label>
          <input id="nome" v-model="formulario.nome" type="text" placeholder="Ex.: Setores de risco alto - Bairro Centro">
          <span class="campo-ajuda">Como a area sera identificada no mapa. Prefira algo que diga o que e, nao o numero do processo.</span>
          <p v-if="formulario.errors.nome" class="campo-erro">{{ formulario.errors.nome }}</p>
        </div>

        <div class="campo">
          <label for="dominio">Dominio</label>
          <select id="dominio" v-model="formulario.dominio">
            <option v-for="(cfg, chave) in dominios" :key="chave" :value="chave">{{ cfg.rotulo }}</option>
          </select>
          <span class="campo-ajuda">Geologico para deslizamento e erosao; hidrologico para inundacao e enxurrada.</span>
          <p v-if="formulario.errors.dominio" class="campo-erro">{{ formulario.errors.dominio }}</p>
        </div>

        <div class="campo">
          <label for="nivel">Nivel</label>
          <select id="nivel" v-model="formulario.nivel">
            <option v-for="n in niveis" :key="n" :value="n">{{ n }}</option>
          </select>
          <span class="campo-ajuda">Grau de risco da area, no criterio do proprio municipio.</span>
          <p v-if="formulario.errors.nivel" class="campo-erro">{{ formulario.errors.nivel }}</p>
        </div>

        <div class="campo">
          <label for="emitido">Emitida em</label>
          <input id="emitido" v-model="formulario.emitido_em" type="date">
          <!--
            O KML nao carrega data de emissao nem validade: nem no arquivo de
            alerta que motivou o modulo. So o operador sabe. Preencher com a
            data de hoje quando o mapeamento e de fevereiro faz o historico
            mentir depois.
          -->
          <span class="campo-ajuda">Data do mapeamento, nao a de hoje. O arquivo nao carrega essa informacao.</span>
          <p v-if="formulario.errors.emitido_em" class="campo-erro">{{ formulario.errors.emitido_em }}</p>
        </div>

        <div class="campo">
          <label for="valido">Valido ate (opcional)</label>
          <input id="valido" v-model="formulario.valido_ate" type="date">
          <span class="campo-ajuda">Deixe vazio se o mapeamento nao tem prazo.</span>
          <p v-if="formulario.errors.valido_ate" class="campo-erro">{{ formulario.errors.valido_ate }}</p>
        </div>

        <div class="campo campo-largo acoes">
          <button type="submit" class="botao" :disabled="formulario.processing || !formulario.arquivo">
            {{ formulario.processing ? 'Enviando...' : 'Enviar para analise' }}
          </button>
          <span class="campo-ajuda">
            O mesmo arquivo nao entra duas vezes: o sistema compara o conteudo,
            nao o nome. Para trazer outra area, envie um KML diferente.
          </span>
        </div>
      </form>
    </section>

    <section class="cartao">
      <h2 class="cartao-titulo">Meus envios</h2>

      <p v-if="minhasCamadas.length === 0" class="cartao-nota">
        Nenhum envio ainda.
      </p>

      <table v-else class="tabela">
        <thead>
          <tr>
            <th>Camada</th>
            <th class="hidden sm:table-cell">Dominio</th>
            <th>Situacao</th>
            <th class="hidden sm:table-cell">Enviada em</th>
            <th class="coluna-acoes">Opcoes</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="camada in minhasCamadas" :key="camada.id">
            <td>
              <strong>{{ camada.nome }}</strong>
              <span class="sub-text">{{ camada.arquivo_nome }}</span>
            </td>
            <td class="hidden sm:table-cell">
              {{ dominios[camada.dominio]?.rotulo ?? camada.dominio }}
              <span class="sub-text">{{ camada.nivel }}</span>
            </td>
            <td>
              <span class="situacao" :class="`is-${camada.status}`">{{ rotularStatus(camada.status) }}</span>
              <!--
                O motivo fica AQUI, e nao so na notificacao: sem ele o remetente
                reenvia o mesmo arquivo, o dedup recusa por conteudo igual, e
                ninguem entende o que aconteceu.
              -->
              <span v-if="camada.motivo_recusa" class="motivo">{{ camada.motivo_recusa }}</span>
              <span v-if="camada.motivo_arquivamento" class="motivo">{{ camada.motivo_arquivamento }}</span>
            </td>
            <td class="hidden sm:table-cell">{{ formatarData(camada.created_at) }}</td>
            <td class="coluna-acoes">
              <ActionButton
                module="geoespacial"
                resource="camadas"
                size="sm"
                :actions="acoesDe(camada)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </section>

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

import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import UploadIcon from '@/Components/Icons/UploadIcon.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import EditarCamadaModal from './Partials/EditarCamadaModal.vue';
import { useAcoesDeCamada } from '@/Composables/useAcoesDeCamada';

const { acoesDe, emEdicao, fecharEdicao, confirmacao, confirmar, cancelar } = useAcoesDeCamada();

const props = defineProps({
  procedencia: { type: Object, required: true },
  podePublicarDireto: { type: Boolean, default: false },
  minhasCamadas: { type: Array, default: () => [] },
  dominios: { type: Object, default: () => ({}) },
  limiteMb: { type: Number, default: 20 },
});

const niveis = computed(() => {
  const primeiro = Object.values(props.dominios)[0];

  return primeiro?.niveis ?? ['baixo', 'moderado', 'alto', 'muito_alto'];
});

const formulario = useForm({
  arquivo: null,
  nome: '',
  dominio: Object.keys(props.dominios)[0] ?? 'geologico',
  nivel: 'moderado',
  emitido_em: '',
  valido_ate: '',
});

function enviar() {
  // forceFormData obrigatorio: sem isto o Inertia serializa como JSON e o
  // arquivo nao sobe, sem erro nenhum na tela.
  formulario.post(route('geoespacial.upload'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      // So o arquivo e o nome: dominio, nivel e datas normalmente repetem entre
      // envios do mesmo municipio, e limpar tudo obrigaria a repreencher.
      formulario.arquivo = null;
      formulario.nome = '';
    },
  });
}

function rotularStatus(status) {
  return {
    pendente: 'Aguardando aprovacao',
    aprovada: 'Aprovada, no mapa',
    recusada: 'Recusada',
    arquivada: 'Arquivada, fora do mapa',
  }[status] ?? status;
}

function formatarData(valor) {
  if (!valor) {
    return '-';
  }

  const d = new Date(valor);

  return Number.isNaN(d.getTime()) ? String(valor) : d.toLocaleDateString('pt-BR');
}
</script>

<style scoped>
/*
 * Regras BASE = tema claro. O escuro vem no bloco NAO-scoped no fim: dentro de
 * scoped, :global(.dark) compila para `.dark` pelado e pinta o <html> inteiro.
 */
.enviar-container {
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

.procedencia {
  display: flex;
  align-items: baseline;
  gap: 8px;
  flex-wrap: wrap;
  padding: 10px 14px;
  border: 1px solid var(--borda);
  border-left: 3px solid #15803d;
  border-radius: 8px;
  background: var(--sup);
  margin-bottom: 12px;
}

.procedencia-rotulo {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--texto-fraco);
}

.procedencia-nome {
  font-size: 1rem;
}

.procedencia-orgao {
  font-size: 0.78rem;
  color: var(--texto-fraco);
}

.bloqueio,
.aviso-cedec,
.aviso-sucesso {
  padding: 12px 14px;
  border-radius: 8px;
  border: 1px solid var(--borda);
  background: var(--sup);
  margin-bottom: 12px;
  font-size: 0.85rem;
}

.bloqueio {
  border-left: 3px solid #dc2626;
}

.bloqueio p {
  margin: 6px 0 0;
  color: var(--texto-fraco);
}

.aviso-cedec {
  border-left: 3px solid #c2410c;
}

.aviso-sucesso {
  border-left: 3px solid #15803d;
  color: #15803d;
}

.colunas {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 12px;
  margin-bottom: 12px;
}

.cartao {
  border: 1px solid var(--borda);
  border-radius: 10px;
  background: var(--sup);
  padding: 14px;
  margin-bottom: 12px;
}

.cartao-titulo {
  font-size: 0.95rem;
  font-weight: 700;
  margin: 0 0 10px;
}

.subtitulo {
  font-size: 0.8rem;
  font-weight: 700;
  margin: 12px 0 6px;
}

.cartao-nota {
  font-size: 0.78rem;
  color: var(--texto-fraco);
  margin: 8px 0 0;
}

.passos,
.passos-curtos {
  margin: 0;
  padding-left: 20px;
  font-size: 0.82rem;
}

.passos li {
  margin-bottom: 10px;
  line-height: 1.5;
}

.passos-curtos li {
  margin-bottom: 4px;
  line-height: 1.45;
  color: var(--texto-fraco);
}

.passos strong {
  display: block;
}

.formulario {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.campo {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.campo-largo {
  grid-column: 1 / -1;
}

.campo label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--texto-fraco);
}

.campo input,
.campo select {
  padding: 8px 10px;
  border: 1px solid var(--borda);
  border-radius: 6px;
  background: var(--sup);
  color: var(--texto);
  font-size: 0.85rem;
}

.campo-ajuda {
  font-size: 0.72rem;
  color: var(--texto-fraco);
  line-height: 1.4;
}

.campo-erro {
  margin: 0;
  font-size: 0.75rem;
  color: #dc2626;
}

.acoes {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.botao {
  padding: 9px 18px;
  border: none;
  border-radius: 6px;
  background: #1d4ed8;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.botao:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.tabela {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.tabela th {
  text-align: left;
  padding: 8px;
  border-bottom: 1px solid var(--borda);
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--texto-fraco);
}

.tabela td {
  padding: 10px 8px;
  border-bottom: 1px solid var(--borda);
  vertical-align: top;
}

.sub-text {
  display: block;
  font-size: 0.7rem;
  color: var(--texto-fraco);
}

.situacao {
  font-weight: 700;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.situacao.is-pendente {
  color: #c2410c;
}

.situacao.is-aprovada {
  color: #15803d;
}

.situacao.is-recusada {
  color: #dc2626;
}

/* Cinza, e nao vermelho: arquivada nao e erro, e camada que cumpriu o prazo. */
.situacao.is-arquivada {
  color: #6b7280;
}

/*
 * A coluna de acoes fica encostada a direita e nao encolhe: com a tabela
 * rolando na horizontal em tela estreita, as acoes precisam continuar
 * alcancaveis sem esticar as colunas de texto.
 */
.coluna-acoes {
  width: 1%;
  white-space: nowrap;
  text-align: right;
}

.motivo {
  display: block;
  margin-top: 4px;
  font-size: 0.72rem;
  color: var(--texto-fraco);
  white-space: normal;
  max-width: 46ch;
}
</style>

<!--
  Bloco NAO-scoped: a variante escura depende da classe `dark` no <html>, que e
  ancestral FORA deste componente.
-->
<style>
.dark .enviar-container {
  --sup: #1a1d21;
  --sup-2: #25292f;
  --borda: #374151;
  --texto: #e5e7eb;
  --texto-fraco: #9ca3af;

  background-color: #111315;
}

.dark .enviar-container .aviso-sucesso,
.dark .enviar-container .situacao.is-aprovada {
  color: #4ade80;
}

.dark .enviar-container .campo-erro,
.dark .enviar-container .situacao.is-recusada {
  color: #f87171;
}

.dark .enviar-container .situacao.is-pendente {
  color: #fb923c;
}

.dark .enviar-container .situacao.is-arquivada {
  color: #9ca3af;
}

.dark .enviar-container .campo select option {
  background: #1a1d21;
  color: #e5e7eb;
}
</style>
