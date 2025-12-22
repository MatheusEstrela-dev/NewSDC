import { DemandaRepository } from '@/domain/demandas/repositories/DemandaRepository';

export class MockDemandaRepository extends DemandaRepository {
  async list() {
    return [];
  }
}


