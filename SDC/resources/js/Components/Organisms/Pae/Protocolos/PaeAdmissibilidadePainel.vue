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

      <section v-if="dados.pode_decidir" class="modal-serie-cartao rounded-xl p-4">
        <h4 class="font-semibold modal-serie-titulo">Decisão da CEDEC</h4>
        <p class="mt-1 text-xs modal-serie-apoio">A decisão exige os nove itens avaliados e os municípios ZAS/ZSS confirmados.</p>
        <form class="mt-4 space-y-3" @submit.prevent="decidir">
          <label class="block text-sm modal-serie-valor" for="decisao-tipo">Resultado</label>
          <select id="decisao-tipo" v-model="decisao.tipo" class="w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white">
            <option value="admitido">Admitir</option>
            <option value="correcao_solicitada">Solicitar correção transitória</option>
            <option value="reprovado_sumariamente">Reprovar sumariamente</option>
          </select>

          <div v-if="decisao.tipo === 'reprovado_sumariamente'" class="space-y-2 rounded-lg border border-slate-600/40 p-3">
            <p class="text-sm modal-serie-valor">Fundamentos legais</p>
            <label v-for="(rotulo, artigo) in dados.fundamentos_disponiveis" :key="artigo" class="flex items-start gap-2 text-sm modal-serie-valor">
              <input v-model="decisao.fundamentos" type="checkbox" :value="Number(artigo)" />
              <span>Art. {{ artigo }} — {{ rotulo }}</span>
            </label>
            <p v-if="decisao.errors.fundamentos" class="text-xs text-red-300">{{ decisao.errors.fundamentos }}</p>
          </div>

          <div v-if="decisao.tipo === 'correcao_solicitada'" class="space-y-3 rounded-lg border border-slate-600/40 p-3">
            <p class="text-xs modal-serie-apoio">Use esta opção apenas quando a CEDEC confirmar documentalmente que o PAE foi submetido antes da publicação da resolução.</p>
            <label class="flex items-start gap-2 text-sm modal-serie-valor">
              <input v-model="decisao.transitorio_confirmado" type="checkbox" />
              <span>Confirmo a submissão anterior à publicação oficial</span>
            </label>
            <label class="block text-sm modal-serie-valor">Data comprovada da submissão
              <input v-model="decisao.submetido_em" type="date" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white" />
            </label>
            <label class="block text-sm modal-serie-valor">Data da notificação da correção
              <input v-model="decisao.notificado_em" type="date" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white" />
            </label>
            <label class="block text-sm modal-serie-valor">Número SEI da comprovação
              <input v-model="decisao.num_sei" type="text" maxlength="100" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white" />
            </label>
            <p v-for="campo in ['transitorio_confirmado', 'submetido_em', 'notificado_em', 'num_sei']" :key="campo" v-show="decisao.errors[campo]" class="text-xs text-red-300">{{ decisao.errors[campo] }}</p>
          </div>

          <label class="block text-sm modal-serie-valor">Fundamentação da decisão
            <textarea v-model="decisao.fundamentacao" rows="3" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white" />
          </label>
          <p v-if="decisao.errors.fundamentacao" class="text-xs text-red-300">{{ decisao.errors.fundamentacao }}</p>
          <p v-if="decisao.errors.triagem || decisao.errors.protocolo" class="text-xs text-red-300">{{ decisao.errors.triagem || decisao.errors.protocolo }}</p>
          <button type="submit" :disabled="decisao.processing" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
            {{ decisao.processing ? 'Registrando...' : 'Registrar decisão' }}
          </button>
        </form>
      </section>

      <section v-if="dados.decisoes?.length" class="modal-serie-cartao rounded-xl p-4">
        <h4 class="font-semibold modal-serie-titulo">Histórico de decisões</h4>
        <ol class="mt-3 space-y-3">
          <li v-for="registro in dados.decisoes" :key="registro.id" class="rounded-lg border border-slate-600/40 p-3 text-sm modal-serie-valor">
            <strong>{{ rotuloDecisao(registro.tipo) }}</strong>
            <p class="mt-1">{{ registro.fundamentacao }}</p>
            <p v-if="registro.fundamentos?.length" class="mt-1 text-xs modal-serie-apoio">Artigos: {{ registro.fundamentos.join(', ') }}</p>
            <p v-if="registro.prazo_correcao_em" class="mt-1 text-xs modal-serie-apoio">Prazo de correção: {{ registro.prazo_correcao_em }}</p>
            <p class="mt-1 text-xs modal-serie-apoio">{{ registro.decisor?.name || 'CEDEC' }} · {{ registro.decidido_em }}</p>
          </li>
        </ol>
      </section>
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
const decisao = useForm({
  tipo: 'admitido', fundamentacao: '', fundamentos: [], transitorio_confirmado: false,
  submetido_em: '', notificado_em: '', num_sei: '', chave_idempotencia: crypto.randomUUID(),
});

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

function decidir() {
  decisao.transform((dadosForm) => ({
    ...dadosForm,
    fundamentos: dadosForm.tipo === 'reprovado_sumariamente' ? dadosForm.fundamentos : [],
    transitorio_confirmado: dadosForm.tipo === 'correcao_solicitada' && dadosForm.transitorio_confirmado,
    submetido_em: dadosForm.tipo === 'correcao_solicitada' ? dadosForm.submetido_em : null,
    notificado_em: dadosForm.tipo === 'correcao_solicitada' ? dadosForm.notificado_em : null,
    num_sei: dadosForm.tipo === 'correcao_solicitada' ? dadosForm.num_sei : null,
  })).post(route('pae.protocolo.admissibilidade.decidir', props.protocoloId), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      decisao.reset();
      decisao.chave_idempotencia = crypto.randomUUID();
      emit('atualizado');
    },
  });
}
</script>
