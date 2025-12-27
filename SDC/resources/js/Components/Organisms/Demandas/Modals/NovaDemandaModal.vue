<template>
  <Modal :show="show" @close="handleClose" max-width="2xl">
    <div class="bg-slate-900 border border-slate-700">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-red-700/10 to-rose-600/5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-red-500/15 text-red-300">
              <PlusIcon class="w-5 h-5" />
            </div>
            <Heading :level="3" color="white">Nova Demanda</Heading>
          </div>
          <button
            @click="handleClose"
            class="text-slate-400 hover:text-slate-200 transition-colors"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
        <form @submit.prevent="handleSubmit" class="space-y-5">
          <!-- Título -->
          <div>
            <label class="block mb-2">
              <Text size="sm" weight="medium" color="white">
                Título <span class="text-red-400">*</span>
              </Text>
            </label>
            <input
              v-model="form.titulo"
              type="text"
              required
              placeholder="Ex: Solicitação de novo servidor"
              class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-500 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all"
            />
          </div>

          <!-- Tipo e Prioridade -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-2">
                <Text size="sm" weight="medium" color="white">
                  Tipo <span class="text-red-400">*</span>
                </Text>
              </label>
              <select
                v-model="form.tipo"
                required
                class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all"
              >
                <option value="">Selecione...</option>
                <option value="tarefa">Tarefa</option>
                <option value="bug">Bug</option>
                <option value="melhoria">Melhoria</option>
                <option value="suporte">Suporte</option>
              </select>
            </div>

            <div>
              <label class="block mb-2">
                <Text size="sm" weight="medium" color="white">
                  Prioridade <span class="text-red-400">*</span>
                </Text>
              </label>
              <select
                v-model="form.prioridade"
                required
                class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all"
              >
                <option value="">Selecione...</option>
                <option value="baixa">Baixa</option>
                <option value="media">Média</option>
                <option value="alta">Alta</option>
                <option value="critica">Crítica</option>
              </select>
            </div>
          </div>

          <!-- Descrição -->
          <div>
            <label class="block mb-2">
              <Text size="sm" weight="medium" color="white">
                Descrição <span class="text-red-400">*</span>
              </Text>
            </label>
            <textarea
              v-model="form.descricao"
              required
              rows="4"
              placeholder="Descreva detalhadamente a demanda..."
              class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-500 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all resize-none"
            ></textarea>
          </div>

          <!-- Responsável e Prazo -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-2">
                <Text size="sm" weight="medium" color="white">
                  Responsável
                </Text>
              </label>
              <select
                v-model="form.responsavel_id"
                class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all"
              >
                <option value="">Não atribuído</option>
                <option value="1">João Silva</option>
                <option value="2">Maria Santos</option>
                <option value="3">Pedro Oliveira</option>
              </select>
            </div>

            <div>
              <label class="block mb-2">
                <Text size="sm" weight="medium" color="white">
                  Prazo
                </Text>
              </label>
              <input
                v-model="form.prazo"
                type="date"
                class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all"
              />
            </div>
          </div>

          <!-- Tags -->
          <div>
            <label class="block mb-2">
              <Text size="sm" weight="medium" color="white">
                Tags
              </Text>
            </label>
            <input
              v-model="form.tags"
              type="text"
              placeholder="Ex: infraestrutura, urgente (separadas por vírgula)"
              class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-500 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all"
            />
            <Text size="xs" color="muted" class="mt-1">
              Separe múltiplas tags com vírgula
            </Text>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-slate-700 bg-slate-900/50 flex items-center justify-end gap-3">
        <Button
          variant="ghost"
          size="md"
          @click="handleClose"
        >
          Cancelar
        </Button>
        <Button
          variant="primary"
          size="md"
          @click="handleSubmit"
          :disabled="!isFormValid"
        >
          Criar Demanda
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
  titulo: '',
  tipo: '',
  prioridade: '',
  descricao: '',
  responsavel_id: '',
  prazo: '',
  tags: '',
});

const isFormValid = computed(() => {
  return form.value.titulo.trim() !== '' &&
         form.value.tipo !== '' &&
         form.value.prioridade !== '' &&
         form.value.descricao.trim() !== '';
});

const handleClose = () => {
  resetForm();
  emit('close');
};

const handleSubmit = () => {
  if (!isFormValid.value) return;

  const demandaData = {
    ...form.value,
    tags: form.value.tags ? form.value.tags.split(',').map(t => t.trim()).filter(t => t) : [],
  };

  emit('submit', demandaData);
  resetForm();
};

const resetForm = () => {
  form.value = {
    titulo: '',
    tipo: '',
    prioridade: '',
    descricao: '',
    responsavel_id: '',
    prazo: '',
    tags: '',
  };
};
</script>
