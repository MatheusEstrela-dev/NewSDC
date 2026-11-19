<script setup>
/**
 * Gestao do curso (issue #86): situacao, resumo das inscricoes, vagas,
 * periodo, cronograma, historico e cancelamento. Compartilhado pela aba
 * Gestao da Area do Gestor (EAD) e pela pagina avulsa das demais modalidades
 * (Gestao/Show), que so mudam a moldura em volta.
 */
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import StatusExibicaoCursoBadge from '@/Components/Molecules/Treinamento/StatusExibicaoCursoBadge.vue';
import CronogramaPainel from '@/Components/Organisms/Treinamento/Gestao/CronogramaPainel.vue';
import HistoricoCursoList from '@/Components/Organisms/Treinamento/Gestao/HistoricoCursoList.vue';
import CancelarCursoModal from '@/Components/Organisms/Treinamento/Gestao/CancelarCursoModal.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  curso: { type: Object, required: true },
  historico: { type: Object, required: true },
  tipoEventoCronogramaOptions: { type: Array, default: () => [] },
  inscricoesUrl: { type: String, default: null },
});

const { can } = usePermissions();
const { show: toast } = useToast();

const podeConfigurar = computed(() => can('treinamento.cursos.configurar'));
const podeCancelar = computed(() => can('treinamento.cursos.cancelar') && props.curso.status_exibicao !== 'CANCELADO');

// StatusExibicaoCurso::getBadgeColor() do backend - GestaoCursoConsulta::resumo()
// so manda o `variant` (semantica do Badge), que repete "info" para Publicado
// e Em andamento. A COR (indigo vs azul) e o que distingue os dois aqui, e
// como enum e curto e estavel (bloco 1), o mapa e repetido no cliente.
const CORES_STATUS = { PLANEJADO: 'gray', PUBLICADO: 'indigo', EM_ANDAMENTO: 'blue', CONCLUIDO: 'green', CANCELADO: 'red' };
const corStatus = computed(() => CORES_STATUS[props.curso.status_exibicao] ?? 'gray');

const resumo = computed(() => [
  { rotulo: 'Inscritos', valor: props.curso.total_inscricoes },
  { rotulo: 'Pendentes', valor: props.curso.inscricoes_pendentes },
  { rotulo: 'Vagas ocupadas', valor: props.curso.vagas_ocupadas },
  { rotulo: 'Vagas restantes', valor: props.curso.vagas_restantes ?? '∞' },
  { rotulo: 'Modalidade', valor: props.curso.tipo_label },
]);

function recarregar(campos) {
  router.reload({ only: campos, preserveScroll: true });
}

function avisarErro(errors, padrao) {
  toast(Object.values(errors)[0] || padrao, 'error');
}

// Vagas e periodo do curso - so manda quando muda de verdade (evita
// PUT em branco a cada perda de foco do campo).
const vagasForm = ref(String(props.curso.numero_vagas ?? ''));
function salvarVagas() {
  const valor = vagasForm.value.trim();
  router.put(route('treinamentos.gestor.vagas.update', props.curso.id), { numero_vagas: valor === '' ? null : valor }, {
    preserveScroll: true,
    onSuccess: () => toast('Vagas do curso atualizadas.', 'success'),
    onError: (errors) => avisarErro(errors, 'Não foi possível atualizar as vagas.'),
  });
}

const periodoForm = ref({ data_inicio: props.curso.data_inicio ?? '', data_fim: props.curso.data_fim ?? '' });
function salvarPeriodo() {
  router.put(route('treinamentos.gestor.periodo.update', props.curso.id), { ...periodoForm.value }, {
    preserveScroll: true,
    onSuccess: () => toast('Período do curso atualizado.', 'success'),
    onError: (errors) => avisarErro(errors, 'Não foi possível atualizar o período.'),
  });
}

const modalCancelar = ref(false);
</script>

<template>
  <div class="min-w-0 space-y-6">
    <div class="flex flex-wrap items-center gap-2">
      <StatusExibicaoCursoBadge :color="corStatus" :label="curso.status_exibicao_label" />
      <Badge v-if="curso.situacao !== 'ATIVO'" variant="warning" size="sm">{{ curso.situacao_label }}</Badge>
      <Badge v-if="curso.publicado" variant="info" size="sm">Publicado no Portal</Badge>
      <div class="ml-auto flex flex-wrap gap-2">
        <Button v-if="inscricoesUrl" variant="secondary" size="sm" :href="inscricoesUrl">
          Inscritos ({{ curso.total_inscricoes }})
        </Button>
        <Button v-if="podeCancelar" variant="danger" size="sm" @click="modalCancelar = true">Cancelar curso</Button>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
      <CardBase v-for="item in resumo" :key="item.rotulo" padding="md">
        <Text size="xs" color="muted">{{ item.rotulo }}</Text>
        <Text size="lg" class="font-semibold">{{ item.valor }}</Text>
      </CardBase>
    </div>

    <CardBase padding="md">
      <Text size="sm" class="mb-3 font-semibold">Vagas e período do curso</Text>
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
          <TextInput v-model="periodoForm.data_inicio" type="date" :disabled="!podeConfigurar" />
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

    <CronogramaPainel
      :treinamento-id="curso.id"
      :tipo-options="tipoEventoCronogramaOptions"
      :pode-configurar="podeConfigurar"
      @alterado="recarregar(['historico'])"
    />

    <HistoricoCursoList :historico="historico" />

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
