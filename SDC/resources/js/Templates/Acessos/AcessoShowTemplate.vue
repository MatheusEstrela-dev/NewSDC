<template>
  <div class="space-y-6 pb-8">
    <PageHeader
      :title="cadastro.nome"
      :description="`Status no AD: ${cadastro.status_ad || '—'}`"
      :icon-image="moduleIcon('acessos')"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="outline" size="md" class="min-h-10" :icon="ArrowLeftIcon" icon-position="left" @click="$emit('voltar')">Voltar</Button>
          <StatusAcessoBadge :status="cadastro.status" />
          <AcessoStatusAcoes
            v-if="pode.aprovar"
            :status="cadastro.status"
            :processando="processando"
            @aprovar="pedirAprovacao"
            @status="pedirStatus"
          />
        </div>
      </template>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="min-w-0 space-y-6 lg:col-span-2">
        <AcessoDadosCard :cadastro="cadastro" :pode-editar="pode.editar" />
      </div>
      <aside class="min-w-0 space-y-6">
        <AcessoSituacaoCard :cadastro="cadastro" />
        <AcessoHistoricoCard :eventos="auditoria" />
      </aside>
    </div>

    <ConfirmDialog
      :is-open="dialogoAberto"
      v-bind="dialogo"
      :loading="processando"
      @confirm="confirmar"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import { moduleIcon } from '@/Support/moduleIcons';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeftIcon} from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import StatusAcessoBadge from '@/Components/Atoms/Acessos/StatusAcessoBadge.vue';
import AcessoStatusAcoes from '@/Components/Organisms/Acessos/Show/AcessoStatusAcoes.vue';
import AcessoDadosCard from '@/Components/Organisms/Acessos/Show/AcessoDadosCard.vue';
import AcessoSituacaoCard from '@/Components/Organisms/Acessos/Show/AcessoSituacaoCard.vue';
import AcessoHistoricoCard from '@/Components/Organisms/Acessos/Show/AcessoHistoricoCard.vue';
import { rotuloStatusAcesso } from '@/Support/acessos';

const props = defineProps({
  cadastro: { type: Object, required: true },
  auditoria: { type: Array, default: () => [] },
  pode: { type: Object, required: true },
});
defineEmits(['voltar']);

// Conteudo do ultimo dialogo aberto. Fica preenchido depois de fechar para o
// titulo e o texto nao sumirem durante a transicao de saida; quem fecha e o
// dialogoAberto. O ConfirmDialog exige title/message mesmo fechado.
const dialogo = ref({ title: '', message: '' });
const dialogoAberto = ref(false);
// { url, dados } da acao aguardando confirmacao.
const acaoPendente = ref(null);
const processando = ref(false);

function abrirConfirmacao(conteudo, url, dados) {
  dialogo.value = conteudo;
  acaoPendente.value = { url, dados };
  dialogoAberto.value = true;
}

function pedirAprovacao() {
  abrirConfirmacao(
    { title: 'Aprovar cadastro', message: 'Aprovar este cadastro?', variant: 'success', confirmText: 'Aprovar' },
    `/acessos/${props.cadastro.id}/aprovar`,
    {},
  );
}

function pedirStatus(status) {
  abrirConfirmacao(
    {
      title: 'Alterar status',
      message: `Alterar status para ${rotuloStatusAcesso(status)}?`,
      variant: status === 'ativo' ? 'info' : 'warning',
    },
    `/acessos/${props.cadastro.id}/status`,
    { status },
  );
}

function cancelar() {
  if (!processando.value) dialogoAberto.value = false;
}

function confirmar() {
  if (processando.value || !dialogoAberto.value) return;
  const { url, dados } = acaoPendente.value;
  processando.value = true;
  router.post(url, dados, {
    onFinish: () => {
      processando.value = false;
      dialogoAberto.value = false;
    },
  });
}
</script>
