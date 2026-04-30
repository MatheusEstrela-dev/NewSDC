export class PaeProtocolo {
  constructor(raw = {}) {
    this.id = raw.id ?? null;
    this.protocoloNumero = raw.protocoloNumero ?? raw.num_protocolo ?? '';
    this.estrutura = raw.estrutura ?? raw.empnto_search ?? '';
    this.empreendedor = raw.empreendedor ?? '';
    this.analista = raw.analista ?? raw.analistaAtual?.name ?? '';
    this.situacao = raw.situacao ?? raw.status ?? 'novo';
    this.statusLabel = raw.statusLabel ?? raw.status_label ?? '';
    this.dataEntradaISO = raw.dataEntradaISO ?? raw.dt_entrada ?? null;
    this.limiteAnaliseISO = raw.limiteAnaliseISO ?? raw.limite_analise ?? null;
    this.dataEntrada = raw.dataEntrada ?? '';
    this.limiteAnalise = raw.limiteAnalise ?? '';
    this.prazo = raw.prazo ?? 'ok';
    this.ccpae = Boolean(raw.ccpae);
    this.notificacoes = Number(raw.notificacoes ?? 0);
    this.analises = Number(raw.analises ?? 0);
  }
}
