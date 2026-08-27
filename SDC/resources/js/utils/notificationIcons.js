import {
    AcademicCapIcon,
    ArchiveBoxIcon,
    BeakerIcon,
    BellIcon,
    BuildingLibraryIcon,
    CheckBadgeIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    CloudIcon,
    DocumentTextIcon,
    HeartIcon,
    LifebuoyIcon,
    MapIcon,
    TruckIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

/**
 * Traduz o nome de icone que vem de config/notificacoes.php no componente Vue.
 *
 * O backend manda a STRING 'DocumentTextIcon'. Passar essa string direto para
 * <component :is> faz o Vue tratar como elemento nativo <documenttexticon>, que
 * o navegador renderiza como caixa vazia -- era a bolinha sem desenho ao lado de
 * cada modulo na tela de preferencias.
 *
 * O mapa e explicito, e nao um import dinamico, porque o Vite precisa enxergar
 * os nomes em build para incluir so estes icones no bundle.
 */
const ICONES = {
    AcademicCapIcon,
    ArchiveBoxIcon,
    BeakerIcon,
    BellIcon,
    BuildingLibraryIcon,
    CheckBadgeIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    CloudIcon,
    DocumentTextIcon,
    HeartIcon,
    LifebuoyIcon,
    MapIcon,
    TruckIcon,
    UserGroupIcon,
};

/**
 * Componente do icone, caindo no sino quando o nome nao esta mapeado. Modulo
 * novo no config nunca quebra a tela: no pior caso aparece com o icone generico.
 */
export function iconeDeNotificacao(nome) {
    return ICONES[nome] ?? BellIcon;
}

export default ICONES;
