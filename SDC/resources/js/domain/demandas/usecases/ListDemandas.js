import { Demanda } from '@/domain/demandas/entities/Demanda';

export class ListDemandas {
  constructor(repository) {
    this.repository = repository;
  }

  async execute(params = {}) {
    const rows = await this.repository.list(params);
    return (rows || []).map((r) => new Demanda(r));
  }
}


