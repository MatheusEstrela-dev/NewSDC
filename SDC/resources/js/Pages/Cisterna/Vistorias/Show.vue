<template>
  <AuthenticatedLayout>
    <Head :title="`Cisternas — ${vistoria.etapa.rotulo}`" />

    <div class="w-full space-y-6 pb-8">
      <PageHeader
        :title="vistoria.etapa.rotulo"
        :description="`${beneficiario.nome}${vistoria.numero_instalacao ? ` — instalacao Nº ${vistoria.numero_instalacao}` : ''}`"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
        :espaco-inferior="false"
      >
        <!--
          Esta tela e LEITURA do relatorio. Nao tem "Editar": a edicao acontece
          pelos botoes de acao da cadeia de fiscalizacao, na tela de vistorias,
          no mesmo painel usado para preencher. Ter dois lugares para editar o
          mesmo relatorio criava dois formularios com estados independentes.

          Concluir fica: e o que DESTRAVA a etapa seguinte, e nao existe em outro
          lugar. Sem esta acao o relatorio do fornecedor ficava eternamente "em
          aberto" e COMPDEC e CEDEC nunca saiam de "bloqueada".
        -->
        <template #actions>
          <button
            v-if="permissoes.editar && !vistoria.concluida"
            type="button"
            :class="BOTAO"
            :disabled="concluindo"
            @click="concluir"
          >
            {{ concluindo ? 'Concluindo...' : 'Concluir etapa' }}
          </button>
        </template>
      </PageHeader>

      <div class="flex flex-wrap items-center gap-2">
        <EtapaVistoriaBadge :etapa="vistoria.etapa.valor" :concluida="vistoria.concluida" />
        <span
          class="rounded px-2 py-0.5 text-xs font-medium"
          :class="vistoria.concluida
            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
            : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'"
        >
          {{ vistoria.concluida ? 'Relatorio concluido' : 'Relatorio em aberto' }}
        </span>
      </div>

      <DadosBloco titulo="Responsavel tecnico" :itens="itensResponsavel" />
      <DadosBloco v-if="vistoria.dados_administrativos" titulo="Dados administrativos" :itens="itensAdministrativos" />
      <DadosBloco titulo="Local da instalacao" :itens="itensLocal" />

      <ChecklistItens :itens="itens" :model-value="checklist" somente-leitura />

      <section v-if="vistoria.observacoes" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
        <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-slate-100">Observacoes</h3>
        <p class="whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ vistoria.observacoes }}</p>
      </section>

      <!-- Confirmacao pelo dialogo do sistema, igual a Decretacoes, PAE e RAT. -->
      <ConfirmDialog
        :is-open="confirmacao.aberto"
        v-bind="confirmacao.opcoes"
        :loading="concluindo"
        @confirm="confirmar"
        @cancel="cancelar"
      />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import EtapaVistoriaBadge from '@/Components/Atoms/Cisterna/EtapaVistoriaBadge.vue';
import DadosBloco from '@/Components/Molecules/Cisterna/DadosBloco.vue';
import ChecklistItens from '@/Components/Organisms/Cisterna/ChecklistItens.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';

const props = defineProps({
  vistoria: { type: Object, required: true },
  beneficiario: { type: Object, required: true },
  itens: { type: Array, default: () => [] },
  permissoes: { type: Object, default: () => ({}) },
});

const { show: toast } = useToast();
const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

const BOTAO = 'rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700';

const concluindo = ref(false);

/**
 * Conclui a etapa, liberando a proxima na cadeia de fiscalizacao.
 *
 * O backend recusa com ValidationException quando falta engenheiro/CREA -- e,
 * na etapa da CEDEC, tambem processo SEI, contrato, empenho e ART. Esses erros
 * chegam por campo, e sem trazer para a tela a pessoa clicaria de novo sem
 * saber o que falta: a tela de detalhe nao desenha erro de formulario, so o
 * modo de edicao.
 */
function concluir() {
  pedirConfirmacao(
    {
      title: 'Concluir etapa',
      message: `Concluir ${props.vistoria.etapa.rotulo}?`,
      description: 'A etapa fica fechada e a proxima da cadeia de fiscalizacao e liberada.',
      // `success`: concluir e avanco de fluxo, nao destruicao nem alerta. Verde
      // tambem separa visualmente das confirmacoes de exclusao (danger, em
      // vermelho) -- num modulo com as duas acoes na mesma tela, a cor e a
      // primeira coisa que a pessoa le antes do texto.
      variant: 'success',
      confirmText: 'Concluir',
    },
    enviarConclusao,
  );
}

function enviarConclusao() {
  concluindo.value = true;

  router.post(route('cisternas.vistorias.concluir', props.vistoria.id), {}, {
    preserveScroll: true,
    onError: (erros) => {
      const pendencias = Object.values(erros ?? {});

      toast(
        pendencias.length > 0
          ? pendencias.join(' ')
          : 'Nao foi possivel concluir a etapa.',
        'error',
      );
    },
    onFinish: () => { concluindo.value = false; },
  });
}

/**
 * O resource entrega os itens como lista; o ChecklistItens trabalha com objeto
 * indexado pelo item. A mesma conversao do composable, aqui em modo leitura.
 */
const checklist = computed(() => {
  const mapa = {};

  (props.vistoria.itens ?? []).forEach((i) => {
    mapa[i.item] = {
      conferido: Boolean(i.conferido),
      quantidade: i.quantidade ?? '',
      detalhes: i.detalhes ?? null,
      observacao: i.observacao ?? '',
    };
  });

  return mapa;
});

const itensResponsavel = computed(() => [
  { rotulo: 'Engenheiro', valor: props.vistoria.engenheiro?.nome },
  { rotulo: 'CREA', valor: props.vistoria.engenheiro?.crea },
  { rotulo: 'ART', valor: props.vistoria.engenheiro?.art },
  { rotulo: 'Data do relatorio', valor: dataBr(props.vistoria.data_relatorio) },
  { rotulo: 'Local', valor: props.vistoria.local_relatorio },
  { rotulo: 'Nº de instalacao', valor: props.vistoria.numero_instalacao },
]);

const itensAdministrativos = computed(() => {
  const a = props.vistoria.dados_administrativos ?? {};

  return [
    { rotulo: 'Processo SEI', valor: a.processo_sei },
    { rotulo: 'Contrato', valor: a.contrato },
    { rotulo: 'Empenho', valor: a.empenho },
    { rotulo: 'Placas de obra', valor: a.placa_obras },
  ];
});

const itensLocal = computed(() => {
  const l = props.vistoria.local ?? {};

  return [
    { rotulo: 'Endereco', valor: l.endereco },
    { rotulo: 'Bairro', valor: l.bairro },
    {
      rotulo: 'Coordenada',
      valor: l.latitude && l.longitude ? `${l.latitude}, ${l.longitude}` : null,
    },
  ];
});

function dataBr(iso) {
  if (!iso) return null;

  const [ano, mes, dia] = String(iso).slice(0, 10).split('-');

  return dia ? `${dia}/${mes}/${ano}` : iso;
}
</script>
