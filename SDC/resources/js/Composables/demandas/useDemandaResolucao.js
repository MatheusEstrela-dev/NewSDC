import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { formatarDataHora, paraInputLocal } from '@/Support/demandasFormat';

export function useDemandaResolucao(demanda) {
  const form = useForm({
    aberta_em: paraInputLocal(demanda.created_at),
    resolvida_em: paraInputLocal(null),
  });

  // Mesma validacao cruzada do legado, antes de ir ao servidor; o FormRequest
  // repete a regra e e ele quem decide.
  const erroAbertura = computed(() => (form.aberta_em > form.resolvida_em
    ? `A data não pode ser posterior ao fechamento (${formatarDataHora(form.resolvida_em)}).` : form.errors.aberta_em));
  const erroFechamento = computed(() => (form.resolvida_em < form.aberta_em
    ? `A data não pode ser anterior à abertura (${formatarDataHora(form.aberta_em)}).` : form.errors.resolvida_em));

  function enviar() {
    if (erroAbertura.value || erroFechamento.value) return;
    form.post(route('demandas.resolver', demanda.id), { preserveScroll: true });
  }

  return { form, erroAbertura, erroFechamento, enviar };
}
