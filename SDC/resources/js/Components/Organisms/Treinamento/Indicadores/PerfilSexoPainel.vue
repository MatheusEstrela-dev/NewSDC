<template>
  <TdapPainel rotulo="Perfil" titulo="Perfil do público" subtitulo="Sexo informado na inscrição">
    <ListEmptyState
      v-if="total === 0"
      :icon="UsersGroupIcon"
      title="Sem participantes no recorte"
      helper="Ajuste os filtros para ver a distribuição por sexo."
    />
    <div v-else class="flex p-5">
      <DonutDistribuicao :itens="itens" rotulo-total="Participantes" />
    </div>
  </TdapPainel>
</template>

<script setup>
/** Rosca por sexo (props.porSexo), com "Nao informado" para inscricao sem o dado. */
import { computed } from 'vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import DonutDistribuicao from '@/Components/Molecules/Statistics/DonutDistribuicao.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import UsersGroupIcon from '@/Components/Icons/UsersGroupIcon.vue';
import { COR_FEMININO, COR_MASCULINO, COR_NAO_INFORMADO } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porSexo: { type: Object, default: () => ({ feminino: 0, masculino: 0, nao_informado: 0 }) },
});

const FATIAS = [
  { chave: 'feminino', nome: 'Feminino', cor: COR_FEMININO },
  { chave: 'masculino', nome: 'Masculino', cor: COR_MASCULINO },
  { chave: 'nao_informado', nome: 'Não informado', cor: COR_NAO_INFORMADO },
];

const total = computed(() => FATIAS.reduce((soma, f) => soma + (props.porSexo[f.chave] ?? 0), 0));

// O Donut espera percentual pronto; arredonda para 1 casa sem deixar a soma
// passar de 100 por causa do arredondamento (o ultimo item absorve a sobra).
const itens = computed(() => {
  let acumulado = 0;
  const ativos = FATIAS.filter((f) => (props.porSexo[f.chave] ?? 0) > 0);

  return ativos.map((f, indice) => {
    const valor = props.porSexo[f.chave];
    const percentual = indice === ativos.length - 1
      ? Math.round((100 - acumulado) * 10) / 10
      : Math.round((valor / total.value) * 1000) / 10;
    acumulado += percentual;

    return { name: f.nome, value: valor, percent: percentual, color: f.cor };
  });
});
</script>
