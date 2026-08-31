<template>
  <!-- Cinco cards numa fileira, mesma gramatica do modulo Decretacoes.
       Por padrao o card fica basico -- titulo e numero -- e o detalhe (barra de
       composicao, legenda, rateio) vem pela seta no rodape do card. A seta e de
       estado UNICO: abrir num card abre nos cinco, porque abrir um por um seria
       cinco cliques para a mesma decisao. -->
  <!-- espaco-inferior=false: o container da pagina governa o ritmo vertical pelo
       `space-y-6`. Com a margem do grid ligada as duas somariam, e o gap depois
       dos cards ficava no dobro dos outros. -->
  <StatCardsGrid :colunas="5" :espaco-inferior="false">
    <!-- 1. Beneficiarios: o total, decomposto pelo eixo ANALISE. Clique no card
            limpa o filtro; clique em cada item da legenda filtra por situacao. -->
    <StatCardWithBreakdown
      retratil
      :detalhe="detalhesAbertos"
      @alternar-detalhe="alternarDetalhes"
      title="Beneficiarios"
      :value="total"
      :show-breakdown="false"
      :composicao="composicaoAnalise"
      nota="Cadastros no seu escopo de perfil. A barra decompoe o total pela situacao de analise, e cada item da legenda filtra a listagem."
      variant="info"
      :icon="UsersIcon"
      @click="$emit('filter', {})"
      @filtrar-parte="$emit('filter', $event)"
    />

    <!-- 2. Aprovados: o portao do programa -- so o cadastro aprovado avanca para
            obra. Fica com card proprio, apesar de tambem ser fatia do card 1,
            porque e o numero que a area acompanha. Mesmo precedente do modulo
            Decretacoes, onde "Vigentes" e subconjunto de "Decretacoes". -->
    <StatCardWithBreakdown
      retratil
      :detalhe="detalhesAbertos"
      @alternar-detalhe="alternarDetalhes"
      title="Aprovados"
      :value="analise.aprovado ?? 0"
      :show-breakdown="false"
      :composicao="composicaoAprovados"
      nota="Cadastros com analise aprovada, incluindo os aprovados com ressalva ja regularizados. E a porta de entrada da fase de obra."
      variant="success"
      :icon="CheckCircleIcon"
      @click="$emit('filter', { situacao_analise: ['aprovado'] })"
      @filtrar-parte="$emit('filter', $event)"
    />

    <!-- 3. Obra: o eixo de andamento, ortogonal ao da analise -- um cadastro
            aprovado pode estar em processamento, e um em edicao pode ter obra.
            O valor em destaque e o instalado, que e o que virou cisterna no
            terreno; a barra mostra onde esta o resto. -->
    <StatCardWithBreakdown
      retratil
      :detalhe="detalhesAbertos"
      @alternar-detalhe="alternarDetalhes"
      title="Obra"
      :value="obra.instalado ?? 0"
      :show-breakdown="false"
      :composicao="composicaoObra"
      nota="Instalacoes concluidas. A barra decompoe todos os cadastros pelo estagio da obra: processamento e a fila de espera, envio e a etapa de logistica."
      variant="success"
      :icon="WrenchScrewdriverIcon"
      @click="$emit('filter', { situacao_obra: ['instalado'] })"
      @filtrar-parte="$emit('filter', $event)"
    />

    <!-- 4. Fiscalizacao: as tres etapas sao subconjuntos ANINHADOS da mesma base
            (os instalados), e nao fatias de um bolo. Por isso rateio, e nao
            barra empilhada: empilhadas, 791+680+658 pintaria 2.129 sobre uma
            base de 791. -->
    <StatCardWithBreakdown
      retratil
      :detalhe="detalhesAbertos"
      @alternar-detalhe="alternarDetalhes"
      title="Fiscalizacao"
      :value="indicadores.com_vistoria_cedec ?? 0"
      :itens="etapasFiscalizacao"
      nota="Funil de validacao das instalacoes: fornecedor, depois COMPDEC, depois CEDEC. Os percentuais tem como base os instalados, nao o total de cadastros."
      nota-align="right"
      variant="info"
      :icon="ClipboardDocumentCheckIcon"
      @click="$emit('filter', { etapa_concluida: 'cedec' })"
      @filtrar-parte="$emit('filter', $event)"
    />

    <!-- 5. Municipios: informativo e nao clicavel. Nao existe "filtrar por
            quantidade de municipios" -- a area usa o numero para conferir a
            cobertura do programa. Mesmo tratamento de "Municipios Atingidos"
            em Decretacoes. -->
    <StatCardWithBreakdown
      retratil
      :detalhe="detalhesAbertos"
      @alternar-detalhe="alternarDetalhes"
      title="Municipios"
      :value="indicadores.municipios ?? 0"
      :show-breakdown="false"
      nota="Municipios com pelo menos um cadastro no seu escopo. Cada municipio conta uma vez, independente do numero de beneficiarios."
      nota-align="right"
      variant="warning"
      :icon="MapPinIcon"
      :clickable="false"
    />
  </StatCardsGrid>
