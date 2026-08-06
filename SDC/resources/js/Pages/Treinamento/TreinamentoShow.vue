<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';
import { router, Link } from '@inertiajs/vue3';

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

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <CardBase class="p-6">
        <!-- Header -->
        <div class="mb-6">
          <div class="flex items-start justify-between mb-3 gap-3">
            <Heading :level="1" class="text-2xl font-bold text-slate-800 dark:text-slate-100">
              {{ treinamento.titulo }}
            </Heading>
            <Badge :color="treinamento.status_color" class="text-sm shrink-0">
              {{ treinamento.status_label }}
            </Badge>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <Badge :color="treinamento.tipo_color">
              {{ treinamento.tipo_label }}
            </Badge>
            <Badge color="gray">
              {{ treinamento.categoria_label }}
            </Badge>
            <Text size="sm" color="muted">
              {{ treinamento.carga_horaria }}h de carga horária
            </Text>
          </div>

          <div v-if="treinamento.esta_publicado" class="mt-3 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <span>Publicado no Portal do Cidadão</span>
            <button type="button" class="text-blue-600 hover:underline dark:text-blue-400" @click="copiarLinkPublico">copiar link público</button>
          </div>

          <div v-if="treinamento.esta_publicado" class="mt-4">
            <img
              :src="route('treinamentos.divulgacao', treinamento.id)"
              :alt="`Imagem de divulgação: ${treinamento.titulo}`"
              class="w-full max-w-xl rounded-lg border border-slate-200 dark:border-slate-700"
            />
            <a
              :href="route('treinamentos.divulgacao', treinamento.id)"
              download
              class="mt-2 inline-block text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
            >
              Baixar imagem de divulgação
            </a>
          </div>
        </div>

        <!-- Ações de fluxo (admin) -->
        <div class="mb-6 flex flex-wrap gap-2 border-y border-slate-200 dark:border-slate-700 py-4">
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

        <!-- Descrição -->
        <div v-if="treinamento.descricao" class="mb-6">
          <Heading :level="3" class="text-lg font-semibold mb-2">
            Descrição
          </Heading>
          <Text size="base" class="text-slate-700 dark:text-slate-300">
            {{ treinamento.descricao }}
          </Text>
        </div>

        <!-- Informações Detalhadas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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

        <!-- Módulos -->
        <div v-if="treinamento.modulos?.length" class="border-t border-slate-200 dark:border-slate-700 pt-6 mb-6">
          <Heading :level="3" class="text-lg font-semibold mb-3">Módulos</Heading>
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

        <!-- Minha inscrição / Ações do participante -->
        <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
          <div v-if="minhaInscricao" class="flex items-center gap-3 flex-wrap">
            <Text size="sm">Sua inscrição:</Text>
            <Badge :color="minhaInscricao.status === 'APROVADA' ? 'green' : minhaInscricao.status === 'REPROVADA' ? 'red' : 'yellow'">
              {{ minhaInscricao.status_label }}
            </Badge>
            <Button
              v-if="minhaInscricao.status === 'APROVADA' && treinamento.tipo === 'ONLINE' && treinamento.presenca_liberada"
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
      </CardBase>
    </div>

</template>
