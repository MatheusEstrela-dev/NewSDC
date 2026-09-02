import { computed } from 'vue';
import { useTheme } from '@/Composables/ui/useTheme';

/*
 * Classificacao de chuva compartilhada entre as redes do medalhao (INMET e
 * CEMADEN).
 *
 * As faixas sao as do sistema LHASA_RIO adaptadas para MG e precisam ser as
 * MESMAS que os CASE das matviews gold.inmet_mapa e gold.cemaden_mapa. Estavam
 * escritas dentro de MapaInmet.vue; com a segunda rede entrando, duplicar
 * significaria as duas telas divergirem em silencio na primeira alteracao de
 * faixa.
 *
 * Duas paletas porque a saturacao que funciona sobre fundo escuro agride sobre
 * branco. Valor literal, nunca var(--...): estas cores vao para o fillColor do
 * Leaflet, que vira atributo SVG, onde variavel CSS nao resolve.
 */
const CORES_ESCURO = {
  sem_chuva: '#22c55e',
  muito_fraca: '#3b82f6',
  fraca: '#06b6d4',
  moderada: '#eab308',
  forte: '#f97316',
  muito_forte: '#ef4444',
  intensa: '#991b1b',
  extrema: '#7f1d1d',
  desconhecido: '#6b7280',
};

const CORES_CLARO = {
  sem_chuva: '#15803d',
  muito_fraca: '#1d4ed8',
  fraca: '#0e7490',
  moderada: '#a16207',
  forte: '#c2410c',
  muito_forte: '#b91c1c',
  intensa: '#7f1d1d',
  extrema: '#581c1c',
  desconhecido: '#475569',
};

const ROTULOS = {
  sem_chuva: 'Sem chuva',
  muito_fraca: 'Muito fraca',
  fraca: 'Fraca',
  moderada: 'Moderada',
  forte: 'Forte',
  muito_forte: 'Muito forte',
  intensa: 'Intensa',
  extrema: 'Extrema',
  desconhecido: 'Sem leitura',
};

// A legenda deriva das mesmas faixas, em vez de repetir as cores em markup —
// era o que permitia a legenda divergir da classificacao sem ninguem notar.
const FAIXAS = [
  { classe: 'sem_chuva', rotulo: 'Sem chuva (0 mm)' },
  { classe: 'muito_fraca', rotulo: 'Muito fraca (0-5 mm)' },
  { classe: 'fraca', rotulo: 'Fraca (5-15 mm)' },
  { classe: 'moderada', rotulo: 'Moderada (15-35 mm)' },
  { classe: 'forte', rotulo: 'Forte (35-60 mm)' },
  { classe: 'muito_forte', rotulo: 'Muito forte (60-100 mm)' },
  { classe: 'intensa', rotulo: 'Intensa (100-140 mm)' },
  { classe: 'extrema', rotulo: 'Extrema (> 140 mm)' },
];

export function usePrecipitacao() {
  const { isDarkMode } = useTheme();

  const cores = computed(() => (isDarkMode.value ? CORES_ESCURO : CORES_CLARO));

  // Computed porque a cor depende do tema: trocar claro/escuro repinta a legenda.
  const legenda = computed(() => FAIXAS.map((faixa) => ({
    ...faixa,
    cor: cores.value[faixa.classe],
  })));

  function corDaClasse(classe) {
    return cores.value[classe] ?? cores.value.desconhecido;
  }

  function rotuloDaClasse(classe) {
    return ROTULOS[classe] ?? ROTULOS.desconhecido;
  }

  function formatarMm(valor) {
    const numero = Number(valor);

    return Number.isFinite(numero) ? `${numero.toFixed(2)} mm` : 'N/A';
  }

  function formatarDataHora(valor) {
    if (!valor) {
      return '-';
    }

    const data = new Date(valor);

    return Number.isNaN(data.getTime()) ? String(valor) : data.toLocaleString('pt-BR');
  }

  return { cores, legenda, corDaClasse, rotuloDaClasse, formatarMm, formatarDataHora };
}
