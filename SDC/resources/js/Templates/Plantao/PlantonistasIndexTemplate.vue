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
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import UserIcon from '@/Components/Icons/UserIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
  plantonistas: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({}),
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

const cards = computed(() => [
  {
    id: 'total',
    title: 'Cadastrados',
    value: props.statistics.total || 0,
    variant: 'info',
    icon: UsersIcon,
  },
  {
    id: 'ativos',
    title: 'Ativos',
    value: props.statistics.ativos || 0,
    variant: 'success',
    icon: CheckCircleIcon,
  },
  {
    id: 'inativos',
    title: 'Inativos',
    value: props.statistics.inativos || 0,
    variant: 'warning',
    icon: UserIcon,
  },
  {
    // Sem posto o relatorio de passagem imprime so o nome, e o documento
    // perde o "Sgt"/"Ten" que a corporacao usa.
    id: 'sem_posto',
    title: 'Sem posto',
    value: props.statistics.sem_posto || 0,
    variant: (props.statistics.sem_posto || 0) > 0 ? 'danger' : 'success',
    icon: ExclamationTriangleIcon,
  },
]);

/**
 * Acoes da linha no padrao do sistema: ActionButton em modo grupo, com os
 * mesmos icones, cores e tooltips da coluna ACOES do Plantao Diario e do RAT.
 *
 * `aliasOverride: 'manage'` em todas: o ActionButton monta o slug
 * `{module}.{resource}.{action}` e consultaria `plantao.plantonistas.edit` e
 * `.delete`, que nao existem -- este modulo tem UM slug so,
 * `plantao.plantonistas.manage`. Sem o alias, o RBAC negaria tudo em silencio e
 * a coluna sumiria.
 *
 * `allowed: postoMudou(p)` no salvar: o botao so aparece quando o campo de
 * posto difere do gravado, entao nao ha requisicao a toa nem botao inerte.
 */
const acoesDe = (p) => [
  {
    // 'edit' e nao 'check': 'check' e 'finalize' compartilham o CheckIcon, e
    // numa linha inativa com posto alterado sairiam dois icones identicos.
    action: 'edit',
    aliasOverride: 'manage',
    label: 'Salvar posto',
    handler: () => salvarPosto(p),
    allowed: postoMudou(p),
  },
  {
    action: p.ativo ? 'archive' : 'finalize',
    aliasOverride: 'manage',
    label: p.ativo ? 'Inativar' : 'Reativar',
    handler: () => emit('atualizar', { ...p, ativo: !p.ativo }),
  },
  {
    action: 'delete',
    aliasOverride: 'manage',
    label: 'Remover da escala',
    handler: () => emit('remover', p),
  },
];

const submeterBusca = () => emit('buscar', busca.value);

const submeterNovo = () => {
  if (!novo.user_id) return;
  emit('adicionar', { ...novo });
};
</script>

<template>
  <div class="plantao-container">
    <PageHeader
      title="Plantonistas"
      description="Quem pode ser escalado no plantão"
      :icon="UsersIcon"
      :icon-image="moduleIcon('plantao')"
      variant="gradient"
      icon-class="text-blue-600 dark:text-blue-400"
    />

    <StatCardsGrid class="mb-6">
      <StatCard
        v-for="card in cards"
        :key="card.id"
        :title="card.title"
        :value="card.value"
        :variant="card.variant"
        :icon="card.icon"
      />
    </StatCardsGrid>

    <!-- Adicionar -->
    <section
      v-if="can.gerir"
      class="mb-6 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-800/60"
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
    <section class="rounded-xl border border-slate-200 bg-white dark:border-slate-700/50 dark:bg-slate-800/60">
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

            <ActionButton
              module="plantao"
              resource="plantonistas"
              size="sm"
              :actions="acoesDe(p)"
            />
          </div>

          <Badge v-else :variant="p.ativo ? 'success' : 'neutral'" size="sm">
            {{ p.ativo ? 'Ativo' : 'Inativo' }}
          </Badge>
        </li>
      </ul>
    </section>
  </div>
</template>

<style scoped>
.plantao-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
