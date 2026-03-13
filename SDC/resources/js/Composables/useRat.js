import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useTabs } from './core/useTabs';

/**
 * Composable central do módulo RAT.
 *
 * Gerencia estado reativo de recursos, envolvidos, vistoria, historico e abas.
 * Provê saveRat, saveDraft, finalizeRat, cancelRat para comunicação com o backend.
 *
 * Imagens são enviadas diretamente via API (POST /rat/{id}/imagens) — não passam por aqui.
 */
export function useRat({
    rat: initialRat = null,
    recursos: initialRecursos = {},
    envolvidos: initialEnvolvidos = [],
    vistoria: initialVistoria = {},
    historico: initialHistorico = {},
    activeTab = 1,
} = {}) {
    const rat        = ref(initialRat ?? null);
    const recursos   = ref(initialRecursos ?? {});
    const envolvidos = ref(Array.isArray(initialEnvolvidos) ? [...initialEnvolvidos] : []);
    const vistoria   = ref(typeof initialVistoria === 'object' && initialVistoria !== null ? { ...initialVistoria } : {});
    const historico  = ref(typeof initialHistorico === 'object' && !Array.isArray(initialHistorico) && initialHistorico !== null ? { ...initialHistorico } : {});

    const tabs = useTabs(activeTab);

    /** Constrói o payload com todos os dados editáveis do RAT. */
    function buildPayload(formData = {}) {
        return {
            dadosGerais: formData.dadosGerais ?? {},
            comunicacao: formData.comunicacao  ?? {},
            local:       formData.local        ?? {},
            endereco:    formData.endereco      ?? {},
            recursos:    recursos.value,
            envolvidos:  envolvidos.value,
            vistoria:    vistoria.value,
            historico:   historico.value,
        };
    }

    /**
     * Persiste todos os dados e avança status para EM_ANDAMENTO.
     * Usa PUT /rat/{id} ou POST /rat se novo.
     */
    function saveRat(formData = {}) {
        const data = buildPayload(formData);
        if (!rat.value?.id) {
            router.post(route('rat.store'), data, { preserveScroll: true });
        } else {
            router.put(route('rat.update', rat.value.id), data, { preserveScroll: true });
        }
    }

    /**
     * Salva dados mantendo status RASCUNHO.
     * Usa PATCH /rat/{id}/draft ou POST /rat se novo.
     */
    function saveDraft(formData = {}) {
        const data = buildPayload(formData);
        if (!rat.value?.id) {
            router.post(route('rat.store'), data, { preserveScroll: true });
        } else {
            router.patch(route('rat.draft', rat.value.id), data, { preserveScroll: true });
        }
    }

    /**
     * Salva dados e finaliza o RAT (status → FINALIZADO).
     * Usa PATCH /rat/{id}/finalize.
     */
    function finalizeRat(formData = {}) {
        const data = buildPayload(formData);
        if (!rat.value?.id) {
            router.post(route('rat.store'), { ...data, finalize: true }, { preserveScroll: true });
        } else {
            router.patch(route('rat.finalize', rat.value.id), data, { preserveScroll: true });
        }
    }

    /** Cancela e retorna para a listagem. */
    function cancelRat() {
        router.visit(route('rat.index'));
    }

    return {
        rat,
        recursos,
        envolvidos,
        vistoria,
        historico,
        tabs,
        saveRat,
        saveDraft,
        finalizeRat,
        cancelRat,
    };
}


