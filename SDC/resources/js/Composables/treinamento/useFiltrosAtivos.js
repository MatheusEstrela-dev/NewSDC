import { computed, unref } from 'vue';

const achar = (lista, campo, id, rotuloCampo) => (lista ?? []).find((i) => String(i[campo]) === String(id))?.[rotuloCampo];

const DEFINICOES = [
  { chave: 'ano', rotulo: 'Ano', texto: (v) => String(v) },
  { chave: 'redec_id', rotulo: 'REDEC', texto: (v, o) => achar(o.redecs, 'id', v, 'nome') },
  { chave: 'municipio_id', rotulo: 'Município', texto: (v, o) => achar(o.municipios, 'id', v, 'nome') },
  { chave: 'treinamento_id', rotulo: 'Capacitação', texto: (v, o) => achar(o.treinamentos, 'id', v, 'titulo') },
  { chave: 'tipo', rotulo: 'Tipo', texto: (v, o) => achar(o.tipos, 'value', v, 'label') },
];

/**
 * Filtros preenchidos como [{ chave, rotulo, texto }], com o id ja resolvido
 * para nome nas `opcoes` do servidor. Uma fonte so para a faixa de filtros
 * ativos e para a linha "Filtros aplicados" do relatorio: os dois dizem a
 * mesma coisa com as mesmas palavras.
 */
export function useFiltrosAtivos(filtros, opcoes) {
  return computed(() => {
    const f = unref(filtros);
    const o = unref(opcoes);

    return DEFINICOES
      .filter((d) => f[d.chave] !== null && f[d.chave] !== undefined && f[d.chave] !== '')
      .map((d) => ({ chave: d.chave, rotulo: d.rotulo, texto: d.texto(f[d.chave], o) ?? String(f[d.chave]) }));
  });
}
