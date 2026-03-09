import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useTabs } from './core/useTabs';

/**
 * Composable central do módulo RAT.
 *
 * Gerencia estado reativo de rat, recursos, envolvidos, vistoria,
 * histórico, anexos e abas. Provê funções de navegação/persistência.
 */
export function useRat({
    rat: initialRat = null,
    recursos: initialRecursos = [],
    envolvidos: initialEnvolvidos = [],
    vistoria: initialVistoria = {},
    historyEvents: initialHistoryEvents = [],
    anexos: initialAnexos = [],
    activeTab = 1,
} = {}) {
    // Estado reativo derivado dos props Inertia
    const rat           = ref(initialRat ?? null);
    const recursos      = ref(Array.isArray(initialRecursos) ? [...initialRecursos] : (initialRecursos ?? {}));
    const envolvidos    = ref([...(Array.isArray(initialEnvolvidos) ? initialEnvolvidos : [])]);
    const vistoria      = ref({ ...(initialVistoria ?? {}) });
    const historyEvents = ref(Array.isArray(initialHistoryEvents) ? [...initialHistoryEvents] : (initialHistoryEvents ?? {}));
    const anexos        = ref([...(Array.isArray(initialAnexos) ? initialAnexos : [])]);

    // Sistema de abas
    const tabs = useTabs(activeTab);

    /**
     * Finaliza o RAT (status → em_andamento), persistindo todos os dados.
     * @param {Object} formData  - dados do formulário principal (dadosGerais, comunicacao, local, endereco)
     */
    function saveRat(formData = {}) {
        const data = {
            dadosGerais: formData.dadosGerais  ?? {},
            comunicacao: formData.comunicacao  ?? {},
            local:       formData.local        ?? {},
            endereco:    formData.endereco      ?? {},
            recursos:    recursos.value,
            envolvidos:  envolvidos.value,
            vistoria:    vistoria.value,
            historico:   historyEvents.value,
        };
        if (!rat.value?.id) {
            router.post(route('rat.store'), data, { preserveScroll: true });
        } else {
            router.put(route('rat.update', rat.value.id), data, { preserveScroll: true });
        }
    }

    /**
     * Salva o RAT como rascunho (status mantido em rascunho).
     * @param {Object} formData  - dados do formulário principal
     */
    function saveDraft(formData = {}) {
        const data = {
            dadosGerais: formData.dadosGerais  ?? {},
            comunicacao: formData.comunicacao  ?? {},
            local:       formData.local        ?? {},
            endereco:    formData.endereco      ?? {},
            recursos:    recursos.value,
            envolvidos:  envolvidos.value,
            vistoria:    vistoria.value,
            historico:   historyEvents.value,
        };
        if (!rat.value?.id) {
            router.post(route('rat.store'), data, { preserveScroll: true });
        } else {
            router.patch(route('rat.draft', rat.value.id), data, { preserveScroll: true });
        }
    }

    /**
     * Cancela e retorna para a listagem.
     */
    function cancelRat() {
        router.visit(route('rat.index'));
    }

    return {
        rat,
        recursos,
        envolvidos,
        vistoria,
        historyEvents,
        anexos,
        tabs,
        saveRat,
        saveDraft,
        cancelRat,
    };
}
