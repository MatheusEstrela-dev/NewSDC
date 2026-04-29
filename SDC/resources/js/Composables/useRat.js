import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useTabs } from './core/useTabs';

/**
 * Composable central do módulo RAT.
 * Gerencia estado reativo, persistência via Inertia e feedback ao usuário.
 */
export function useRat({
    rat: initialRat = null,
    recursos: initialRecursos = [],
    envolvidos: initialEnvolvidos = [],
    vistoria: initialVistoria = {},
    historico: historicoInicial = [],
    anexos: initialAnexos = [],
    activeTab = 1,
} = {}) {

    const rat        = ref(initialRat ?? null);
    const recursos   = ref(Array.isArray(initialRecursos) ? [...initialRecursos] : []);
    const envolvidos = ref([...(Array.isArray(initialEnvolvidos) ? initialEnvolvidos : [])]);
    const vistoria   = ref({ ...(initialVistoria ?? {}) });
    const historico  = ref(Array.isArray(historicoInicial) ? [...historicoInicial] : []);
    const anexos     = ref([...(Array.isArray(initialAnexos) ? initialAnexos : [])]);
    const loading    = ref(false);

    const tabs = useTabs(activeTab);

    // ─── Helpers ─────────────────────────────────────────────────────────────

    function buildPayload(formData = {}) {
        return {
            dadosGerais: formData.dadosGerais ?? {},
            comunicacao: formData.comunicacao ?? {},
            local:       formData.local       ?? {},
            endereco:    formData.endereco     ?? {},
            recursos:    recursos.value,
            envolvidos:  envolvidos.value,
            vistoria:    vistoria.value,
            historico:   historico.value,
        };
    }

    function requestOptions(extra = {}) {
        return {
            preserveScroll: true,
            onStart:  () => { loading.value = true; },
            onFinish: () => { loading.value = false; },
            ...extra,
        };
    }

    // ─── Ações principais ────────────────────────────────────────────────────

    /**
     * Salva/atualiza o RAT completo.
     */
    function salvarRat(formData = {}) {
        const data = buildPayload(formData);
        if (!rat.value?.id) {
            router.post(route('rat.store'), data, requestOptions());
        } else {
            router.put(route('rat.update', rat.value.id), data, requestOptions());
        }
    }

    /**
     * Salva como rascunho (não altera status para em_andamento).
     */
    function salvarRascunho(formData = {}) {
        const data = buildPayload(formData);
        if (!rat.value?.id) {
            router.post(route('rat.store'), data, requestOptions());
        } else {
            router.patch(route('rat.draft', rat.value.id), data, requestOptions());
        }
    }

    /**
     * Finaliza o RAT (status → finalizado).
     */
    function finalizarRat(formData = {}) {
        const data = buildPayload(formData);

        if (!rat.value?.id) {
            router.post(
                route('rat.store'),
                { ...data, finalize: true },
                requestOptions(),
            );
            return;
        }

        if (!confirm('Deseja finalizar este RAT? Esta ação não poderá ser desfeita.')) return;

        router.patch(route('rat.finalize', rat.value.id), data, requestOptions());
    }

    /**
     * Exclui o RAT após confirmação.
     */
    function excluirRat(id) {
        if (!confirm('Tem certeza que deseja excluir este RAT? Esta ação é irreversível.')) return;
        router.delete(route('rat.destroy', id), requestOptions({
            onSuccess: () => router.visit(route('rat.index')),
        }));
    }

    /**
     * Cancela e volta para a listagem.
     */
    function cancelarRat() {
        router.visit(route('rat.index'));
    }

    // ─── Recursos ────────────────────────────────────────────────────────────

    function adicionarRecurso(recurso) {
        if (!Array.isArray(recursos.value)) recursos.value = [];
        recursos.value.push(recurso);
    }

    function removerRecurso(id) {
        if (!Array.isArray(recursos.value)) return;
        const i = recursos.value.findIndex(r => r.id === id);
        if (i > -1) recursos.value.splice(i, 1);
    }

    function atualizarRecursos(data) {
        recursos.value = Array.isArray(data) ? [...data] : [];
    }

    // ─── Envolvidos ──────────────────────────────────────────────────────────

    function adicionarEnvolvido(e) {
        envolvidos.value.push(e);
    }

    function removerEnvolvido(id) {
        const i = envolvidos.value.findIndex(e => e.id === id);
        if (i > -1) envolvidos.value.splice(i, 1);
    }

    function atualizarEnvolvidos(data) {
        envolvidos.value = Array.isArray(data) ? [...data] : [];
    }

    // ─── Vistoria ────────────────────────────────────────────────────────────

    function atualizarVistoria(data) {
        vistoria.value = { ...vistoria.value, ...data };
    }

    // ─── Histórico ───────────────────────────────────────────────────────────

    function adicionarObservacao(obs) {
        if (!Array.isArray(historico.value)) historico.value = [];
        historico.value.unshift({
            id: Date.now(),
            ...obs,
            created_at: new Date().toISOString(),
        });
    }

    function atualizarHistorico(data) {
        historico.value = Array.isArray(data) ? [...data] : [];
    }

    // ─── Anexos ──────────────────────────────────────────────────────────────

    function adicionarAnexo(anexo) {
        if (!Array.isArray(anexos.value)) anexos.value = [];
        anexos.value.push(anexo);
    }

    function removerAnexo(id) {
        if (!Array.isArray(anexos.value)) return;
        const i = anexos.value.findIndex(a => a.id === id);
        if (i > -1) anexos.value.splice(i, 1);
    }

    function atualizarAnexos(data) {
        if (Array.isArray(data)) anexos.value = [...data];
    }

    return {
        rat,
        recursos,
        envolvidos,
        vistoria,
        historico,
        anexos,
        loading,
        tabs,
        salvarRat,
        salvarRascunho,
        finalizarRat,
        excluirRat,
        cancelarRat,
        adicionarRecurso,
        removerRecurso,
        atualizarRecursos,
        adicionarEnvolvido,
        removerEnvolvido,
        atualizarEnvolvidos,
        atualizarVistoria,
        adicionarObservacao,
        atualizarHistorico,
        adicionarAnexo,
        removerAnexo,
        atualizarAnexos,
    };
}
