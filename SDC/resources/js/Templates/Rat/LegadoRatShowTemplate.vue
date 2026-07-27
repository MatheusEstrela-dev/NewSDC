<template>
  <div class="legado-rat-show">
    <PageHeader
      :title="`RAT ${rat.num_ocorrencia}`"
      description="Arquivo morto do RAT legado (somente leitura)"
      :icon="ArchiveBoxIcon"
      :icon-image="moduleIcon('rat')"
      variant="gradient"
    >
      <template #actions>
        <a :href="route('rat.arquivados.print', rat.id)" target="_blank">
          <Button variant="secondary" size="md" :icon="PrinterIcon" icon-position="left">
            <span class="hidden sm:inline">Imprimir</span>
          </Button>
        </a>
      </template>
    </PageHeader>

    <div class="mb-4">
      <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 text-xs font-medium px-3 py-1">
        <ArchiveBoxIcon class="w-4 h-4" /> Registro legado — somente leitura
      </span>
    </div>

    <!-- Dados principais -->
    <section class="card">
      <h2 class="card-title">Dados da Ocorrencia</h2>
      <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
        <Campo label="Numero" :valor="rat.num_ocorrencia" />
        <Campo label="Data" :valor="formatDate(rat.dt_ocorrencia)" />
        <Campo label="Municipio" :valor="rat.municipio" />
        <Campo label="Tipo" :valor="rat.tipo" />
        <Campo label="Alvo" :valor="rat.alvo" />
        <Campo label="COBRADE" :valor="rat.cobrade || 'Nao informado'" />
        <Campo label="Operador" :valor="rat.operador" />
        <Campo label="Nome da operacao" :valor="rat.nome_operacao || '-'" />
      </dl>
    </section>

    <!-- Local -->
    <section class="card">
      <h2 class="card-title">Local</h2>
      <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
        <Campo label="Endereco" :valor="[rat.endereco, rat.numero].filter(Boolean).join(', ') || '-'" />
        <Campo label="Bairro" :valor="rat.bairro || '-'" />
        <Campo label="CEP" :valor="rat.cep || '-'" />
        <Campo label="Estado" :valor="rat.estado || '-'" />
        <Campo label="Referencia" :valor="rat.referencia || '-'" class="lg:col-span-2" />
        <Campo label="Envolvidos" :valor="rat.envolvidos || '-'" class="sm:col-span-2 lg:col-span-3" />
      </dl>
    </section>

    <!-- Acoes / Historico -->
    <section class="card">
      <h2 class="card-title">Acoes / Historico</h2>
      <div
        v-if="rat.acoes_html"
        class="prose prose-sm max-w-none dark:prose-invert text-gray-700 dark:text-gray-300"
        v-html="rat.acoes_html"
      />
      <p v-else class="text-gray-400 italic">Sem descricao registrada.</p>
    </section>

    <!-- Imagens Relacionadas / Anexos (bloco retratil) -->
    <section class="card">
      <button
        type="button"
        class="w-full flex items-center justify-between gap-3 text-left"
        :aria-expanded="anexosAberto.toString()"
        @click="anexosAberto = !anexosAberto"
      >
        <span class="flex items-center gap-2 card-title !mb-0 !border-0 !pb-0">
          <PhotoIcon class="w-5 h-5 text-slate-400" />
          Imagens Relacionadas
          <span
            v-if="anexos.length"
            class="ml-1 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-medium px-2 py-0.5"
          >{{ anexos.length }}</span>
        </span>
        <ChevronDownIcon
          class="w-5 h-5 text-slate-400 transition-transform duration-200"
          :class="anexosAberto ? 'rotate-0' : '-rotate-90'"
        />
      </button>

      <div v-show="anexosAberto" class="mt-4">
        <div v-if="imagens.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <a
            v-for="anexo in imagens"
            :key="anexo.url"
            :href="anexo.url"
            target="_blank"
            rel="noopener"
            class="group block overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700"
            :title="anexo.nome"
          >
            <img
              :src="anexo.url"
              :alt="anexo.nome"
              loading="lazy"
              class="h-40 w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
          </a>
        </div>

        <div v-if="documentos.length" class="mt-4 flex flex-col gap-2">
          <a
            v-for="anexo in documentos"
            :key="anexo.url"
            :href="anexo.url"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline"
          >
            <PaperClipIcon class="w-4 h-4" /> {{ anexo.nome }}
          </a>
        </div>

        <p v-if="!anexos.length" class="text-gray-400 italic">Sem anexos.</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { ArchiveBoxIcon, ChevronDownIcon, PaperClipIcon, PhotoIcon, PrinterIcon } from '@heroicons/vue/24/outline';
import { computed, h, ref } from 'vue';

const props = defineProps({
  rat: { type: Object, required: true },
  anexos: { type: Array, default: () => [] },
});

const anexosAberto = ref(false);
const imagens = computed(() => props.anexos.filter((a) => a.is_imagem));
const documentos = computed(() => props.anexos.filter((a) => !a.is_imagem));

// Componente inline (atomo local) para pares rotulo/valor.
const Campo = (props) =>
  h('div', {}, [
    h('dt', { class: 'text-xs font-semibold uppercase tracking-wide text-gray-400' }, props.label),
    h('dd', { class: 'mt-1 text-sm text-gray-800 dark:text-gray-100 break-words' }, props.valor ?? '-'),
  ]);
Campo.props = ['label', 'valor'];

function formatDate(iso) {
  if (!iso) return 'Nao informado';
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}
</script>

<style scoped>
.card {
  border-radius: 0.75rem;
  border: 1px solid rgb(229 231 235);
  background: #fff;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.25rem;
}
.dark .card { border-color: rgb(55 65 81); background: rgb(31 41 55); }
.card-title {
  font-weight: 600;
  font-size: 1rem;
  color: rgb(17 24 39);
  margin-bottom: 1rem;
  padding-bottom: .5rem;
  border-bottom: 1px solid rgb(243 244 246);
}
.dark .card-title { color: rgb(243 244 246); border-color: rgb(55 65 81); }
</style>
