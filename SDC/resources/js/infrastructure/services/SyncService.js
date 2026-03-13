import axios from 'axios';
import { db } from '../database/db';

export const SyncService = {
    async syncPendente() {
        if (!navigator.onLine) return;

        console.log('Iniciando sincronização de dados...');
        
        try {
            const pendentes = await db.rat_pendentes
                .where('sync_status')
                .equals('pending')
                .toArray();

            if (pendentes.length === 0) {
                console.log('Nenhum dado pendente para sincronizar.');
                return;
            }

            for (const rat of pendentes) {
                try {
                    // Tenta enviar para o backend
                    await axios.post('/rat/sync', rat);
                    
                    // Se sucesso, remove do banco local
                    await db.rat_pendentes.delete(rat.id);
                    console.log(`RAT ${rat.id} sincronizado com sucesso!`);
                    
                    // Opcional: Disparar evento para atualizar a UI
                    window.dispatchEvent(new CustomEvent('rat-synced', { detail: rat.id }));
                } catch (error) {
                    console.error(`Falha ao sincronizar RAT ${rat.id}:`, error);

                    // Tratamento específico para Erro 422 (Validação) ou 403 (Permissão)
                    if (error.response && [403, 422].includes(error.response.status)) {
                        console.warn(`RAT ${rat.id} rejeitado pelo servidor. Marcando como erro.`);
                        
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
            console.error("Erro geral na sincronização:", error);
        }
    },

    init() {
        window.addEventListener('online', () => {
            console.log('Conexão restabelecida via SyncService.');
            this.syncPendente();
        });
        
        // Tenta sincronizar ao iniciar também, caso já tenha voltado
        if (navigator.onLine) {
            this.syncPendente();
        }
    }
};
