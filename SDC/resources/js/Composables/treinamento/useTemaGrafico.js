import { computed } from 'vue';
import { useTheme } from '@/Composables/ui/useTheme';

/**
 * Cores de eixo, grade e tooltip dos graficos do painel de indicadores.
 *
 * Uma fonte so para os seis graficos: o ApexCharts nao le o `html.dark`, entao
 * cada opcao precisa receber a cor ja resolvida pelo tema do usuario.
 */
export const COR_CAPACITACOES = '#10b981';
export const COR_INSTITUCIONAL = '#2563eb';

export function useTemaGrafico() {
  const { isDarkMode } = useTheme();

  const tema = computed(() => ({
    corTexto: isDarkMode.value ? '#94a3b8' : '#64748b',
    corGrade: isDarkMode.value ? '#1e293b' : '#e2e8f0',
    corFundoMarcador: isDarkMode.value ? '#0f172a' : '#ffffff',
    tooltip: isDarkMode.value ? 'dark' : 'light',
  }));

  return { tema };
}

export const formatarNumero = (valor) => Number(valor ?? 0).toLocaleString('pt-BR');

export const formatarDecimal = (valor, casas = 1) => Number(valor ?? 0)
  .toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });

/** Corta rotulo longo de eixo: titulo de capacitacao nao cabe em 375px. */
export const encurtar = (texto, limite = 26) => {
  const valor = String(texto ?? '');
  return valor.length > limite ? `${valor.slice(0, limite - 1).trimEnd()}...` : valor;
};

/** Mesmas cores do sexo em todo o painel (rosca, barras empilhadas, aneis). */
export const COR_FEMININO = '#8b5cf6';
export const COR_MASCULINO = '#3b82f6';
export const COR_NAO_INFORMADO = '#94a3b8';

const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

// 'YYYY-MM' lido na mao: new Date('2026-03') cai em UTC e volta um mes no fuso do Brasil.
export const rotuloMes = (mes) => {
  const [ano, numero] = String(mes).split('-');
  return `${MESES[Number(numero) - 1] ?? numero}/${String(ano).slice(2)}`;
};
