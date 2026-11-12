<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import MenuCurso from '@/Components/Organisms/Treinamento/Professor/MenuCurso.vue';
import ProvaEditor from '@/Components/Organisms/Treinamento/Professor/ProvaEditor.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  curso: { type: Object, required: true },
  provas: { type: Array, default: () => [] },
  pendentes: { type: Number, default: 0 },
});

// `?item=` (vindo do Conteudo) escolhe a prova pelo item da pasta.
function provaInicial() {
  const item = Number(new URLSearchParams(window.location.search).get('item'));
  return props.provas.find((p) => p.item_id === item)?.id ?? props.provas[0]?.id ?? null;
}

const selecionadaId = ref(provaInicial());
const selecionada = computed(() => props.provas.find((p) => p.id === selecionadaId.value) ?? null);

function atualizarResumos() {
  router.reload({ only: ['provas'], preserveScroll: true });
}
</script>

<template>
  <div>
    <PageHeader
      :title="curso.titulo"
      description="Edite as questoes e a configuracao das provas de cada pasta."
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[14rem_1fr]">
      <MenuCurso :curso="curso" ativo="provas" :pendentes="pendentes" />

      <div class="min-w-0">
        <CardBase v-if="provas.length === 0">
          <Text color="muted">Nenhuma prova ainda. Adicione um item do tipo Prova ao fim de cada pasta na aba Conteudo.</Text>
        </CardBase>

        <div v-else class="grid grid-cols-1 gap-4 xl:grid-cols-[16rem_1fr]">
          <nav class="space-y-2" aria-label="Provas do curso">
            <button
              v-for="prova in provas"
              :key="prova.id"
              type="button"
              :aria-current="prova.id === selecionadaId ? 'true' : undefined"
              :class="[
                'w-full rounded-md border px-3 py-2 text-left text-sm transition-colors',
                prova.id === selecionadaId
                  ? 'border-blue-300 bg-blue-50 dark:border-blue-500/40 dark:bg-blue-500/20'
                  : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800',
              ]"
              @click="selecionadaId = prova.id"
            >
              <span class="block truncate font-medium">{{ prova.titulo }}</span>
              <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ prova.pasta }}</span>
              <span class="mt-1 flex flex-wrap gap-1">
                <Badge :variant="prova.total_questoes > 0 ? 'neutral' : 'warning'" size="sm">{{ prova.total_questoes }} questao(oes)</Badge>
                <Badge v-if="prova.travados > 0" variant="danger" size="sm">{{ prova.travados }} travado(s)</Badge>
              </span>
            </button>
          </nav>

          <ProvaEditor
            v-if="selecionada"
            :key="selecionada.id"
            :treinamento-id="curso.id"
            :prova-id="selecionada.id"
            @alterado="atualizarResumos"
          />
        </div>
      </div>
    </div>
  </div>
</template>
