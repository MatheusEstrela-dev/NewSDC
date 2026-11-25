<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import SectionCard from '@/Components/Atoms/Card/SectionCard.vue';
import CursoHero from '@/Components/Molecules/Treinamento/CursoHero.vue';
import CursoInfoTile from '@/Components/Molecules/Treinamento/CursoInfoTile.vue';
import CursoResumoTurma from '@/Components/Molecules/Treinamento/CursoResumoTurma.vue';
import {
  BookOpenIcon as CatalogoIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  ClockIcon,
  MapPinIcon,
  UserIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import CursoSelos from '@/Components/Molecules/Treinamento/CursoSelos.vue';
import InscricaoSexoModal from '@/Components/Organisms/Treinamento/InscricaoSexoModal.vue';
import ListaDeVersoes from '@/Components/Organisms/Treinamento/Versoes/ListaDeVersoes.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import DocumentDuplicateIcon from '@/Components/Icons/DocumentDuplicateIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import TreinamentoTabs from '@/Components/Treinamento/TreinamentoTabs.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';
import { useToast } from '@/Composables/useToast';
import { useTabs } from '@/Composables/core/useTabs';
import { router, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { useCursosAoVivo } from '@/Composables/treinamento/useCursosAoVivo';

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
  professores: {
    type: Array,
    default: () => [],
  },
  sexoPadrao: {
    type: String,
    default: null,
  },
  dataNascimentoPadrao: {
    type: String,
    default: null,
  },
  versoes: {
    type: Array,
    default: () => [],
  },
  pode_criar_versao: {
    type: Boolean,
    default: false,
  },
  rascunho_id: {
    type: Number,
    default: null,
  },
  // So para quem baixa certificados: { pode_emitir, nome_sugerido, emitidos[] }.
  certificados_instrutor: {
    type: Object,
    default: null,
  },
});

useCursosAoVivo(['treinamento', 'minhaInscricao', 'professores', 'versoes', 'pode_criar_versao', 'rascunho_id', 'certificados_instrutor']);

const { can } = usePermissions();
const { show: toast } = useToast();
const tabs = useTabs('detalhes');
const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

const tabConfig = computed(() => [
  { id: 'detalhes', label: 'Detalhes', icon: DocumentTextIcon },
  { id: 'modulos', label: 'Módulos', icon: BookOpenIcon, badge: props.treinamento.modulos?.length || null, hidden: !props.treinamento.modulos?.length },
  { id: 'inscricao', label: 'Inscrições', icon: UsersIcon },
]);

// Servidor so se inscreve em curso para servidores; EAD ainda nao publicado nao aceita inscricao.
const destinadoAoServidor = computed(() => props.treinamento.publico_alvo === 'SERVIDORES');
const podeInscreverServidor = computed(() => destinadoAoServidor.value
  && props.treinamento.pode_receber_inscricao
  && (props.treinamento.tipo !== 'EAD' || props.treinamento.esta_publicado));

const formatDate = (dateValue) => {
  if (!dateValue) return null;
  const str = String(dateValue).trim();
  if (str.includes('/')) return str;
  const d = new Date(dateValue);
  if (isNaN(d.getTime())) return str;
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

// successMessage null = sem toast local (o flash do servidor já informa o resultado).
function postAction(url, data = {}, successMessage = 'Feito.') {
  router.post(route(url, props.treinamento.id), data, {
    preserveScroll: true,
    onSuccess: () => { if (successMessage) toast(successMessage, 'success'); },
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível concluir a ação.', 'error'),
  });
}

const modalInscricao = ref(false);
const inscreverSe = (perfil) => {
  postAction('treinamentos.inscrever', perfil, null);
  modalInscricao.value = false;
};

const confirmarPresenca = () => {
  router.post(route('treinamentos.inscricoes.autoconfirmar', props.minhaInscricao.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Presença confirmada com sucesso!', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível confirmar a presença.', 'error'),
  });
};
const criarVersao = () => postAction('treinamentos.versoes.store', {}, 'Nova versão criada.');
const publicar = () => postAction('treinamentos.publicar', {}, 'Treinamento publicado no Portal do Cidadão!');
const liberarPresenca = () => postAction('treinamentos.liberar-presenca', {}, 'Presenca liberada.');
const bloquearPresenca = () => postAction('treinamentos.bloquear-presenca', {}, 'Presenca bloqueada.');
const transicionar = (status) => postAction('treinamentos.status', { status }, 'Status atualizado.');

