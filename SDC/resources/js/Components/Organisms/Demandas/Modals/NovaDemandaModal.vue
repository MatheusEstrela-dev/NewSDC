<template>
  <Modal :show="show" @close="handleClose" max-width="2xl">
    <div class="bg-white dark:bg-slate-900">
      <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nova Demanda</h3>
        <button @click="handleClose" class="text-slate-400 hover:text-slate-500">&times;</button>
      </div>
      
      <div class="p-6">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ttulo</label>
            <input v-model="form.titulo" type="text" class="form-input w-full rounded-md" required placeholder="Resumo do problema ou solicitauo" />
            <div v-if="form.errors.titulo" class="text-red-500 text-xs mt-1">{{ form.errors.titulo }}</div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria</label>
              <select v-model="form.categoria" class="form-select w-full rounded-md">
                <option value="">Selecione a categoria...</option>
                <option v-for="cat in filterOptions?.categorias || []" :key="cat.id" :value="cat.nome">{{ cat.nome }}</option>
              </select>
              <div v-if="form.errors.categoria" class="text-red-500 text-xs mt-1">{{ form.errors.categoria }}</div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Assunto / Subcategoria</label>
              <select v-model="form.subcategoria" class="form-select w-full rounded-md" :disabled="!selectedCategoriaObj">
                <option value="">Selecione o assunto...</option>
                <option v-for="sub in selectedCategoriaObj?.subcategorias || []" :key="sub.id" :value="sub.nome">{{ sub.nome }}</option>
              </select>
              <div v-if="form.errors.subcategoria" class="text-red-500 text-xs mt-1">{{ form.errors.subcategoria }}</div>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo de Demanda</label>
              <select v-model="form.tipo" class="form-select w-full rounded-md" required>
                <option value="">Selecione...</option>
                <option v-for="(label, val) in filterOptions?.tipos || {}" :key="val" :value="val">{{ label }}</option>
              </select>
              <div v-if="form.errors.tipo" class="text-red-500 text-xs mt-1">{{ form.errors.tipo }}</div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">UrgGncia Estimada</label>
              <select v-model="form.urgencia" class="form-select w-full rounded-md">
                <option value="">Deixar TI Avaliar</option>
                <option value="alta">Alta</option>
                <option value="media">M?dia</option>
                <option value="baixa">Baixa</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Atribuir para (Destinatorio)</label>
            <select v-model="form.responsavel_id" class="form-select w-full rounded-md">
              <option value="">Nuo atribuir agora (Fila geral)</option>
              <option v-for="user in filterOptions?.usuarios || []" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
            <div v-if="form.errors.responsavel_id" class="text-red-500 text-xs mt-1">{{ form.errors.responsavel_id }}</div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descriuo Detalhada</label>
            <textarea v-model="form.descricao" class="form-textarea w-full rounded-md" rows="5" required placeholder="Descreva os detalhes da solicitauo, passos para reproduzir o erro, etc."></textarea>
            <div v-if="form.errors.descricao" class="text-red-500 text-xs mt-1">{{ form.errors.descricao }}</div>
          </div>
          
          <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
            <Button variant="ghost" type="button" @click="handleClose">Cancelar</Button>
            <Button variant="primary" type="submit" :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
              {{ form.processing ? 'Salvando...' : 'Salvar Demanda' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  show: Boolean,
  filterOptions: {
    type: Object,
    default: () => ({ tipos: {}, categorias: [], usuarios: [] })
  }
});

const emit = defineEmits(['close']);

const form = useForm({
  titulo: '',
  tipo: '',
  urgencia: '',
  impacto: '',
  descricao: '',
  categoria: '',
  subcategoria: '',
  responsavel_id: '',
});

const selectedCategoriaObj = computed(() => {
  if (!props.filterOptions?.categorias) return null;
  return props.filterOptions.categorias.find(c => c.nome === form.categoria);
});

watch(() => form.categoria, () => {
  form.subcategoria = '';
});

const submit = () => {
  form.post(route('demandas.store'), {
    onSuccess: () => {
      form.reset();
      emit('close');
    }
  });
};

const handleClose = () => {
  form.reset();
  form.clearErrors();
  emit('close');
};
</script>
