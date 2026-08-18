import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

/**
 * CRUD em modal, para os quatro recursos de apoio do modulo.
 *
 * As rotas do NewSDC foram desenhadas sem `create` nem `edit` para comunidades,
 * lotes, ordens de servico e notificacoes -- existem so `index`, `store`,
 * `update` e `destroy`. Logo o formulario e modal sobre a lista, e nao pagina.
 * Isso eliminou 7 das 11 views que o legado tinha para essas quatro entidades.
 *
 * @param {string} recurso  Prefixo da rota, ex. 'cisternas.comunidades'.
 * @param {object} vazio    Campos iniciais de um registro novo.
 * @param {(registro: object) => object} paraFormulario
 *        Achata o registro do resource nos nomes que o Request espera.
 * @param {{ comArquivo?: boolean, paraPayload?: (dados: object) => object }} opcoes
 *        `paraPayload` traduz os campos na saida. Existe porque o Inertia RESERVA
 *        nomes no objeto do form -- `data`, `errors`, `processing`, `reset` e os
 *        verbos HTTP -- e define `data()` DEPOIS de espalhar os campos, entao um
 *        campo chamado `data` e sobrescrito pelo metodo e nao preenche nem salva.
 *        O lote tem um campo de data, e por isso usa nome local diferente.
 */
export function useCrudModal(recurso, vazio, paraFormulario = null, opcoes = {}) {
  const { comArquivo = false, paraPayload = null } = opcoes;

  const aberto = ref(false);
  const emEdicao = ref(null);

  const form = useForm({ ...vazio });

  const editando = computed(() => emEdicao.value !== null);

  function abrirNovo() {
    emEdicao.value = null;
    form.defaults({ ...vazio });
    form.reset();
    form.clearErrors();
    aberto.value = true;
  }

  function abrirEdicao(registro) {
    emEdicao.value = registro;

    const campos = paraFormulario ? paraFormulario(registro) : { ...vazio, ...registro };

    // `defaults` antes de `reset` para o botao cancelar voltar ao registro, e nao
    // ao vazio -- fechar e reabrir tem que mostrar o dado de novo.
    form.defaults(campos);
    form.reset();
    form.clearErrors();
    aberto.value = true;
  }

  function fechar() {
    aberto.value = false;
    emEdicao.value = null;
    form.clearErrors();
  }

  function salvar() {
    const opcoesEnvio = {
      preserveScroll: true,
      onSuccess: fechar,
    };

    if (paraPayload) {
      form.transform(paraPayload);
    }

    // `forceFormData` so quando ha arquivo: forcar sempre transformaria booleano
    // em string '1'/'0' no multipart e quebraria validacao de boolean.
    if (comArquivo) {
      opcoesEnvio.forceFormData = true;
    }

    if (editando.value) {
      // POST + _method em recurso com arquivo: o PHP nao popula $_FILES em PUT.
      if (comArquivo) {
        form.transform((dados) => ({
          ...(paraPayload ? paraPayload(dados) : dados),
          _method: 'put',
        })).post(route(`${recurso}.update`, emEdicao.value.id), opcoesEnvio);

        return;
      }

      form.put(route(`${recurso}.update`, emEdicao.value.id), opcoesEnvio);

      return;
    }

    form.post(route(`${recurso}.store`), opcoesEnvio);
  }

  function anexar({ campo, arquivo }) {
    form[campo] = arquivo;
  }

  /**
   * Exclusao pede confirmacao: e soft delete no dominio, mas o usuario nao sabe
   * disso, e a lista nao deve perder registro por clique errado.
   */
  function excluir(registro, rotulo = 'este registro') {
    if (!window.confirm(`Remover ${rotulo}?`)) return;

    router.delete(route(`${recurso}.destroy`, registro.id), { preserveScroll: true });
  }

  return { aberto, emEdicao, editando, form, abrirNovo, abrirEdicao, fechar, salvar, anexar, excluir };
}
