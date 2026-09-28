import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

export function useDemandaAutosave(demandaId) {
  const salvando = ref(false);

  function salvar(campos) {
    salvando.value = true;
    router.put(route('admin.demandas.update', demandaId), campos, {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => { salvando.value = false; },
    });
  }

  return { salvando, salvar };
}
