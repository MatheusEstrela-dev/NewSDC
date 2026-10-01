<template>
  <!-- Cartao (mobile): rotulo visivel e alvo de 40px, porque no toque o title nao aparece. -->
  <div v-if="rotulado" class="grid grid-cols-2 gap-2">
    <Button
      v-for="acao in acoes"
      :key="acao.chave"
      :variant="acao.variante"
      size="md"
      :icon="acao.icone"
      :disabled="acao.trava && processando"
      :title="acao.titulo"
      class="min-h-10 w-full"
      :class="{ 'col-span-2': acao.largo }"
      @click="acao.acionar"
    >
      {{ acao.rotulo }}
    </Button>
  </div>

  <div v-else class="flex flex-wrap items-center justify-end gap-1">
    <template v-for="acao in acoes" :key="acao.chave">
      <Button v-if="acao.largo" variant="outline" size="sm" :title="acao.titulo" @click="acao.acionar">
        {{ acao.rotulo }}
      </Button>
      <ButtonIcon
        v-else
        :icon="acao.icone"
        :variant="acao.variante"
        size="sm"
        :class="acao.classe"
        :title="acao.titulo"
        :aria-label="acao.titulo"
        :disabled="acao.trava && processando"
        @click="acao.acionar"
      />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  ArrowDownTrayIcon, ArrowUturnLeftIcon, ChevronDownIcon, PaperAirplaneIcon, PencilSquareIcon, TicketIcon,
} from '@heroicons/vue/24/outline';
import Button from '@/Components/Atoms/Button/Button.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import { formatarDataHora } from '@/utils/dateFormatter';

const props = defineProps({
  lote: { type: Object, required: true },
  pode: { type: Object, required: true },
  expandido: { type: Boolean, default: false },
  // Acao do lote em andamento: trava os botoes que disparam requisicao.
  processando: { type: Boolean, default: false },
  // Botao com texto (cartao do mobile) em vez de so o icone (tabela).
  rotulado: { type: Boolean, default: false },
});
const emit = defineEmits(['seplag', 'expandir', 'planilha', 'editar', 'desfazer', 'chamado', 'abrir-chamado']);

const ativo = computed(() => props.lote.status === 'ativo');

const tituloSeplag = computed(() => {
  const { envios, ultimo_envio_em: ultimo } = props.lote.seplag;
  if (!envios) return 'Enviar à SEPLAG';
  return `Reenviar à SEPLAG — ${envios} envio(s), último em ${formatarDataHora(ultimo)}`;
});

// Uma lista so para as duas formas: a ordem e as regras de exibicao nao divergem.
const acoes = computed(() => {
  const { lote, pode, expandido } = props;
  const editavel = pode.editar && ativo.value;
  const lista = [
    pode.seplag && ativo.value && {
      chave: 'seplag', icone: PaperAirplaneIcon, variante: 'info', trava: true,
      rotulo: lote.seplag.envios ? 'Reenviar à SEPLAG' : 'Enviar à SEPLAG', titulo: tituloSeplag.value,
      acionar: () => emit('seplag', lote),
    },
    {
      chave: 'expandir', icone: ChevronDownIcon, variante: 'secondary',
      classe: ['transition-transform', { 'rotate-180': expandido }],
      rotulo: expandido ? 'Recolher itens' : 'Ver itens', titulo: expandido ? 'Recolher itens' : 'Ver itens do lote',
      acionar: () => emit('expandir', lote),
    },
    pode.planilha && {
      chave: 'planilha', icone: ArrowDownTrayIcon, variante: 'secondary',
      rotulo: 'Planilha', titulo: 'Baixar planilha',
      acionar: () => emit('planilha', lote),
    },
    editavel && {
      chave: 'editar', icone: PencilSquareIcon, variante: 'primary', trava: true,
      rotulo: 'Editar', titulo: 'Editar lote',
      acionar: () => emit('editar', lote),
    },
    editavel && {
      chave: 'desfazer', icone: ArrowUturnLeftIcon, variante: 'danger', trava: true,
      rotulo: 'Desfazer', titulo: 'Desfazer lote',
      acionar: () => emit('desfazer', lote),
    },
    lote.demanda
      ? {
        chave: 'abrir-chamado', icone: TicketIcon, variante: 'outline', largo: true,
        rotulo: `Chamado #${lote.demanda.id}`, titulo: `Protocolo ${lote.demanda.protocolo}`,
        acionar: () => emit('abrir-chamado', lote),
      }
      : editavel && {
        chave: 'chamado', icone: TicketIcon, variante: 'success', trava: true,
        rotulo: 'Registrar chamado', titulo: 'Registrar chamado',
        acionar: () => emit('chamado', lote),
      },
  ];

  return lista.filter(Boolean);
});
</script>
