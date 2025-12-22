import { PaeProtocolo } from '@/domain/pae/entities/PaeProtocolo';

export class ListPaeProtocolos {
  constructor(repository) {
    this.repository = repository;
  }

  async execute(params = {}) {
    const rows = await this.repository.list(params);
    return (rows || []).map((r) => new PaeProtocolo(r));
  }
}


