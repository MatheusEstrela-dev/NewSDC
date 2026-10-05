<template>
  <div class="space-y-4">
    <template v-if="dados">
      <p v-if="dados.legado_sem_triagem && !dados.decisao_vigente" class="rounded-lg border border-amber-400/40 p-3 text-sm text-amber-200">
        Triagem não registrada no sistema para este protocolo anterior à implantação.
      </p>

      <div v-if="dados.decisao_vigente" class="modal-serie-cartao rounded-xl p-4 text-sm modal-serie-valor">
        Decisão vigente: <strong>{{ rotuloDecisao(dados.decisao_vigente.tipo) }}</strong>
      </div>

      <form class="space-y-5" @submit.prevent="salvar">
        <section class="modal-serie-cartao rounded-xl p-4">
          <h4 class="font-semibold modal-serie-titulo">Municípios abrangidos pela ZAS e ZSS</h4>
          <p class="mt-1 text-xs modal-serie-apoio">Marque ambas as zonas quando o município integrar as duas áreas.</p>

          <div v-if="form.municipios.length" class="mt-3 space-y-2">
            <div v-for="(municipio, indice) in form.municipios" :key="municipio.municipio_id" class="flex flex-wrap items-center gap-3 rounded-lg border border-slate-600/40 p-2 text-sm modal-serie-valor">
              <span class="min-w-0 flex-1">{{ nomeMunicipio(municipio.municipio_id) }}</span>
              <label class="inline-flex items-center gap-1"><input v-model="municipio.na_zas" type="checkbox" :disabled="!podeEditar" /> ZAS</label>
              <label class="inline-flex items-center gap-1"><input v-model="municipio.na_zss" type="checkbox" :disabled="!podeEditar" /> ZSS</label>
              <button v-if="podeEditar" type="button" class="text-red-300 hover:underline" @click="form.municipios.splice(indice, 1)">Remover</button>
              <p v-if="form.errors[`municipios.${indice}.na_zas`]" class="w-full text-xs text-red-300">{{ form.errors[`municipios.${indice}.na_zas`] }}</p>
            </div>
          </div>
          <p v-else class="mt-3 text-sm modal-serie-apoio">Nenhum município cadastrado.</p>

          <div v-if="podeEditar" class="mt-3 flex flex-wrap gap-2">
            <select v-model="municipioSelecionado" aria-label="Município para adicionar" class="min-w-0 flex-1 rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white">
              <option value="">Selecione um município</option>
              <option v-for="municipio in municipiosDisponiveis" :key="municipio.id" :value="municipio.id">{{ municipio.nome }} / {{ municipio.uf }}</option>
            </select>
            <button type="button" class="rounded-lg border border-blue-400 px-3 py-2 text-sm text-blue-200" :disabled="!municipioSelecionado" @click="adicionarMunicipio">Adicionar</button>
          </div>
          <p v-if="form.errors.municipios" class="mt-2 text-xs text-red-300">{{ form.errors.municipios }}</p>
        </section>

        <section class="modal-serie-cartao rounded-xl p-4">
          <h4 class="font-semibold modal-serie-titulo">Checklist físico do Anexo J</h4>
          <p class="mt-1 text-xs modal-serie-apoio">Uma ausência exige justificativa e será avaliada pela CEDEC. Não gera reprovação automática.</p>
          <div class="mt-3 space-y-3">
            <div v-for="(item, indice) in form.itens" :key="item.chave" class="rounded-lg border border-slate-600/40 p-3">
              <label class="block text-sm font-medium modal-serie-valor" :for="`item-${item.chave}`">{{ rotulos[item.chave] }}</label>
              <select :id="`item-${item.chave}`" v-model="item.resultado" :disabled="!podeEditar" class="mt-2 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white">
                <option value="">Pendente</option>
                <option value="sim">Apresentado</option>
                <option value="nao">Não apresentado</option>
                <option value="nao_aplicavel">Não aplicável</option>
              </select>
              <textarea v-if="item.resultado === 'nao' || item.resultado === 'nao_aplicavel'" v-model="item.justificativa" :disabled="!podeEditar" rows="2" placeholder="Justificativa da avaliação" class="mt-2 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white" />
              <p v-if="form.errors[`itens.${indice}.justificativa`]" class="mt-1 text-xs text-red-300">{{ form.errors[`itens.${indice}.justificativa`] }}</p>
            </div>
          </div>
        </section>

        <button v-if="podeEditar" type="submit" :disabled="form.processing" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
          {{ form.processing ? 'Salvando...' : 'Salvar triagem' }}
        </button>
      </form>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  protocoloId: { type: Number, default: null },
  canEdit: { type: Boolean, default: false },
  admissibilidade: { type: Object, default: null },
  municipiosDisponiveis: { type: Array, default: () => [] },
});
const emit = defineEmits(['atualizado']);
const dados = computed(() => props.admissibilidade);
const municipiosDisponiveis = computed(() => props.municipiosDisponiveis);
const municipioSelecionado = ref('');
const form = useForm({ municipios: [], itens: [] });

const podeEditar = computed(() => props.canEdit && dados.value?.pode_editar);
const rotulos = computed(() => Object.fromEntries((dados.value?.itens || []).map((item) => [item.chave, item.rotulo])));

watch(() => props.admissibilidade, (valor) => {
  form.municipios = (valor?.municipios || []).map(({ municipio_id, na_zas, na_zss }) => ({ municipio_id, na_zas, na_zss }));
  form.itens = (valor?.itens || []).map(({ chave, resultado, justificativa }) => ({ chave, resultado: resultado || '', justificativa: justificativa || '' }));
  form.clearErrors();
}, { immediate: true });

function nomeMunicipio(id) {
  const municipio = municipiosDisponiveis.value.find((item) => Number(item.id) === Number(id));
  return municipio ? `${municipio.nome} / ${municipio.uf}` : `Município ${id}`;
}

function adicionarMunicipio() {
  const id = Number(municipioSelecionado.value);
  if (!id || form.municipios.some((item) => Number(item.municipio_id) === id)) return;
  form.municipios.push({ municipio_id: id, na_zas: true, na_zss: false });
  municipioSelecionado.value = '';
}

function rotuloDecisao(tipo) {
  return { admitido: 'Admitido', correcao_solicitada: 'Correção solicitada', reprovado_sumariamente: 'Reprovado sumariamente' }[tipo] || tipo;
}

function salvar() {
  form.transform((dadosForm) => ({
    municipios: dadosForm.municipios,
    itens: dadosForm.itens.filter((item) => item.resultado),
  })).put(route('pae.protocolo.admissibilidade.salvar', props.protocoloId), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => emit('atualizado'),
  });
}
</script>
