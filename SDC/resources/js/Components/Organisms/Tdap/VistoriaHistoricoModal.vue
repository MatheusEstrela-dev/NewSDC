<template>
  <Modal :show="open" max-width="2xl" @close="$emit('close')">
    <div class="flex max-h-full min-h-0 flex-col modal-serie-corpo">
      <div class="shrink-0 px-4 py-4 md:px-6 md:py-5 modal-serie-header">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-full bg-slate-900/40 border border-slate-700/40 flex items-center justify-center">
              <ClockIcon class="w-5 h-5 text-slate-200" />
            </div>
            <div class="min-w-0">
              <h3 class="text-lg font-semibold text-white truncate">Série Histórica de Vistorias</h3>
              <p class="text-sm text-slate-200/80 truncate">
                Caminhão
                <span class="font-mono">{{ caminhao?.placa || '—' }}</span>
                <span v-if="descricaoDoVeiculo" class="text-slate-200/60"> · {{ descricaoDoVeiculo }}</span>
              </p>
            </div>
          </div>

          <button
            type="button"
            class="p-2 rounded-lg text-slate-200/80 hover:text-white hover:bg-white/10 transition-all"
            title="Fechar"
            @click="$emit('close')"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="tira-rolavel mt-4 items-center gap-4 border-b border-slate-700/40 sm:gap-6">
          <button
            type="button"
            class="modal-serie-aba pb-3 text-sm font-semibold flex items-center gap-2"
            :class="{ 'is-ativa': abaAtiva === 'timeline' }"
            @click="abaAtiva = 'timeline'"
          >
            Vistorias
            <Badge v-if="total" variant="info" size="sm">{{ total }}</Badge>
          </button>
          <button
            type="button"
            class="modal-serie-aba pb-3 text-sm font-semibold flex items-center gap-2"
            :class="{ 'is-ativa': abaAtiva === 'reprovadas' }"
            @click="abaAtiva = 'reprovadas'"
          >
            Reprovações
            <Badge v-if="reprovadas.length" variant="warning" size="sm">{{ reprovadas.length }}</Badge>
          </button>
        </div>
      </div>

      <div class="flex-1 min-h-0 overflow-y-auto p-4 pb-8 md:p-6">
        <div v-if="carregando" class="py-10 text-center modal-serie-apoio">
          Carregando a série histórica...
        </div>

        <div v-else-if="erro" class="py-10 text-center text-red-300">
          {{ erro }}
        </div>

        <!-- Serie completa -->
        <div v-else-if="abaAtiva === 'timeline'">
          <ol v-if="total" class="modal-serie-trilho relative ml-5 space-y-6">
            <li v-for="vistoria in vistorias" :key="vistoria.id" class="ml-8 relative">
              <span
                class="absolute flex items-center justify-center w-9 h-9 rounded-full -left-12 modal-serie-marcador"
                :class="corDoMarcador(vistoria)"
              >
                <component :is="iconeDoParecer(vistoria.parecer)" class="w-4 h-4" />
              </span>

              <div class="modal-serie-cartao rounded-xl p-4">
                <div class="flex items-start justify-between gap-3 mb-2">
                  <div class="min-w-0">
                    <h4 class="text-base font-semibold modal-serie-titulo truncate">
                      {{ vistoria.parecer_label || 'Vistoria' }}
                    </h4>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs modal-serie-apoio">
                      <Badge :variant="varianteDoParecer(vistoria.parecer)" size="sm">
                        {{ vistoria.parecer_label || 'Sem parecer' }}
                      </Badge>
                      <span class="font-mono modal-serie-apoio">{{ fmtDate(vistoria.data) }}</span>
                      <!-- Vigencia no lugar de "aprovada" solta: o que decide
                           se o caminhao roda hoje e a data, nao o carimbo. -->
                      <Badge v-if="vistoria.vigente" variant="success" size="sm">
                        {{ rotuloDeVigencia(vistoria) }}
                      </Badge>
                      <Badge v-else-if="vistoria.parecer === 'aprovada'" variant="warning" size="sm">
                        {{ rotuloDeVigencia(vistoria) }}
                      </Badge>
                    </div>
                  </div>

                  <Link
                    v-if="podeVerVistoria"
                    :href="route('tdap.vistorias.show', vistoria.id)"
                    class="modal-serie-elo shrink-0 rounded-lg px-3 py-1 text-xs font-semibold transition"
                  >
                    Abrir ficha
                  </Link>
                </div>

                <p v-if="vistoria.observacoes" class="text-sm modal-serie-texto leading-relaxed">
                  {{ vistoria.observacoes }}
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                  <span class="inline-flex items-center gap-2 modal-serie-pilula px-3 py-1 rounded-full">
                    <UsersIcon class="w-4 h-4 modal-serie-apoio" />
                    <span class="modal-serie-apoio">Vistoriador:</span>
                    <span class="modal-serie-valor">{{ vistoria.vistoriador || '—' }}</span>
                  </span>
                  <span v-if="vistoria.ficha" class="inline-flex items-center gap-2 modal-serie-pilula px-3 py-1 rounded-full">
                    <span class="modal-serie-apoio">Ficha:</span>
                    <span class="font-mono modal-serie-valor">{{ vistoria.ficha }}</span>
                  </span>
                  <span v-if="vistoria.lacre" class="inline-flex items-center gap-2 modal-serie-pilula px-3 py-1 rounded-full">
                    <span class="modal-serie-apoio">Lacre:</span>
                    <span class="font-mono modal-serie-valor">{{ vistoria.lacre }}</span>
                  </span>
                </div>
              </div>
            </li>
          </ol>

          <div v-else class="text-center py-10 modal-serie-apoio">
            Este caminhão nunca foi vistoriado.
          </div>
        </div>

        <!-- So as reprovacoes: a pergunta e "este veiculo e confiavel?" -->
        <div v-else>
          <div v-if="reprovadas.length" class="space-y-3">
            <div
              v-for="vistoria in reprovadas"
              :key="vistoria.id"
              class="modal-serie-cartao rounded-xl p-4"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <h4 class="text-base font-semibold modal-serie-titulo">{{ fmtDate(vistoria.data) }}</h4>
                  <p class="text-sm modal-serie-apoio mt-1">
                    <span class="modal-serie-valor">{{ vistoria.vistoriador || '—' }}</span>
                    <span v-if="vistoria.edital"> • Edital {{ vistoria.edital }}</span>
                  </p>
                  <p v-if="vistoria.observacoes" class="mt-2 text-sm modal-serie-texto">{{ vistoria.observacoes }}</p>
                </div>
                <Badge variant="danger" size="sm">{{ vistoria.parecer_label || 'Reprovada' }}</Badge>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-10 modal-serie-apoio">
            Nenhuma reprovação registrada.
          </div>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Serie historica de vistorias de um caminhao, no desenho do PaeHistoricoModal.
 *
 * Em modal, e nao em pagina, porque o operador consulta caminhao a caminhao: a
 * cada consulta, sair da listagem custava filtro, pagina e posicao de rolagem.
 *
 * A listagem ja diz se o veiculo pode rodar HOJE. O que ela nao diz -- e so
 * aparece com as inspecoes lado a lado -- e se o caminhao e confiavel:
 * reprovado tres vezes seguidas e um problema, nao um detalhe do cadastro.
 */
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  /** Caminhao da linha clicada: `{ id, placa, marca, modelo }`. */
  caminhao: { type: Object, default: null },
  podeVerVistoria: { type: Boolean, default: false },
});

