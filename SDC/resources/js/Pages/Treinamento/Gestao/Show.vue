<script setup>
/**
 * Pagina do curso na Area do Gestor (issue #86): resumo, turmas, cronograma
 * e historico do fluxo. Turmas/vagas/periodo/cronograma sao escritos por
 * aqui (GestaoCursoComandos); conteudo (EAD), alunos e inscritos continuam
 * nas telas que ja existiam - a gestao so linka para elas.
 */
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import StatusExibicaoCursoBadge from '@/Components/Molecules/Treinamento/StatusExibicaoCursoBadge.vue';
import TurmasPainel from '@/Components/Organisms/Treinamento/Gestao/TurmasPainel.vue';
import CronogramaPainel from '@/Components/Organisms/Treinamento/Gestao/CronogramaPainel.vue';
import HistoricoCursoList from '@/Components/Organisms/Treinamento/Gestao/HistoricoCursoList.vue';
import CancelarCursoModal from '@/Components/Organisms/Treinamento/Gestao/CancelarCursoModal.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  curso: { type: Object, required: true },
  turmas: { type: Array, default: () => [] },
  historico: { type: Object, required: true },
  tipoEventoCronogramaOptions: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
  links: { type: Object, default: () => ({}) },
});

const { can } = usePermissions();
const { show: toast } = useToast();

const podeConfigurar = computed(() => can('treinamento.cursos.configurar'));
const podeCancelar = computed(() => can('treinamento.cursos.cancelar'));

// StatusExibicaoCurso::getBadgeColor() do backend - GestaoCursoConsulta::resumo()
// so manda o `variant` (semantica do Badge), que repete "info" para Publicado
// e Em andamento. A COR (indigo vs azul) e o que distingue os dois aqui, e
// como enum e curto e estavel (bloco 1), o mapa e repetido no cliente.
const CORES_STATUS = { PLANEJADO: 'gray', PUBLICADO: 'indigo', EM_ANDAMENTO: 'blue', CONCLUIDO: 'green', CANCELADO: 'red' };
const corStatus = computed(() => CORES_STATUS[props.curso.status_exibicao] ?? 'gray');

function formatarData(data) {
  if (!data) return '—';
  const d = new Date(`${data}T00:00:00`);
  return Number.isNaN(d.getTime()) ? data : d.toLocaleDateString('pt-BR');
}

function recarregar(campos) {
  router.reload({ only: campos, preserveScroll: true });
}

