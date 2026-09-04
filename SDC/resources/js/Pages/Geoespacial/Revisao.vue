<template>
  <div class="revisao-container">
    <div class="header-section">
      <h1 class="page-title">Revisao de camadas municipais</h1>
      <p class="page-subtitle">
        Camadas enviadas por COMPDECs, aguardando aprovacao. Enquanto pendentes,
        nao aparecem no mapa estadual.
      </p>
    </div>

    <div v-if="$page.props.flash?.sucesso" class="aviso-sucesso">
      {{ $page.props.flash.sucesso }}
    </div>
    <div v-if="erros.camada" class="aviso-erro">{{ erros.camada }}</div>

    <div v-if="pendentes.length === 0" class="vazio">
      Nenhuma camada aguardando revisao.
    </div>

    <div v-for="camada in pendentes" :key="camada.id" class="cartao">
      <div class="cartao-cabecalho">
        <div>
          <strong class="cartao-nome">{{ camada.nome }}</strong>
          <span class="cartao-badge">{{ dominios[camada.dominio]?.rotulo ?? camada.dominio }}</span>
          <span class="cartao-badge">{{ camada.nivel }}</span>
        </div>
        <span class="cartao-municipio">{{ camada.municipio_nome }}</span>
      </div>

      <div class="cartao-dados">
        <div><span>Feicoes</span><strong>{{ camada.feicoes }}</strong></div>
        <div><span>Area</span><strong>{{ formatarKm2(camada.area_km2) }}</strong></div>
        <div><span>Emitida em</span><strong>{{ formatarData(camada.emitido_em) }}</strong></div>
        <div><span>Enviada por</span><strong>{{ camada.enviado_por_nome ?? '-' }}</strong></div>
        <div><span>Arquivo</span><strong class="cartao-arquivo">{{ camada.arquivo_nome }}</strong></div>
      </div>

      <!--
        Plausibilidade territorial: e AVISO, nao bloqueio. Nao existe poligono
        municipal no banco (municipios tem so latitude/longitude), entao nao ha
        como afirmar que a area esta dentro do territorio. Municipio grande com
        area de risco na borda daria falso positivo, por isso a decisao fica com
        o revisor.
      -->
      <p v-if="camada.distancia_km !== null" class="cartao-aviso" :class="{ 'is-distante': distante(camada) }">
        Centroide da geometria a {{ camada.distancia_km }} km do centroide de
        {{ camada.municipio_nome }}.
        <template v-if="distante(camada)">
          Distancia alta: confira se a area pertence a este municipio.
        </template>
        <template v-else>
          Distancia compativel.
        </template>
        Nao ha malha municipal no sistema, entao isto e indicio e nao prova.
      </p>

      <div class="cartao-acoes">
        <button type="button" class="botao-aprovar" :disabled="ocupado === camada.id" @click="aprovar(camada)">
          Aprovar e publicar
        </button>

        <div class="recusa">
          <input
            v-model="motivos[camada.id]"
            type="text"
            class="recusa-motivo"
            placeholder="Motivo da recusa (minimo 10 caracteres)"
            :aria-label="`Motivo da recusa de ${camada.nome}`"
          >
          <button
            type="button"
            class="botao-recusar"
            :disabled="ocupado === camada.id || (motivos[camada.id] ?? '').trim().length < 10"
            @click="recusar(camada)"
          >
            Recusar
          </button>
        </div>
      </div>
      <p v-if="erros[`motivo-${camada.id}`]" class="campo-erro">{{ erros[`motivo-${camada.id}`] }}</p>
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
  pendentes: { type: Array, default: () => [] },
  dominios: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
});

/*
 * Acima disto o revisor recebe alerta. Nao e regra de negocio: e o raio em que
 * um municipio de MG cabe com folga, e serve para chamar atencao sem impedir
 * decisao. Municipio grande com area na borda pode passar disto legitimamente.
 */
const LIMITE_KM = 150;

const motivos = reactive({});
const ocupado = ref(null);

const erros = computed(() => props.errors ?? {});

function distante(camada) {
  return Number(camada.distancia_km) > LIMITE_KM;
}

function formatarKm2(valor) {
  const n = Number(valor);

  return Number.isFinite(n) ? `${n.toLocaleString('pt-BR')} km2` : '-';
}

