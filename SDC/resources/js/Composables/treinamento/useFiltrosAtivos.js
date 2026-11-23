import { computed, toValue } from 'vue';
import { SITUACOES, situacoesFiltradas } from '@/Composables/treinamento/useTemaGrafico';

const achar = (lista, campo, id, rotuloCampo) => (lista ?? []).find((i) => String(i[campo]) === String(id))?.[rotuloCampo];

const nomesDasSituacoes = (valores) => situacoesFiltradas(valores)
  .map((v) => SITUACOES.find((s) => s.valor === v)?.nome ?? v)
  .join(', ');

const DEFINICOES = [
  { chave: 'ano', rotulo: 'Ano', texto: (v) => String(v) },
  { chave: 'redec_id', rotulo: 'REDEC', texto: (v, o) => achar(o.redecs, 'id', v, 'nome') },
  { chave: 'municipio_id', rotulo: 'Município', texto: (v, o) => achar(o.municipios, 'id', v, 'nome') },
  { chave: 'curso_id', rotulo: 'Curso', texto: (v, o) => achar(o.cursos, 'id', v, 'nome') },
  { chave: 'treinamento_id', rotulo: 'Turma', texto: (v, o) => achar(o.treinamentos, 'id', v, 'titulo') },
  { chave: 'tipo', rotulo: 'Tipo', texto: (v, o) => achar(o.tipos, 'value', v, 'label') },
  { chave: 'situacoes', rotulo: 'Situação', texto: nomesDasSituacoes },
];

// Lista so conta como filtro quando restringe algo (vazia ou completa = todas).
const preenchido = (chave, valor) => (chave === 'situacoes'
  ? situacoesFiltradas(valor).length > 0
  : valor !== null && valor !== undefined && valor !== '');

/**
 * Filtros preenchidos como [{ chave, rotulo, texto }], com o id ja resolvido
 * para nome nas `opcoes` do servidor. Uma fonte so para a faixa de filtros
 * ativos e para a linha "Filtros aplicados" do relatorio: os dois dizem a
 * mesma coisa com as mesmas palavras.
 */
export function useFiltrosAtivos(filtros, opcoes) {
  return computed(() => {
    // toValue (nao unref): quem chama passa getter (() => props.filtros), que unref nao executa.
    const f = toValue(filtros);
    const o = toValue(opcoes);

    return DEFINICOES
      .filter((d) => preenchido(d.chave, f[d.chave]))
      .map((d) => ({ chave: d.chave, rotulo: d.rotulo, texto: d.texto(f[d.chave], o) ?? String(f[d.chave]) }));
  });
}
