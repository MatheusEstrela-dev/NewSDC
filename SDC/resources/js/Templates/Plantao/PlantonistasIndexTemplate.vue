<script setup>
/**
 * Cadastro de quem pode ser escalado.
 *
 * O campo de adicionar e BUSCA, nao um select da base inteira: o SDC tem
 * milhares de contas COMPDEC municipais que nunca fazem plantao no Predio
 * Alterosas, e um select com todas seria inutilizavel -- alem de inflar o
 * payload do Inertia em toda visita.
 *
 * O posto ("Sgt", "Ten") vive aqui e nao em `users` de proposito: `users` e
 * tabela transversal a todo o sistema, e posto e vocabulario deste modulo.
 */
import Button from '@/Components/Atoms/Button/Button.vue';
import TrashIcon from '@/Components/Icons/TrashIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
  plantonistas: {
    type: Array,
    default: () => [],
  },
  filtros: {
    type: Object,
    default: () => ({ busca: '' }),
  },
  candidatos: {
    type: Array,
    default: () => [],
  },
  can: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['buscar', 'adicionar', 'atualizar', 'remover']);

const busca = ref(props.filtros?.busca ?? '');
const novo = reactive({ user_id: '', posto: '' });

watch(
  () => props.filtros,
  (valores) => {
    busca.value = valores?.busca ?? '';
  },
);

// Some com o formulario de adicionar quando o cadastro conclui.
watch(
  () => props.plantonistas.length,
  () => {
    novo.user_id = '';
    novo.posto = '';
  },
);

// Rascunho do posto por linha.
//
// O campo NAO emite a cada tecla: ligado direto ao emit, digitar "Sgt"
// dispararia tres PUTs. O botao de salvar so aparece quando o valor difere do
// gravado, e ai sai uma requisicao so.
const rascunhoPosto = reactive({});

watch(
  () => props.plantonistas,
  (lista) => {
    lista.forEach((p) => {
      rascunhoPosto[p.id] = p.posto ?? '';
    });
  },
  { immediate: true, deep: true },
);

const postoMudou = (p) => (rascunhoPosto[p.id] ?? '') !== (p.posto ?? '');

const salvarPosto = (p) => {
  if (!postoMudou(p)) return;
  emit('atualizar', { ...p, posto: rascunhoPosto[p.id] });
};

const submeterBusca = () => emit('buscar', busca.value);

const submeterNovo = () => {
  if (!novo.user_id) return;
  emit('adicionar', { ...novo });
};
</script>

<template>
  <div class="space-y-4 p-4 sm:space-y-6 sm:p-6">
    <PageHeader
      title="Plantonistas"
      description="Quem pode ser escalado no plantao"
      :icon="UsersIcon"
    />

    <!-- Adicionar -->
    <section
      v-if="can.gerir"
      class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-800/50"
    >
      <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
        Adicionar plantonista
      </h2>

      <form class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="submeterBusca">
        <div class="flex-1">
          <FormField
            v-model="busca"
            label="Buscar usuario"
            placeholder="Nome ou e-mail..."
            hint="A busca lista ate 20 usuarios que ainda nao sao plantonistas."
          />
        </div>
        <Button type="submit" variant="secondary" size="md">Buscar</Button>
      </form>

      <form
        v-if="candidatos.length"
        class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-end dark:border-slate-700/50"
        @submit.prevent="submeterNovo"
      >
        <div class="flex-1">
          <FormSelect
            v-model="novo.user_id"
            label="Usuario"
            required
            :options="candidatos"
            :error="errors.user_id"
            placeholder="Selecione..."
          />
        </div>
        <div class="w-full sm:w-32">
          <FormField
            v-model="novo.posto"
            label="Posto"
            placeholder="Sgt, Ten..."
            :error="errors.posto"
          />
        </div>
        <Button type="submit" variant="primary" size="md">Adicionar</Button>
      </form>

      <p
        v-else-if="filtros.busca"
        class="mt-3 text-sm text-slate-500 dark:text-slate-400"
      >
        Nenhum usuario novo encontrado para "{{ filtros.busca }}".
      </p>
    </section>

    <!-- Lista -->
    <section class="rounded-lg border border-slate-200 bg-white dark:border-slate-700/50 dark:bg-slate-800/50">
      <ListEmptyState
        v-if="!plantonistas.length"
        title="Nenhum plantonista cadastrado"
        helper="Adicione quem faz plantao para poder montar a escala."
        class="py-10"
      />

      <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700/50">
        <li
          v-for="p in plantonistas"
          :key="p.id"
          class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center"
        >
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
              {{ p.nome_com_posto }}
            </p>
            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ p.email }}</p>
          </div>

          <div v-if="can.gerir" class="flex items-center gap-2">
            <div class="w-24">
              <FormField
                v-model="rascunhoPosto[p.id]"
                placeholder="Posto"
                size="sm"
              />
            </div>

            <Button
              v-if="postoMudou(p)"
              variant="primary"
              size="sm"
              @click="salvarPosto(p)"
            >
              Salvar
            </Button>

            <Button
              :variant="p.ativo ? 'secondary' : 'success'"
              size="sm"
              @click="emit('atualizar', { ...p, ativo: !p.ativo })"
            >
              {{ p.ativo ? 'Inativar' : 'Reativar' }}
            </Button>

            <Button variant="danger" size="sm" @click="emit('remover', p)">
              <TrashIcon class="h-4 w-4" />
            </Button>
          </div>

          <span
            v-else
            class="rounded-full px-2 py-0.5 text-xs font-medium"
            :class="p.ativo
              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
              : 'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300'"
          >
            {{ p.ativo ? 'Ativo' : 'Inativo' }}
          </span>
        </li>
      </ul>
    </section>
  </div>
</template>
