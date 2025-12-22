export class PaeHistoricoEvento {
  constructor(raw = {}) {
    this.id = raw.id ?? null;
    this.tipo = raw.tipo ?? 'criacao';
    this.titulo = raw.titulo ?? '';
    this.descricao = raw.descricao ?? '';
    this.dataISO = raw.dataISO ?? null;
    this.data = raw.data ?? '';
    this.responsavel = raw.responsavel ?? '';
  }
}


