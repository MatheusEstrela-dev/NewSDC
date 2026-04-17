// Composable melhorado para RAT - integração completa com backend
// Arquivo: resources/js/Composables/useRat.js

import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Composable central do módulo RAT.
 *
 * Gerencia estado reativo e integração com API REST.
 * Todos os dados são persistidos no banco de dados.
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
    // Estado reativo
    const rat = ref(initialRat ?? null);
    const recursos = ref(Array.isArray(initialRecursos) ? [...initialRecursos] : []);
    const envolvidos = ref(Array.isArray(initialEnvolvidos) ? [...initialEnvolvidos] : []);
    const vistoria = ref({ ...(initialVistoria ?? {}) });
    const historico = ref(Array.isArray(historicoInicial) ? [...historicoInicial] : []);
    const anexos = ref(Array.isArray(initialAnexos) ? [...initialAnexos] : []);

    // Estado de carregamento e erro
    const loading = ref(false);
    const errors = ref({});

    /**
     * Salva dados gerais da ocorrência.
     * POST /compdec/rat/ocorrencias/{id}/dados-gerais
     */
    async function saveDadosGerais(ratId, dadosGerais) {
        loading.value = true;
        errors.value = {};

        try {
            await fetch(route('compdec.rat.ocorrencias.dados-gerais.store', ratId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify(dadosGerais),
            }).then(r => r.json());

            return { success: true };
        } catch (err) {
            errors.value = err.response?.data?.errors || { global: err.message };
            return { success: false, errors: errors.value };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Adiciona um envolvido.
     * POST /compdec/rat/ocorrencias/{id}/envolvidos
     */
    async function saveEnvolvido(ratId, envolvidoData) {
        loading.value = true;

        try {
            const response = await fetch(route('compdec.rat.ocorrencias.envolvidos.store', ratId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify(envolvidoData),
            }).then(r => r.json());

            if (response.success && response.data) {
                // Adiciona ou atualiza na lista local
                const idx = envolvidos.value.findIndex(e => e.id === response.data.id);
                if (idx > -1) {
                    envolvidos.value[idx] = response.data;
                } else {
                    envolvidos.value.push(response.data);
                }
            }

            return response;
        } catch (err) {
            errors.value = err.response?.data?.errors || { global: err.message };
            return { success: false, errors: errors.value };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Adiciona um recurso.
     * POST /compdec/rat/ocorrencias/{id}/recursos
     */
    async function saveRecurso(ratId, recursoData) {
        loading.value = true;

        try {
            const response = await fetch(route('compdec.rat.ocorrencias.recursos.store', ratId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify(recursoData),
            }).then(r => r.json());

            if (response.success && response.data) {
                const idx = recursos.value.findIndex(r => r.id === response.data.id);
                if (idx > -1) {
                    recursos.value[idx] = response.data;
                } else {
                    recursos.value.push(response.data);
                }
            }

            return response;
        } catch (err) {
            errors.value = err.response?.data?.errors || { global: err.message };
            return { success: false, errors: errors.value };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Salva vistoria técnica.
     * POST /compdec/rat/ocorrencias/{id}/vistoria
     */
    async function saveVistoria(ratId, vistoriaData) {
        loading.value = true;

        try {
            const response = await fetch(route('compdec.rat.ocorrencias.vistoria.store', ratId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify(vistoriaData),
            }).then(r => r.json());

            if (response.success && response.data) {
                vistoria.value = response.data;
            }

            return response;
        } catch (err) {
            errors.value = err.response?.data?.errors || { global: err.message };
            return { success: false, errors: errors.value };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Registra evento no histórico.
     * POST /compdec/rat/ocorrencias/{id}/historico
     */
    async function saveHistoricoEvent(ratId, eventoData) {
        loading.value = true;

        try {
            const response = await fetch(route('compdec.rat.ocorrencias.historico.store', ratId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify(eventoData),
            }).then(r => r.json());

            if (response.success) {
                historico.value.unshift(response.data || { ...eventoData, created_at: new Date().toISOString() });
            }

            return response;
        } catch (err) {
            errors.value = err.response?.data?.errors || { global: err.message };
            return { success: false, errors: errors.value };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Remove um envolvido.
     * DELETE /compdec/rat/ocorrencias/{id}/envolvidos/{envolvidoId}
     */
    async function removeEnvolvido(ratId, envolvidoId) {
        loading.value = true;

        try {
            const response = await fetch(
                route('compdec.rat.ocorrencias.envolvidos.destroy', [ratId, envolvidoId]),
                { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } }
            ).then(r => r.json());

            if (response.success) {
                envolvidos.value = envolvidos.value.filter(e => e.id !== envolvidoId);
            }

            return response;
        } catch (err) {
            errors.value = err.response?.data?.errors || { global: err.message };
            return { success: false, errors: errors.value };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Remove um recurso.
     * DELETE /compdec/rat/ocorrencias/{id}/recursos/{recursoId}
     */
    async function removeRecurso(ratId, recursoId) {
        loading.value = true;

        try {
            const response = await fetch(
                route('compdec.rat.ocorrencias.recursos.destroy', [ratId, recursoId]),
                { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } }
            ).then(r => r.json());

            if (response.success) {
                recursos.value = recursos.value.filter(r => r.id !== recursoId);
            }

            return response;
        } catch (err) {
            errors.value = err.response?.data?.errors || { global: err.message };
            return { success: false, errors: errors.value };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Cria ou atualiza RAT completo.
     * POST /compdec/rat (novo)
     * PUT /compdec/rat/{id} (atualizar)
     */
    function salvarRat(formData = {}) {
        const data = {
            dadosGerais: formData.dadosGerais ?? {},
            comunicacao: formData.comunicacao ?? {},
            local: formData.local ?? {},
            endereco: formData.endereco ?? {},
            recursos: recursos.value,
            envolvidos: envolvidos.value,
            vistoria: vistoria.value,
            historico: historico.value,
        };

        if (!rat.value?.id) {
            router.post(route('compdec.rat.store'), data, {
                preserveScroll: true,
                onError: (err) => {
                    errors.value = err;
                    console.error('Erro ao criar RAT:', err);
                },
            });
        } else {
            router.put(route('compdec.rat.update', rat.value.id), data, {
                preserveScroll: true,
                onError: (err) => {
                    errors.value = err;
                    console.error('Erro ao atualizar RAT:', err);
                },
            });
        }
    }

    /**
     * Salva como rascunho.
     * PATCH /compdec/rat/{id}/draft
     */
    function salvarRascunho(formData = {}) {
        const data = {
            dadosGerais: formData.dadosGerais ?? {},
            comunicacao: formData.comunicacao ?? {},
            local: formData.local ?? {},
            endereco: formData.endereco ?? {},
            recursos: recursos.value,
            envolvidos: envolvidos.value,
            vistoria: vistoria.value,
            historico: historico.value,
        };

        if (!rat.value?.id) {
            // Cria novo
            router.post(route('compdec.rat.store'), data, { preserveScroll: true });
        } else {
            // Salva como rascunho
            router.patch(route('compdec.rat.draft', rat.value.id), data, { preserveScroll: true });
        }
    }

    /**
     * Finaliza RAT.
     * PATCH /compdec/rat/{id}/finalize
     */
    function finalizarRat(formData = {}) {
        const data = {
            dadosGerais: formData.dadosGerais ?? {},
            comunicacao: formData.comunicacao ?? {},
            local: formData.local ?? {},
            endereco: formData.endereco ?? {},
            recursos: recursos.value,
            envolvidos: envolvidos.value,
            vistoria: vistoria.value,
            historico: historico.value,
        };

        if (!rat.value?.id) {
            router.post(route('compdec.rat.store'), { ...data, finalize: true }, {
                preserveScroll: true,
            });
        } else {
            router.patch(route('compdec.rat.finalize', rat.value.id), data, {
                preserveScroll: true,
            });
        }
    }

    /**
     * Cancela e volta para listagem.
     */
    function cancelarRat() {
        router.visit(route('compdec.rat.index'));
    }

    /**
     * Exclui RAT.
     * DELETE /compdec/rat/{id}
     */
    function deletarRat(ratId) {
        if (confirm('Tem certeza que deseja excluir este RAT?')) {
            router.delete(route('compdec.rat.destroy', ratId), {
                preserveScroll: true,
                onSuccess: () => router.visit(route('compdec.rat.index')),
            });
        }
    }

    return {
        rat,
        recursos,
        envolvidos,
        vistoria,
        historico,
        anexos,
        loading,
        errors,
        saveDadosGerais,
        saveEnvolvido,
        saveRecurso,
        saveVistoria,
        saveHistoricoEvent,
        removeEnvolvido,
        removeRecurso,
        salvarRat,
        salvarRascunho,
        finalizarRat,
        cancelarRat,
        deletarRat,
    };
}