defineEmits(['close']);

const abaAtiva = ref('timeline');
const carregando = ref(false);
const erro = ref('');
const vistorias = ref([]);

const total = computed(() => vistorias.value.length);
const reprovadas = computed(() => vistorias.value.filter((v) => v.parecer === 'reprovada'));

const descricaoDoVeiculo = computed(() => {
  const partes = [props.caminhao?.marca, props.caminhao?.modelo].filter(Boolean);

  return partes.join(' ');
});

// Busca ao ABRIR, e nao ao montar: sao 132 caminhoes na frota, e carregar a
// serie de todos para exibir a de um seria trafego jogado fora.
watch(
  () => [props.open, props.caminhao?.id],
  async ([aberto, id]) => {
    if (! aberto || ! id) return;

    abaAtiva.value = 'timeline';
    carregando.value = true;
    erro.value = '';
    vistorias.value = [];

    try {
      // `route()` lanca quando o Ziggy carregado na pagina nao conhece a rota
      // -- acontece quando o worker do Octane ainda servia a colecao antiga no
      // momento em que a pagina foi montada. A mensagem diz o que resolve, em
      // vez de um "nao foi possivel" que manda o usuario adivinhar.
      let url;

      try {
        url = route('tdap.caminhoes.vistorias', id);
      } catch {
        erro.value = 'Esta tela está desatualizada. Recarregue a página (Ctrl+Shift+R) e tente de novo.';
        return;
      }

      const resposta = await fetch(url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });

      if (! resposta.ok) {
        erro.value = `Não foi possível carregar a série histórica (HTTP ${resposta.status}).`;
        return;
      }

      const dados = await resposta.json();
      vistorias.value = dados.vistorias ?? [];
    } catch {
      erro.value = 'Não foi possível carregar a série histórica deste caminhão.';
    } finally {
      carregando.value = false;
    }
  },
  { immediate: true },
);

function fmtDate(valor) {
  if (! valor) return '—';

  const [ano, mes, dia] = String(valor).slice(0, 10).split('-');

  return `${dia}/${mes}/${ano}`;
}

function rotuloDeVigencia(vistoria) {
  const dias = vistoria.dias_restantes;

  if (dias === null || dias === undefined) return 'Sem data';
  if (dias < 0) return `Venceu há ${Math.abs(dias)}d`;
  if (dias === 0) return 'Vence hoje';

  return `Vigente por ${dias}d`;
}

// Classes por extenso: string dinamica some no purge do Tailwind.
function corDoMarcador(vistoria) {
  if (vistoria.parecer === 'reprovada') return 'bg-red-600/90 text-white';

  return vistoria.vigente ? 'bg-emerald-600/90 text-white' : 'bg-amber-500/90 text-white';
}

function varianteDoParecer(parecer) {
  return { aprovada: 'success', reprovada: 'danger' }[parecer] ?? 'default';
}

function iconeDoParecer(parecer) {
  return { aprovada: CheckCircleIcon, reprovada: ExclamationTriangleIcon }[parecer] ?? DocumentTextIcon;
}
</script>
