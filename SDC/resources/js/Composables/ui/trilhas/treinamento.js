/**
 * Trilha (breadcrumb) das telas de Capacitacoes.
 *
 * Fonte unica dos rotulos e dos destinos do modulo. Modulo puro (sem Vue nem
 * Inertia) para poder ser exercitado fora do navegador.
 *
 * Cada degrau aponta para uma pagina que o MESMO usuario consegue abrir:
 *
 *  - area interna (servidor): Capacitacoes, Meus cursos, Area do Gestor;
 *  - portal do cidadao: so rotas `portal.treinamento.*`, nunca o dashboard
 *    interno, que o guard "cidadao" nao abre.
 *
 * A ordem e a da hierarquia real das paginas, e o Voltar sobe um degrau por vez.
 * O ultimo degrau e a pagina atual e nunca tem rota.
 */

/**
 * Permissao (qualquer uma) que a rota exige, quando o degrau e destino de
 * link. Espelha o middleware da rota em routes/modules/treinamento*.php: um
 * degrau que o usuario nao pode abrir perde o link em vez de levar a um 403.
 */
export const ACESSO_DA_ROTA = {
    'treinamentos.index': ['treinamento.cursos.view'],
    'treinamentos.show': ['treinamento.cursos.view'],
    'treinamentos.aluno.index': ['treinamento.cursos.view'],
    'treinamentos.professor.index': ['treinamento.professor.acessar', 'treinamento.cursos.edit'],
};

const aqui = (label) => ({ label, route: null });

// Sem o argumento o degrau e uma rota sem parametro. Com id ausente, o Ziggy
// lancaria por falta do parametro: o degrau fica sem link.
const degrau = (label, route, id) => {
    if (id === undefined) {
        return { label, route };
    }

    return id === null || id === '' ? { label, route: null } : { label, route, params: id };
};

const inicio = { label: 'Início', route: 'dashboard' };
const inicioPortal = { label: 'Início', route: 'portal.treinamento.catalogo' };
const capacitacoes = degrau('Capacitações', 'treinamentos.index');
const meusCursos = degrau('Meus cursos', 'treinamentos.aluno.index');
const areaDoGestor = degrau('Área do Gestor', 'treinamentos.professor.index');
const cursosEEventos = degrau('Cursos e Eventos', 'portal.treinamento.catalogo');
const minhasInscricoesPortal = degrau('Minhas inscrições', 'portal.treinamento.inscricoes.index');

/** Id do curso que a tela mostra: `curso` (sala e area do gestor) ou `treinamento`. */
const idDoCurso = (props) => props?.curso?.id ?? props?.treinamento?.id ?? null;

const detalhes = (props) => degrau('Detalhes', 'treinamentos.show', idDoCurso(props));

/**
 * Telas com pastas de categoria (?categoria=VALOR): dentro de uma pasta, a
 * lista raiz vira degrau com link (sem a categoria) e a pasta e a pagina atual.
 * O rotulo vem do item `ativa` de `props.categorias`, montado pelo servidor
 * a partir do enum: o front nao repete os rotulos.
 */
const comPasta = (prefixo, raiz, props) => {
    const pasta = props?.categorias?.find((c) => c.ativa);

    return pasta ? [...prefixo, raiz, aqui(pasta.rotulo)] : [...prefixo, aqui(raiz.label)];
};

const trilhas = {
    'Treinamento/TreinamentoIndex': () => [inicio, aqui('Capacitações')],
    'Treinamento/Indicadores/Index': () => [inicio, capacitacoes, aqui('Painel')],
    'Treinamento/ValidarParticipante/Index': () => [inicio, capacitacoes, aqui('Validar participante')],
    'Treinamento/TreinamentoCreate': () => [inicio, capacitacoes, aqui('Nova capacitação')],
    'Treinamento/TreinamentoShow': () => [inicio, capacitacoes, aqui('Detalhes')],
    'Treinamento/TreinamentoEdit': (props) => [inicio, capacitacoes, detalhes(props), aqui('Edição')],
    'Treinamento/Inscricoes/Index': (props) => [inicio, capacitacoes, detalhes(props), aqui('Inscrições')],
    'Treinamento/Certificados/Index': (props) => [inicio, capacitacoes, detalhes(props), aqui('Certificados')],
    'Treinamento/Gestao/Show': (props) => [inicio, capacitacoes, detalhes(props), aqui('Gestão')],

    'Treinamento/Aluno/MeusCursos': (props) => comPasta([inicio, capacitacoes], meusCursos, props),
    'Treinamento/Aluno/SalaDeAula': () => [inicio, capacitacoes, meusCursos, aqui('Sala de aula')],

    'Treinamento/Professor/MeusCursos': (props) => comPasta([inicio, capacitacoes], areaDoGestor, props),
    'Treinamento/Professor/Conteudo': () => [inicio, capacitacoes, areaDoGestor, aqui('Conteúdo')],
    'Treinamento/Professor/Provas': () => [inicio, capacitacoes, areaDoGestor, aqui('Provas')],
    'Treinamento/Professor/Correcoes': () => [inicio, capacitacoes, areaDoGestor, aqui('Correções')],
    'Treinamento/Professor/Alunos': () => [inicio, capacitacoes, areaDoGestor, aqui('Inscritos')],
    'Treinamento/Professor/Previa': () => [inicio, capacitacoes, areaDoGestor, aqui('Prévia')],
    'Treinamento/Professor/Gestao': () => [inicio, capacitacoes, areaDoGestor, aqui('Gestão')],

    'Treinamento/Portal/Catalogo': (props) => comPasta([inicioPortal], cursosEEventos, props),
    'Treinamento/Portal/Detalhe': () => [inicioPortal, cursosEEventos, aqui('Detalhes')],
    'Treinamento/Portal/MinhasInscricoes': () => [inicioPortal, aqui('Minhas inscrições')],
    'Treinamento/Portal/Certificados': () => [inicioPortal, aqui('Certificados')],
    'Treinamento/Portal/SalaDeAula': () => [inicioPortal, minhasInscricoesPortal, aqui('Sala de aula')],
};

/** Componentes cobertos por esta trilha (para o teste varrer as telas). */
export const COMPONENTES_DO_TREINAMENTO = Object.keys(trilhas);

/** Rota da pagina inicial do portal do cidadao (o icone de casa do cabecalho). */
export const INICIO_DO_PORTAL = inicioPortal.route;

/**
 * Trilha do componente, ou null se a tela nao for de Capacitacoes.
 *
 * @param {string} componente nome da pagina Inertia
 * @param {object} props props da pagina (traz o id do curso)
 * @param {(permissoes: string[]) => boolean} podeAbrir true se o usuario tem
 *        alguma das permissoes; sem ela o degrau perde o link
 */
export function trilhaTreinamento(componente, props = {}, podeAbrir = () => true) {
    const montar = trilhas[componente];

    if (!montar) {
        return null;
    }

    return montar(props).map((item) => {
        const exigidas = item.route ? ACESSO_DA_ROTA[item.route] : null;

        return exigidas && !podeAbrir(exigidas) ? { label: item.label, route: null } : item;
    });
}
