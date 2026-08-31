import { useForm, router } from '@inertiajs/vue3';

/**
 * Formulario da vistoria, para as tres etapas.
 *
 * O que muda por etapa e apenas o que o StoreVistoriaRequest exige ou proibe:
 * numero de instalacao so no fornecedor, e processo/contrato/empenho/placa/ART so
 * na CEDEC. Campo proibido NAO pode ir no payload -- a regra e `prohibited`, e
 * enviar vazio ja reprova a validacao.
 */
export function useVistoriaForm(beneficiarioId, etapa, vistoria = null) {
  const form = useForm(camposIniciais(beneficiarioId, etapa, vistoria));

  function anexar({ campo, arquivo }) {
    form[campo] = arquivo;
  }

  function salvar() {
    // Update com arquivo vai por POST + _method: o PHP nao popula $_FILES em PUT.
    if (vistoria?.id) {
      form.transform((dados) => ({ ...limpar(dados, etapa), _method: 'put' }))
        .post(route('cisternas.vistorias.update', vistoria.id), {
          forceFormData: true,
          preserveScroll: true,
        });

      return;
    }

    form.transform((dados) => limpar(dados, etapa))
      .post(route('cisternas.vistorias.store'), {
        forceFormData: true,
        preserveScroll: true,
      });
  }

  function cancelar() {
    router.visit(route('cisternas.vistorias.index', beneficiarioId));
  }

  return { form, anexar, salvar, cancelar };
}

/**
 * Tira do payload o que a etapa proibe e normaliza o checklist.
 *
 * Sem isto, uma vistoria de fornecedor mandaria `processo_sei: ''` e levaria erro
 * de campo proibido -- num campo que a tela nem mostra, o que seria impossivel de
 * entender pela mensagem.
 */
function limpar(dados, etapa) {
  const payload = { ...dados };

  if (etapa !== 'fornecedor') {
    delete payload.numero_instalacao;
  }

  if (etapa !== 'cedec') {
    ['processo_sei', 'contrato', 'empenho', 'placa_obras', 'engenheiro_art']
      .forEach((campo) => delete payload[campo]);
  }

  // Item nao conferido nao vai: o checklist do dominio guarda uma linha por item
  // avaliado, e mandar os 13 sempre criaria 13 linhas para toda vistoria.
  payload.itens = Object.fromEntries(
    Object.entries(payload.itens ?? {}).filter(([, linha]) => linha?.conferido === true),
  );

  return payload;
}

function camposIniciais(beneficiarioId, etapa, vistoria) {
  const engenheiro = vistoria?.engenheiro ?? {};
  const local = vistoria?.local ?? {};
  const administrativos = vistoria?.dados_administrativos ?? {};

  return {
    beneficiario_id: beneficiarioId,
    etapa,

    engenheiro_nome: engenheiro.nome ?? '',
    engenheiro_crea: engenheiro.crea ?? '',
    engenheiro_art: engenheiro.art ?? '',
    data_relatorio: vistoria?.data_relatorio ?? '',
    local_relatorio: vistoria?.local_relatorio ?? '',
    numero_instalacao: vistoria?.numero_instalacao ?? '',

    processo_sei: administrativos.processo_sei ?? '',
    contrato: administrativos.contrato ?? '',
    empenho: administrativos.empenho ?? '',
    placa_obras: administrativos.placa_obras ?? '',

    endereco: local.endereco ?? '',
    bairro: local.bairro ?? '',
    latitude: local.latitude ?? '',
    longitude: local.longitude ?? '',

    itens: doResource(vistoria?.itens),
    observacoes: vistoria?.observacoes ?? '',

    assinatura_engenheiro: null,
  };
}

/**
 * O resource devolve os itens como LISTA; o formulario e o request trabalham com
 * objeto indexado pelo item. Sem converter, o checklist abriria vazio numa
 * vistoria que tem itens gravados.
 */
function doResource(itens) {
  const mapa = {};

  (itens ?? []).forEach((i) => {
    mapa[i.item] = {
      conferido: Boolean(i.conferido),
      quantidade: i.quantidade ?? '',
      detalhes: i.detalhes ?? null,
      observacao: i.observacao ?? '',
    };
  });

  return mapa;
}
