import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const VAZIO = {
  search: '', etapa: '', prioridade: '', assunto_id: '', solicitante_id: '',
  responsavel_id: '', criado_por_id: '', data_inicial: '', data_final: '',
};

export function useDemandaFilters(filtrosIniciais = {}, rota = 'demandas.index') {
  const local = reactive({ ...VAZIO, ...filtrosIniciais });

  function aplicar(extra = {}) {
    const params = Object.fromEntries(
      Object.entries({ ...local, ...extra }).filter(([, v]) => v !== '' && v !== null && v !== undefined),
    );
    router.get(route(rota), params, { preserveState: true, preserveScroll: true, replace: true });
  }

  function limpar() {
    Object.assign(local, VAZIO);
    aplicar();
  }

  function filtrarEtapa(etapa) {
    local.etapa = local.etapa === etapa ? '' : etapa;
    aplicar();
  }

  return { local, aplicar, limpar, filtrarEtapa };
}
