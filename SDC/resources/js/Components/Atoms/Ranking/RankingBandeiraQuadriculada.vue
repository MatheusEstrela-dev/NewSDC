<template>
  <span class="bandeira" :class="[`bandeira--${lado}`, { 'bandeira--tremulando': tremulando }]" aria-hidden="true">
    <span class="bandeira__mastro" />
    <span class="bandeira__ponteira" />
    <span class="bandeira__pano">
      <span
        v-for="faixa in FAIXAS"
        :key="faixa.chave"
        class="bandeira__faixa"
        :style="faixa.estilo"
      />
    </span>
  </span>
</template>

<script setup>
/**
 * Bandeira quadriculada de largada, em CSS puro.
 *
 * FLAMULAR
 * O pano e fatiado em faixas verticais; cada faixa sobe e desce com a mesma
 * onda, defasada da vizinha, e a amplitude cresce do mastro para a ponta
 * solta - o tecido preso quase nao mexe, a ponta bate no vento. A luz de cada
 * faixa acompanha a onda (crista clara, vale escuro), o que desenha as dobras.
 * Um bloco unico girado inteiro parecia placa rigida.
 *
 * `lado` espelha a bandeira: duas, uma de cada lado, formam o X da bandeirada.
 * Com prefers-reduced-motion o pano fica parado.
 */
defineProps({
  lado: { type: String, default: 'esquerda', validator: (valor) => ['esquerda', 'direita'].includes(valor) },
  tremulando: { type: Boolean, default: true },
});

const LARGURA = 124;
const QUANTAS = 18;
const PERIODO_S = 1.05;
const largura = LARGURA / QUANTAS;

// Faixa 0 na ponta solta (esquerda); a ultima encosta no mastro (direita).
const FAIXAS = Array.from({ length: QUANTAS }, (_, i) => {
  const distanciaDoMastro = (QUANTAS - 1 - i) / (QUANTAS - 1);
  return {
    chave: i,
    estilo: {
      left: `${i * largura}px`,
      // Meio pixel de sobra cobre a costura entre faixas vizinhas.
      width: `${largura + 0.6}px`,
      backgroundPosition: `${-i * largura}px 0`,
      '--amplitude': `${1 + distanciaDoMastro * 9}px`,
      // A onda corre do mastro para a ponta: faixas mais longe atrasam.
      animationDelay: `${-(PERIODO_S * 2) + distanciaDoMastro * PERIODO_S * 1.4}s`,
    },
  };
});
</script>

<style scoped>
.bandeira { position: relative; display: block; width: 150px; height: 220px; }
.bandeira--direita { transform: scaleX(-1); }

/* Mastro de madeira inclinado; os dois se cruzam na base. */
.bandeira__mastro {
  position: absolute; left: 128px; top: 10px; width: 7px; height: 210px; border-radius: 4px;
  background: linear-gradient(90deg, #7c5a2c, #c49a5a 45%, #8a6532);
  transform: rotate(-18deg); transform-origin: 50% 100%;
}
.bandeira__ponteira {
  position: absolute; left: 61px; top: 8px; width: 12px; height: 12px; border-radius: 9999px;
  background: radial-gradient(circle at 35% 35%, #fde68a, #b8860b);
}

/* Pano preso no alto do mastro (topo em ~67,20 apos a inclinacao), voando
   para fora; transborda o elemento de proposito. Sem overflow: as faixas
   precisam subir e descer alem da caixa. */
.bandeira__pano {
  position: absolute; left: -57px; top: 20px; width: 124px; height: 88px;
  transform-origin: 100% 0;
  transform: rotate(-16deg);
  filter: drop-shadow(0 6px 8px rgb(0 0 0 / 35%));
}
.bandeira__faixa {
  position: absolute; top: 0; height: 100%;
  background-image: repeating-conic-gradient(#111827 0 25%, #f8fafc 0 50%);
  background-size: 22px 22px;
}

.bandeira--tremulando .bandeira__faixa {
  animation: faixa-onda 1.05s ease-in-out infinite alternate;
}

@keyframes faixa-onda {
  0% { transform: translateY(calc(var(--amplitude) * -1)) scaleY(1.02); filter: brightness(1.12); }
  50% { filter: brightness(.95); }
  100% { transform: translateY(var(--amplitude)) scaleY(.97); filter: brightness(.72); }
}

@media (prefers-reduced-motion: reduce) {
  .bandeira--tremulando .bandeira__faixa { animation: none; }
}
</style>
