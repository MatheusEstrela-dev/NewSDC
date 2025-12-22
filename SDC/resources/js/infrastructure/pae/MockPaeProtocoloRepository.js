import { PaeProtocoloRepository } from '@/domain/pae/repositories/PaeProtocoloRepository';
import { getMockPaeProtocolos, getMockPaeHistorico } from '@/mocks/pae';

export class MockPaeProtocoloRepository extends PaeProtocoloRepository {
  async list() {
    return getMockPaeProtocolos();
  }

  async getHistorico(protocoloId) {
    return getMockPaeHistorico(protocoloId);
  }
}


