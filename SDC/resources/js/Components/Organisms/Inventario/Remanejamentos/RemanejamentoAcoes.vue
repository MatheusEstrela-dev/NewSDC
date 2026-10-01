<template>
  <div class="flex flex-wrap items-center justify-end gap-1">
    <ButtonIcon
      v-if="pode.seplag && ativo"
      :icon="PaperAirplaneIcon"
      variant="info"
      size="sm"
      :title="tituloSeplag"
      :disabled="processando"
      @click="$emit('seplag', lote)"
    />
    <ButtonIcon
      :icon="ChevronDownIcon"
      variant="secondary"
      size="sm"
      class="transition-transform"
      :class="{ 'rotate-180': expandido }"
      :title="expandido ? 'Recolher itens' : 'Ver itens do lote'"
      @click="$emit('expandir', lote)"
    />
    <ButtonIcon v-if="pode.planilha" :icon="ArrowDownTrayIcon" variant="secondary" size="sm" title="Baixar planilha" @click="$emit('planilha', lote)" />
    <ButtonIcon
      v-if="pode.editar && ativo"
      :icon="PencilSquareIcon"
      variant="primary"
      size="sm"
      title="Editar lote"
      :disabled="processando"
      @click="$emit('editar', lote)"
    />
    <ButtonIcon
      v-if="pode.editar && ativo"
      :icon="ArrowUturnLeftIcon"
      variant="danger"
      size="sm"
      title="Desfazer lote"
      :disabled="processando"
      @click="$emit('desfazer', lote)"
    />
    <Button v-if="lote.demanda" variant="outline" size="sm" :title="`Protocolo ${lote.demanda.protocolo}`" @click="$emit('abrir-chamado', lote)">
      Chamado #{{ lote.demanda.id }}
    </Button>
    <ButtonIcon
      v-else-if="pode.editar && ativo"
      :icon="TicketIcon"
      variant="success"
      size="sm"
      title="Registrar chamado"
      :disabled="processando"
      @click="$emit('chamado', lote)"
    />
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
});
defineEmits(['seplag', 'expandir', 'planilha', 'editar', 'desfazer', 'chamado', 'abrir-chamado']);

const ativo = computed(() => props.lote.status === 'ativo');

const tituloSeplag = computed(() => {
  const { envios, ultimo_envio_em: ultimo } = props.lote.seplag;
  if (!envios) return 'Enviar à SEPLAG';
  return `Reenviar à SEPLAG — ${envios} envio(s), último em ${formatarDataHora(ultimo)}`;
});
</script>
