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

/** "1 turma" / "3 turmas", com o numero formatado. */
export const rotuloContagem = (qtd, singular, plural) => `${formatarNumero(qtd)} ${Number(qtd) === 1 ? singular : plural}`;

export const formatarDecimal =(valor, casas = 1) => Number(valor ?? 0)
  .toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });

/** Corta rotulo longo de eixo: titulo de capacitacao nao cabe em 375px. */
export const encurtar = (texto, limite = 26) => {
  const valor = String(texto ?? '');
  return valor.length > limite ? `${valor.slice(0, limite - 1).trimEnd()}...` : valor;
};

/** Mesmas cores do sexo em todo o painel (rosca, relatorio). */
export const COR_FEMININO = '#8b5cf6';
export const COR_MASCULINO = '#3b82f6';
export const COR_NAO_BINARIO = '#f59e0b';
export const COR_OUTRO = '#14b8a6';
export const COR_NAO_INFORMADO = '#94a3b8';

/** Categorias de sexo na ordem de exibicao; `chave` e o campo de props.porSexo. */
export const SEXOS = [
  { chave: 'feminino', nome: 'Feminino', cor: COR_FEMININO },
  { chave: 'masculino', nome: 'Masculino', cor: COR_MASCULINO },
  { chave: 'nao_binario', nome: 'Não-binário', cor: COR_NAO_BINARIO },
  { chave: 'outro', nome: 'Outro', cor: COR_OUTRO },
  { chave: 'nao_informado', nome: 'Não informado', cor: COR_NAO_INFORMADO },
];

/**
 * Situacao da participacao: `valor` e o do filtro situacoes[] (enum
 * SituacaoParticipacao), `chave` o campo numerico nas linhas do servidor
 * (porMes, porCurso, detalheMes, tabela). Azul e laranja sao os do mapa.
 */
export const COR_CONCLUIDO = '#2563eb';
export const COR_EM_ANDAMENTO = '#f97316';
export const COR_DESISTENTE = '#e11d48';

/** Notas da avaliacao do curso (1 Pessimo .. 5 Excelente): escala vermelho -> verde. */
const CORES_NOTA = { 1: '#dc2626', 2: '#f97316', 3: '#eab308', 4: '#65a30d', 5: '#16a34a' };
export const corDaNota = (nota) => CORES_NOTA[nota] ?? '#94a3b8';

export const SITUACOES = [
  { valor: 'CONCLUIDO', chave: 'concluidos', nome: 'Concluídos', cor: COR_CONCLUIDO },
  { valor: 'EM_ANDAMENTO', chave: 'em_andamento', nome: 'Em andamento', cor: COR_EM_ANDAMENTO },
  { valor: 'DESISTENTE', chave: 'desistentes', nome: 'Desistentes', cor: COR_DESISTENTE },
];

/**
 * Situacoes que o filtro realmente restringe: vazio ou as tres marcadas e o
 * mesmo que nao filtrar, entao ambos viram [].
 */
export const situacoesFiltradas = (lista) => {
  const validas = (Array.isArray(lista) ? lista : []).filter((v) => SITUACOES.some((s) => s.valor === v));
  return validas.length >= SITUACOES.length ? [] : validas;
};

/** Percentual de `parte` em `total` com 1 casa; 0 quando nao ha total. */
export const percentual = (parte, total) => (total > 0 ? Math.round((parte / total) * 1000) / 10 : 0);

// 'YYYY-MM-DD' lido como texto, pelo mesmo motivo do rotuloMes (fuso).
export const formatarData = (iso) => (iso ? String(iso).slice(0, 10).split('-').reverse().join('/') : '');

/** "dd/mm/aaaa a dd/mm/aaaa", ou a data que existir, ou traco. */
export const formatarPeriodo = (inicio, fim) => {
  const ini = formatarData(inicio);
  const ate = formatarData(fim);
  if (ini && ate && ini !== ate) return `${ini} a ${ate}`;
  return ini || ate || '—';
};

const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

// 'YYYY-MM' lido na mao: new Date('2026-03') cai em UTC e volta um mes no fuso do Brasil.
export const rotuloMes = (mes) => {
  const [ano, numero] = String(mes).split('-');
  return `${MESES[Number(numero) - 1] ?? numero}/${String(ano).slice(2)}`;
};
