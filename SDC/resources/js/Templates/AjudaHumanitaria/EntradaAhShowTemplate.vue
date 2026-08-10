<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      :title="tituloDaEntrada"
      :description="descricaoDaEntrada"
      :icon="UploadIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <Badge v-if="entrada.cancelado" variant="danger" size="md">Cancelada</Badge>
        <Badge v-else-if="temCorrecao" variant="warning" size="md">Correção de saldo</Badge>
        <Badge v-else variant="success" size="md">Ativa</Badge>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6 items-start">
      <div class="xl:col-span-2">
        <ListContainer title="Dados do recebimento" :icon="UploadIcon" icon-class="text-blue-500">
          <dl class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <Campo rotulo="Depósito" :valor="depositoCompleto" />
            <Campo rotulo="Recebido em" :valor="formatarData(entrada.recebido_em)" />
            <Campo rotulo="Nota fiscal" :valor="entrada.nota_fiscal" />
            <Campo rotulo="Fonte de recurso" :valor="entrada.fonte" />
            <Campo rotulo="Fornecedor" :valor="entrada.fornecedor" />
            <Campo rotulo="Código no legado" :valor="entrada.codigo_legado" mono />
            <!-- 215 das 752 entradas trazem em origem um texto que nao casa com
                 fonte cadastrada, porque o legado misturava fonte de recurso
                 com tipo de movimento. Mostrar o texto cru evita a impressao de
                 que o dado se perdeu. -->
            <Campo
              v-if="entrada.origem_legado && entrada.origem_legado !== entrada.fonte"
              rotulo="Origem no legado"
              :valor="entrada.origem_legado"
            />
            <Campo v-if="entrada.observacao" class="sm:col-span-2" rotulo="Observação" :valor="entrada.observacao" />
          </dl>
        </ListContainer>
      </div>

      <div class="xl:col-span-1">
        <ListContainer
          title="Materiais"
          :icon="ArchiveBoxIcon"
          :count="entrada.itens.length"
          icon-class="text-emerald-500"
        >
          <ListEmptyState
            v-if="!entrada.itens.length"
            :icon="ArchiveBoxIcon"
            title="Sem material registrado"
            helper="Esta entrada não tem itens no sistema anterior."
          />

          <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
            <li v-for="item in entrada.itens" :key="item.id" class="p-4 space-y-1">
              <p class="text-sm text-slate-800 dark:text-slate-100">{{ item.material }}</p>
              <p
                class="text-sm font-semibold tabular-nums"
                :class="item.qtd < 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'"
              >
                {{ numero.format(item.qtd) }}
                <span class="font-normal text-slate-500 dark:text-slate-400">{{ item.unidade }}</span>
              </p>
              <p v-if="item.data_validade" class="text-xs text-slate-500 dark:text-slate-400">
                Validade: {{ formatarData(item.data_validade) }}
              </p>
              <p v-if="item.valor_total !== null" class="text-xs text-slate-500 dark:text-slate-400">
                {{ moeda.format(item.valor_total) }}
              </p>
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
import UploadIcon from '@/Components/Icons/UploadIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  entrada: { type: Object, required: true },
});

const numero = new Intl.NumberFormat('pt-BR');
const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

const tituloDaEntrada = computed(() => 'Entrada ' + (props.entrada.codigo_legado ?? props.entrada.id));

const descricaoDaEntrada = computed(() => {
  const deposito = props.entrada.deposito ?? 'Depósito não informado';

  return deposito + ' · ' + (formatarData(props.entrada.recebido_em) ?? 'sem data');
});

const temCorrecao = computed(() => props.entrada.itens.some((item) => item.qtd < 0));

const depositoCompleto = computed(() => {
  if (!props.entrada.deposito) return null;

  return props.entrada.sigla
    ? props.entrada.sigla + ' — ' + props.entrada.deposito
    : props.entrada.deposito;
});

/**
 * Monta a data a partir das partes: new Date('2022-03-08') e interpretado como
 * UTC e volta um dia atras em fuso negativo.
 */
function formatarData(iso) {
  if (!iso) return null;

  const [ano, mes, dia] = iso.slice(0, 10).split('-');

  return dia + '/' + mes + '/' + ano;
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
