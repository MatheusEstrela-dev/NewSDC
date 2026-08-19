<template>
  <AuthenticatedLayout>
    <Head :title="`Cisternas — Vistorias de ${beneficiario.nome}`" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Vistorias"
        :description="`${beneficiario.nome} — ${municipio}`"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
      >
        <!--
          Sem slot de acoes: o unico botao aqui era "Ver cadastro", e a trilha
          traz o nome do beneficiario como link para o mesmo destino.
        -->
      </PageHeader>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
          <VistoriaTimeline
            :vistorias="vistorias.data ?? vistorias"
            :opcoes-etapa="OPCOES_ETAPA"
            :etapa-disponivel="etapa_disponivel"
            :pode-criar="permissoes.criar"
            @preencher="abrirFormulario"
          />

          <!--
            A cadeia terminou: nada a preencher. Dizer isso e melhor que so nao
            mostrar botao, que parece falta de permissao.
          -->
          <p v-if="etapa_disponivel === null" class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            As tres etapas foram concluidas. Para corrigir um relatorio, abra-o e edite.
          </p>
        </div>

        <div class="lg:col-span-2">
          <div v-if="etapaEmEdicao">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
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
              modo="criar"
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

const municipio = computed(() => {
  const m = props.beneficiario.municipio;

  return m ? [m.nome, m.uf].filter(Boolean).join(' / ') : '';
});

const ajuda = computed(() => {
  if (props.etapa_disponivel === null) return 'A cadeia de fiscalizacao esta completa.';
  if (!props.permissoes.criar) return 'Voce nao tem permissao para registrar vistoria.';

  return 'Escolha "Preencher" na etapa liberada ao lado.';
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
