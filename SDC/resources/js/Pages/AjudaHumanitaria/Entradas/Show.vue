<template>
  <AuthenticatedLayout>
    <Head :title="titulo" />

    <EntradaAhShowTemplate
      :entrada="entrada"
      :pode-cancelar="podeCancelar"
      @cancelar="confirmando = true"
    />

    <ConfirmDialog
      :is-open="confirmando"
      title="Cancelar entrada"
      :message="`Cancelar a entrada ${entrada.codigo_legado ?? entrada.id}?`"
      description="O saldo recebido é estornado por um lançamento compensatório no estoque. A entrada continua no histórico, marcada como cancelada."
      variant="danger"
      confirm-text="Sim, cancelar"
      cancel-text="Voltar"
      :loading="cancelando"
      @confirm="cancelar"
      @cancel="confirmando = false"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
// ZiggyVue registra route() apenas em globalProperties, o que so alcanca o
// template. Em <script setup> a funcao precisa ser importada.
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import EntradaAhShowTemplate from '@/Templates/AjudaHumanitaria/EntradaAhShowTemplate.vue';

const props = defineProps({
  entrada: { type: Object, required: true },
  podeCancelar: { type: Boolean, default: false },
});

const titulo = computed(() => 'Entrada ' + (props.entrada.codigo_legado ?? props.entrada.id));

const confirmando = ref(false);
const cancelando = ref(false);

function cancelar() {
  cancelando.value = true;

  router.post(route('ajuda-humanitaria.entradas.cancelar', props.entrada.id), {}, {
    preserveScroll: true,
    // onFinish, e nao onSuccess: material que ja saiu do deposito volta com
    // mensagem de erro, e o dialogo aberto esconderia o aviso.
    onFinish: () => {
      cancelando.value = false;
      confirmando.value = false;
    },
  });
}
</script>
