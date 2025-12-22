/**
 * Contrato do repositório de Protocolos PAE.
 * Implementações podem ser API (futuro) ou Mock (agora).
 */
export class PaeProtocoloRepository {
  /**
   * @returns {Promise<Array<object>>}
   */
  async list(_params = {}) {
    throw new Error('Not implemented');
  }

  /**
   * @returns {Promise<object>}
   */
  async getHistorico(_protocoloId) {
    throw new Error('Not implemented');
  }
}


