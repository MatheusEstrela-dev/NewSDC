<template>
  <span
    class="icone"
    :class="{ 'icone--girando': girando }"
    :style="{ width: `${tamanho}px`, height: `${tamanho * 0.84}px`, perspective: `${tamanho * 5}px` }"
    role="img"
    :aria-label="rotulo"
  >
    <svg class="icone__svg" viewBox="0 40 512 430" aria-hidden="true" focusable="false">
      <polygon points="105,52 0,158 146,158" fill="#03A9F4" />
      <polygon points="105,52 256,52 146,158" fill="#00C3FF" />
      <polygon points="256,52 146,158 366,158" fill="#88D8FF" />
      <polygon points="256,52 405,52 366,158" fill="#00C3FF" />
      <polygon points="405,52 512,158 366,158" fill="#A6E7FF" />
      <polygon points="0,158 146,158 256,458" fill="#0398DC" />
      <polygon points="146,158 366,158 256,458" fill="#00C3FF" />
      <polygon points="366,158 512,158 256,458" fill="#88D8FF" />
    </svg>
    <span v-if="girando" class="icone__cintilar" aria-hidden="true" />
  </span>
</template>

<script setup>
/**
 * Diamante plano (icone de facetas chapadas) das animacoes: celebracao da
 * conquista e o destaque sobre o ouro no podio. O cabecalho da faixa na lista
 * usa a imagem estatica /imgs/diamante.png.
 *
 * `girando` vira o icone no eixo Y, como moeda, com brilho pulsante. Com
 * prefers-reduced-motion fica parado e so o brilho permanece.
 */
defineProps({
  girando: { type: Boolean, default: false },
  tamanho: { type: Number, default: 64 },
  rotulo: { type: String, default: 'Diamante' },
});
</script>

<style scoped>
.icone { position: relative; display: inline-block; flex-shrink: 0; line-height: 0; }
.icone__svg { width: 100%; height: 100%; filter: drop-shadow(0 6px 14px rgb(3 169 244 / 45%)); }
.icone--girando .icone__svg { animation: icone-giro 3.2s linear infinite, icone-brilho 1.6s ease-in-out infinite alternate; }

.icone__cintilar {
  position: absolute; top: 4%; right: 14%; width: 22%; height: 26%;
  background: radial-gradient(circle, #fff 0 12%, transparent 13%),
    linear-gradient(90deg, transparent 45%, #fff 50%, transparent 55%),
    linear-gradient(0deg, transparent 45%, #fff 50%, transparent 55%);
  animation: icone-cintilar 2.2s ease-in-out infinite;
  pointer-events: none;
}

@keyframes icone-giro {
  from { transform: rotateY(0deg); }
  to { transform: rotateY(360deg); }
}
@keyframes icone-brilho {
  from { filter: drop-shadow(0 6px 12px rgb(3 169 244 / 35%)); }
  to { filter: drop-shadow(0 8px 22px rgb(0 195 255 / 80%)); }
}
@keyframes icone-cintilar {
  0%, 60%, 100% { opacity: 0; transform: scale(.3) rotate(0deg); }
  75% { opacity: 1; transform: scale(1) rotate(45deg); }
}

@media (prefers-reduced-motion: reduce) {
  .icone--girando .icone__svg { animation: icone-brilho 1.6s ease-in-out infinite alternate; }
  .icone__cintilar { animation: none; }
}
</style>
