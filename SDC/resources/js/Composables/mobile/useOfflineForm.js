import { ref } from 'vue';
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { db } from '@/infrastructure/database/db';
import { v4 as uuidv4 } from 'uuid';

/**
 * Composable para gerenciar formulários com suporte offline
 * @param {Object} initialData - Dados iniciais do formulário
 * @param {String} table - Nome da tabela no Dexie (ex: 'rat_pendentes')
 * @param {String} routeName - Nome da rota Laravel para envio online (ex: 'rat.store')
 */
export function useOfflineForm(initialData, table = 'rat_pendentes', routeName) {
    // Adiciona ID se não existir
    const dataWithId = {
        id: initialData.id || uuidv4(),
        ...initialData
    };

    const form = useInertiaForm(dataWithId);
    const isOffline = ref(!navigator.onLine);

    // Atualiza status online/offline
    window.addEventListener('online', () => isOffline.value = false);
    window.addEventListener('offline', () => isOffline.value = true);

    const submit = async (options = {}) => {
        if (!navigator.onLine) {
            try {
                // Salva no IndexedDB
                await db.table(table).add({
                    ...form.data(),
                    sync_status: 'pending',
                    created_at: new Date().toISOString()
                });

                // Simula sucesso do Inertia para a UI
                form.reset();
                form.clearErrors();
                
                // Feedback visual (pode ser substituído por um sistema de toast/notificação)
                alert('Sem conexão. Dados salvos localmente e serão enviados assim que possível.');
                
                if (options.onSuccess) {
                    options.onSuccess();
                }
            } catch (error) {
                console.error('Erro ao salvar offline:', error);
                if (options.onError) {
                    options.onError(error);
                }
            }
        } else {
            // Envio normal via Inertia
            form.post(route(routeName), {
                ...options,
                onSuccess: (page) => {
                    // Se o servidor retornar que salvou, ótimo.
                    if (options.onSuccess) options.onSuccess(page);
                }
            });
        }
    };

    return {
        form,
        submit,
        isOffline
    };
}
