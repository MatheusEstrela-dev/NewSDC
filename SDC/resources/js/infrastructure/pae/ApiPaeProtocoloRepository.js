import { PaeProtocoloRepository } from '@/domain/pae/repositories/PaeProtocoloRepository';

export class ApiPaeProtocoloRepository extends PaeProtocoloRepository {
  async list(_params = {}) {
    return [];
  }

  async getHistorico(protocoloId) {
    const response = await fetch(route('pae.protocolos.historico', { paeProtocolo: protocoloId }), {
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      throw new Error('Erro ao buscar histórico do protocolo.');
    }

    return response.json();
  }
}