const confirmarFinalizar = () => pedirConfirmacao({
  title: 'Finalizar treinamento',
  message: `Finalizar "${props.treinamento.titulo}"?`,
  description: 'O treinamento passa a Concluído e deixa de receber inscrições e presenças.',
  variant: 'warning',
  confirmText: 'Finalizar',
  cancelText: 'Voltar',
}, () => transicionar('CONCLUIDO'));

const confirmarCancelar = () => pedirConfirmacao({
  title: 'Cancelar treinamento',
  message: `Cancelar "${props.treinamento.titulo}"?`,
  description: 'O cancelamento fica registrado no histórico e não pode ser desfeito por esta tela.',
  variant: 'danger',
  confirmText: 'Cancelar treinamento',
  cancelText: 'Voltar',
}, () => transicionar('CANCELADO'));

// O EAD publica pelo conteúdo do curso (item, prova e notas da versão são conferidos lá).
const ehEad = computed(() => props.treinamento.tipo === 'EAD');
// Link e selo do Portal do Cidadão só fazem sentido em curso de cidadãos (o de servidores não tem página pública).
const publicoCidadao = computed(() => props.treinamento.publico_alvo === 'CIDADAOS');

const podeTransicionarPara = (status) => (props.treinamento.status_transicoes || []).includes(status);

// Certificados de instrutor: professor designado (EAD) ou nome do instrutor (presencial).
const nomeInstrutor = ref(props.certificados_instrutor?.nome_sugerido ?? '');
const professoresSemCertificado = computed(() => {
  const comCertificado = new Set((props.certificados_instrutor?.emitidos ?? []).map((c) => c.user_id).filter(Boolean));
  return props.professores.filter((p) => !comCertificado.has(p.id));
});

function emitirCertificadoInstrutor(dados) {
  router.post(route('treinamentos.certificados-instrutor.store', props.treinamento.id), dados, {
    preserveScroll: true,
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível emitir o certificado.', 'error'),
  });
}

// A impressao e uma pagina Blade, fora do Inertia: abre em aba propria.
function abrirCertificado(url) {
  window.open(url, '_blank', 'noopener');
}

const corInscricao = (status) => ({ APROVADA: 'green', REPROVADA: 'red' }[status] ?? 'yellow');

// Professores (somente cursos EAD)
const buscaProfessor = ref('');
const candidatos = ref([]);
let timerBusca = null;

const candidatosDisponiveis = computed(() => {
  const ja = new Set(props.professores.map((p) => p.id));
  return candidatos.value.filter((c) => !ja.has(c.id));
});

watch(buscaProfessor, (valor) => {
  clearTimeout(timerBusca);
  const termo = valor.trim();
  if (!termo) {
    candidatos.value = [];
    return;
  }
  timerBusca = setTimeout(async () => {
    const { data } = await axios.get(route('treinamentos.professores.candidatos'), { params: { busca: termo } });
    candidatos.value = data;
  }, 300);
});

function adicionarProfessor(userId) {
  router.post(route('treinamentos.professores.store', props.treinamento.id), { user_id: userId }, {
    preserveScroll: true,
    onSuccess: () => {
      toast('Professor designado.', 'success');
      buscaProfessor.value = '';
    },
    onError: (errors) => toast(Object.values(errors)[0] || 'Nao foi possivel designar o professor.', 'error'),
  });
}

function removerProfessor(professor) {
  pedirConfirmacao({
    title: 'Remover professor',
    message: `Remover ${professor.name} dos professores deste curso?`,
    description: 'Ele perde o acesso à área do professor deste curso.',
    variant: 'danger',
    confirmText: 'Remover',
    cancelText: 'Voltar',
  }, () => removerProfessorConfirmado(professor.id));
}

