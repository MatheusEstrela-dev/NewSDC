<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      :title="`Transferência ${transferencia.codigo_legado ?? transferencia.id}`"
      :description="`${transferencia.origem ?? '—'} para ${transferencia.destino ?? '—'}`"
      :icon="ArrowsRightLeftIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <Badge :variant="transferencia.status_cor" size="md">{{ transferencia.status_label }}</Badge>
          <Button variant="secondary" size="md" @click="$emit('voltar')">Voltar</Button>
        </div>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6 items-start">
      <div class="xl:col-span-2 space-y-4 sm:space-y-6">
        <ListContainer title="Trajeto e transporte" :icon="TruckIcon" icon-class="text-blue-500">
          <div class="p-4 sm:p-6 space-y-6">
            <!-- Origem e destino lado a lado: e a informacao que define a
                 transferencia, e o CHECK do banco garante que sao distintos. -->
            <div class="flex flex-wrap items-center gap-3 sm:gap-4">
              <div class="flex-1 min-w-[8rem] rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Origem</p>
                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                  {{ transferencia.origem_sigla }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ transferencia.origem }}</p>
              </div>

              <span class="text-2xl text-slate-400" aria-hidden="true">&rarr;</span>

              <div class="flex-1 min-w-[8rem] rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Destino</p>
                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                  {{ transferencia.destino_sigla }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ transferencia.destino }}</p>
              </div>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
              <Campo rotulo="Saída" :valor="formatarDataHora(transferencia.saiu_em)" />
              <Campo rotulo="Chegada" :valor="formatarDataHora(transferencia.chegou_em)" />
              <Campo rotulo="Motorista" :valor="transferencia.motorista" />
              <Campo rotulo="Veículo" :valor="transferencia.veiculo" />
              <Campo rotulo="Placa" :valor="transferencia.placa" />
              <Campo rotulo="Responsável" :valor="transferencia.responsavel" />
              <Campo rotulo="Código no legado" :valor="transferencia.codigo_legado" mono />
              <Campo
                v-if="transferencia.observacao"
                class="sm:col-span-2"
                rotulo="Observação"
                :valor="transferencia.observacao"
              />
            </dl>
          </div>
        </ListContainer>
      </div>

      <div class="xl:col-span-1">
        <ListContainer
          title="Materiais"
          :icon="ArchiveBoxIcon"
          :count="transferencia.itens.length"
          icon-class="text-emerald-500"
        >
          <ListEmptyState
            v-if="!transferencia.itens.length"
            :icon="ArchiveBoxIcon"
            title="Sem material registrado"
            helper="Esta transferência não tem itens no sistema anterior."
          />

          <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
            <li v-for="item in transferencia.itens" :key="item.id" class="p-4">
              <p class="text-sm text-slate-800 dark:text-slate-100">{{ item.material }}</p>
              <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white tabular-nums">
                {{ numero.format(item.qtd) }}
                <span class="font-normal text-slate-500 dark:text-slate-400">{{ item.unidade }}</span>
              </p>
            </li>
          </ul>
        </ListContainer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { h } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  transferencia: { type: Object, required: true },
});

defineEmits(['voltar']);

const numero = new Intl.NumberFormat('pt-BR');

function formatarDataHora(iso) {
  if (!iso) return null;

  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

/** Par rotulo/valor da ficha, com travessao quando nao ha dado. */
const Campo = (propriedades) => h('div', { class: propriedades.class }, [
  h('dt', { class: 'text-xs font-medium text-slate-500 dark:text-slate-400' }, propriedades.rotulo),
  h('dd', {
    class: [propriedades.mono ? 'font-mono' : '', 'text-sm text-slate-800 dark:text-slate-100 break-words'],
  }, propriedades.valor || '—'),
]);
Campo.props = ['rotulo', 'valor', 'mono', 'class'];
</script>
