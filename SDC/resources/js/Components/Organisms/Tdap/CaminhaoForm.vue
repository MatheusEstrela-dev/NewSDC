<template>
  <form @submit.prevent="$emit('submit', form)" class="space-y-6">
    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Vínculo e Identificação</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <InputLabel for="prestador_id" value="Prestador *" />
          <select
            id="prestador_id"
            v-model="form.prestador_id"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            required
          >
            <option :value="null">Selecione um prestador</option>
            <option v-for="p in prestadores" :key="p.id" :value="p.id">
              {{ p.nome }} ({{ p.cnpj }})
            </option>
          </select>
          <InputError :message="form.errors.prestador_id" class="mt-2" />
        </div>
        <div>
          <InputLabel for="placa" value="Placa *" />
          <TextInput
            id="placa"
            v-model="form.placa"
            type="text"
            class="mt-1 block w-full uppercase"
            placeholder="AAA1A11"
            maxlength="8"
            required
          />
          <InputError :message="form.errors.placa" class="mt-2" />
          <p class="text-xs text-slate-500 mt-1">Mercosul (AAA1A11) ou antigo (AAA1111).</p>
        </div>
        <div>
          <InputLabel for="capacidade_m3" value="Capacidade do Tanque (m³) *" />
          <TextInput
            id="capacidade_m3"
            v-model="form.capacidade_m3"
            type="number"
            step="0.5"
            min="0.5"
            max="999.99"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.capacidade_m3" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Veículo</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="marca" value="Marca" />
          <TextInput id="marca" v-model="form.marca" type="text" class="mt-1 block w-full" maxlength="50" />
          <InputError :message="form.errors.marca" class="mt-2" />
        </div>
        <div>
          <InputLabel for="modelo" value="Modelo" />
          <TextInput id="modelo" v-model="form.modelo" type="text" class="mt-1 block w-full" maxlength="50" />
          <InputError :message="form.errors.modelo" class="mt-2" />
        </div>
        <div>
          <InputLabel for="cor" value="Cor" />
          <TextInput id="cor" v-model="form.cor" type="text" class="mt-1 block w-full" maxlength="30" />
          <InputError :message="form.errors.cor" class="mt-2" />
        </div>
        <div>
          <InputLabel for="ano" value="Ano" />
          <TextInput
            id="ano"
            v-model="form.ano"
            type="text"
            class="mt-1 block w-full"
            maxlength="4"
            placeholder="2024"
          />
          <InputError :message="form.errors.ano" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Outros</h3>
      <div class="space-y-4">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" v-model="form.ativo" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          <span class="text-sm text-slate-700 dark:text-slate-300">Caminhão ativo</span>
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
  prestadores: { type: Array, default: () => [] },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);
</script>
