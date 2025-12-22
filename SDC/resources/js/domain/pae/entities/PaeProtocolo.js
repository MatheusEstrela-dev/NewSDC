export class PaeProtocolo {
  constructor(raw = {}) {
    this.id = raw.id ?? null;
    this.protocoloNumero = raw.protocoloNumero ?? '';
    this.estrutura = raw.estrutura ?? '';
    this.empreendedor = raw.empreendedor ?? '';
    this.analista = raw.analista ?? '';
    this.situacao = raw.situacao ?? 'aguardando_analise';
    this.dataEntradaISO = raw.dataEntradaISO ?? null;
    this.limiteAnaliseISO = raw.limiteAnaliseISO ?? null;
    this.dataEntrada = raw.dataEntrada ?? '';
    this.limiteAnalise = raw.limiteAnalise ?? '';
    this.prazo = raw.prazo ?? 'ok'; // ok|proximo|vencido
    this.ccpae = Boolean(raw.ccpae);
    this.notificacoes = Number(raw.notificacoes ?? 0);
    this.analises = Number(raw.analises ?? 0);
  }
}


