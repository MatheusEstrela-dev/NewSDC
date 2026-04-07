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
    historico: historicoInicial = [],
    anexos: initialAnexos = [],
    activeTab = 1,
} = {}) {
    // Estado reativo derivado dos props Inertia
    const rat           = ref(initialRat ?? null);
    const recursos      = ref(Array.isArray(initialRecursos) ? [...initialRecursos] : (initialRecursos ?? {}));
    const envolvidos    = ref([...(Array.isArray(initialEnvolvidos) ? initialEnvolvidos : [])]);
    const vistoria      = ref({ ...(initialVistoria ?? {}) });
    const historico = ref(Array.isArray(historicoInicial) ? [...historicoInicial] : (historicoInicial ?? {}));
    const anexos        = ref([...(Array.isArray(initialAnexos) ? initialAnexos : [])]);

    // Sistema de abas
    const tabs = useTabs(activeTab);

    /**
     * Finaliza o RAT (status → em_andamento), persistindo todos os dados.
     * @param {Object} formData  - dados do formulário principal (dadosGerais, comunicacao, local, endereco)
     */
    function salvarRat(formData = {}) {
        const data = {
            dadosGerais: formData.dadosGerais  ?? {},
            comunicacao: formData.comunicacao  ?? {},
            local:       formData.local        ?? {},
            endereco:    formData.endereco      ?? {},
            recursos:    recursos.value,
            envolvidos:  envolvidos.value,
            vistoria:    vistoria.value,
            historico:   historico.value,
        };
        if (!rat.value?.id) {
            router.post(route('compdec.rat.store'), data, { preserveScroll: true });
        } else {
            router.put(route('compdec.rat.update', rat.value.id), data, { preserveScroll: true });
        }
    }

    /**
     * Salva o RAT como rascunho (status mantido em rascunho).
     * @param {Object} formData  - dados do formulário principal
     */
    function salvarRascunho(formData = {}) {
        const data = {
            dadosGerais: formData.dadosGerais  ?? {},
            comunicacao: formData.comunicacao  ?? {},
            local:       formData.local        ?? {},
            endereco:    formData.endereco      ?? {},
            recursos:    recursos.value,
            envolvidos:  envolvidos.value,
            vistoria:    vistoria.value,
            historico:   historico.value,
        };
        if (!rat.value?.id) {
            router.post(route('compdec.rat.store'), data, { preserveScroll: true });
        } else {
            router.patch(route('compdec.rat.draft', rat.value.id), data, { preserveScroll: true });
        }
    }

    /**
     * Finaliza o RAT — salva todos os dados e muda status para FINALIZADO.
     * @param {Object} formData  - dados do formulário principal
     */
    function finalizarRat(formData = {}) {
        const data = {
            dadosGerais: formData.dadosGerais  ?? {},
            comunicacao: formData.comunicacao  ?? {},
            local:       formData.local        ?? {},
            endereco:    formData.endereco      ?? {},
            recursos:    recursos.value,
            envolvidos:  envolvidos.value,
            vistoria:    vistoria.value,
            historico:   historico.value,
        };
        if (!rat.value?.id) {
            // Página de criação: cria e finaliza em uma única requisição
            router.post(route('compdec.rat.store'), { ...data, finalize: true }, { preserveScroll: true });
            return;
        }
        router.patch(route('compdec.rat.finalize', rat.value.id), data, { preserveScroll: true });
    }

    /**
     * Cancela e retorna para a listagem.
     */
    function cancelarRat() {
        router.visit(route('compdec.rat.index'));
    }

    // Recursos
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
        recursos.value = data;
    }

    // Envolvidos
    function adicionarEnvolvido(e) {
        envolvidos.value.push(e);
    }
    function removerEnvolvido(id) {
        const i = envolvidos.value.findIndex(e => e.id === id);
        if (i > -1) envolvidos.value.splice(i, 1);
    }
    function atualizarEnvolvidos(data) {
        envolvidos.value = Array.isArray(data) ? data : [];
    }

    // Vistoria
    function atualizarVistoria(data) {
        vistoria.value = { ...vistoria.value, ...data };
    }

    // Historico
    function adicionarObservacao(obs) {
        if (!Array.isArray(historico.value)) historico.value = [];
        historico.value.unshift({ id: Date.now(), ...obs, created_at: new Date().toISOString() });
    }
    function atualizarHistorico(data) {
        historico.value = data;
    }

    // Anexos
    function adicionarAnexo(anexo) {
        if (!anexos.value) anexos.value = [];
        anexos.value.push(anexo);
    }
    function removerAnexo(id) {
        if (!anexos.value) return;
        const i = anexos.value.findIndex(a => a.id === id);
        if (i > -1) anexos.value.splice(i, 1);
    }
    function atualizarAnexos(data) {
        anexos.value = data;
    }

    return {
        rat,
        recursos,
        envolvidos,
        vistoria,
        historico,
        anexos,
        tabs,
        salvarRat,
        salvarRascunho,
        finalizarRat,
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
