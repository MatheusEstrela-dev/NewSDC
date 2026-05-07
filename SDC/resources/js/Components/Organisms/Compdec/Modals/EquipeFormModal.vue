<template>
  <Modal :show="show" max-width="2xl" @close="handleClose">
    <div class="bg-slate-900 border border-slate-700">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-blue-700/10 to-cyan-600/5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-blue-500/15 text-blue-300">
              <UsersIcon class="w-5 h-5" />
            </div>
            <Heading :level="3" color="white">
              {{ isEditing ? 'Editar Membro' : 'Adicionar Membro a Equipe' }}
            </Heading>
          </div>
          <button
            class="text-slate-400 hover:text-slate-200 transition-colors"
            @click="handleClose"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Body -->
      <form class="px-6 py-5 max-h-[70vh] overflow-y-auto" @submit.prevent="handleSubmit">
        <div class="space-y-5">
          <!-- Nome + Funcao -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              v-model="form.nome"
              label="Nome"
              required
              :error="errors.nome"
            />

            <div>
              <Label required>Funcao</Label>
              <select
                v-model="form.funcao"
                required
                class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
              >
                <option value="" disabled>Selecione...</option>
                <option value="coordenador">Coordenador</option>
                <option value="agente">Agente</option>
                <option value="tecnico">Tecnico</option>
                <option value="apoio">Apoio</option>
                <option value="outro">Outro</option>
              </select>
              <p v-if="errors.funcao" class="mt-1 text-xs text-red-400">{{ errors.funcao }}</p>
            </div>
          </div>

          <!-- Email + CPF -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              v-model="form.email"
              type="email"
              label="E-mail"
              :error="errors.email"
            />

            <FormField
              v-model="form.cpf"
              label="CPF"
              :error="errors.cpf"
              placeholder="000.000.000-00"
            />
          </div>

          <!-- Telefone + Celular -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              v-model="form.telefone"
              label="Telefone"
              :error="errors.telefone"
              placeholder="(00) 0000-0000"
            />

            <FormField
              v-model="form.celular"
              label="Celular"
              :error="errors.celular"
              placeholder="(00) 00000-0000"
            />
          </div>

          <!-- Ordem + Ativo -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              v-model.number="form.ordem"
              type="number"
              label="Ordem"
              :error="errors.ordem"
              hint="Ordem de exibicao na equipe (0 = primeiro)"
            />

            <ToggleField
              v-model="form.ativo"
              label="Ativo"
              hint="Membros inativos nao contam para qtd_efetivo"
            />
          </div>

          <!-- Observacoes -->
          <FormTextarea
            v-model="form.observacoes"
            label="Observacoes"
            :error="errors.observacoes"
            :rows="3"
          />
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-700">
          <Button variant="ghost" type="button" @click="handleClose">
            Cancelar
          </Button>
          <Button variant="primary" type="submit" :loading="loading">
            {{ isEditing ? 'Salvar' : 'Adicionar' }}
          </Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { XMarkIcon, UsersIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Label from '@/Components/Atoms/Typography/Label.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  orgaoId: {
    type: [Number, String],
    required: true,
  },
  equipe: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const errors = ref({});

const form = reactive({
  nome: '',
  funcao: '',
  email: '',
  cpf: '',
  telefone: '',
  celular: '',
  ordem: 0,
  ativo: true,
  observacoes: '',
});

const isEditing = computed(() => !!props.equipe?.id);

watch(
  () => props.show,
  (visible) => {
    if (!visible) return;
    errors.value = {};
    if (props.equipe) {
      Object.assign(form, {
        nome: props.equipe.nome ?? '',
        funcao: props.equipe.funcao ?? '',
        email: props.equipe.email ?? '',
        cpf: props.equipe.cpf ?? '',
        telefone: props.equipe.telefone ?? '',
        celular: props.equipe.celular ?? '',
        ordem: props.equipe.ordem ?? 0,
        ativo: props.equipe.ativo ?? true,
        observacoes: props.equipe.observacoes ?? '',
      });
    } else {
      Object.assign(form, {
        nome: '',
        funcao: '',
        email: '',
        cpf: '',
        telefone: '',
        celular: '',
        ordem: 0,
        ativo: true,
        observacoes: '',
      });
    }
  },
);

function handleClose() {
  if (loading.value) return;
  emit('close');
}

function handleSubmit() {
  loading.value = true;
  errors.value = {};

  const payload = { ...form };
  const onSuccess = () => emit('saved');
  const onError = (e) => { errors.value = e; };
  const onFinish = () => { loading.value = false; };

  if (isEditing.value) {
    router.put(
      route('compdec.equipe.update', { orgao: props.orgaoId, equipe: props.equipe.id }),
      payload,
      { preserveScroll: true, onSuccess, onError, onFinish },
    );
  } else {
    router.post(
      route('compdec.equipe.store', props.orgaoId),
      payload,
      { preserveScroll: true, onSuccess, onError, onFinish },
    );
  }
}
</script>
