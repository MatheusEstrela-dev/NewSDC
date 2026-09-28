import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { formatarDataHora, paraInputLocal } from '@/Support/demandasFormat';

export function useDemandaResolucao(demanda) {
  const form = useForm({
    aberta_em: paraInputLocal(demanda.created_at),
    resolvida_em: paraInputLocal(null),
  });

  // Mesma validacao cruzada do legado, antes de ir ao servidor; o FormRequest
  // repete a regra e e ele quem decide. So essa checagem do cliente trava o
  // envio e o botao: form.errors e do servidor e o Inertia nao limpa sozinho
  // quando o usuario edita a data, entao usa-lo aqui prendia a tela apos
  // qualquer erro anterior mesmo com datas validas.
  const erroAberturaCliente = computed(() => (form.aberta_em > form.resolvida_em
    ? `A data não pode ser posterior ao fechamento (${formatarDataHora(form.resolvida_em)}).` : null));
  const erroFechamentoCliente = computed(() => (form.resolvida_em < form.aberta_em
    ? `A data não pode ser anterior à abertura (${formatarDataHora(form.aberta_em)}).` : null));

  // Exibicao: erro do cliente tem prioridade; senao mostra o erro que o
  // servidor devolveu, ate a proxima mudanca de data limpar form.errors.
  const erroAbertura = computed(() => erroAberturaCliente.value ?? form.errors.aberta_em);
  const erroFechamento = computed(() => erroFechamentoCliente.value ?? form.errors.resolvida_em);

  const podeEnviar = computed(() => !erroAberturaCliente.value && !erroFechamentoCliente.value);

  watch([() => form.aberta_em, () => form.resolvida_em], () => {
    form.clearErrors('aberta_em', 'resolvida_em');
  });

  function enviar() {
    if (!podeEnviar.value) return;
    form.post(route('demandas.resolver', demanda.id), { preserveScroll: true });
  }

  return {
    form, erroAbertura, erroFechamento, podeEnviar, enviar,
  };
}