// Vagas e periodo do curso inteiro - so manda quando muda de verdade (evita
// PUT em branco a cada perda de foco do campo).
const vagasForm = ref(String(props.curso.numero_vagas ?? ''));
function salvarVagas() {
  const valor = vagasForm.value.trim();
  router.put(route('treinamentos.gestor.vagas.update', props.curso.id), { numero_vagas: valor === '' ? null : valor }, {
    preserveScroll: true,
    onSuccess: () => toast('Vagas do curso atualizadas.', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível atualizar as vagas.', 'error'),
  });
}

const periodoForm = ref({ data_inicio: props.curso.data_inicio ?? '', data_fim: props.curso.data_fim ?? '' });
function salvarPeriodo() {
  router.put(route('treinamentos.gestor.periodo.update', props.curso.id), { ...periodoForm.value }, {
    preserveScroll: true,
    onSuccess: () => toast('Período do curso atualizado.', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível atualizar o período.', 'error'),
  });
}

const modalCancelar = ref(false);
</script>

<template>
  <div class="w-full space-y-6 pb-8">
    <PageHeader
      :title="curso.titulo"
      :description="`Gestão do curso · ${curso.tipo_label}`"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    >
      <template #actions>
        <Button variant="outline" size="sm" :href="links.detalhes">Ver detalhes do curso</Button>
        <Button v-if="podeCancelar && curso.status_exibicao !== 'CANCELADO'" variant="danger" size="sm" @click="modalCancelar = true">
          Cancelar curso
        </Button>
      </template>
    </PageHeader>

    <div class="flex flex-wrap items-center gap-2">
      <StatusExibicaoCursoBadge :color="corStatus" :label="curso.status_exibicao_label" />
      <Badge v-if="curso.situacao !== 'ATIVO'" variant="warning" size="sm">{{ curso.situacao_label }}</Badge>
      <Badge v-if="curso.publicado" variant="info" size="sm">Publicado no Portal</Badge>
    </div>

    <!-- Resumo -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
      <CardBase padding="md">
        <Text size="xs" color="muted">Turmas</Text>
        <Text size="lg" class="font-semibold">{{ curso.total_turmas }}</Text>
      </CardBase>
      <CardBase padding="md">
        <Text size="xs" color="muted">Inscritos</Text>
        <Text size="lg" class="font-semibold">{{ curso.total_inscricoes }}</Text>
      </CardBase>
      <CardBase padding="md">
        <Text size="xs" color="muted">Pendentes</Text>
        <Text size="lg" class="font-semibold">{{ curso.inscricoes_pendentes }}</Text>
      </CardBase>
      <CardBase padding="md">
        <Text size="xs" color="muted">Vagas ocupadas</Text>
        <Text size="lg" class="font-semibold">{{ curso.vagas_ocupadas }}</Text>
      </CardBase>
      <CardBase padding="md">
        <Text size="xs" color="muted">Vagas restantes</Text>
        <Text size="lg" class="font-semibold">{{ curso.vagas_restantes ?? '∞' }}</Text>
      </CardBase>
      <CardBase padding="md">
        <Text size="xs" color="muted">Modalidade</Text>
        <Text size="lg" class="font-semibold">{{ curso.tipo_label }}</Text>
      </CardBase>
    </div>

    <!-- Links para as telas que ja existiam -->
    <div class="flex flex-wrap gap-2">
      <Button v-if="links.conteudo" variant="secondary" size="sm" :href="links.conteudo">Conteúdo do curso</Button>
      <Button v-if="links.alunos" variant="secondary" size="sm" :href="links.alunos">Alunos</Button>
      <Button variant="secondary" size="sm" :href="links.inscricoes">Inscritos ({{ curso.total_inscricoes }})</Button>
    </div>

    <!-- Vagas e periodo do curso inteiro (so vale quando o curso nao tem
         turma - com turma, quem manda e o periodo/vagas dela). -->
    <CardBase padding="md">
      <Text size="sm" class="mb-3 font-semibold">Vagas e período do curso</Text>
      <Text v-if="curso.usa_turma && curso.total_turmas > 0" size="xs" color="muted" class="mb-3">
        Este curso já tem turma: o período e as vagas que valem para a inscrição são os da turma escolhida, não os daqui.
      </Text>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label class="field-label">Vagas (em branco = ilimitadas)</label>
          <div class="flex gap-2">
            <TextInput v-model="vagasForm" type="number" min="1" :disabled="!podeConfigurar" />
            <Button v-if="podeConfigurar" variant="secondary" size="sm" @click="salvarVagas">Salvar</Button>
          </div>
        </div>
        <div>
          <label class="field-label">Data de início</label>
          <div class="flex gap-2">
            <TextInput v-model="periodoForm.data_inicio" type="date" :disabled="!podeConfigurar" />
          </div>
        </div>
        <div>
          <label class="field-label">Data de término</label>
          <div class="flex gap-2">
            <TextInput v-model="periodoForm.data_fim" type="date" :disabled="!podeConfigurar" />
            <Button v-if="podeConfigurar" variant="secondary" size="sm" @click="salvarPeriodo">Salvar</Button>
          </div>
        </div>
      </div>
    </CardBase>

    <TurmasPainel
      :treinamento-id="curso.id"
      :turmas="turmas"
      :usa-turma="curso.usa_turma"
      :municipios="municipios"
      :pode-configurar="podeConfigurar"
      @alterado="recarregar(['curso', 'turmas'])"
    />

    <CronogramaPainel
      :treinamento-id="curso.id"
      :turmas="turmas"
      :tipo-options="tipoEventoCronogramaOptions"
      :pode-configurar="podeConfigurar"
      @alterado="recarregar(['historico'])"
    />

    <HistoricoCursoList :treinamento-id="curso.id" :historico="historico" />

    <CancelarCursoModal
      :show="modalCancelar"
      :treinamento-id="curso.id"
      :titulo-curso="curso.titulo"
      @fechar="modalCancelar = false"
    />
  </div>
</template>

<style scoped>
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
</style>