function formatarData(valor) {
  if (!valor) {
    return '-';
  }

  const d = new Date(valor);

  return Number.isNaN(d.getTime()) ? String(valor) : d.toLocaleDateString('pt-BR');
}

function aprovar(camada) {
  ocupado.value = camada.id;

  router.post(route('geoespacial.aprovar', camada.id), {}, {
    preserveScroll: true,
    onFinish: () => { ocupado.value = null; },
  });
}

function recusar(camada) {
  const motivo = (motivos[camada.id] ?? '').trim();

  if (motivo.length < 10) {
    return;
  }

  ocupado.value = camada.id;

  router.post(route('geoespacial.recusar', camada.id), { motivo }, {
    preserveScroll: true,
    onFinish: () => { ocupado.value = null; },
  });
}
</script>

<style scoped>
/*
 * Regras BASE = tema claro. O escuro vem no bloco NAO-scoped no fim do arquivo,
 * qualificado pelo container: :global(.dark) dentro de scoped compila para
 * `.dark` pelado e pinta o <html> inteiro.
 */
.revisao-container {
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

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

.page-subtitle {
  margin: 4px 0 16px;
  font-size: 0.85rem;
  color: var(--texto-fraco);
  max-width: 70ch;
}

.aviso-sucesso,
.aviso-erro,
.vazio {
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

.cartao {
  border: 1px solid var(--borda);
  border-radius: 10px;
  background: var(--sup);
  padding: 14px;
  margin-bottom: 12px;
}

.cartao-cabecalho {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  flex-wrap: wrap;
}

.cartao-nome {
  font-size: 1rem;
}

.cartao-badge {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 8px;
  border: 1px solid var(--borda);
  border-radius: 999px;
  font-size: 0.68rem;
  color: var(--texto-fraco);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.cartao-municipio {
  font-weight: 600;
  font-size: 0.85rem;
}

.cartao-dados {
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
  margin: 12px 0;
  font-size: 0.8rem;
}

.cartao-dados div {
  display: flex;
  flex-direction: column;
}

.cartao-dados span {
  color: var(--texto-fraco);
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.cartao-arquivo {
  font-family: ui-monospace, monospace;
  font-size: 0.75rem;
  word-break: break-all;
}

.cartao-aviso {
  margin: 0 0 12px;
  padding: 8px 10px;
  border-radius: 6px;
  background: var(--sup-2);
  font-size: 0.75rem;
  color: var(--texto-fraco);
}

.cartao-aviso.is-distante {
  border: 1px solid #c2410c;
  color: #c2410c;
}

.cartao-acoes {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.recusa {
  display: flex;
  gap: 8px;
  flex: 1 1 320px;
}

.recusa-motivo {
  flex: 1;
  padding: 7px 10px;
  border: 1px solid var(--borda);
  border-radius: 6px;
  background: var(--sup);
  color: var(--texto);
  font-size: 0.8rem;
}

.botao-aprovar,
.botao-recusar {
  padding: 7px 14px;
  border-radius: 6px;
  border: 1px solid transparent;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.botao-aprovar {
  background: #15803d;
  color: #ffffff;
}

.botao-recusar {
  background: var(--sup);
  border-color: #dc2626;
  color: #dc2626;
}

.botao-aprovar:disabled,
.botao-recusar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.campo-erro {
  margin: 8px 0 0;
  font-size: 0.75rem;
  color: #dc2626;
}
</style>

<!--
  Bloco NAO-scoped: a variante escura depende da classe `dark` no <html>, que e
  ancestral FORA deste componente. Qualificar por .revisao-container mantem o
  alcance na pagina mesmo sem scope.
-->
<style>
.dark .revisao-container {
  --sup: #1a1d21;
  --sup-2: #25292f;
  --borda: #374151;
  --texto: #e5e7eb;
  --texto-fraco: #9ca3af;

  background-color: #111315;
}

.dark .revisao-container .aviso-sucesso {
  border-color: #4ade80;
  color: #4ade80;
}

.dark .revisao-container .aviso-erro,
.dark .revisao-container .campo-erro,
.dark .revisao-container .botao-recusar {
  border-color: #f87171;
  color: #f87171;
}

.dark .revisao-container .cartao-aviso.is-distante {
  border-color: #fb923c;
  color: #fb923c;
}

.dark .revisao-container .botao-aprovar {
  background: #16a34a;
}
</style>
