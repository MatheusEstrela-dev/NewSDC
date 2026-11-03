<template>
  <Modal :show="show" max-width="lg" @close="$emit('close')">
    <div class="bg-white dark:bg-slate-900">
      <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Organizar pastas</h2>
        <ButtonIcon :icon="XMarkIcon" title="Fechar" size="sm" @click="$emit('close')" />
      </header>

      <div class="space-y-4 px-5 py-4">
        <form class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_12rem_auto] sm:items-end" @submit.prevent="criar">
          <FormField v-model="nova.nome" label="Nova pasta" placeholder="Nome da pasta" :maxlength="120" :error="nova.errors.nome" />
          <FormSelect v-model="nova.parent_id" label="Dentro de" :options="opcoesPai" placeholder="Raiz" />
          <Button type="submit" variant="primary" size="sm" :icon="PlusIcon" :loading="nova.processing" :disabled="!nova.nome.trim()">
            Criar
          </Button>
        </form>

        <p v-if="erro" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
          {{ erro }}
        </p>

        <ul class="max-h-80 space-y-1 overflow-y-auto">
          <li
            v-for="pasta in pastas"
            :key="pasta.id"
            class="flex min-h-[2.5rem] items-center gap-2 rounded-lg px-2 hover:bg-slate-50 dark:hover:bg-slate-800/50"
            :style="{ paddingLeft: `${0.5 + pasta.nivel * 1}rem` }"
          >
            <FolderIcon class="h-4 w-4 shrink-0 text-slate-400" />
            <input
              v-if="emEdicao === pasta.id"
              v-model="nomeEditado"
              class="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              maxlength="120"
              @keydown.enter.prevent="renomear(pasta)"
              @keydown.esc="emEdicao = null"
            />
            <span v-else class="min-w-0 flex-1 truncate text-sm text-slate-700 dark:text-slate-300">{{ pasta.nome }}</span>
            <span class="shrink-0 text-xs tabular-nums text-slate-400">{{ pasta.total }}</span>

            <template v-if="emEdicao === pasta.id">
              <ButtonIcon :icon="CheckIcon" title="Salvar nome" size="sm" variant="success" @click="renomear(pasta)" />
            </template>
            <template v-else>
              <ButtonIcon :icon="PencilSquareIcon" title="Renomear" size="sm" @click="editar(pasta)" />
              <ButtonIcon :icon="TrashIcon" title="Excluir pasta vazia" size="sm" variant="danger" @click="excluir(pasta)" />
            </template>
          </li>
        </ul>
      </div>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Gestao das pastas do workspace: criar (na raiz ou dentro de outra), renomear
 * e excluir pasta vazia. Recebe a mesma arvore que o painel de pastas exibe.
 */
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CheckIcon, FolderIcon, PencilSquareIcon, PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  categorias: { type: Array, default: () => [] },
});

defineEmits(['close']);

/** Arvore achatada, com nivel para o recuo e caminho para o select. */
const pastas = computed(() => {
  const lista = [];
  const visitar = (nos, nivel, caminho) => nos.forEach((no) => {
    const rotulo = caminho ? `${caminho} / ${no.nome}` : no.nome;
    lista.push({ ...no, nivel, rotulo });
    visitar(no.filhas ?? [], nivel + 1, rotulo);
  });
  visitar(props.categorias, 0, '');

  return lista;
});

const opcoesPai = computed(() => pastas.value.map((p) => ({ value: p.id, label: p.rotulo })));

const nova = useForm({ nome: '', parent_id: '' });
const emEdicao = ref(null);
const nomeEditado = ref('');
const erro = ref('');

watch(() => props.show, (aberto) => {
  if (!aberto) return;
  nova.reset();
  nova.clearErrors();
  emEdicao.value = null;
  erro.value = '';
});

// So a arvore muda: lista e nota aberta no workspace continuam como estao.
const recarga = {
  only: ['categorias', 'flash', 'errors'],
  preserveState: true,
  preserveScroll: true,
  onError: (e) => { erro.value = e.categoria ?? e.nome ?? 'Não foi possível salvar.'; },
  onSuccess: () => { erro.value = ''; },
};

function criar() {
  nova.transform((d) => ({ ...d, parent_id: d.parent_id || null }))
    .post(route('notas-tecnicas.categorias.store'), { ...recarga, onSuccess: () => { nova.reset(); erro.value = ''; } });
}

function editar(pasta) {
  emEdicao.value = pasta.id;
  nomeEditado.value = pasta.nome;
}

function renomear(pasta) {
  if (!nomeEditado.value.trim()) return;

  router.put(route('notas-tecnicas.categorias.update', pasta.id), { nome: nomeEditado.value }, {
    ...recarga,
    onSuccess: () => { emEdicao.value = null; erro.value = ''; },
  });
}

function excluir(pasta) {
  router.delete(route('notas-tecnicas.categorias.destroy', pasta.id), recarga);
}
</script>
