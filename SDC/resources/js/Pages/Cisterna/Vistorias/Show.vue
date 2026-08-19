<template>
  <AuthenticatedLayout>
    <Head :title="`Cisternas — ${vistoria.etapa.rotulo}`" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        :title="vistoria.etapa.rotulo"
        :description="`${beneficiario.nome}${vistoria.numero_instalacao ? ` — instalacao Nº ${vistoria.numero_instalacao}` : ''}`"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
      >
        <template #actions>
          <Link :href="route('cisternas.vistorias.index', beneficiario.id)" :class="BOTAO_SEC">Vistorias</Link>
          <button v-if="permissoes.editar && !editando" type="button" :class="BOTAO_SEC" @click="editar">
            Editar relatorio
          </button>
          <!--
            Concluir e o que DESTRAVA a etapa seguinte da cadeia: sem esta acao
            o relatorio do fornecedor ficava eternamente "em aberto" e COMPDEC e
            CEDEC nunca saiam de "bloqueada". O endpoint ja existia; faltava a
            tela chamar.
          -->
          <button
            v-if="permissoes.editar && !editando && !vistoria.concluida"
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

      <VistoriaForm
        v-if="editando"
        :form="form"
        :itens="itens"
        :etapa="vistoria.etapa.valor"
        :processando="form.processing"
        modo="editar"
        @arquivo="anexar"
        @submit="salvar"
        @cancel="editando = false"
      />

      <template v-else>
        <DadosBloco titulo="Responsavel tecnico" :itens="itensResponsavel" />
        <DadosBloco v-if="vistoria.dados_administrativos" titulo="Dados administrativos" :itens="itensAdministrativos" />
        <DadosBloco titulo="Local da instalacao" :itens="itensLocal" />

        <ChecklistItens :itens="itens" :model-value="checklist" somente-leitura />

        <section v-if="vistoria.observacoes" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
          <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-slate-100">Observacoes</h3>
          <p class="whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ vistoria.observacoes }}</p>
        </section>
      </template>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, shallowRef } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import EtapaVistoriaBadge from '@/Components/Atoms/Cisterna/EtapaVistoriaBadge.vue';
import DadosBloco from '@/Components/Molecules/Cisterna/DadosBloco.vue';
import ChecklistItens from '@/Components/Organisms/Cisterna/ChecklistItens.vue';
import VistoriaForm from '@/Components/Organisms/Cisterna/VistoriaForm.vue';
import { useVistoriaForm } from '@/Composables/cisterna/useVistoriaForm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  vistoria: { type: Object, required: true },
  beneficiario: { type: Object, required: true },
  itens: { type: Array, default: () => [] },
  permissoes: { type: Object, default: () => ({}) },
});

const { show: toast } = useToast();

const BOTAO = 'rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700';
const BOTAO_SEC = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';

const editando = ref(false);
const formulario = shallowRef(null);
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
  if (!window.confirm(`Concluir ${props.vistoria.etapa.rotulo}? Isso libera a proxima etapa.`)) return;

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

const form = computed(() => formulario.value?.form ?? { errors: {}, processing: false });

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

function editar() {
  formulario.value = useVistoriaForm(
    props.beneficiario.id,
    props.vistoria.etapa.valor,
    props.vistoria,
  );
  editando.value = true;
}

function anexar(evento) {
  formulario.value?.anexar(evento);
}

function salvar() {
  formulario.value?.salvar();
}

function dataBr(iso) {
  if (!iso) return null;

  const [ano, mes, dia] = String(iso).slice(0, 10).split('-');

  return dia ? `${dia}/${mes}/${ano}` : iso;
}
</script>
