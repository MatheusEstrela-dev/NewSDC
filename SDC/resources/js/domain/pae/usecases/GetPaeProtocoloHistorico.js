import { PaeHistoricoEvento } from '@/domain/pae/entities/PaeHistoricoEvento';

export class GetPaeProtocoloHistorico {
  constructor(repository) {
    this.repository = repository;
  }

  async execute(protocoloId) {
    const payload = await this.repository.getHistorico(protocoloId);
    const timeline = (payload?.timeline || []).map((e) => new PaeHistoricoEvento(e));

    return {
      ...payload,
      timeline,
    };
  }
}


