<template>
  <div class="space-y-4">
    <p v-if="carregando && !dados" class="text-sm text-slate-500 dark:text-slate-400">Carregando checklist...</p>
    <PaeAviso v-if="erroGeral" tom="erro">{{ erroGeral }}</PaeAviso>

    <template v-if="dados">
      <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Checklist GMG 83 · {{ dados.titulo }}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ resumo.respondidos }} de {{ resumo.total }} respondidos<template v-if="resumo.nao"> · <span class="font-semibold text-red-600 dark:text-red-300">{{ resumo.nao }} com "Não"</span></template>
          </p>
          <div class="mt-2 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div class="h-full rounded-full bg-green-600 transition-all" :style="{ width: `${percentual}%` }" />
          </div>
        </div>
        <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filtrar itens">
          <button
            v-for="opcao in FILTROS"
            :key="opcao.id"
            type="button"
            class="rounded-full border px-3 py-1 text-xs font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
            :class="filtro === opcao.id ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'border-slate-300 text-slate-600 dark:border-slate-600 dark:text-slate-300'"
            :aria-pressed="filtro === opcao.id"
            @click="filtro = opcao.id"
          >{{ opcao.rotulo }}</button>
        </div>
      </div>

      <CollapsibleSection
        v-if="dados.pendencias"
        namespace="pae"
        :section-id="`checklist-${secao}-pendencias`"
        title="Itens marcados &quot;Não&quot; no protocolo"
        subtitle="Reúne as pendências de todas as seções da pasta. Serve de base para a notificação ao empreendedor."
        :icon="ExclamationTriangleIcon"
        :tom="dados.pendencias.length ? 'danger' : 'success'"
        :status-text="String(dados.pendencias.length)"
      >
        <p v-if="!dados.pendencias.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhum item marcado "Não" até agora.</p>
        <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
          <li v-for="pendencia in dados.pendencias" :key="pendencia.codigo" class="py-3 text-sm">
            <p class="text-slate-800 dark:text-slate-100"><span class="mr-2 font-mono text-xs font-semibold text-slate-500 dark:text-slate-400">{{ pendencia.codigo }}</span>{{ pendencia.texto }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ pendencia.secao_titulo }}<template v-if="pendencia.artigos"> · {{ pendencia.artigos }}</template></p>
            <p v-if="pendencia.observacao" class="mt-1 whitespace-pre-line text-slate-700 dark:text-slate-300">{{ pendencia.observacao }}</p>
          </li>
        </ul>
      </CollapsibleSection>

      <CollapsibleSection
        v-for="grupo in gruposVisiveis"
        :key="grupo.titulo"
        namespace="pae"
        :section-id="`checklist-${secao}-${grupo.indice}`"
        :title="grupo.titulo"
        :icon="ClipboardDocumentCheckIcon"
        :status-text="contagem(grupo)"
        :tom="tomDoGrupo(grupo)"
        :expandido-por-padrao="dados.grupos.length <= 3"
        sanfona-no-mobile
      >
        <PaeChecklistItem
          v-for="item in grupo.itensFiltrados"
          :key="item.codigo"
          v-model="rascunho[item.codigo]"
          :item="item"
          :somente-leitura="!podeEditar"
          :alterado="alterados.includes(item.codigo)"
          :erro="erros[item.codigo] || ''"
        />
      </CollapsibleSection>

      <p v-if="!gruposVisiveis.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhum item neste filtro.</p>

      <div v-if="podeEditar" class="sticky bottom-0 z-10 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white/95 p-3 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95 sm:flex-row sm:items-center sm:justify-end">
        <p class="text-sm text-slate-600 dark:text-slate-300 sm:mr-auto">
          {{ alterados.length ? `${alterados.length} alteração(ões) não salva(s)` : 'Nenhuma alteração pendente' }}
        </p>
        <Button v-if="alterados.length" variant="outline" :disabled="salvando" @click="descartar">Descartar</Button>
        <Button :loading="salvando" :disabled="!alterados.length" @click="salvarChecklist">{{ salvando ? 'Salvando...' : 'Salvar checklist' }}</Button>
      </div>
      <p v-else class="text-xs text-slate-500 dark:text-slate-400">Checklist disponível somente para consulta.</p>
    </template>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import PaeChecklistItem from '@/Components/Organisms/Pae/Checklist/PaeChecklistItem.vue';
import { usePaeChecklist } from '@/Composables/pae/usePaeChecklist';
import { useToast } from '@/Composables/useToast';
import { ClipboardDocumentCheckIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  protocoloId: { type: Number, required: true },
  // Secao da pasta: ficha, dco, evacuacao, simulados, relatorio ou anexo_a..anexo_j.
  secao: { type: String, required: true },
  // Tela em modo consulta (ex.: ?readonly=1 ou versao historica).
  somenteLeitura: { type: Boolean, default: false },
});

const FILTROS = [
  { id: 'todos', rotulo: 'Todos' },
  { id: 'pendentes', rotulo: 'Sem resposta' },
  { id: 'nao', rotulo: 'Marcados "Não"' },
];

const { dados, rascunho, alterados, resumo, carregando, salvando, erroGeral, erros, carregar, salvar, descartar } = usePaeChecklist(props.protocoloId, props.secao);
const filtro = ref('todos');
const { show: toast } = useToast();

const podeEditar = computed(() => !!dados.value?.pode_editar && !props.somenteLeitura);
const percentual = computed(() => (resumo.value.total ? Math.round((100 * resumo.value.respondidos) / resumo.value.total) : 0));

function passaFiltro(item) {
  const resposta = rascunho[item.codigo]?.resposta;
  if (filtro.value === 'pendentes') return item.tipo === 'resposta' && !resposta;
  if (filtro.value === 'nao') return resposta === 'nao';
  return true;
}

const gruposVisiveis = computed(() => (dados.value?.grupos ?? [])
  .map((grupo, indice) => ({ ...grupo, indice, itensFiltrados: grupo.itens.filter(passaFiltro) }))
  .filter((grupo) => grupo.itensFiltrados.length));

function avaliaveis(grupo) {
  return grupo.itens.filter((item) => item.tipo === 'resposta');
}

function contagem(grupo) {
  const lista = avaliaveis(grupo);
  if (!lista.length) return '';
  return `${lista.filter((item) => rascunho[item.codigo]?.resposta).length}/${lista.length}`;
}

function tomDoGrupo(grupo) {
  if (grupo.itens.some((item) => rascunho[item.codigo]?.resposta === 'nao')) return 'danger';
  const lista = avaliaveis(grupo);
  return lista.length && lista.every((item) => rascunho[item.codigo]?.resposta) ? 'success' : 'info';
}

async function salvarChecklist() {
  if (await salvar()) toast('Checklist salvo.');
}

onMounted(carregar);
</script>
