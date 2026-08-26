<template>
  <AuthenticatedLayout>
    <Head :title="`Cisternas — Vistorias de ${beneficiario.nome}`" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Vistorias"
        :description="`${beneficiario.nome} — ${municipio}`"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
        :espaco-inferior="false"
      >
        <!--
          Sem slot de acoes: o unico botao aqui era "Ver cadastro", e a trilha
          traz o nome do beneficiario como link para o mesmo destino.
        -->
      </PageHeader>

      <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <!--
          A cadeia e barra lateral FIXA a direita: o formulario de vistoria e
          longo (responsavel, local, 13 itens de checklist, observacoes) e, com a
          cadeia rolando junto, ela saia da tela na primeira dobra -- justamente
          quando a pessoa precisa conferir em que etapa esta mexendo.

          Ordem trocada por breakpoint, nao por acaso: no DOM a cadeia vem
          primeiro, porque no celular (uma coluna) escolher a etapa acontece
          ANTES de preencher. No desktop o `order` joga o formulario para a
          esquerda e a cadeia para a direita.

          `items-start` no grid e `self-start` aqui sao o que faz o sticky
          funcionar: item de grid esticado ocupa a altura toda da linha e nao
          sobra distancia para grudar.

          `top-20` e o mesmo deslocamento do FlashNotification e do
          ToastContainer -- a TopBar e `fixed` com ~4rem, e este e o valor que o
          projeto ja usa para "logo abaixo do cabecalho". O max-height com
          overflow existe para tela baixa: sem ele a terceira etapa ficaria
          cortada, sem rolagem propria.
        -->
        <aside
          class="lg:order-2 lg:col-span-1 lg:sticky lg:top-20 lg:self-start lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto"
        >
          <VistoriaTimeline
            :vistorias="vistorias.data ?? vistorias"
            :opcoes-etapa="OPCOES_ETAPA"
            :etapa-disponivel="etapa_disponivel"
            :pode-criar="permissoes.criar"
            :pode-editar="permissoes.editar"
            @preencher="abrirFormulario"
            @editar="abrirEdicao"
          />

          <!--
            A cadeia terminou: nada a preencher. Dizer isso e melhor que so nao
            mostrar botao, que parece falta de permissao.
          -->
          <p v-if="etapa_disponivel === null" class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            As tres etapas foram concluidas. Use "Corrigir" na etapa para ajustar um relatorio.
          </p>
        </aside>

        <div class="lg:order-1 lg:col-span-2">
          <div v-if="etapaEmEdicao">
            <!--
              Cabecalho do painel, com borda: antes o "Fechar" flutuava solto
              acima do formulario, sem nada que o ligasse a ele, e parecia botao
              perdido no meio da pagina.
            -->
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
              <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                {{ rotuloEtapa(etapaEmEdicao) }}
              </h2>
              <button type="button" :class="BOTAO_SEC" @click="etapaEmEdicao = null">Fechar</button>
            </div>

            <VistoriaForm
              :key="etapaEmEdicao"
              :form="form"
              :itens="itens"
              :etapa="etapaEmEdicao"
              :processando="form.processing"
              :modo="modo"
              @arquivo="anexar"
              @submit="salvar"
              @cancel="etapaEmEdicao = null"
            />
          </div>

          <ListEmptyState
            v-else
            title="Nenhuma etapa em preenchimento"
            :helper="ajuda"
          />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, shallowRef } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import VistoriaTimeline from '@/Components/Organisms/Cisterna/VistoriaTimeline.vue';
import VistoriaForm from '@/Components/Organisms/Cisterna/VistoriaForm.vue';
import { useVistoriaForm } from '@/Composables/cisterna/useVistoriaForm';

const props = defineProps({
  beneficiario: { type: Object, required: true },
  vistorias: { type: [Object, Array], default: () => [] },
  /**
   * snake_case de proposito: e a chave EXATA que o controller manda. O Vue
   * converte kebab-case para camelCase, mas NAO converte snake_case -- declarar
   * `etapaDisponivel` deixava a prop sempre undefined, e com isso nenhuma etapa
   * aparecia como liberada: o botao de preencher nunca surgia.
   */
  etapa_disponivel: { type: String, default: null },
  itens: { type: Array, default: () => [] },
  permissoes: { type: Object, default: () => ({}) },
});

const BOTAO_SEC = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';

/**
 * A ordem das etapas e a da cadeia, e nao a de um `options()` qualquer: e ela
 * que a timeline numera. Fixa aqui porque a sequencia fornecedor -> compdec ->
 * cedec e regra do dominio, nao configuracao.
 */
const OPCOES_ETAPA = [
  { value: 'fornecedor', label: 'Relatorio do Fornecedor' },
  { value: 'compdec', label: 'Conferencia da COMPDEC' },
  { value: 'cedec', label: 'Fiscalizacao da CEDEC' },
];

const etapaEmEdicao = ref(null);
const modo = ref('criar');

const municipio = computed(() => {
  const m = props.beneficiario.municipio;

  return m ? [m.nome, m.uf].filter(Boolean).join(' / ') : '';
});

const ajuda = computed(() => {
  if (props.etapa_disponivel === null) return 'A cadeia de fiscalizacao esta completa.';
  if (!props.permissoes.criar) return 'Voce nao tem permissao para registrar vistoria.';

  return 'Escolha "Preencher" na etapa liberada, ao lado.';
});

/**
 * O formulario e recriado a cada etapa escolhida. Reaproveitar a instancia
 * traria o valor da etapa anterior, inclusive em campo que a nova etapa proibe.
 */
const formulario = shallowRef(null);

const form = computed(() => formulario.value?.form ?? { errors: {}, processing: false });

function abrirFormulario(etapa) {
  formulario.value = useVistoriaForm(props.beneficiario.id, etapa);
  etapaEmEdicao.value = etapa;
  modo.value = 'criar';
}

/**
 * Mesma gaveta, com a vistoria existente: o useVistoriaForm ja aceita o terceiro
 * argumento e troca o POST por PUT quando ele vem. Sem passar a vistoria, salvar
 * criaria um segundo relatorio para a mesma etapa e bateria no indice unico.
 */
function abrirEdicao(etapa) {
  formulario.value = useVistoriaForm(props.beneficiario.id, etapa.valor, etapa.vistoria);
  etapaEmEdicao.value = etapa.valor;
  modo.value = 'editar';
}

function anexar(evento) {
  formulario.value?.anexar(evento);
}

function salvar() {
  formulario.value?.salvar();
}

function rotuloEtapa(valor) {
  return OPCOES_ETAPA.find((o) => o.value === valor)?.label ?? valor;
}
</script>