</template>

<script setup>
import { computed, ref } from 'vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCardWithBreakdown from '@/Components/Molecules/Statistics/StatCardWithBreakdown.vue';
import {
  UsersIcon,
  MapPinIcon,
  CheckCircleIcon,
  WrenchScrewdriverIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline';

/**
 * Cada card e tambem um atalho de filtro -- convencao obrigatoria do projeto
 * para pagina de indice. Aqui a legenda das barras tambem filtra, o que e o que
 * permite mostrar 5 cards sem perder atalho nenhum.
 *
 * Estes cards SUBSTITUEM o `menu.blade.php` do legado, que era uma pagina
 * separada com 11 contadores linkando para `cisterna/index?status=N`. Trazer os
 * contadores para o proprio indice remove um clique de todo fluxo.
 *
 * Por que 5 cards e nao os 11 anteriores: os 11 numeros do legado atacam dois
 * eixos ortogonais (analise do cadastro e andamento da obra), e uma fileira de
 * cards indiferenciados nao mostrava isso -- ainda empurrava a tabela para fora
 * da tela. Cada eixo agora e UM card, com a barra dando o peso das fatias.
 *
 * Ganho de dado, nao so de espaco: os 11 cards nao exibiam `duplicado` (516),
 * `desconsiderado` (45) nem `processamento` (7.106). Eram mais de 500 cadastros
 * fora de qualquer contador na analise, e a fila de espera inteira invisivel na
 * obra, sem atalho de filtro. Todos os tres valores ja vinham na API: o service
 * itera SituacaoAnalise::valores() e SituacaoObra::valores().
 *
 * Dois defeitos do menu legado nao se repetem aqui:
 *  - "Aprovados Ressalva" e "Envio para Instalacao" apontavam AMBOS para
 *    `status=3`, copia e cola: um dos cards levava a lista errada. Aqui cada
 *    item sai do indicador certo -- ressalva de `por_analise`, envio de
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
 * Estado UNICO do detalhe dos cards, e nao um por card: a seta de qualquer card
 * abre e fecha os cinco ao mesmo tempo. Recolher card a card seriam cinco
 * cliques para uma decisao que e sempre a mesma -- "quero ver o resumo" ou
 * "quero a tabela".
 *
 * Comeca FECHADO: o card basico (titulo e numero) e o que a maioria consulta, e
 * o detalhe custa cerca de 90px de altura por fileira, que e altura tirada da
 * listagem.
 *
 * Lembra a escolha em localStorage, por pagina. Sem isso, quem recolhe para
 * trabalhar na tabela reabre tudo a cada navegacao.
 */
const CHAVE_MEMORIA = 'sdc:stat-cards-detalhe:cisterna-beneficiarios';

function lerMemoria() {
  try {
    return window.localStorage.getItem(CHAVE_MEMORIA) === 'aberto';
  } catch {
    // localStorage bloqueado (modo restrito do navegador, SSR) nao e motivo
    // para a pagina quebrar: o bloco so deixa de lembrar o estado.
    return false;
  }
}

const detalhesAbertos = ref(lerMemoria());

function alternarDetalhes() {
  detalhesAbertos.value = !detalhesAbertos.value;

  try {
    window.localStorage.setItem(CHAVE_MEMORIA, detalhesAbertos.value ? 'aberto' : 'recolhido');
  } catch {
    // Ver lerMemoria(): sem localStorage o estado vale so para esta sessao.
  }
}

/**
 * O filtro e um objeto, e nao uma string: os cards atacam eixos diferentes e
 * uma string sozinha nao diria em qual aplicar. `{}` limpa tudo.
 */
const analise = computed(() => props.indicadores?.por_analise ?? {});
const obra = computed(() => props.indicadores?.por_obra ?? {});
const total = computed(() => Number(props.indicadores?.total ?? 0));

/**
 * Cores solidas para a barra e para o ponto da legenda, alinhadas com
 * SituacaoAnaliseBadge e SituacaoObraBadge. Ficam como literal aqui, e nao
 * montadas em string nem vindas do enum em PHP: o Tailwind nao escaneia
 * `app/**\/*.php` e nao gera classe que so existe fora de .vue/.blade.
 */
const COR = {
  aprovado: 'bg-emerald-500',
  ressalva: 'bg-blue-500',
  em_edicao: 'bg-amber-500',
  reprovado: 'bg-red-500',
  // Duplicado e desconsiderado sao cinza no badge exatamente por sairem da
  // lista ativa; agrupa-los numa fatia unica e fiel ao dominio e mantem a
  // legenda em 5 itens em vez de 6.
  fora: 'bg-slate-400',
  processamento: 'bg-slate-400',
  envio_instalacao: 'bg-indigo-500',
  instalado: 'bg-emerald-500',
};

const composicaoAnalise = computed(() => {
  const a = analise.value;
  const fora = Number(a.duplicado ?? 0) + Number(a.desconsiderado ?? 0);

  return {
    base: total.value,
    // Ordem fixa por dominio (do melhor desfecho ao pior), e nao por tamanho:
    // ordenada por valor, a fatia trocaria de lugar a cada mudanca de dado e o
    // usuario perderia a referencia visual.
    partes: [
      { rotulo: 'Aprovado', valor: Number(a.aprovado ?? 0), classe: COR.aprovado, filtro: { situacao_analise: ['aprovado'] } },
      { rotulo: 'Ressalva', valor: Number(a.ressalva ?? 0), classe: COR.ressalva, filtro: { situacao_analise: ['ressalva'] } },
      { rotulo: 'Em edicao', valor: Number(a.em_edicao ?? 0), classe: COR.em_edicao, filtro: { situacao_analise: ['em_edicao'] } },
      { rotulo: 'Reprovado', valor: Number(a.reprovado ?? 0), classe: COR.reprovado, filtro: { situacao_analise: ['reprovado'] } },
      { rotulo: 'Fora da lista', valor: fora, classe: COR.fora, filtro: { situacao_analise: ['duplicado', 'desconsiderado'] } },
    ],
  };
});

/**
 * Fatia unica: o card ja e "Aprovados", entao o que a barra precisa dizer e
 * quanto isso representa do total. Mesmo desenho do card "Registros" em
 * Decretacoes. Sem `filtro` na parte -- clicar nela aplicaria exatamente o
 * filtro do card, e duas coisas clicaveis com o mesmo efeito confundem.
 */
const composicaoAprovados = computed(() => ({
  base: total.value,
  partes: [
    { rotulo: 'Do total', valor: Number(analise.value.aprovado ?? 0), classe: COR.aprovado },
  ],
}));

const composicaoObra = computed(() => {
  const o = obra.value;

  return {
    base: total.value,
    // Ordem do fluxo da obra: fila de espera, logistica, concluido.
    partes: [
      { rotulo: 'Processamento', valor: Number(o.processamento ?? 0), classe: COR.processamento, filtro: { situacao_obra: ['processamento'] } },
      { rotulo: 'Envio', valor: Number(o.envio_instalacao ?? 0), classe: COR.envio_instalacao, filtro: { situacao_obra: ['envio_instalacao'] } },
      { rotulo: 'Instalado', valor: Number(o.instalado ?? 0), classe: COR.instalado, filtro: { situacao_obra: ['instalado'] } },
    ],
  };
});

/**
 * Base do funil e o instalado, nao o total: vistoria so existe depois da
 * instalacao, e medir contra 8.096 diria que a fiscalizacao esta em 8% quando
 * ela esta em 83% do que ha para fiscalizar.
 */
const etapasFiscalizacao = computed(() => {
  const i = props.indicadores ?? {};
  const base = Number(obra.value.instalado ?? 0);

  const dica = (rotulo, valor) => {
    const n = Number(valor ?? 0);

    if (base <= 0) {
      return `${rotulo}: ${n.toLocaleString('pt-BR')}`;
    }

    return `${rotulo}: ${n.toLocaleString('pt-BR')} de ${base.toLocaleString('pt-BR')} instalados (${Math.round((n / base) * 100)}%)`;
  };

  return [
    {
      rotulo: 'Fornec.',
      valor: Number(i.com_vistoria_fornecedor ?? 0),
      dica: dica('Validado pelo fornecedor', i.com_vistoria_fornecedor),
      filtro: { etapa_concluida: 'fornecedor' },
    },
    {
      rotulo: 'COMPDEC',
      valor: Number(i.com_vistoria_compdec ?? 0),
      dica: dica('Validado pela COMPDEC', i.com_vistoria_compdec),
      filtro: { etapa_concluida: 'compdec' },
    },
    {
      rotulo: 'CEDEC',
      valor: Number(i.com_vistoria_cedec ?? 0),
      dica: dica('Validado pela CEDEC', i.com_vistoria_cedec),
      filtro: { etapa_concluida: 'cedec' },
    },
  ];
});
</script>
