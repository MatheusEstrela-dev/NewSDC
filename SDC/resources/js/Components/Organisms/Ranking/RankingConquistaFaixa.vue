<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="conquista"
      :class="{ 'conquista--saindo': saindo }"
      :style="tema.variaveis"
      role="dialog"
      aria-modal="true"
      aria-labelledby="conquista-faixa-titulo"
      :data-conquista-faixa="faixa"
      @click="encerrar"
    >
      <span class="conquista__clarao" aria-hidden="true" />

      <div class="conquista__palco" aria-hidden="true">
        <span class="conquista__raios" />
        <span class="conquista__onda" />
        <span class="conquista__onda conquista__onda--segunda" />
        <span
          v-for="estilhaco in estilhacos"
          :key="estilhaco.chave"
          class="conquista__estilhaco"
          :style="estilhaco.estilo"
        />
        <div class="conquista__trofeu">
          <RankingDiamanteIcone v-if="faixa === 'diamante'" :tamanho="220" />
          <RankingMedalha v-else :posicao="tema.metal" sem-numero class="conquista__medalha" />
        </div>
      </div>

      <div class="conquista__texto">
        <p class="conquista__sobretitulo text-xs font-bold uppercase tracking-[0.3em]">Novo patamar</p>
        <h2 id="conquista-faixa-titulo" class="mt-2 text-3xl font-extrabold text-white sm:text-4xl">Nível {{ tema.nome }}!</h2>
        <p class="conquista__mensagem mt-2 text-sm">{{ tema.mensagem }}</p>
        <button type="button" class="conquista__botao mt-6 rounded-xl px-6 py-2.5 text-sm font-bold text-slate-900 shadow-lg transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" @click.stop="encerrar">
          Continuar
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
/**
 * Celebracao de subida de faixa (Bronze, Prata, Ouro e Diamante), no estilo
 * das conquistas do Duolingo, em tres atos:
 *
 *   1. entrada  - o trofeu da faixa surge de frente e "carrega" o brilho;
 *   2. explosao - clarao, onda de choque e estilhacos na cor do metal;
 *   3. repouso  - o titulo sobe e o trofeu flutua, sem girar.
 *
 * Diamante usa o diamante plano; as demais faixas, a medalha do metal sem
 * numero (numero na medalha indica posicao, nao faixa).
 *
 * Sai sozinha depois de `duracaoMs`; clique, Escape ou "Continuar" encerram
 * antes. Quem decide QUANDO celebrar e a pagina; este componente so encena e
 * avisa `fechar`. Remontar (show false -> true) reinicia a sequencia.
 */
import { computed, onUnmounted, ref, watch } from 'vue';
import RankingDiamanteIcone from '@/Components/Atoms/Ranking/RankingDiamanteIcone.vue';
import RankingMedalha from '@/Components/Atoms/Ranking/RankingMedalha.vue';

/**
 * Tema por faixa: metal da medalha, textos e cores (rgb sem alfa) do fundo,
 * do brilho e dos estilhacos.
 */
const TEMAS = {
  diamante: {
    nome: 'Diamante', mensagem: 'Você alcançou a faixa mais alta do placar.', metal: null,
    fundo: '14 116 204', brilho: '125 211 252', claro: '224 247 255', texto: '#7dd3fc', botao: '#38bdf8',
    estilhacos: ['#e0f7ff', '#bae6fd', '#7dd3fc', '#ffffff', '#a5d8e6'],
  },
  ouro: {
    nome: 'Ouro', mensagem: 'Referência em entregas: você chegou ao Ouro.', metal: 1,
    fundo: '180 110 10', brilho: '252 211 77', claro: '255 247 209', texto: '#fcd34d', botao: '#fbbf24',
    estilhacos: ['#fff7d1', '#fde68a', '#fbbf24', '#ffffff', '#f59e0b'],
  },
  prata: {
    nome: 'Prata', mensagem: 'Participação consistente: você chegou à Prata.', metal: 2,
    fundo: '71 85 105', brilho: '203 213 225', claro: '248 250 252', texto: '#e2e8f0', botao: '#cbd5e1',
    estilhacos: ['#f8fafc', '#e2e8f0', '#cbd5e1', '#ffffff', '#94a3b8'],
  },
  bronze: {
    nome: 'Bronze', mensagem: 'Você entrou no placar. Cada entrega conta.', metal: 3,
    fundo: '140 70 30', brilho: '224 163 111', claro: '255 237 213', texto: '#fdba74', botao: '#e0a36f',
    estilhacos: ['#ffedd5', '#f5c9a0', '#e0a36f', '#ffffff', '#c2783f'],
  },
};

