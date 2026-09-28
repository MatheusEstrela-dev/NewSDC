<template>
  <div class="space-y-6 pb-8">
    <PageHeader :title="`Demanda ${demanda.protocolo}`" :description="demanda.titulo" :icon-image="moduleIcon('demandas')" variant="gradient" :espaco-inferior="false">
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <DemandaStatusBadge :etapa="demanda.etapa" :label="demanda.etapa_label" />
          <DemandaPrioridadeBadge :prioridade="demanda.prioridade_simples" :label="demanda.prioridade_label" :itil="demanda.prioridade_itil" />
          <DemandaAutomacaoButton v-if="automacao.disponivel && pode.automatizar" :demanda-id="demanda.id" />
        </div>
      </template>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="min-w-0 space-y-6 lg:col-span-2">
        <DemandaDescricaoCard :demanda-id="demanda.id" :descricao="demanda.descricao" :pode-editar="pode.editar" />
        <DemandaAssuntoCard :demanda-id="demanda.id" :assunto-id="demanda.assunto?.id ?? null" :assuntos="assuntos" :campos="campos"
          :valores-iniciais="demanda.campos_customizados" :pode-editar="pode.editar" />
        <DemandaAbasAtividade :demanda-id="demanda.id" :comentarios="comentarios" :historico="historico" :pode-interno="pode.comentarInterno" />
      </div>
      <aside class="min-w-0 space-y-6">
        <DemandaEnvolvidosCard :demanda="demanda" :usuarios="usuarios" :pode-gerir="pode.gerir" />
        <DemandaInformacoesCard :demanda="demanda" :pode-editar="pode.editar" :pode-resolver="pode.resolver" @concluir="resolver?.focar()" />
        <DemandaAnexosCard :demanda-id="demanda.id" :anexos="anexos" />
        <DemandaResolverCard v-if="pode.resolver || pode.reabrir" ref="resolver" :demanda="demanda" :pode-resolver="pode.resolver" :pode-reabrir="pode.reabrir" />
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import DemandaDescricaoCard from '@/Components/Organisms/Demandas/Show/DemandaDescricaoCard.vue';
import DemandaAssuntoCard from '@/Components/Organisms/Demandas/Show/DemandaAssuntoCard.vue';
import DemandaAbasAtividade from '@/Components/Organisms/Demandas/Show/DemandaAbasAtividade.vue';
import DemandaEnvolvidosCard from '@/Components/Organisms/Demandas/Show/DemandaEnvolvidosCard.vue';
import DemandaInformacoesCard from '@/Components/Organisms/Demandas/Show/DemandaInformacoesCard.vue';
import DemandaAnexosCard from '@/Components/Organisms/Demandas/Show/DemandaAnexosCard.vue';
import DemandaResolverCard from '@/Components/Organisms/Demandas/Show/DemandaResolverCard.vue';
import DemandaAutomacaoButton from '@/Components/Organisms/Demandas/Show/DemandaAutomacaoButton.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  demanda: { type: Object, required: true },
  campos: { type: Array, default: () => [] },
  comentarios: { type: Array, default: () => [] },
  historico: { type: Array, default: () => [] },
  anexos: { type: Array, default: () => [] },
  assuntos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  automacao: { type: Object, required: true },
  pode: { type: Object, required: true },
});

const resolver = ref(null);
</script>
