import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

/**
 * Monta o formulario do beneficiario, em modo criar ou editar.
 *
 * Existe porque o BeneficiarioResource NAO tem o mesmo formato do
 * StoreBeneficiarioRequest: o resource agrupa os campos em blocos aninhados
 * (`criterios_sociais`, `avaliacao_tecnica`, `atendimento_pipa`,
 * `responsaveis_cadastro`) e o request espera tudo plano. Sem achatar, o Edit
 * abriria com metade dos campos vazios e o submit apagaria dado bom.
 *
 * Concentrar aqui evita a duplicacao entre Create e Edit -- que foi exatamente o
 * defeito do legado, onde `create.blade.php` e `edit.blade.php` somavam 1.121
 * linhas quase identicas e ja tinham divergido.
 */
export function useBeneficiarioForm(beneficiario = null) {
  const form = useForm(camposIniciais(beneficiario));
  const { show: toast } = useToast();

  const comunidades = ref([]);
  const carregandoComunidades = ref(false);

  /**
   * Comunidade depende do municipio escolhido. O Create nao recebe a lista (nao
   * ha municipio ainda) e o Edit recebe so a do municipio atual, entao trocar de
   * municipio exige buscar.
   */
  async function carregarComunidades(municipioId) {
    if (!municipioId) {
      comunidades.value = [];

      return;
    }

    carregandoComunidades.value = true;

    try {
      const resposta = await fetch(route('cisternas.comunidades.do-municipio', municipioId), {
        headers: { Accept: 'application/json' },
      });

      comunidades.value = resposta.ok ? await resposta.json() : [];
    } catch {
      // Falha de rede nao pode travar o formulario: o campo fica vazio e o
      // usuario segue preenchendo o resto.
      comunidades.value = [];
    } finally {
      carregandoComunidades.value = false;
    }
  }

  /**
   * Anexa arquivo ao payload. Precisa de `forceFormData`, senao o Inertia manda
   * JSON e o upload nao chega.
   */
  function anexar({ campo, arquivo }) {
    form[campo] = arquivo;
  }

  /**
   * Opcoes comuns ao criar e ao editar.
   *
   * Sem toast de SUCESSO de proposito: o controller ja redireciona com
   * `->with('success', ...)` e o FlashNotification do layout desenha isso no
   * mesmo canto superior direito. Emitir toast aqui empilharia dois avisos
   * identicos.
   *
   * O erro, sim, precisa: numa falha de validacao o Inertia so preenche
   * `form.errors`, sem flash nenhum. Com `preserveScroll` a pessoa fica parada
   * no botao, e se o campo invalido estiver numa secao recolhida ela nao ve
   * marca vermelha nenhuma -- o formulario parece ter ignorado o clique, que foi
   * exatamente a queixa original.
   */
  const opcoesDeEnvio = {
    forceFormData: true,
    preserveScroll: true,
    onError: (erros) => {
      const quantidade = Object.keys(erros ?? {}).length;
      const primeiro = Object.values(erros ?? {})[0];

      toast(
        quantidade > 1
          ? `${quantidade} campos precisam de correcao. ${primeiro}`
          : (primeiro ?? 'Nao foi possivel salvar o cadastro.'),
        'error',
      );
    },
  };

  function salvar() {
    // Update com arquivo exige POST + _method, porque PHP nao popula $_FILES em
    // PUT.
    if (beneficiario?.id) {
      form.transform((dados) => ({ ...dados, _method: 'put' }))
        .post(route('cisternas.beneficiarios.update', beneficiario.id), opcoesDeEnvio);

      return;
    }

    form.post(route('cisternas.beneficiarios.store'), opcoesDeEnvio);
  }

  function cancelar() {
    router.visit(
      beneficiario?.id
        ? route('cisternas.beneficiarios.show', beneficiario.id)
        : route('cisternas.beneficiarios.index'),
    );
  }

  return { form, comunidades, carregandoComunidades, carregarComunidades, anexar, salvar, cancelar };
}

/**
 * Achata o resource nos nomes que o request espera. Em modo criar devolve os
 * defaults.
 */
function camposIniciais(b) {
  const social = b?.criterios_sociais ?? {};
  const tecnica = b?.avaliacao_tecnica ?? {};
  const pipa = b?.atendimento_pipa ?? {};
  const responsaveis = b?.responsaveis_cadastro ?? {};

  return {
    cpf: b?.cpf ?? '',
    nome: b?.nome ?? '',
    telefone: b?.telefone ?? '',
    data_nascimento: b?.data_nascimento ?? '',
    cadastro_unico: b?.cadastro_unico ?? '',

    municipio_id: b?.municipio?.id ?? '',
    comunidade_id: b?.comunidade?.id ?? '',
    endereco: b?.endereco ?? '',
    latitude: b?.latitude ?? '',
    longitude: b?.longitude ?? '',
    ordem_servico_id: b?.ordem_servico?.id ?? '',

    situacao_analise: b?.situacao_analise?.valor ?? '',
    situacao_analise_obs: b?.situacao_analise?.observacao ?? '',
    situacao_obra: b?.situacao_obra?.valor ?? '',
    ranqueamento_ordem: b?.ranqueamento_ordem ?? '',

    qtd_pessoas: social.qtd_pessoas ?? '',
    renda: social.renda ?? '',
    renda_per_capita: social.renda_per_capita ?? '',
    possui_deficiencia: Boolean(social.possui_deficiencia),
    possui_crianca: Boolean(social.possui_crianca),
    data_nascimento_crianca: social.data_nascimento_crianca ?? '',
    possui_idoso: Boolean(social.possui_idoso),
    chefiada_mulher: Boolean(social.chefiada_mulher),

    tipo_moradia: tecnica.tipo_moradia ?? '',
    tipo_moradia_outro: tecnica.tipo_moradia_outro ?? '',
    comprimento_telhado: tecnica.comprimento_telhado ?? '',
    largura_telhado: tecnica.largura_telhado ?? '',
    area_telhado: tecnica.area_telhado ?? '',
    comprimento_testada: tecnica.comprimento_testada ?? '',
    num_caidas_telhado: tecnica.num_caidas_telhado ?? '',
    cobertura_telhado: tecnica.cobertura_telhado ?? '',
    cobertura_outro: tecnica.cobertura_outro ?? '',
    possui_fogao_lenha: Boolean(tecnica.possui_fogao_lenha),
    medida_telhado_area_fogao: tecnica.medida_telhado_area_fogao ?? '',
    testada_disp_parte_fogao: tecnica.testada_disp_parte_fogao ?? '',

    atendido_por_pipa: Boolean(pipa.atendido),
    responsaveis_pipa: (pipa.responsaveis ?? []).map((r) => r.valor ?? r),
    atendimento_pipa_outro: pipa.descricao ?? '',

    agente_nome: responsaveis.agente_nome ?? '',
    agente_cpf: responsaveis.agente_cpf ?? '',
    engenheiro_nome: responsaveis.engenheiro_nome ?? '',
    engenheiro_crea: responsaveis.engenheiro_crea ?? '',

    observacoes: b?.observacoes ?? '',

    // Arquivos entram por referencia, preenchidos pelo ArquivoField.
    comprovante_deficiencia: null,
    comprovante_chefia_mulher: null,
    comprovante_observacao: null,
  };
}
