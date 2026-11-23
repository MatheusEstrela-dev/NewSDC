/**
 * Espelho no front de ClassificacaoDeOferta (PHP) para o formulario da
 * capacitacao: decide o que mostrar antes de gravar. A classificacao que vale
 * e a do backend.
 */
const TIPOS_QUE_ADMITEM_TUTORIA = ['EAD'];

/** "Com tutoria" so e opcao para EAD fora de evento (ClassificacaoDeOferta::admiteTutoria). */
export function admiteTutoria({ tipo, categoria }) {
  return categoria !== 'EVENTO' && TIPOS_QUE_ADMITEM_TUTORIA.includes(tipo);
}

/** Capacitacao livre: EAD fora de evento e sem tutoria (CategoriaOferta::LIVRE). */
export function ehCapacitacaoLivre({ tipo, categoria, comTutoria }) {
  return admiteTutoria({ tipo, categoria }) && !comTutoria;
}
