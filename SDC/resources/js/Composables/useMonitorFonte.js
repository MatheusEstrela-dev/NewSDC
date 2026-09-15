import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

/*
 * Watchdog de fonte: diz se o coletor esta de fato no ar.
 *
 * A pergunta que isto responde nao e "o dado e recente", e sim "o pipeline
 * continua consultando a fonte". Sao coisas diferentes e foi justamente a
 * confusao entre elas que fazia a tela de sismos parecer morta: houve 6 eventos
 * em 90 dias, entao "nenhuma novidade" e a resposta certa quase sempre, e isso
 * era indistinguivel de um coletor parado.
 *
 * O relogio proprio importa. Sem ele, a etiqueta so mudaria quando chegasse um
 * GoldAtualizado -- e esse evento so e disparado quando ha conteudo novo, ou
 * seja, exatamente o caso que NAO acontece quando a fonte cai. O verde ficaria
 * congelado na tela justamente na hora em que precisa virar vermelho.
 */
const INTERVALO_MS = 30_000;

export function useMonitorFonte(verificadoEm, toleranciaMinutos) {
  const agora = ref(Date.now());
  let cronometro = null;

  onMounted(() => {
    cronometro = setInterval(() => {
      agora.value = Date.now();
    }, INTERVALO_MS);
  });

  onBeforeUnmount(() => {
    if (cronometro !== null) {
      clearInterval(cronometro);
      cronometro = null;
    }
  });

  const segundosDesde = computed(() => {
    const valor = typeof verificadoEm === 'function' ? verificadoEm() : verificadoEm?.value;

    if (!valor) {
      return null;
    }

    const instante = new Date(valor).getTime();

    if (Number.isNaN(instante)) {
      return null;
    }

    // Nunca negativo: relogio do cliente adiantado em relacao ao servidor
    // produziria "verificado daqui a 3 minutos", que nao ajuda ninguem.
    return Math.max(0, Math.round((agora.value - instante) / 1000));
  });

  // Sem verificacao registrada nao ha como afirmar que esta no ar. Vermelho e a
  // resposta segura: alegar disponibilidade sem evidencia e pior que admitir
  // desconhecimento numa tela de Defesa Civil.
  const ativo = computed(() => {
    if (segundosDesde.value === null) {
      return false;
    }

    return segundosDesde.value <= toleranciaMinutos * 60;
  });

  /** Texto curto do tempo decorrido: "agora", "8 min", "3 h", "2 d". */
  const desde = computed(() => {
    const segundos = segundosDesde.value;

    if (segundos === null) {
      return 'sem registro';
    }

    if (segundos < 90) {
      return 'agora';
    }

    const minutos = Math.round(segundos / 60);

    if (minutos < 60) {
      return `${minutos} min`;
    }

    const horas = Math.round(minutos / 60);

    return horas < 24 ? `${horas} h` : `${Math.round(horas / 24)} d`;
  });

  const rotulo = computed(() => (ativo.value ? 'ao vivo' : 'sem resposta'));

  return { ativo, desde, rotulo, segundosDesde };
}
