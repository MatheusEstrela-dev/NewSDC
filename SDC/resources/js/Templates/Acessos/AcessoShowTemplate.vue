<template>
  <div class="space-y-6 pb-8">
    <PageHeader
      :title="cadastro.nome"
      :description="`Status no AD: ${cadastro.status_ad || '—'}`"
      :icon="IdentificationIcon"
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
      :is-open="Boolean(confirmacao)"
      v-bind="confirmacao?.dialogo ?? DIALOGO_VAZIO"
      :loading="processando"
      @confirm="confirmar"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeftIcon, IdentificationIcon } from '@heroicons/vue/24/outline';
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

// O ConfirmDialog exige title/message mesmo fechado.
const DIALOGO_VAZIO = { title: '', message: '' };

// { dialogo, url, dados } da acao aguardando confirmacao; nulo = nada pendente.
const confirmacao = ref(null);
const processando = ref(false);

function pedirAprovacao() {
  confirmacao.value = {
    dialogo: { title: 'Aprovar cadastro', message: 'Aprovar este cadastro?', variant: 'success', confirmText: 'Aprovar' },
    url: `/acessos/${props.cadastro.id}/aprovar`,
    dados: {},
  };
}

function pedirStatus(status) {
  confirmacao.value = {
    dialogo: {
      title: 'Alterar status',
      message: `Alterar status para ${rotuloStatusAcesso(status)}?`,
      variant: status === 'ativo' ? 'info' : 'warning',
    },
    url: `/acessos/${props.cadastro.id}/status`,
    dados: { status },
  };
}

function cancelar() {
  if (!processando.value) confirmacao.value = null;
}

function confirmar() {
  if (processando.value || !confirmacao.value) return;
  const { url, dados } = confirmacao.value;
  processando.value = true;
  router.post(url, dados, {
    onFinish: () => {
      processando.value = false;
      confirmacao.value = null;
    },
  });
}
</script>
