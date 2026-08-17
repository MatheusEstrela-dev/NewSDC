<template>
  <div class="space-y-4">
    <section v-for="grupo in grupos" :key="grupo.chave">
      <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ grupo.titulo }}
      </h3>

      <StatCardsGrid :colunas="grupo.colunas">
        <StatCard
          v-for="card in grupo.cards"
          :key="card.chave"
          :title="card.titulo"
          :value="card.valor"
          :variant="card.variante"
          :icon="card.icone"
          :clickable="card.filtro !== null"
          @click="card.filtro !== null && $emit('filter', card.filtro)"
        />
      </StatCardsGrid>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import {
  UsersIcon,
  MapPinIcon,
  CheckCircleIcon,
  PencilSquareIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline';

/**
 * Cada card e tambem um atalho de filtro -- convencao obrigatoria do projeto
 * para pagina de indice.
 *
 * Estes cards SUBSTITUEM o `menu.blade.php` do legado, que era uma pagina
 * separada com 11 contadores linkando para `cisterna/index?status=N`. Trazer os
 * contadores para o proprio indice remove um clique de todo fluxo.
 *
 * Estao em dois grupos de proposito. Os 11 numeros do legado atacam eixos
 * diferentes -- analise do cadastro e andamento da obra sao ORTOGONAIS -- e numa
 * fileira unica de 11 cards indiferenciados a tabela era empurrada para fora da
 * tela e o usuario nao percebia que "Aprovado" e "Instalado" nao competem entre
 * si.
 *
 * Dois defeitos do menu legado nao se repetem aqui:
 *  - "Aprovados Ressalva" e "Envio para Instalacao" apontavam AMBOS para
 *    `status=3`, copia e cola: um dos cards levava a lista errada. Aqui cada
 *    card sai do indicador certo -- ressalva de `por_analise`, envio de
 *    `por_obra`.
 *  - o total era texto solto, sem card. Agora e card e limpa o filtro.
 */
const props = defineProps({
  /**
   * Vem de BeneficiarioService::indicadores(): total, municipios,
   * por_analise{}, por_obra{} e com_vistoria_{fornecedor,compdec,cedec}.
   */
  indicadores: { type: Object, required: true },
});

defineEmits(['filter']);

/**
 * O filtro e um objeto, e nao uma string: os cards atacam eixos diferentes e
 * uma string sozinha nao diria em qual aplicar. `{}` limpa tudo.
 *
 * `filtro: null` marca card informativo, que NAO e clicavel -- prometer clique
 * e nao filtrar nada e pior que nao oferecer.
 */
const grupos = computed(() => {
  const i = props.indicadores ?? {};
  const analise = i.por_analise ?? {};
  const obra = i.por_obra ?? {};

  return [
    {
      chave: 'cadastro',
      titulo: 'Cadastro e analise',
      colunas: 3,
      cards: [
        {
          chave: 'total',
          titulo: 'Beneficiarios',
          valor: i.total ?? 0,
          variante: 'info',
          icone: UsersIcon,
          filtro: {},
        },
        {
          // Informativo: nao existe "filtrar por quantidade de municipios". A
          // area usa este numero para conferir a cobertura do programa.
          chave: 'municipios',
          titulo: 'Municipios',
          valor: i.municipios ?? 0,
          variante: 'info',
          icone: MapPinIcon,
          filtro: null,
        },
        {
          chave: 'aprovado',
          titulo: 'Aprovados',
          valor: analise.aprovado ?? 0,
          variante: 'success',
          icone: CheckCircleIcon,
          filtro: { situacao_analise: ['aprovado'] },
        },
        {
          chave: 'em_edicao',
          titulo: 'Em edicao',
          valor: analise.em_edicao ?? 0,
          variante: 'warning',
          icone: PencilSquareIcon,
          filtro: { situacao_analise: ['em_edicao'] },
        },
        {
          chave: 'ressalva',
          titulo: 'Ressalva',
          valor: analise.ressalva ?? 0,
          variante: 'warning',
          icone: ExclamationTriangleIcon,
          filtro: { situacao_analise: ['ressalva'] },
        },
        {
          chave: 'reprovado',
          titulo: 'Reprovados',
          valor: analise.reprovado ?? 0,
          variante: 'danger',
          icone: XCircleIcon,
          filtro: { situacao_analise: ['reprovado'] },
        },
      ],
    },
    {
      chave: 'obra',
      titulo: 'Obra e fiscalizacao',
      colunas: 5,
      cards: [
        {
          chave: 'envio_instalacao',
          titulo: 'Envio instalacao',
          valor: obra.envio_instalacao ?? 0,
          variante: 'info',
          icone: TruckIcon,
          filtro: { situacao_obra: ['envio_instalacao'] },
        },
        {
          chave: 'instalado',
          titulo: 'Instalados',
          valor: obra.instalado ?? 0,
          variante: 'success',
          icone: WrenchScrewdriverIcon,
          filtro: { situacao_obra: ['instalado'] },
        },
        {
          chave: 'vistoria_fornecedor',
          titulo: 'Validado fornecedor',
          valor: i.com_vistoria_fornecedor ?? 0,
          variante: 'info',
          icone: ClipboardDocumentCheckIcon,
          filtro: { etapa_concluida: 'fornecedor' },
        },
        {
          chave: 'vistoria_compdec',
          titulo: 'Validado COMPDEC',
          valor: i.com_vistoria_compdec ?? 0,
          variante: 'info',
          icone: ClipboardDocumentCheckIcon,
          filtro: { etapa_concluida: 'compdec' },
        },
        {
          chave: 'vistoria_cedec',
          titulo: 'Validado CEDEC',
          valor: i.com_vistoria_cedec ?? 0,
          variante: 'success',
          icone: ClipboardDocumentCheckIcon,
          filtro: { etapa_concluida: 'cedec' },
        },
      ],
    },
  ];
});
</script>
