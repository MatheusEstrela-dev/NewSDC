<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import InscricoesTable from '@/Components/Organisms/Treinamento/InscricoesTable.vue';
import QrScanner from '@/Components/Molecules/Treinamento/QrScanner.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';
import { useOfflinePresenca } from '@/Composables/treinamento/useOfflinePresenca';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  treinamento: { type: Object, required: true },
  inscricoes: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  filterOptions: { type: Object, default: () => ({}) },
});

const { can } = usePermissions();
const { show: toast } = useToast();
const { pendentes, sincronizando, cachearRoster, validarTokenLocal, enfileirar, sincronizar } = useOfflinePresenca();

const moduloSelecionado = ref(props.treinamento.modulos?.[0]?.id ?? null);
const chamadaHabilitada = computed(() => can('treinamento.presencas.registrar') && props.treinamento.presenca_liberada);

const pagination = computed(() => {
  const m = props.inscricoes?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 15,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

function irParaPagina(page) {
  router.visit(route('treinamentos.inscricoes.index', props.treinamento.id), {
    data: { ...props.filters, page },
    preserveState: true,
  });
}

function aprovar(inscricao) {
  router.post(route('treinamentos.inscricoes.aprovar', inscricao.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Inscrição aprovada.', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível aprovar.', 'error'),
  });
}

function reprovar(inscricao) {
  const observacoes = prompt('Motivo da reprovação:');
  if (!observacoes) return;

  router.post(route('treinamentos.inscricoes.reprovar', inscricao.id), { observacoes }, {
    preserveScroll: true,
    onSuccess: () => toast('Inscrição reprovada.', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível reprovar.', 'error'),
  });
}

// RF07: as duas acoes de presenca (manual e QR) usam axios direto (nao o
// router do Inertia) porque precisamos distinguir "erro de validacao" (tem
// error.response) de "sem conexao de verdade" (sem error.response) para so
// cair na fila offline no segundo caso.
async function marcarPresenca(inscricao) {
  if (!moduloSelecionado.value) return;

  try {
    await axios.post(route('treinamentos.presencas.manual'), {
      inscricao_id: inscricao.id,
      modulo_id: moduloSelecionado.value,
    });
    toast('Presença registrada com sucesso.', 'success');
  } catch (error) {
    if (error.response) {
      toast(Object.values(error.response.data?.errors ?? {})[0]?.[0] || error.response.data?.message || 'Não foi possível registrar a presença.', 'error');
      return;
    }

    await enfileirar({
      qr_code_token: inscricao.qr_code_token,
      modulo_id: moduloSelecionado.value,
      treinamento_id: props.treinamento.id,
    });
    toast('Sem conexão: presença salva no dispositivo e será sincronizada automaticamente.', 'info');
  }
}

async function handleQrDecode(token) {
  if (!moduloSelecionado.value) {
    toast('Selecione um módulo antes de escanear o QR Code.', 'error');
    return;
  }

  try {
    await axios.post(route('treinamentos.presencas.qr'), {
      qr_code_token: token,
      modulo_id: moduloSelecionado.value,
    });
    toast('Presença registrada com sucesso.', 'success');
  } catch (error) {
    if (error.response) {
      toast(Object.values(error.response.data?.errors ?? {})[0]?.[0] || error.response.data?.message || 'QR Code inválido ou já utilizado.', 'error');
      return;
    }

    const inscrito = await validarTokenLocal(props.treinamento.id, token);
    if (!inscrito) {
      toast('Sem conexão e este QR Code não está no cache local. Sincronize a lista de inscritos com internet antes de ir a campo.', 'error');
      return;
    }

    await enfileirar({
      qr_code_token: token,
      modulo_id: moduloSelecionado.value,
      treinamento_id: props.treinamento.id,
    });
    toast(`Sem conexão: presença de ${inscrito.nome} salva no dispositivo e será sincronizada automaticamente.`, 'info');
  }
}

async function sincronizarAgora() {
  const { sincronizados, falharam } = await sincronizar();
  if (sincronizados > 0) toast(`${sincronizados} presença(s) sincronizada(s) com sucesso.`, 'success');
  if (falharam > 0) toast(`${falharam} item(ns) não puderam ser sincronizados (verifique os dados).`, 'error');
  if (sincronizados === 0 && falharam === 0) toast('Nada pendente para sincronizar.', 'info');
}

function handleOnline() {
  if (chamadaHabilitada.value) sincronizarAgora();
}

onMounted(() => {
  if (chamadaHabilitada.value) {
    cachearRoster(props.treinamento.id).catch(() => {
      // Sem conexao no momento em que a pagina abriu - segue com o cache
      // antigo (se existir); nao ha nada de novo a fazer aqui.
    });
  }
  window.addEventListener('online', handleOnline);
});

onUnmounted(() => {
  window.removeEventListener('online', handleOnline);
});
</script>

<template>
  <div class="treinamento-inscricoes-container">
    <PageHeader
      title="Inscritos e Presença"
      :description="treinamento.titulo"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <CardBase v-if="chamadaHabilitada" class="p-6 mb-6">
      <div class="flex items-center justify-between mb-3">
        <Heading :level="3" class="text-base font-semibold">Chamada</Heading>
        <div v-if="pendentes > 0" class="flex items-center gap-2">
          <Text size="xs" color="muted">{{ pendentes }} pendente(s) de sincronização</Text>
          <Button size="sm" variant="secondary" :loading="sincronizando" @click="sincronizarAgora">Sincronizar agora</Button>
        </div>
      </div>

      <div class="mb-4">
        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Módulo / Aula</label>
        <select
          v-model="moduloSelecionado"
          class="w-full max-w-sm rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option v-for="modulo in treinamento.modulos" :key="modulo.id" :value="modulo.id">{{ modulo.titulo }}</option>
        </select>
      </div>

      <QrScanner @decode="handleQrDecode" />

      <Text size="xs" color="muted" class="mt-3 block">
        A lista de inscritos é salva neste dispositivo automaticamente. Se a internet cair durante o evento, a
        chamada continua funcionando e sincroniza sozinha quando a conexão voltar.
      </Text>
    </CardBase>

    <CardBase class="p-6">
      <InscricoesTable
        :inscricoes="inscricoes.data"
        :can-aprovar="can('treinamento.inscricoes.aprovar')"
        :can-reprovar="can('treinamento.inscricoes.reprovar')"
        :can-registrar-presenca="can('treinamento.presencas.registrar')"
        :presenca-liberada="treinamento.presenca_liberada"
        :modulo-selecionado="moduloSelecionado"
        @aprovar="aprovar"
        @reprovar="reprovar"
        @marcar-presenca="marcarPresenca"
      />

      <div v-if="pagination" class="mt-6">
        <Pagination :pagination="pagination" @page-change="irParaPagina" />
      </div>
    </CardBase>
  </div>
</template>

<style scoped>
.treinamento-inscricoes-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
