<template>
  <CardBase>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <FormField
        v-model="local.search"
        label="Buscar"
        placeholder="Codigo ou nome"
        @blur="emitChange"
      />

      <div>
        <Label>Tipo</Label>
        <select
          v-model="local.tipo"
          class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
          @change="emitChange"
        >
          <option value="">Todos</option>
          <option value="comunitaria">Comunitaria</option>
          <option value="individual">Individual</option>
          <option value="escolar">Escolar</option>
        </select>
      </div>

      <div>
        <Label>Status</Label>
        <select
          v-model="local.status"
          class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
          @change="emitChange"
        >
          <option value="">Todos</option>
          <option value="ativa">Ativa</option>
          <option value="pendente">Pendente</option>
          <option value="inativa">Inativa</option>
          <option value="em_obras">Em Obras</option>
        </select>
      </div>

      <div class="flex items-end">
        <Button variant="ghost" size="sm" @click="resetFilters">Limpar</Button>
      </div>
    </div>
  </CardBase>
</template>

<script setup>
import { reactive, watch } from 'vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Label from '@/Components/Atoms/Typography/Label.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['change']);

const local = reactive({
  search: props.filters?.search ?? '',
  tipo: props.filters?.tipo ?? '',
  status: props.filters?.status ?? '',
});

watch(() => props.filters, (val) => {
  Object.assign(local, {
    search: val?.search ?? '',
    tipo: val?.tipo ?? '',
    status: val?.status ?? '',
  });
}, { deep: true });

function emitChange() {
  emit('change', { ...local });
}

function resetFilters() {
  Object.assign(local, { search: '', tipo: '', status: '' });
  emitChange();
}
</script>
