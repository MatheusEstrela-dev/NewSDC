<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import { useToast } from '@/Composables/useToast';
import { formatDateTime } from '@/utils/dateFormatter';

/*
 * Credencial do proprio servidor: QR que a equipe da CEDEC le num evento para
 * conferir as capacitacoes. "Gerar novo QR" invalida o anterior.
 */
defineProps({
  credencial: { type: Object, required: true },
});

const { show: toast } = useToast();
const regerando = ref(false);

function regerar() {
  if (!confirm('Gerar um novo QR? O QR atual deixa de valer.')) return;

  regerando.value = true;
  router.post(route('treinamentos.aluno.credencial.regerar'), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Novo QR gerado. O anterior não vale mais.', 'success'),
    onError: () => toast('Não foi possível gerar um novo QR.', 'error'),
    onFinish: () => { regerando.value = false; },
  });
}
</script>

<template>
  <CardBase class="flex flex-col items-center gap-4 text-center sm:flex-row sm:items-center sm:gap-8 sm:text-left">
    <div class="w-full max-w-xs shrink-0 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200 sm:w-64">
      <img
        :src="'data:image/png;base64,' + credencial.qr_png_base64"
        alt="QR Code da sua credencial de capacitações"
        class="block h-auto w-full"
        style="image-rendering: pixelated"
      />
    </div>
    <div class="min-w-0 space-y-3">
      <p class="text-base font-medium text-slate-900 dark:text-white">
        Mostre este QR para a equipe da CEDEC conferir suas capacitações.
      </p>
      <p v-if="credencial.cpf_mascarado" class="text-sm text-slate-600 dark:text-slate-300">
        Vinculada ao CPF {{ credencial.cpf_mascarado }}
      </p>
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Gerado em {{ formatDateTime(credencial.gerado_em) || '-' }}
      </p>
      <Button variant="secondary" size="md" :loading="regerando" @click="regerar">Gerar novo QR</Button>
    </div>
  </CardBase>
</template>
