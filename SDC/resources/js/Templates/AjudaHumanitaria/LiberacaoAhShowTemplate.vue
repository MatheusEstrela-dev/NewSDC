<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      :title="`Liberação ${liberacao.codigo_legado ?? liberacao.id}`"
      :description="`${liberacao.municipio ?? 'Município não informado'} · ${liberacao.deposito ?? 'Depósito não informado'}`"
      :icon="ClipboardDocumentListIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <!-- Sem botao de voltar: a barra de breadcrumb ja traz um, e repetir a
           mesma acao no cabecalho so disputa atencao com a situacao. -->
      <template #actions>
        <Badge :variant="liberacao.status_cor" size="md">{{ liberacao.status_label }}</Badge>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6 items-start">
      <div class="xl:col-span-2 space-y-4 sm:space-y-6">
        <ListContainer title="Dados da liberação" :icon="ClipboardDocumentListIcon" icon-class="text-blue-500">
          <dl class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <Campo rotulo="Município" :valor="liberacao.municipio" />
            <Campo rotulo="UF" :valor="liberacao.uf" />
            <Campo rotulo="Depósito" :valor="depositoCompleto" />
            <Campo rotulo="Beneficiário" :valor="liberacao.beneficiario" />
            <Campo rotulo="Data da liberação" :valor="formatarData(liberacao.data_libera)" />
            <Campo rotulo="Prazo" :valor="formatarData(liberacao.data_limite)" />
            <Campo rotulo="Solicitante" :valor="liberacao.solicitante" />
            <Campo rotulo="Código no legado" :valor="liberacao.codigo_legado" mono />
            <Campo v-if="liberacao.observacao" class="sm:col-span-2" rotulo="Observação" :valor="liberacao.observacao" />
          </dl>
        </ListContainer>

        <ListContainer
          v-if="liberacao.cancelado_em"
          title="Cancelamento"
          :icon="XMarkIcon"
          icon-class="text-red-500"
        >
          <dl class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <Campo rotulo="Cancelada em" :valor="formatarDataHora(liberacao.cancelado_em)" />
            <Campo class="sm:col-span-2" rotulo="Motivo" :valor="liberacao.motivo_cancelamento" />
          </dl>
        </ListContainer>

        <ListContainer
          title="Materiais"
          :icon="ArchiveBoxIcon"
          :count="liberacao.itens.length"
          icon-class="text-emerald-500"
        >
          <!-- Nao e falha do refino: aju_item nao tem uma linha na base de
               producao, entao nenhuma das 3.582 liberacoes migradas registra
               material. O aviso e explicito para nao parecer erro de carga. -->
          <ListEmptyState
            v-if="!liberacao.itens.length"
            :icon="ArchiveBoxIcon"
            title="Sem material registrado"
            helper="O sistema anterior não guardou os itens desta liberação."
          />

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                <tr class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  <th scope="col" class="px-4 py-3 font-medium">Material</th>
                  <th scope="col" class="px-4 py-3 font-medium text-right">Quantidade</th>
                  <th scope="col" class="px-4 py-3 font-medium">Unidade</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <tr v-for="item in liberacao.itens" :key="item.id">
                  <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ item.material }}</td>
                  <td class="px-4 py-3 text-right tabular-nums text-slate-900 dark:text-white">
                    {{ numero.format(item.qtd) }}
                  </td>
                  <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ item.unidade }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </ListContainer>
      </div>

      <div class="xl:col-span-1">
        <ListContainer
          title="Recibos de retirada"
          :icon="CheckCircleIcon"
          :count="liberacao.recibos.length"
          icon-class="text-emerald-500"
        >
          <ListEmptyState
            v-if="!liberacao.recibos.length"
            :icon="CheckCircleIcon"
            title="Nenhum recibo"
            helper="O material ainda não foi retirado."
          />

          <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
            <li v-for="recibo in liberacao.recibos" :key="recibo.id" class="p-4 space-y-2">
              <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-semibold text-slate-900 dark:text-white">
                  {{ recibo.n_recibo ? `Recibo ${recibo.n_recibo}` : 'Recibo' }}
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                  {{ formatarData(recibo.pago_em) }}
                </span>
              </div>
              <dl class="text-xs space-y-1">
                <Campo compacto rotulo="Responsável" :valor="recibo.responsavel" />
                <Campo compacto rotulo="CPF" :valor="recibo.cpf" />
                <Campo compacto rotulo="Documento" :valor="recibo.n_documento" />
                <Campo compacto rotulo="Placa" :valor="recibo.placa" />
                <Campo v-if="recibo.motivo" compacto rotulo="Motivo" :valor="recibo.motivo" />
              </dl>
            </li>
          </ul>
        </ListContainer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  liberacao: { type: Object, required: true },
});

const numero = new Intl.NumberFormat('pt-BR');

const depositoCompleto = computed(() => {
  if (!props.liberacao.deposito) return null;

  return props.liberacao.sigla
    ? `${props.liberacao.sigla} — ${props.liberacao.deposito}`
    : props.liberacao.deposito;
});

/**
 * Monta a data a partir das partes. new Date('2022-03-08') e interpretado como
 * UTC e volta um dia atras em fuso negativo, que e o caso de Minas.
 */
function formatarData(iso) {
  if (!iso) return null;

  const [ano, mes, dia] = iso.slice(0, 10).split('-');

  return `${dia}/${mes}/${ano}`;
}

function formatarDataHora(iso) {
  if (!iso) return null;

  const d = new Date(iso);

  return d.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

/** Par rotulo/valor da ficha, com travessao quando nao ha dado. */
const Campo = (propriedades) => h('div', { class: propriedades.class }, [
  h('dt', { class: 'text-xs font-medium text-slate-500 dark:text-slate-400' }, propriedades.rotulo),
  h('dd', {
    class: [
      propriedades.compacto ? 'text-xs' : 'text-sm',
      propriedades.mono ? 'font-mono' : '',
      'text-slate-800 dark:text-slate-100 break-words',
    ],
  }, propriedades.valor || '—'),
]);
Campo.props = ['rotulo', 'valor', 'mono', 'compacto', 'class'];
</script>
