import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

// Espelham AvisoDeCursos::RECURSO_CURSOS e RECURSO_PROFESSOR.
const CANAL_CURSOS = 'listagem.treinamento-cursos';
const CANAL_PROFESSOR = 'listagem.treinamento-professor';

const EVENTO = '.RecursoAtualizado';
const INTERVALO_SEM_TEMPO_REAL_MS = 20000;

/**
 * Mantem uma tela de cursos em dia quando curso ou inscricao mudam.
 *
 * @param {string[]} props Props a rebuscar, no formato do Inertia.
 * @param {object}   [opcoes]
 * @param {boolean}  [opcoes.professor] Area do professor: o professor designado
 *                                      nao autoriza o canal de cursos, entao a
 *                                      tela assina tambem o canal dele.
 */
export function useCursosAoVivo(props, { professor = false } = {}) {
    useAtualizacaoAoVivo({
        canal: CANAL_CURSOS,
        evento: EVENTO,
        props,
        intervaloSemTempoRealMs: INTERVALO_SEM_TEMPO_REAL_MS,
    });

    // Sem a reserva aqui: uma por tela basta, e a do canal de cursos ja cobre.
    if (professor) {
        useAtualizacaoAoVivo({ canal: CANAL_PROFESSOR, evento: EVENTO, props });
    }
}