const props = defineProps({
  show: { type: Boolean, default: false },
  faixa: { type: String, default: 'diamante', validator: (valor) => ['bronze', 'prata', 'ouro', 'diamante'].includes(valor) },
  // Tempo em cena antes de sair sozinho.
  duracaoMs: { type: Number, default: 5200 },
});
const emit = defineEmits(['fechar']);

const SAIDA_MS = 600;

const tema = computed(() => {
  const escolhido = TEMAS[props.faixa] ?? TEMAS.diamante;
  return {
    ...escolhido,
    variaveis: {
      '--tema-fundo': escolhido.fundo,
      '--tema-brilho': escolhido.brilho,
      '--tema-claro': escolhido.claro,
      '--tema-texto': escolhido.texto,
      '--tema-botao': escolhido.botao,
    },
  };
});

// Estilhacos com angulo, distancia, tamanho e giro variados: explosao
// uniforme parece fogos de artificio, nao vidro partindo.
const estilhacos = computed(() => Array.from({ length: 22 }, (_, i) => {
  const angulo = (360 / 22) * i + ((i * 37) % 13);
  const distancia = 150 + ((i * 53) % 110);
  const tamanho = 8 + ((i * 29) % 12);
  const cores = tema.value.estilhacos;
  return {
    chave: i,
    estilo: {
      '--angulo': `${angulo}deg`,
      '--distancia': `${distancia}px`,
      '--giro-estilhaco': `${((i * 71) % 540) - 270}deg`,
      '--atraso': `${(i % 5) * 25}ms`,
      width: `${tamanho}px`,
      height: `${tamanho * 1.6}px`,
      background: cores[i % cores.length],
    },
  };
}));

const saindo = ref(false);
let temporizadores = [];

function limpar() {
  temporizadores.forEach(clearTimeout);
  temporizadores = [];
}

function agendar(fn, ms) {
  temporizadores.push(setTimeout(fn, ms));
}

function encerrar() {
  if (saindo.value) return;
  limpar();
  saindo.value = true;
  agendar(() => {
    saindo.value = false;
    emit('fechar');
  }, SAIDA_MS);
}

const aoTeclar = (evento) => { if (evento.key === 'Escape') encerrar(); };

watch(() => props.show, (aberto) => {
  limpar();
  if (aberto) {
    saindo.value = false;
    document.addEventListener('keydown', aoTeclar);
    agendar(encerrar, props.duracaoMs);
  } else {
    document.removeEventListener('keydown', aoTeclar);
  }
}, { immediate: true });

onUnmounted(() => {
  limpar();
  document.removeEventListener('keydown', aoTeclar);
});
</script>

<style scoped>
.conquista {
  --explosao: 1s;
  position: fixed; inset: 0; z-index: 10050; overflow: hidden;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
  padding: 16px; text-align: center; cursor: pointer;
  background: radial-gradient(circle at 50% 42%, rgb(var(--tema-fundo) / 55%), rgb(2 6 23 / 94%) 62%);
  animation: conquista-entrada .35s ease-out both;
}
.conquista--saindo { animation: conquista-saida .6s ease-in forwards; }
.conquista--saindo .conquista__trofeu { animation: trofeu-saida .6s ease-in forwards; }

.conquista__sobretitulo { color: var(--tema-texto); }
.conquista__mensagem { color: rgb(var(--tema-claro) / 90%); }
.conquista__botao { background: var(--tema-botao); box-shadow: 0 10px 20px rgb(var(--tema-brilho) / 30%); }
.conquista__botao:hover { filter: brightness(1.1); }

/* A medalha nasce com 112px; na celebracao aparece do tamanho do diamante. */
.conquista__medalha { transform: scale(1.7); transform-origin: center; margin: 40px 0 70px; }

/* Ato 3: o palco inteiro treme de leve no impacto. */
.conquista__palco { position: relative; display: grid; place-items: center; width: 340px; height: 300px; animation: palco-impacto .35s ease-out var(--explosao) both; }

/* Ato 1: surge de frente, parado, e carrega o brilho ate o estouro. */
.conquista__trofeu {
  position: relative; z-index: 2;
  animation:
    trofeu-surgir .7s cubic-bezier(.2, 1.3, .4, 1) both,
    trofeu-carregar .3s ease-in .7s both,
    trofeu-estouro .5s cubic-bezier(.2, 1.6, .4, 1) var(--explosao) both,
    trofeu-flutuar 2.4s ease-in-out calc(var(--explosao) + .5s) infinite alternate;
}