function removerProfessorConfirmado(userId) {
  router.delete(route('treinamentos.professores.destroy', [props.treinamento.id, userId]), {
    preserveScroll: true,
    onSuccess: () => toast('Professor removido.', 'success'),
  });
}

onBeforeUnmount(() => clearTimeout(timerBusca));

function copiarLinkPublico() {
  const url = `${window.location.origin}/portal-treinamento/eventos/${props.treinamento.link_publico_slug}`;
  navigator.clipboard?.writeText(url);
  toast('Link publico copiado!', 'success');
}
</script>

<template>

    <div class="treinamento-show-container">
      <CursoHero :treinamento="treinamento" :pode-trocar-capa="can('treinamento.cursos.configurar')">
        <template v-if="treinamento.tipo === 'EAD' && can('treinamento.cursos.configurar')" #selos>
          <CursoSelos :publicado="treinamento.esta_publicado" :situacao="treinamento.situacao" :situacao-label="treinamento.situacao_label" />
        </template>
        <template #tabs>
          <TreinamentoTabs :active-tab="tabs.activeTab.value" :tabs="tabConfig" @tab-change="tabs.setActiveTab" />
        </template>
      </CursoHero>

      <!-- Nova versao (RN07): destaque no topo, antes das demais acoes. -->
      <div v-if="pode_criar_versao || (rascunho_id && can('treinamento.cursos.edit'))" class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-900 dark:bg-blue-950/40">
        <DocumentDuplicateIcon class="h-6 w-6 shrink-0 text-blue-600 dark:text-blue-400" aria-hidden="true" />
        <div class="min-w-0 flex-1">
          <Text size="sm" class="font-semibold">Nova versão do curso</Text>
          <Text size="xs" color="muted">
            {{ pode_criar_versao ? 'Crie uma nova versão para atualizar o conteúdo sem alterar a versão em andamento.' : 'Há um rascunho de nova versão em edição.' }}
          </Text>
        </div>
        <Button v-if="pode_criar_versao" variant="primary" size="sm" @click="criarVersao">
          <DocumentDuplicateIcon class="mr-1.5 h-4 w-4" aria-hidden="true" />
          Nova versão
        </Button>
        <Link v-else :href="route('treinamentos.professor.conteudo', rascunho_id)">
          <Button variant="primary" size="sm">
            <DocumentDuplicateIcon class="mr-1.5 h-4 w-4" aria-hidden="true" />
            Continuar rascunho da nova versão
          </Button>
        </Link>
      </div>

      <!-- Ações de fluxo (admin) -->
      <div class="mb-6 flex flex-wrap items-center gap-2">
        <template v-if="can('treinamento.cursos.configurar') && !treinamento.esta_publicado">
          <Link v-if="ehEad" :href="route('treinamentos.professor.conteudo', treinamento.id)">
            <Button variant="secondary" size="sm">Publicar pelo conteúdo do curso</Button>
          </Link>
          <Button v-else variant="secondary" size="sm" @click="publicar">
            Publicar no Portal
          </Button>
        </template>
        <Button v-if="can('treinamento.cursos.configurar') && podeTransicionarPara('EM_ANDAMENTO')" variant="primary" size="sm" @click="transicionar('EM_ANDAMENTO')">
          Iniciar Treinamento
        </Button>
        <Button v-if="treinamento.usa_controle_presenca && can('treinamento.presencas.registrar') && !treinamento.presenca_liberada" variant="secondary" size="sm" @click="liberarPresenca">
          Liberar Presença (check-in)
        </Button>
        <Button v-if="treinamento.usa_controle_presenca && can('treinamento.presencas.registrar') && treinamento.presenca_liberada" variant="secondary" size="sm" @click="bloquearPresenca">
          Bloquear Presença
        </Button>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <!-- Coluna principal: conteudo da aba ativa -->
        <div class="min-w-0 space-y-6">
          <!-- Aba: Detalhes -->
          <template v-if="tabs.activeTab.value === 'detalhes'">
            <SectionCard v-if="treinamento.descricao">
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sobre o curso</p>
              <Heading :level="3" class="mt-1 text-lg font-semibold">Descrição</Heading>
              <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ treinamento.descricao }}</p>
            </SectionCard>

            <SectionCard>
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Informações essenciais</p>
              <Heading :level="3" class="mt-1 text-lg font-semibold">Detalhes da capacitação</Heading>
              <div :class="treinamento.curso_nome ? 'xl:[&>*:last-child:nth-child(even)]:col-span-2' : 'xl:[&>*:last-child:nth-child(odd)]:col-span-2'" class="mt-4 grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-slate-200 bg-slate-200 dark:border-slate-700/50 dark:bg-slate-700/50 xl:grid-cols-2 [&>*]:bg-white dark:[&>*]:bg-slate-900">
                <CursoInfoTile v-if="treinamento.curso_nome" :icon="CatalogoIcon" rotulo="Curso do catálogo" :valor="treinamento.curso_nome" class="xl:col-span-2" />
                <CursoInfoTile v-if="treinamento.instrutor" :icon="UserIcon" rotulo="Instrutor" :valor="treinamento.instrutor" />
                <CursoInfoTile v-if="treinamento.local" :icon="MapPinIcon" rotulo="Local" :valor="treinamento.local" />
                <CursoInfoTile v-if="treinamento.municipio_nome" :icon="BuildingOffice2Icon" rotulo="Município de realização" :valor="treinamento.municipio_nome" />
                <CursoInfoTile v-if="treinamento.data_inicio" :icon="CalendarDaysIcon" rotulo="Data de início" :valor="formatDate(treinamento.data_inicio)" />
                <CursoInfoTile v-if="treinamento.data_fim" :icon="CalendarDaysIcon" rotulo="Data de término" :valor="formatDate(treinamento.data_fim)" />
                <CursoInfoTile v-if="treinamento.hora_inicio" :icon="ClockIcon" rotulo="Horário de início" :valor="treinamento.hora_inicio" />
                <CursoInfoTile :icon="UsersIcon" rotulo="Público-alvo" :valor="treinamento.publico_alvo_label" />
              </div>
            </SectionCard>

            <SectionCard v-if="treinamento.esta_publicado">
              <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Divulgação</p>
                  <Heading :level="3" class="mt-1 text-lg font-semibold">
                    {{ publicoCidadao ? 'Publicado no Portal do Cidadão' : 'Publicado' }}
                  </Heading>
                </div>
                <Button v-if="publicoCidadao" variant="secondary" size="sm" @click="copiarLinkPublico">Copiar link público</Button>
              </div>
              <img
                :src="route('treinamentos.divulgacao', treinamento.id)"
                :alt="`Imagem de divulgação: ${treinamento.titulo}`"
                class="w-full max-w-xl rounded-lg border border-slate-200 dark:border-slate-700"
              />
            </SectionCard>

            <ListaDeVersoes v-if="versoes.length > 1" :versoes="versoes" />
          </template>

          <!-- Aba: Módulos -->
          <SectionCard v-else-if="tabs.activeTab.value === 'modulos'">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Estrutura</p>
            <Heading :level="3" class="mt-1 text-lg font-semibold">Módulos do curso</Heading>
            <ol class="mt-4 space-y-2">
              <li
                v-for="(modulo, indice) in treinamento.modulos"
                :key="modulo.id"
                class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-700/50"
              >
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                  {{ modulo.ordem || indice + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                  <Text size="sm" class="font-medium">{{ modulo.titulo }}</Text>
                  <Text size="xs" color="muted">{{ modulo.carga_horaria }}h</Text>
                </div>
                <Text size="xs" color="muted" class="shrink-0">{{ formatDate(modulo.data_prevista) || '—' }}</Text>
              </li>
            </ol>
          </SectionCard>

          <!-- Aba: Inscrição -->
          <SectionCard v-else-if="tabs.activeTab.value === 'inscricao'">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Participação</p>
            <Heading :level="3" class="mb-4 mt-1 text-lg font-semibold">Inscrições</Heading>
            <div v-if="minhaInscricao" class="flex flex-wrap items-center gap-3">
              <Text size="sm">Sua inscrição:</Text>
              <Badge :cor="corInscricao(minhaInscricao.status)">
                {{ minhaInscricao.status_label }}
              </Badge>
              <Button
                v-if="treinamento.usa_controle_presenca && minhaInscricao.status === 'APROVADA' && (treinamento.tipo === 'ONLINE' || treinamento.presenca_autoconfirmavel) && treinamento.presenca_liberada"
                variant="success"
                size="sm"
                @click="confirmarPresenca"
              >
                Confirmar minha presença
              </Button>
              <Link
                v-if="treinamento.tipo === 'EAD' && minhaInscricao.status === 'APROVADA'"
                :href="route('treinamentos.aluno.sala', treinamento.id)"
                class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
              >
                Abrir sala
              </Link>
              <a
                v-if="minhaInscricao.certificado_disponivel"
                :href="route('treinamentos.certificados.imprimir', minhaInscricao.certificado_id)"
                target="_blank"
                class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
              >
                Ver / imprimir certificado
              </a>
            </div>
            <Button v-else-if="podeInscreverServidor" variant="primary" @click="modalInscricao = true">
              Inscrever-se
            </Button>
            <Text v-else-if="!destinadoAoServidor" size="sm" color="muted">Este treinamento é destinado ao público do Portal do Cidadão.</Text>
            <Text v-else size="sm" color="muted">Este treinamento não está recebendo inscrições no momento.</Text>
          </SectionCard>

          <!-- Professores (EAD) -->
          <SectionCard v-if="treinamento.tipo === 'EAD'">
            <div class="space-y-3">
              <Heading :level="3" class="text-lg font-semibold">Professores</Heading>
              <Text size="xs" color="muted">
                O campo Instrutor é apenas o nome exibido no curso. Designar um professor aqui é o que dá a ele acesso à área do professor.
              </Text>
              <Text v-if="!professores.length" size="sm" color="muted">Nenhum professor designado.</Text>
              <ul v-else class="space-y-2">
                <li
                  v-for="professor in professores"
                  :key="professor.id"
                  class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700/50"
                >
                  <Text size="sm" class="font-medium">{{ professor.name }}</Text>
                  <Button v-if="can('treinamento.cursos.configurar')" variant="danger" size="sm" @click="removerProfessor(professor)">
                    Remover
                  </Button>
                </li>
              </ul>

              <div v-if="can('treinamento.cursos.configurar')" class="space-y-2">
                <TextInput v-model="buscaProfessor" placeholder="Buscar professor por nome ou e-mail" />
                <ul v-if="candidatosDisponiveis.length" class="space-y-2">
                  <li
                    v-for="candidato in candidatosDisponiveis"
                    :key="candidato.id"
                    class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700/50"
                  >
                    <div>
                      <Text size="sm" class="font-medium">{{ candidato.name }}</Text>
                      <Text size="xs" color="muted">{{ candidato.email }}</Text>
                    </div>
                    <Button variant="primary" size="sm" @click="adicionarProfessor(candidato.id)">Adicionar</Button>
                  </li>
                </ul>
              </div>
            </div>
          </SectionCard>

          <!-- Certificados de instrutor -->
          <SectionCard v-if="certificados_instrutor">
            <div class="space-y-3">
              <Heading :level="3" class="text-lg font-semibold">Certificados de instrutor</Heading>
              <Text v-if="!certificados_instrutor.pode_emitir" size="xs" color="muted">
                O certificado de instrutor sai quando o curso termina (concluído ou com a data de término já passada).
              </Text>

              <ul v-if="certificados_instrutor.emitidos.length" class="space-y-2">
                <li
                  v-for="certificado in certificados_instrutor.emitidos"
                  :key="certificado.id"
                  class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700/50"
                >
                  <div class="min-w-0">
                    <Text size="sm" class="font-medium">{{ certificado.nome }}</Text>
                    <Text size="xs" color="muted">{{ certificado.codigo }} · emitido em {{ certificado.emitido_em }}</Text>
                  </div>
                  <Button variant="secondary" size="sm" @click="abrirCertificado(certificado.imprimir_url)">Imprimir</Button>
                </li>
              </ul>

              <template v-if="certificados_instrutor.pode_emitir">
                <ul v-if="professoresSemCertificado.length" class="space-y-2">
                  <li
                    v-for="professor in professoresSemCertificado"
                    :key="professor.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700/50"
                  >
                    <Text size="sm" class="font-medium">{{ professor.name }}</Text>
                    <Button variant="primary" size="sm" @click="emitirCertificadoInstrutor({ user_id: professor.id })">Emitir certificado</Button>
                  </li>
                </ul>

                <div class="space-y-1">
                  <Text size="xs" color="muted">Instrutor sem cadastro no sistema (presencial): emita pelo nome, como deve sair no certificado.</Text>
                  <div class="flex flex-col gap-2 sm:flex-row">
                    <TextInput v-model="nomeInstrutor" class="min-w-0 flex-1" placeholder="Nome do instrutor" />
                    <Button variant="primary" size="sm" :disabled="!nomeInstrutor.trim()" @click="emitirCertificadoInstrutor({ nome: nomeInstrutor })">
                      Emitir por nome
                    </Button>
                  </div>
                </div>
              </template>
            </div>
          </SectionCard>
        </div>

        <!-- Lateral: resumo da turma -->
        <aside class="min-w-0">
          <div class="lg:sticky lg:top-4">
            <CursoResumoTurma :treinamento="treinamento">
              <template #acao>
                <Button
                  v-if="can('treinamento.inscricoes.view')"
                  variant="primary"
                  full-width
                  :href="route('treinamentos.inscricoes.index', treinamento.id)"
                  :icon="ChevronRightIcon"
                  icon-position="right"
                >
                  Gerenciar inscrições
                </Button>
                <div v-else-if="minhaInscricao" class="flex flex-wrap items-center gap-2">
                  <Text size="sm">Sua inscrição:</Text>
                  <Badge :cor="corInscricao(minhaInscricao.status)">{{ minhaInscricao.status_label }}</Badge>
                </div>
                <Button v-else-if="podeInscreverServidor" variant="primary" full-width @click="modalInscricao = true">
                  Inscrever-se
                </Button>
                <Text v-else size="sm" color="muted">
                  {{ destinadoAoServidor ? 'Este treinamento não está recebendo inscrições no momento.' : 'Destinado ao público do Portal do Cidadão.' }}
                </Text>
                <div v-if="can('treinamento.inscricoes.view') || can('treinamento.certificados.view')" class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                  <Button
                    v-if="can('treinamento.inscricoes.view')"
                    variant="secondary"
                    size="sm"
                    full-width
                    :href="route('treinamentos.inscricoes.index', treinamento.id)"
                  >
                    Ver Inscritos ({{ treinamento.total_inscricoes }})
                  </Button>
                  <Button
                    v-if="can('treinamento.certificados.view')"
                    variant="secondary"
                    size="sm"
                    full-width
                    :href="route('treinamentos.certificados.index', treinamento.id)"
                  >
                    Ver Certificados
                  </Button>
                </div>
              </template>
            </CursoResumoTurma>
          </div>
        </aside>
      </div>

      <ConfirmDialog
        :is-open="confirmacao.aberto"
        v-bind="confirmacao.opcoes"
        @confirm="confirmar"
        @cancel="cancelar"
      />

      <InscricaoSexoModal
        :show="modalInscricao"
        :titulo="treinamento.titulo"
        :padrao="sexoPadrao"
        :data-nascimento-padrao="dataNascimentoPadrao"
        @fechar="modalInscricao = false"
        @confirmar="inscreverSe"
      />
    </div>

</template>

<style scoped>
.treinamento-show-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
