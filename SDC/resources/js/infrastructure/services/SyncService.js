import axios from 'axios';
import { db } from '../database/db';

export const SyncService = {
    async syncPendente() {
        if (!navigator.onLine) return;

        try {
            const pendentes = await db.rat_pendentes
                .where('sync_status')
                .equals('pending')
                .toArray();

            if (pendentes.length === 0) {
                return;
            }

            for (const rat of pendentes) {
                try {
                    // Tenta enviar para o backend
                    await axios.post('/rat/sync', rat);
                    
                    // Se sucesso, remove do banco local
                    await db.rat_pendentes.delete(rat.id);
                    
                    // Opcional: Disparar evento para atualizar a UI
                    window.dispatchEvent(new CustomEvent('rat-synced', { detail: rat.id }));
                } catch (error) {
                    // Tratamento específico para Erro 422 (Validação) ou 403 (Permissão)
                    if (error.response && [403, 422].includes(error.response.status)) {;
                        
                        // Atualiza o status no Dexie para 'error' para não tentar enviar novamente infinitamente
                        await db.rat_pendentes.update(rat.id, { 
                            sync_status: 'error',
                            sync_error: error.response.data.message || 'Erro de validação ou permissão'
                        });
                        
                        // Notifica o usuário
                        alert(`Erro ao sincronizar relatório: ${error.response.data.message || 'Dados inválidos.'}`);
                    }
                    // Outros erros (500, rede) serão tentados novamente na próxima janela 'online'
                }
            }
        } catch (error) {
            // sync error handled silently
        }
    },

    init() {
        window.addEventListener('online', () => {
            this.syncPendente();
        });
        
        // Tenta sincronizar ao iniciar também, caso já tenha voltado
        if (navigator.onLine) {
            this.syncPendente();
        }
    }
};