/* Ato 2: clarao, ondas de choque e estilhacos. */
.conquista__clarao {
  position: absolute; inset: 0; z-index: 4; pointer-events: none;
  background: radial-gradient(circle at 50% 42%, #fff, rgb(var(--tema-claro) / 70%) 30%, transparent 70%);
  opacity: 0;
  animation: clarao .55s ease-out var(--explosao) both;
}
.conquista__onda {
  position: absolute; z-index: 1; width: 120px; height: 120px; border-radius: 9999px;
  border: 4px solid rgb(var(--tema-claro) / 90%); box-shadow: 0 0 30px rgb(var(--tema-brilho) / 80%);
  opacity: 0;
  animation: onda .9s ease-out var(--explosao) both;
}
.conquista__onda--segunda { border-width: 2px; animation-delay: calc(var(--explosao) + .15s); }
.conquista__estilhaco {
  position: absolute; z-index: 3; top: 50%; left: 50%;
  clip-path: polygon(50% 0, 100% 40%, 50% 100%, 0 40%);
  filter: drop-shadow(0 0 6px rgb(var(--tema-brilho) / 90%));
  opacity: 0;
  animation: estilhaco 1.1s cubic-bezier(.1, .8, .3, 1) calc(var(--explosao) + var(--atraso)) both;
}
.conquista__raios {
  position: absolute; inset: -60px; border-radius: 9999px; z-index: 0;
  background: repeating-conic-gradient(from 0deg, rgb(var(--tema-brilho) / 22%) 0deg 10deg, transparent 10deg 30deg);
  mask-image: radial-gradient(circle, #000 20%, transparent 68%);
  opacity: 0;
  animation: raios-giro 9s linear infinite, raios-surgir .8s ease-out var(--explosao) both;
}

.conquista__texto { position: relative; z-index: 3; cursor: default; animation: texto-subir .6s ease-out calc(var(--explosao) + .3s) both; }

@keyframes conquista-entrada { from { opacity: 0; } to { opacity: 1; } }
@keyframes conquista-saida { to { opacity: 0; } }
@keyframes trofeu-surgir {
  0% { transform: translateY(30px) scale(.4); opacity: 0; }
  100% { transform: translateY(0) scale(1); opacity: 1; }
}
@keyframes trofeu-carregar {
  from { filter: brightness(1); }
  to { filter: brightness(1.8) drop-shadow(0 0 28px rgb(var(--tema-claro))); }
}
@keyframes trofeu-estouro {
  0% { transform: scale(.85); filter: brightness(2.2); }
  45% { transform: scale(1.22); }
  100% { transform: scale(1); filter: brightness(1); }
}
@keyframes trofeu-flutuar {
  from { transform: translateY(0); filter: drop-shadow(0 0 12px rgb(var(--tema-brilho) / 50%)); }
  to { transform: translateY(-10px); filter: drop-shadow(0 0 26px rgb(var(--tema-claro) / 90%)); }
}
@keyframes trofeu-saida { to { transform: scale(.3) translateY(40px); opacity: 0; filter: blur(4px); } }
@keyframes palco-impacto {
  0%, 100% { transform: translate(0, 0); }
  25% { transform: translate(-4px, 2px); }
  50% { transform: translate(4px, -2px); }
  75% { transform: translate(-2px, 1px); }
}
@keyframes clarao { 0% { opacity: 0; } 15% { opacity: 1; } 100% { opacity: 0; } }
@keyframes onda {
  0% { transform: scale(.3); opacity: 1; }
  100% { transform: scale(4.5); opacity: 0; }
}
@keyframes estilhaco {
  0% { transform: translate(-50%, -50%) rotate(var(--angulo)) translateY(0) rotate(0) scale(.4); opacity: 1; }
  70% { opacity: 1; }
  100% { transform: translate(-50%, -50%) rotate(var(--angulo)) translateY(calc(var(--distancia) * -1)) rotate(var(--giro-estilhaco)) scale(1); opacity: 0; }
}
@keyframes raios-giro { to { transform: rotate(360deg); } }
@keyframes raios-surgir { from { opacity: 0; } to { opacity: 1; } }
@keyframes texto-subir { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }

@media (prefers-reduced-motion: reduce) {
  .conquista, .conquista__trofeu, .conquista__texto, .conquista__raios, .conquista__palco { animation-duration: .01ms; animation-delay: 0s; animation-iteration-count: 1; }
  .conquista__estilhaco, .conquista__onda, .conquista__clarao { display: none; }
}
</style>
