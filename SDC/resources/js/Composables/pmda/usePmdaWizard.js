import { computed, ref, unref } from 'vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import UsersGroupIcon from '@/Components/Icons/UsersGroupIcon.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';

/**
 * Definicao das 7 etapas do wizard PMDA. `key` casa com os componentes de etapa.
 */
export const PMDA_STEPS = [
  { id: 1, key: 'inicio', label: 'Início', icon: DocumentTextIcon },
  { id: 2, key: 'iss', label: 'ISS', icon: BuildingOfficeIcon },
  { id: 3, key: 'compdec', label: 'COMPDEC', icon: UsersGroupIcon },
  { id: 4, key: 'ponto', label: 'Ponto Captação', icon: MapIcon },
  { id: 5, key: 'distribuicao', label: 'Locais Distribuição', icon: TruckIcon },
  { id: 6, key: 'acoes', label: 'Ações Resposta', icon: CheckBadgeIcon },
  { id: 7, key: 'anexos', label: 'Anexos', icon: PaperClipIcon },
];

export const TOTAL_ETAPAS = PMDA_STEPS.length;

/**
 * Estado de navegacao do wizard.
 * @param {Object} opts
 * @param {boolean} opts.allUnlocked  Edit abre tudo; Create libera so ate maxUnlocked.
 * @param {number}  opts.maxUnlocked  No Create, ultima etapa liberada sem plano salvo
 *                                     (Inicio + ISS sao campos do plano e rodam em memoria;
 *                                     COMPDEC+ exige o ID, entao a criacao persiste ao chegar la).
 */
export function usePmdaWizard({ allUnlocked = false, initialTab = 1, maxUnlocked = 1 } = {}) {
  const activeTab = ref(initialTab);

  // allUnlocked/maxUnlocked podem ser valor, ref ou getter (reativos no Create).
  const resolve = (v) => (typeof v === 'function' ? v() : unref(v));

  // No Create as etapas <= maxUnlocked ficam liberadas (as demais exigem o plano salvo).
  const isUnlocked = (id) => resolve(allUnlocked) || id <= resolve(maxUnlocked);

  const tabs = computed(() =>
    PMDA_STEPS.map((s) => ({
      ...s,
      disabled: !isUnlocked(s.id),
    }))
  );

  const goTo = (id) => {
    if (isUnlocked(id)) activeTab.value = id;
  };
  const next = () => goTo(Math.min(activeTab.value + 1, TOTAL_ETAPAS));
  const prev = () => goTo(Math.max(activeTab.value - 1, 1));

  return { activeTab, tabs, isUnlocked, goTo, next, prev };
}
