<template>
  <span
    class="inline-flex"
    @mouseenter="abrir"
    @mousemove="posicionar"
    @mouseleave="fechar"
    @focusin="abrirNoElemento"
    @focusout="fechar"
  >
    <span
      class="cursor-help underline decoration-dotted underline-offset-2"
      :class="triggerClass"
      tabindex="0"
    >
      <slot />
    </span>

    <!-- position: fixed, e nao absolute. A tabela vive dentro de um
         .overflow-x-auto, e pela spec do CSS isso forca overflow-y a auto:
         um filho absolute abaixo do conteudo estende a area rolavel e abre um
         vao em branco no fim da lista. Fixed nao entra no overflow de nenhum
         container. E a mesma razao pela qual o legado usa fixed. -->
    <Teleport to="body">
      <span
        v-if="visivel && temConteudo"
        role="tooltip"
        class="pointer-events-none fixed z-[60] w-72 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-xl dark:border-slate-700 dark:bg-slate-800"
        :style="{ top: `${pos.y}px`, left: `${pos.x}px` }"
      >
        <span
          v-for="linha in linhas"
          :key="linha.rotulo"
          class="flex items-start justify-between gap-3 border-b border-slate-100 py-1 last:border-b-0 dark:border-slate-700/60"
        >
          <span class="shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ linha.rotulo }}</span>
          <span class="text-right text-xs font-semibold text-slate-800 dark:text-slate-100">{{ linha.valor }}</span>
        </span>
      </span>
    </Teleport>
  </span>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  // Payload de tipo_desastre_completo, ja entregue por ProcessoResource.
  desastre: {
    type: Object,
    default: null,
  },
  triggerClass: {
    type: String,
    default: '',
  },
});

const SEM_VALOR = 'N/A';
const LARGURA = 288; // w-72
const ALTURA_ESTIMADA = 170;
const AFASTAMENTO = 16;

const visivel = ref(false);
const pos = ref({ x: 0, y: 0 });

const linhas = computed(() => {
  const d = props.desastre;
  if (!d) return [];

  return [
    { rotulo: 'Definição', valor: d.definicao || SEM_VALOR },
    { rotulo: 'Grupo', valor: d.grupo || SEM_VALOR },
    { rotulo: 'Sub-Grupo', valor: d.subgrupo || SEM_VALOR },
    { rotulo: 'Tipo', valor: d.tipo || SEM_VALOR },
    { rotulo: 'Sub-Tipo', valor: d.subtipo || SEM_VALOR },
  ];
});

// Sem nenhum campo preenchido o card seria cinco linhas de "N/A": melhor nao
// abrir nada e deixar o texto se comportar como texto comum.
const temConteudo = computed(
  () => linhas.value.some((l) => l.valor !== SEM_VALOR),
);

// Mantem o balao dentro da viewport: perto da borda direita ele abre para a
// esquerda do cursor, e perto do rodape, para cima.
function posicionar(event) {
  const cabeADireita = event.clientX + AFASTAMENTO + LARGURA <= window.innerWidth;
  const cabeAbaixo = event.clientY + AFASTAMENTO + ALTURA_ESTIMADA <= window.innerHeight;

  pos.value = {
    x: cabeADireita ? event.clientX + AFASTAMENTO : event.clientX - AFASTAMENTO - LARGURA,
    y: cabeAbaixo ? event.clientY + AFASTAMENTO : event.clientY - AFASTAMENTO - ALTURA_ESTIMADA,
  };
}

function abrir(event) {
  posicionar(event);
  visivel.value = true;
}

// Foco por teclado nao traz coordenadas de mouse: ancora no proprio elemento.
function abrirNoElemento(event) {
  const r = event.target.getBoundingClientRect();
  posicionar({ clientX: r.left, clientY: r.bottom });
  visivel.value = true;
}

function fechar() {
  visivel.value = false;
}
</script>
