<script setup>
import { onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import CidadaoPortalLayout from '@/Layouts/CidadaoPortalLayout.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: CidadaoPortalLayout });

const props = defineProps({
  treinamento: { type: Object, required: true },
  minhaInscricao: { type: Object, default: null },
});

const { show: toast } = useToast();
const qrCanvas = ref(null);

const formatDate = (dateValue) => {
  if (!dateValue) return null;
  const d = new Date(dateValue);
  if (isNaN(d.getTime())) return dateValue;
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

async function desenharQr() {
  if (!props.minhaInscricao?.qr_code_token || !qrCanvas.value) return;
  const QRCode = (await import('qrcode')).default;
  QRCode.toCanvas(qrCanvas.value, props.minhaInscricao.qr_code_token, { width: 220 });
}

onMounted(desenharQr);
watch(() => props.minhaInscricao?.qr_code_token, desenharQr);

function inscreverSe() {
  router.post(route('portal.treinamento.inscricoes.store', props.treinamento.link_publico_slug), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Inscrição realizada com sucesso!', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível concluir a inscrição.', 'error'),
  });
}

function confirmarPresenca() {
  router.post(route('portal.treinamento.inscricoes.presenca', props.minhaInscricao.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Presença confirmada com sucesso!', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível confirmar a presença.', 'error'),
  });
}
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <img
      v-if="treinamento.esta_publicado"
      :src="route('treinamentos.divulgacao', treinamento.id)"
      :alt="`Imagem de divulgação: ${treinamento.titulo}`"
      class="w-full rounded-lg border border-slate-200 dark:border-slate-700 mb-4"
    />

    <CardBase class="p-6">
      <div class="flex items-start justify-between mb-3 gap-3">
        <Heading :level="1" class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ treinamento.titulo }}</Heading>
        <Badge :color="treinamento.tipo_color" class="shrink-0">{{ treinamento.tipo_label }}</Badge>
      </div>

      <Text v-if="treinamento.descricao" size="base" class="text-slate-700 dark:text-slate-300 mb-6">
        {{ treinamento.descricao }}
      </Text>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div v-if="treinamento.instrutor">
          <Text size="sm" color="muted" class="mb-1">Instrutor</Text>
          <Text size="base" class="font-medium">{{ treinamento.instrutor }}</Text>
        </div>
        <div v-if="treinamento.local">
          <Text size="sm" color="muted" class="mb-1">Local</Text>
          <Text size="base" class="font-medium">{{ treinamento.local }}</Text>
        </div>
        <div v-if="treinamento.data_inicio">
          <Text size="sm" color="muted" class="mb-1">Data</Text>
          <Text size="base" class="font-medium">
            {{ formatDate(treinamento.data_inicio) }}
            <span v-if="treinamento.data_fim"> até {{ formatDate(treinamento.data_fim) }}</span>
          </Text>
        </div>
        <div>
          <Text size="sm" color="muted" class="mb-1">Carga Horária</Text>
          <Text size="base" class="font-medium">{{ treinamento.carga_horaria }}h</Text>
        </div>
        <div>
          <Text size="sm" color="muted" class="mb-1">Vagas</Text>
          <Text size="base" class="font-medium">
            <span v-if="treinamento.numero_vagas">{{ treinamento.vagas_disponiveis }} de {{ treinamento.numero_vagas }} disponíveis</span>
            <span v-else>Ilimitadas</span>
          </Text>
        </div>
      </div>

      <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
        <div v-if="minhaInscricao" class="space-y-4">
          <div class="flex items-center gap-3">
            <Text size="sm">Sua inscrição:</Text>
            <Badge :color="minhaInscricao.status === 'APROVADA' ? 'green' : minhaInscricao.status === 'REPROVADA' ? 'red' : 'yellow'">
              {{ minhaInscricao.status_label }}
            </Badge>
          </div>

          <div v-if="minhaInscricao.status === 'APROVADA' && treinamento.tipo === 'PRESENCIAL'" class="text-center">
            <Text size="sm" color="muted" class="mb-2">Apresente este QR Code no dia do evento/curso para confirmar sua presença.</Text>
            <canvas ref="qrCanvas" class="mx-auto rounded-lg border border-slate-200 dark:border-slate-700"></canvas>
          </div>

          <Button
            v-if="minhaInscricao.status === 'APROVADA' && treinamento.tipo === 'ONLINE' && treinamento.presenca_liberada"
            variant="success"
            @click="confirmarPresenca"
          >
            Confirmar minha presença
          </Button>

          <a
            v-if="minhaInscricao.certificado_disponivel"
            :href="route('portal.treinamento.certificados.imprimir', minhaInscricao.certificado_id)"
            target="_blank"
            class="inline-block text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
          >
            Ver / imprimir certificado
          </a>
        </div>

        <Button v-else-if="treinamento.pode_receber_inscricao" variant="primary" @click="inscreverSe">
          Inscrever-se
        </Button>
        <Text v-else size="sm" color="muted">Este treinamento não está recebendo inscrições no momento.</Text>
      </div>
    </CardBase>
  </div>
</template>
