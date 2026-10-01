<template>
  <div data-decisao-pedido>
    <div v-if="!acao" class="flex flex-wrap items-center justify-end gap-2">
      <p v-if="bloqueio" class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ bloqueio }}</p>
      <Button v-if="podeCancelar" variant="outline" size="sm" data-pedido-cancelar @click="abrir('cancelar')">Cancelar pedido</Button>
      <Button v-if="podeDecidir" variant="outline" size="sm" data-pedido-recusar @click="abrir('recusar')">Recusar</Button>
      <Button v-if="podeDecidir" variant="primary" size="sm" data-pedido-aprovar @click="abrir('aprovar')">Aprovar</Button>
    </div>

    <!-- Decisao no proprio card, sem modal. -->
    <form v-else class="space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700" @submit.prevent="enviar">
      <p class="text-xs font-semibold" :class="acao === 'aprovar' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'">{{ AVISOS[acao] }}</p>
      <FormField v-if="acao === 'aprovar'" v-model="form.processo_sei" label="Processo SEI" placeholder="1234.01.0012345/2026-12" required hint="Nenhuma transferência sem processo SEI." :error="form.errors.processo_sei" />
      <FormTextarea v-model="form.justificativa" label="Justificativa" :rows="3" required :error="form.errors.justificativa" />
      <p v-if="form.errors.pedido || form.errors.item" role="alert" class="text-sm font-semibold text-red-600 dark:text-red-400">{{ form.errors.pedido || form.errors.item }}</p>
      <div class="flex justify-end gap-2">
        <Button variant="outline" size="sm" :disabled="form.processing" @click="acao = null">Voltar</Button>
        <Button type="submit" size="sm" :variant="acao === 'aprovar' ? 'primary' : 'danger'" :loading="form.processing">{{ ROTULOS[acao] }}</Button>
      </div>
    </form>
  </div>
</template>

<script setup>
/**
 * Acoes sobre um pedido RESERVADO, na propria linha/pagina (sem modal):
 * a CEDEC aprova ou recusa; o solicitante pode cancelar. Quem solicitou nao
 * ve os botoes de decisao - o backend recusa de novo (servico e trigger).
 */
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  pedido: { type: Object, required: true },
  podeAprovar: { type: Boolean, default: false },
  usuarioId: { type: Number, default: 0 },
});

const AVISOS = {
  aprovar: 'Ao aprovar, a reserva segue até a entrega; os pontos continuam comprometidos.',
  recusar: 'Ao recusar, os pontos e a unidade voltam para o ente.',
  cancelar: 'Ao cancelar, os pontos e a unidade voltam para o ente.',
};
const ROTULOS = { aprovar: 'Aprovar pedido', recusar: 'Recusar pedido', cancelar: 'Cancelar pedido' };

const autor = computed(() => props.pedido.solicitado_por === props.usuarioId);
const reservado = computed(() => props.pedido.status === 'reservado');
const podeDecidir = computed(() => reservado.value && props.podeAprovar && !autor.value);
const podeCancelar = computed(() => reservado.value && autor.value);
const bloqueio = computed(() => (reservado.value && props.podeAprovar && autor.value ? 'Seu pedido: outra pessoa da CEDEC decide' : ''));

const acao = ref(null);
const form = useForm({ aprovar: true, justificativa: '', processo_sei: '' });

function abrir(qual) {
  form.reset();
  form.clearErrors();
  acao.value = qual;
}

function enviar() {
  const opcoes = { preserveScroll: true, onSuccess: () => { acao.value = null; } };
  if (acao.value === 'cancelar') {
    form.post(route('resgate.pedidos.cancelar', props.pedido.id), opcoes);
    return;
  }
  form.aprovar = acao.value === 'aprovar';
  form.post(route('resgate.pedidos.decidir', props.pedido.id), opcoes);
}
</script>
