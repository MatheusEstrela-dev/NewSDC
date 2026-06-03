<template>
  <form @submit.prevent="$emit('submit', form)" class="space-y-6">
    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Identificação da Ata</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="numero" value="Número da Ata *" />
          <TextInput
            id="numero"
            v-model="form.numero"
            type="text"
            class="mt-1 block w-full uppercase"
            maxlength="20"
            placeholder="Ex: 001/2026"
            required
          />
          <InputError :message="form.errors.numero" class="mt-2" />
        </div>
        <div>
          <InputLabel for="dt_inicio" value="Data Inicial *" />
          <TextInput
            id="dt_inicio"
            v-model="form.dt_inicio"
            type="date"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.dt_inicio" class="mt-2" />
        </div>
        <div>
          <InputLabel for="dt_final" value="Data Final *" />
          <TextInput
            id="dt_final"
            v-model="form.dt_final"
            type="date"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.dt_final" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Detalhes</h3>
      <div class="space-y-4">
        <div>
          <InputLabel for="historico" value="Histórico" />
          <textarea
            id="historico"
            v-model="form.historico"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            rows="4"
            maxlength="5000"
            placeholder="Histórico, edital de origem, processo licitatório, etc."
          />
          <InputError :message="form.errors.historico" class="mt-2" />
        </div>
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" v-model="form.ativo" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          <span class="text-sm text-slate-700 dark:text-slate-300">Ata ativa</span>
        </label>
        <div>
          <InputLabel for="observacoes" value="Observações" />
          <textarea
            id="observacoes"
            v-model="form.observacoes"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            rows="3"
            maxlength="2000"
          />
          <InputError :message="form.errors.observacoes" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3">
      <SecondaryButton type="button" @click="$emit('cancel')">Cancelar</SecondaryButton>
      <PrimaryButton type="submit" :disabled="form.processing">
        {{ form.processing ? 'Salvando...' : submitLabel }}
      </PrimaryButton>
    </div>
  </form>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);
</script>
