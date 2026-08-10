<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import TreinamentoHeader from '@/Components/Treinamento/TreinamentoHeader.vue';
import TreinamentoTabs from '@/Components/Treinamento/TreinamentoTabs.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';
import { useTabs } from '@/Composables/core/useTabs';
import { router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  treinamento: {
    type: Object,
    required: true,
  },
  minhaInscricao: {
    type: Object,
    default: null,
  },
});

const { can } = usePermissions();
const { show: toast } = useToast();
const tabs = useTabs('detalhes');

const tabConfig = computed(() => [
  { id: 'detalhes', label: 'Detalhes', icon: DocumentTextIcon },
  { id: 'modulos', label: 'Módulos', icon: BookOpenIcon, badge: props.treinamento.modulos?.length || null, hidden: !props.treinamento.modulos?.length },
  { id: 'inscricao', label: 'Inscrição', icon: UsersIcon },
]);

const formatDate = (dateValue) => {
  if (!dateValue) return null;
  const str = String(dateValue).trim();
  if (str.includes('/')) return str;
  const d = new Date(dateValue);
  if (isNaN(d.getTime())) return str;
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

function postAction(url, data = {}, successMessage = 'Feito.') {
  router.post(route(url, props.treinamento.id), data, {
    preserveScroll: true,
    onSuccess: () => toast(successMessage, 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Nao foi possivel concluir a acao.', 'error'),
  });
}

const inscreverSe = () => postAction('treinamentos.inscrever', {}, 'Inscricao realizada! Aguarde a aprovacao.');

const confirmarPresenca = () => {
  router.post(route('treinamentos.inscricoes.autoconfirmar', props.minhaInscricao.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Presença confirmada com sucesso!', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível confirmar a presença.', 'error'),
  });
};
const publicar = () => postAction('treinamentos.publicar', {}, 'Treinamento publicado no Portal do Cidadao!');
const liberarPresenca = () => postAction('treinamentos.liberar-presenca', {}, 'Presenca liberada.');
const bloquearPresenca = () => postAction('treinamentos.bloquear-presenca', {}, 'Presenca bloqueada.');
const transicionar = (status) => postAction('treinamentos.status', { status }, 'Status atualizado.');

const podeTransicionarPara = (status) => (props.treinamento.status_transicoes || []).includes(status);

function copiarLinkPublico() {
  const url = `${window.location.origin}/portal-treinamento/eventos/${props.treinamento.link_publico_slug}`;
  navigator.clipboard?.writeText(url);
  toast('Link publico copiado!', 'success');
}
</script>

<template>

    <div class="treinamento-show-container">
      <TreinamentoHeader :treinamento="treinamento" />

      <!-- Ações de fluxo (admin) -->
      <div class="mb-6 flex flex-wrap gap-2">
        <Button v-if="can('treinamento.cursos.edit') && !treinamento.esta_publicado" variant="secondary" size="sm" @click="publicar">
          Publicar no Portal
        </Button>
        <Button v-if="can('treinamento.cursos.edit') && podeTransicionarPara('EM_ANDAMENTO')" variant="primary" size="sm" @click="transicionar('EM_ANDAMENTO')">
          Iniciar Treinamento
        </Button>
        <Button v-if="can('treinamento.cursos.edit') && podeTransicionarPara('CONCLUIDO')" variant="success" size="sm" @click="transicionar('CONCLUIDO')">
          Finalizar Treinamento
        </Button>
        <Button v-if="can('treinamento.cursos.edit') && podeTransicionarPara('CANCELADO')" variant="danger" size="sm" @click="transicionar('CANCELADO')">
          Cancelar Treinamento
        </Button>
        <Button v-if="can('treinamento.presencas.registrar') && !treinamento.presenca_liberada" variant="secondary" size="sm" @click="liberarPresenca">
          Liberar Presença (check-in)
        </Button>
        <Button v-if="can('treinamento.presencas.registrar') && treinamento.presenca_liberada" variant="secondary" size="sm" @click="bloquearPresenca">
          Bloquear Presença
        </Button>
        <Link v-if="can('treinamento.inscricoes.view')" :href="route('treinamentos.inscricoes.index', treinamento.id)">
          <Button variant="secondary" size="sm">Ver Inscritos ({{ treinamento.total_inscricoes }})</Button>
        </Link>
        <Link v-if="can('treinamento.certificados.view')" :href="route('treinamentos.certificados.index', treinamento.id)">
          <Button variant="secondary" size="sm">Ver Certificados</Button>
        </Link>
      </div>

      <!-- Sistema de Abas -->
      <TreinamentoTabs :active-tab="tabs.activeTab.value" :tabs="tabConfig" @tab-change="tabs.setActiveTab">
        <template #default="{ activeTab }">
          <!-- Aba: Detalhes -->
          <div v-if="activeTab === 'detalhes'" class="space-y-6">
            <div v-if="treinamento.esta_publicado" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
              <span>Publicado no Portal do Cidadão</span>
              <button type="button" class="text-blue-600 hover:underline dark:text-blue-400" @click="copiarLinkPublico">copiar link público</button>
            </div>

            <img
              v-if="treinamento.esta_publicado"
              :src="route('treinamentos.divulgacao', treinamento.id)"
              :alt="`Imagem de divulgação: ${treinamento.titulo}`"
              class="w-full max-w-xl rounded-lg border border-slate-200 dark:border-slate-700"
            />

            <div v-if="treinamento.descricao">
              <Heading :level="3" class="text-lg font-semibold mb-2">Descrição</Heading>
              <Text size="base" class="text-slate-700 dark:text-slate-300">
                {{ treinamento.descricao }}
              </Text>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-if="treinamento.instrutor">
                <Text size="sm" color="muted" class="mb-1">Instrutor</Text>
                <Text size="base" class="font-medium">{{ treinamento.instrutor }}</Text>
              </div>

              <div v-if="treinamento.local">
                <Text size="sm" color="muted" class="mb-1">Local</Text>
                <Text size="base" class="font-medium">{{ treinamento.local }}</Text>
              </div>

              <div v-if="treinamento.data_inicio">
                <Text size="sm" color="muted" class="mb-1">Data de Início</Text>
                <Text size="base" class="font-medium">{{ formatDate(treinamento.data_inicio) }}</Text>
              </div>

              <div v-if="treinamento.data_fim">
                <Text size="sm" color="muted" class="mb-1">Data de Término</Text>
                <Text size="base" class="font-medium">{{ formatDate(treinamento.data_fim) }}</Text>
              </div>

              <div>
                <Text size="sm" color="muted" class="mb-1">Vagas</Text>
                <Text size="base" class="font-medium">
                  <span v-if="treinamento.numero_vagas">{{ treinamento.vagas_disponiveis }} de {{ treinamento.numero_vagas }} disponíveis</span>
                  <span v-else>Ilimitadas</span>
                </Text>
              </div>

              <div>
                <Text size="sm" color="muted" class="mb-1">Frequência Mínima</Text>
                <Text size="base" class="font-medium">{{ treinamento.percentual_frequencia_minimo }}%</Text>
              </div>
            </div>
          </div>

          <!-- Aba: Módulos -->
          <div v-else-if="activeTab === 'modulos'">
            <ul class="space-y-2">
              <li
                v-for="modulo in treinamento.modulos"
                :key="modulo.id"
                class="flex items-center justify-between rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-2"
              >
                <div>
                  <Text size="sm" class="font-medium">{{ modulo.titulo }}</Text>
                  <Text size="xs" color="muted">{{ modulo.carga_horaria }}h</Text>
                </div>
                <Text size="xs" color="muted">{{ formatDate(modulo.data_prevista) || '—' }}</Text>
              </li>
            </ul>
          </div>

          <!-- Aba: Inscrição -->
          <div v-else-if="activeTab === 'inscricao'">
            <div v-if="minhaInscricao" class="flex items-center gap-3 flex-wrap">
              <Text size="sm">Sua inscrição:</Text>
              <Badge :color="minhaInscricao.status === 'APROVADA' ? 'green' : minhaInscricao.status === 'REPROVADA' ? 'red' : 'yellow'">
                {{ minhaInscricao.status_label }}
              </Badge>
              <Button
                v-if="minhaInscricao.status === 'APROVADA' && (treinamento.tipo === 'ONLINE' || treinamento.presenca_autoconfirmavel) && treinamento.presenca_liberada"
                variant="success"
                size="sm"
                @click="confirmarPresenca"
              >
                Confirmar minha presença
              </Button>
              <a
                v-if="minhaInscricao.certificado_disponivel"
                :href="route('treinamentos.certificados.imprimir', minhaInscricao.certificado_id)"
                target="_blank"
                class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
              >
                Ver / imprimir certificado
              </a>
            </div>
            <Button v-else-if="treinamento.pode_receber_inscricao" variant="primary" @click="inscreverSe">
              Inscrever-se
            </Button>
            <Text v-else size="sm" color="muted">Este treinamento não está recebendo inscrições no momento.</Text>
          </div>
        </template>
      </TreinamentoTabs>
    </div>

</template>

<style scoped>
.treinamento-show-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
