export class Demanda {
  constructor(raw = {}) {
    this.id = raw.id ?? null;
    this.titulo = raw.titulo ?? '';
    this.status = raw.status ?? 'aberta'; // aberta|em_andamento|concluida|cancelada
    this.prioridade = raw.prioridade ?? 'media'; // baixa|media|alta
    this.criadaEm = raw.criadaEm ?? null;
    this.atualizadaEm = raw.atualizadaEm ?? null;
  }
}


