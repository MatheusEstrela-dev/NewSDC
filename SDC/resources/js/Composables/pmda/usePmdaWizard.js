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
/**
 * Etapa mais alta ja alcancada por um plano salvo, deduzida dos dados (#56,
 * "Travar as abas ate prosseguir"): reabrir um rascunho nao libera etapa que o
 * municipio ainda nao percorreu.
 */
export function progressoDoPlano(plano, equipe = []) {
  if (!plano) return 2;
  let etapa = 3;
  if (plano.compdec_coordenador || equipe.length) etapa = 4;
  if (etapa === 4 && plano.pontos?.length) etapa = 5;
  if (etapa === 5 && plano.comunidades?.length) etapa = 6;
  const temAcao = plano.acao_decreto_se || plano.acao_caminhao_pipa || plano.acao_cestas_basicas || plano.justificativa_apoio;
  if (etapa === 6 && temAcao) etapa = 7;
  return etapa;
}

export function usePmdaWizard({ allUnlocked = false, initialTab = 1, maxUnlocked = 1 } = {}) {
  const activeTab = ref(initialTab);
  // "Salvar e Avancar" libera a proxima etapa; clicar numa aba travada nao.
  const alcancada = ref(0);

  // allUnlocked/maxUnlocked podem ser valor, ref ou getter (reativos no Create).
  const resolve = (v) => (typeof v === 'function' ? v() : unref(v));

  // No Create as etapas <= maxUnlocked ficam liberadas (as demais exigem o plano salvo).
  const isUnlocked = (id) => resolve(allUnlocked) || id <= Math.max(resolve(maxUnlocked), alcancada.value);

  const tabs = computed(() =>
    PMDA_STEPS.map((s) => ({
      ...s,
      disabled: !isUnlocked(s.id),
    }))
  );

  const goTo = (id) => {
    if (isUnlocked(id)) activeTab.value = id;
  };
  const next = () => {
    const proxima = Math.min(activeTab.value + 1, TOTAL_ETAPAS);
    alcancada.value = Math.max(alcancada.value, proxima);
    goTo(proxima);
  };
  const prev = () => goTo(Math.max(activeTab.value - 1, 1));

  return { activeTab, tabs, isUnlocked, goTo, next, prev };
}
