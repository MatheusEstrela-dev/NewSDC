<template>
  <span class="medalha" :class="[`medalha--${posicao}`, { 'medalha--compacta': compacta }]" role="img" :aria-label="`${posicao}º lugar — medalha de ${metais[posicao] ?? 'participação'}`">
    <span class="medalha__fita" aria-hidden="true" />
    <span class="medalha__disco" aria-hidden="true">
      <svg class="medalha__coroa" viewBox="0 0 40 24" fill="currentColor">
        <path d="m5 7 8 5L20 2l7 10 8-5-4 15H9L5 7Z" />
        <circle cx="5" cy="5" r="2.5" /><circle cx="20" cy="2.5" r="2.5" /><circle cx="35" cy="5" r="2.5" />
      </svg>
      <svg class="medalha__louros" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M37 81C17 70 13 49 24 30M25 67l-10-4m8-7-10-7m10-5-7-9m12 38-1-11m-5-7 6-8m-5-5 7-8M63 81c20-11 24-32 13-51M75 67l10-4m-8-7 10-7m-10-5 7-9m-12 38 1-11m5-7-6-8m5-5-7-8" />
      </svg>
      <span class="medalha__numero">{{ posicao }}</span>
      <svg class="medalha__estrela" viewBox="0 0 20 20" fill="currentColor"><path d="m10 1 2.5 6.5L19 10l-6.5 2.5L10 19l-2.5-6.5L1 10l6.5-2.5Z" /></svg>
    </span>
  </span>
</template>

<script setup>
defineProps({
  posicao: { type: Number, required: true },
  compacta: { type: Boolean, default: false },
});
const metais = { 1: 'ouro', 2: 'prata', 3: 'bronze' };
</script>

<style scoped>
.medalha {
  --metal-claro: #fff2b4; --metal: #f6c453; --metal-escuro: #b87916; --metal-tinta: #80500a;
  position: relative; display: inline-grid; flex: none; width: 112px; height: 132px; place-items: start center;
  filter: drop-shadow(0 8px 10px rgb(15 23 42 / 18%));
}
.medalha--2 { --metal-claro: #f1f7ff; --metal: #c2d2e4; --metal-escuro: #7c93af; --metal-tinta: #435b78; }
.medalha--3 { --metal-claro: #ffe0bd; --metal: #dca174; --metal-escuro: #a45d32; --metal-tinta: #783d20; }
.medalha__fita {
  position: absolute; inset: 58% 16% 0;
  background: linear-gradient(90deg, var(--metal-escuro), var(--metal) 24%, var(--metal-escuro) 49%, var(--metal-claro) 51%, var(--metal) 76%, var(--metal-escuro));
  clip-path: polygon(10% 0, 90% 0, 100% 100%, 72% 86%, 53% 100%, 50% 50%, 47% 100%, 28% 86%, 0 100%);
}
.medalha__disco {
  position: relative; display: grid; width: 100%; aspect-ratio: 1; place-items: center; overflow: hidden;
  border: 3px solid var(--metal-escuro); border-radius: 50%; color: var(--metal-tinta);
  background: linear-gradient(135deg, var(--metal-claro) 5%, var(--metal) 44%, var(--metal-claro) 48%, var(--metal) 65%, var(--metal-escuro));
  box-shadow: inset 0 0 0 3px var(--metal-claro), inset 0 0 0 7px var(--metal), inset 0 0 0 8px var(--metal-escuro);
}
.medalha__coroa { position: absolute; width: 29%; top: 16%; }
.medalha__louros { position: absolute; inset: 0; width: 100%; opacity: .7; }
.medalha__numero { margin-top: 9%; font-size: 40px; font-weight: 900; line-height: 1; text-shadow: 0 1px var(--metal-claro); }
.medalha__estrela { position: absolute; bottom: 10%; width: 12px; }
.medalha--compacta { width: 34px; height: 40px; filter: none; }
.medalha--compacta .medalha__disco { border-width: 1px; box-shadow: inset 0 0 0 1px var(--metal-claro), inset 0 0 0 3px var(--metal); }
.medalha--compacta .medalha__numero { margin-top: 0; font-size: 17px; }
.medalha--compacta .medalha__coroa, .medalha--compacta .medalha__louros, .medalha--compacta .medalha__estrela { display: none; }
</style>
