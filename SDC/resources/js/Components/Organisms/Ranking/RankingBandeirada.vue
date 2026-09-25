<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="largada"
      :class="{ 'largada--saindo': saindo }"
      role="dialog"
      aria-modal="true"
      aria-labelledby="largada-titulo"
      data-bandeirada
      @click="encerrar"
    >
      <div class="largada__palco" aria-hidden="true">
        <span class="largada__brilho" />
        <span class="largada__bandeira largada__bandeira--esquerda"><RankingBandeiraQuadriculada lado="esquerda" /></span>
        <span class="largada__bandeira largada__bandeira--direita"><RankingBandeiraQuadriculada lado="direita" /></span>
      </div>
      <div class="largada__texto">
        <p class="text-xs font-bold uppercase tracking-[0.35em] text-amber-300">Largada</p>
        <h2 id="largada-titulo" class="mt-2 text-3xl font-extrabold text-white sm:text-4xl">Nova temporada!</h2>
        <p v-if="subtitulo" class="mt-2 text-sm text-slate-200">{{ subtitulo }}</p>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
/**
 * Bandeirada de largada da temporada: as duas bandeiras quadriculadas descem
 * girando ate se cruzar em X, tremulam e a cena sai sozinha, abrindo caminho
 * para o aviso da nova temporada. Clique ou Escape encerram antes.
 *
 * Quem decide QUANDO largar e a pagina (so na virada, uma vez por temporada);
 * este componente so encena e avisa `fim`.
 */
import { onUnmounted, ref, watch } from 'vue';
import RankingBandeiraQuadriculada from '@/Components/Atoms/Ranking/RankingBandeiraQuadriculada.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  subtitulo: { type: String, default: '' },
  duracaoMs: { type: Number, default: 3200 },
});
const emit = defineEmits(['fim']);

const SAIDA_MS = 500;
const saindo = ref(false);
let temporizadores = [];

function limpar() {
  temporizadores.forEach(clearTimeout);
  temporizadores = [];
}

function encerrar() {
  if (saindo.value) return;
  limpar();
  saindo.value = true;
  temporizadores.push(setTimeout(() => {
    saindo.value = false;
    emit('fim');
  }, SAIDA_MS));
}

const aoTeclar = (evento) => { if (evento.key === 'Escape') encerrar(); };

watch(() => props.show, (aberto) => {
  limpar();
  if (aberto) {
    saindo.value = false;
    document.addEventListener('keydown', aoTeclar);
    temporizadores.push(setTimeout(encerrar, props.duracaoMs));
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
.largada {
  position: fixed; inset: 0; z-index: 10050; overflow: hidden;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
  padding: 16px; text-align: center; cursor: pointer;
  background: radial-gradient(circle at 50% 40%, rgb(180 110 10 / 45%), rgb(2 6 23 / 94%) 62%);
  animation: largada-entrada .3s ease-out both;
}
.largada--saindo { animation: largada-saida .5s ease-in forwards; }

.largada__palco { position: relative; width: 300px; height: 240px; }
.largada__brilho {
  position: absolute; left: 50%; top: 55%; width: 260px; height: 260px; margin: -130px 0 0 -130px; border-radius: 9999px;
  background: radial-gradient(circle, rgb(253 230 138 / 45%), transparent 65%);
  opacity: 0; animation: brilho-pulsar 1.2s ease-out .55s both;
}

/* Cada bandeira desce girando do seu lado e para cruzada no centro. */
.largada__bandeira { position: absolute; top: 0; }
/* Bases trocadas de lado (esquerda mais a direita que a direita): os mastros
   se cruzam no terco de baixo e formam o X. */
.largada__bandeira--esquerda { left: 40px; animation: bandeira-esquerda .75s cubic-bezier(.2, 1.3, .4, 1) both; }
.largada__bandeira--direita { right: 40px; animation: bandeira-direita .75s cubic-bezier(.2, 1.3, .4, 1) .08s both; }

.largada__texto { animation: texto-subir .5s ease-out .6s both; }

@keyframes largada-entrada { from { opacity: 0; } to { opacity: 1; } }
@keyframes largada-saida { to { opacity: 0; transform: scale(1.04); } }
@keyframes bandeira-esquerda {
  from { opacity: 0; transform: translate(-120px, -160px) rotate(-60deg); }
  to { opacity: 1; transform: translate(0, 0) rotate(0); }
}
@keyframes bandeira-direita {
  from { opacity: 0; transform: translate(120px, -160px) rotate(60deg); }
  to { opacity: 1; transform: translate(0, 0) rotate(0); }
}
@keyframes brilho-pulsar {
  0% { opacity: 0; transform: scale(.4); }
  40% { opacity: 1; }
  100% { opacity: .55; transform: scale(1.1); }
}
@keyframes texto-subir { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

@media (prefers-reduced-motion: reduce) {
  .largada, .largada__bandeira, .largada__texto, .largada__brilho { animation-duration: .01ms; animation-delay: 0s; }
}
</style>
