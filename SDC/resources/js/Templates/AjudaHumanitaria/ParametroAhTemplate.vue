<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      title="Parâmetros do Módulo"
      description="Regras que o CEDEC ajusta sem depender de nova versão do sistema."
      :icon="ClockIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    />

    <ListContainer title="Prestação de contas" :icon="ClipboardDocumentListIcon" icon-class="text-blue-500">
      <form class="space-y-6 p-4 sm:p-6" @submit.prevent="salvar">
        <div class="max-w-sm">
          <InputLabel for="prazo" value="Prazo para prestar contas (dias) *" />
          <TextInput
            id="prazo"
            v-model="form.prazo_prestacao_contas_dias"
            type="number"
            min="1"
            max="365"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.prazo_prestacao_contas_dias" class="mt-2" />

          <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            Contado em dias corridos a partir do atendimento do pedido. Vale para as
            próximas prestações: as que já estão abertas mantêm a data que receberam.
          </p>
        </div>

        <div
          v-if="parametros.atualizado_em"
          class="text-xs text-slate-500 dark:text-slate-400"
        >
          Última alteração em {{ formatarDataHora(parametros.atualizado_em) }}.
        </div>

        <div class="flex items-center gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
          <PrimaryButton type="submit" :disabled="form.processing || !mudou">
            {{ form.processing ? 'Salvando...' : 'Salvar' }}
          </PrimaryButton>

          <SecondaryButton v-if="mudou" type="button" @click="desfazer">Desfazer</SecondaryButton>
        </div>
      </form>
    </ListContainer>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  parametros: { type: Object, required: true },
});

const form = useForm({
  prazo_prestacao_contas_dias: props.parametros.prazo_prestacao_contas_dias,
});

// Salvar so fica disponivel quando ha o que salvar: a tela tem um campo unico,
// e um botao sempre ativo convidaria a gravar sem alteracao.
const mudou = computed(
  () => Number(form.prazo_prestacao_contas_dias) !== Number(props.parametros.prazo_prestacao_contas_dias),
);

function salvar() {
  form.put(route('ajuda-humanitaria.parametros.update'), { preserveScroll: true });
}

function desfazer() {
  form.clearErrors();
  form.prazo_prestacao_contas_dias = props.parametros.prazo_prestacao_contas_dias;
}

function formatarDataHora(iso) {
  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>
