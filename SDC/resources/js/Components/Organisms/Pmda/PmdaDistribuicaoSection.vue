<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import { usePermissions } from '@/Composables/usePermissions';
import ComunidadeAddModal from '@/Components/Organisms/Pmda/ComunidadeAddModal.vue';
import ComunidadeSolicitarModal from '@/Components/Organisms/Pmda/ComunidadeSolicitarModal.vue';
import RepresentantesModal from '@/Components/Organisms/Pmda/RepresentantesModal.vue';

const props = defineProps({
  plano: { type: Object, required: true },
  comunidadesDisponiveis: { type: Array, default: () => [] },
  solicitacoes: { type: Array, default: () => [] },
});

defineEmits(['next', 'prev']);

const { can } = usePermissions();

const showAdd = ref(false);
const showSolicitar = ref(false);

const comunidades = computed(() => props.plano.comunidades ?? []);

// Espelha PmdaPlanoService::REPRESENTANTES_POR_COMUNIDADE. O backend continua
// sendo quem barra o envio; aqui o numero so avisa antes de o usuario tentar.
const REPRESENTANTES_MINIMO = 3;
const representantesOk = (c) => (c.representantes ?? []).length >= REPRESENTANTES_MINIMO;

const representantesModal = ref({ open: false, comunidade: null });

function abrirRepresentantes(comunidade) {
  representantesModal.value = { open: true, comunidade };
}

// A comunidade vem do prop do plano: apos gravar, o Inertia devolve a lista nova
// e o modal precisa apontar para o objeto recem-chegado, nao para o antigo.
const comunidadeDoModal = computed(() => {
  const alvo = representantesModal.value.comunidade;

  return alvo ? comunidades.value.find((c) => c.id === alvo.id) ?? alvo : null;
});
const totalPop = computed(() => comunidades.value.reduce((s, c) => s + (Number(c.pop_atendida) || 0), 0));
const totalDemanda = computed(() => comunidades.value.reduce((s, c) => s + (Number(c.demanda_litros) || 0), 0));

const fmt = (n) => new Intl.NumberFormat('pt-BR').format(n ?? 0);

function remover(id) {
  router.delete(route('pmda.comunidades.destroy', id), { preserveScroll: true });
}
</script>

<template>
  <PmdaWizardPanel
    :step="5"
    title="Comunidades Atendidas"
    subtitle="Listagem e detalhamento das comunidades afetadas."
    :icon="TruckIcon"
    @next="$emit('next')"
    @prev="$emit('prev')"
  >
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <p class="text-sm text-slate-600 dark:text-slate-400">
        Adicione as comunidades (rurais e urbanas) que necessitam de atendimento com caminhões-pipa.
      </p>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="can('pmda.comunidades.create')"
          type="button"
          class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          @click="showAdd = true"
        >
          Adicionar Comunidade
        </button>
        <button
          v-if="can('pmda.comunidades.solicitar')"
          type="button"
          class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
          @click="showSolicitar = true"
        >
          Solicitar Cadastro de Comunidade
        </button>
      </div>
    </div>

    <div class="pmda-info-box mb-4">
      Caso a comunidade não esteja na lista, clique em <strong>Solicitar Cadastro de Comunidade</strong>;
      a CEDEC analisará o pedido e, aprovado, ela ficará disponível para seleção.
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr class="text-left text-slate-500 dark:text-slate-400">
            <th class="px-4 py-2 font-medium">Comunidade</th>
            <th class="px-4 py-2 font-medium">População</th>
            <th class="px-4 py-2 font-medium">Distância Sede</th>
            <th class="px-4 py-2 font-medium">Demanda Est. (L)</th>
            <th class="px-4 py-2 font-medium">Representantes</th>
            <th class="px-4 py-2 text-right font-medium">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-for="c in comunidades" :key="c.id" class="text-slate-700 dark:text-slate-300">
            <td class="px-4 py-2.5 font-medium">{{ c.nome }}</td>
            <td class="px-4 py-2.5">{{ fmt(c.pop_atendida) }}</td>
            <td class="px-4 py-2.5">{{ c.distancia_km ? `${c.distancia_km} km` : '—' }}</td>
            <td class="px-4 py-2.5 font-semibold text-blue-600 dark:text-blue-400">{{ fmt(c.demanda_litros) }} L</td>
            <td class="px-4 py-2.5">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="representantesOk(c)
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
                  : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'"
                :title="representantesOk(c) ? '' : 'Está faltando representantes para esta comunidade'"
              >
                {{ (c.representantes ?? []).length }}/{{ REPRESENTANTES_MINIMO }}
              </span>
            </td>
            <td class="px-4 py-2.5 text-right">
              <button
                type="button"
                class="mr-3 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                @click="abrirRepresentantes(c)"
              >
                Representantes
              </button>
              <button
                v-if="can('pmda.comunidades.delete')"
                type="button"
                class="text-sm font-medium text-red-600 hover:underline dark:text-red-400"
                @click="remover(c.id)"
              >
                Remover
              </button>
            </td>
          </tr>
          <tr v-if="comunidades.length === 0">
            <td colspan="6" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
              Nenhuma comunidade cadastrada.
            </td>
          </tr>
        </tbody>
        <tfoot v-if="comunidades.length > 0" class="bg-slate-50 font-semibold text-slate-800 dark:bg-slate-800/40 dark:text-slate-100">
          <tr>
            <td class="px-4 py-2.5">TOTAL</td>
            <td class="px-4 py-2.5">{{ fmt(totalPop) }}</td>
            <td class="px-4 py-2.5"></td>
            <td class="px-4 py-2.5 text-blue-600 dark:text-blue-400">{{ fmt(totalDemanda) }} L</td>
            <td class="px-4 py-2.5"></td>
            <td class="px-4 py-2.5"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <ComunidadeAddModal
      :show="showAdd"
      :plano-id="plano.id"
      :comunidades="comunidadesDisponiveis"
      @close="showAdd = false"
    />
    <ComunidadeSolicitarModal
      :show="showSolicitar"
      :plano-id="plano.id"
      :historico="solicitacoes"
      @close="showSolicitar = false"
    />
    <RepresentantesModal
      :show="representantesModal.open"
      :comunidade="comunidadeDoModal"
      @close="representantesModal.open = false"
    />
  </PmdaWizardPanel>
</template>
