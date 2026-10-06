<template>
  <section class="space-y-4">
    <p v-if="dados?.enderecamento_pendente" class="rounded-lg border border-amber-400/50 p-3 text-sm text-amber-200">
      Destinatários COMPDEC da ZAS não confirmados. Confira a lista de municípios antes de considerar a comunicação concluída.
    </p>
    <p v-if="!comunicacoes.length" class="text-sm modal-serie-apoio">Nenhuma comunicação oficial registrada para este protocolo.</p>

    <article v-for="comunicacao in comunicacoes" :key="comunicacao.id" class="modal-serie-cartao rounded-xl p-4 text-sm">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
          <h4 class="font-semibold modal-serie-titulo">{{ destinatario(comunicacao) }}</h4>
          <p class="mt-1 modal-serie-apoio">{{ origem(comunicacao.origem_tipo) }}</p>
          <p v-if="comunicacao.origem_referencia" class="mt-1 modal-serie-apoio">{{ comunicacao.origem_referencia }}</p>
        </div>
        <span :class="comunicacao.status === 'registrada' ? 'text-emerald-300' : 'text-amber-300'" class="font-semibold">
          {{ comunicacao.status === 'registrada' ? 'Envio registrado' : 'Envio pendente' }}
        </span>
      </div>
      <p v-if="comunicacao.motivos" class="mt-3 modal-serie-valor">Motivos: {{ comunicacao.motivos }}</p>

      <div v-if="comunicacao.status === 'registrada'" class="mt-3 space-y-1 modal-serie-valor">
        <p>Enviado em {{ formatarData(comunicacao.dt_envio) }} · SEI {{ comunicacao.num_sei }}</p>
        <p v-if="comunicacao.registrado_por" class="text-xs modal-serie-apoio">Registrado por {{ comunicacao.registrado_por }}</p>
        <a v-if="comunicacao.comprovante_nome_original" :href="route('pae.comunicacoes.comprovante', comunicacao.id)"
          class="inline-block text-blue-300 underline hover:text-blue-200">
          Baixar comprovante: {{ comunicacao.comprovante_nome_original }}
        </a>
      </div>

      <button v-if="comunicacao.status === 'pendente' && canEdit && ativa !== comunicacao.id" type="button"
        class="mt-3 text-blue-300 underline hover:text-blue-200" @click="abrirFormulario(comunicacao.id)">
        Registrar envio oficial
      </button>
      <form v-if="comunicacao.status === 'pendente' && canEdit && ativa === comunicacao.id" class="mt-4 space-y-3" @submit.prevent="registrar(comunicacao.id)">
        <label class="block modal-serie-valor">Data do envio oficial
          <input v-model="form.dt_envio" type="date" :max="hoje" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-white" />
        </label>
        <label class="block modal-serie-valor">Número SEI
          <input v-model="form.num_sei" type="text" maxlength="100" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-white" />
        </label>
        <label class="block modal-serie-valor">Comprovante do envio (PDF ou imagem)
          <input type="file" accept=".pdf,.png,.jpg,.jpeg" class="mt-1 block w-full text-sm" @change="selecionarArquivo" />
        </label>
        <p v-for="campo in ['dt_envio', 'num_sei', 'comprovante']" :key="campo" v-show="form.errors[campo]" class="text-xs text-red-300">
          {{ form.errors[campo] }}
        </p>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white disabled:opacity-50">
          {{ form.processing ? 'Registrando...' : 'Registrar envio oficial' }}
        </button>
      </form>
    </article>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  comunicacoes: { type: Object, default: null },
  canEdit: { type: Boolean, default: false },
});
const emit = defineEmits(['atualizado']);
const dados = computed(() => props.comunicacoes);
const comunicacoes = computed(() => dados.value?.comunicacoes || []);
const agora = new Date();
const hoje = `${agora.getFullYear()}-${String(agora.getMonth() + 1).padStart(2, '0')}-${String(agora.getDate()).padStart(2, '0')}`;
const form = useForm({ dt_envio: hoje, num_sei: '', comprovante: null });
const ativa = ref(null);

function destinatario(comunicacao) {
  return comunicacao.destinatario_tipo === 'feam'
    ? 'FEAM'
    : `COMPDEC de ${comunicacao.municipio || `município ${comunicacao.municipio_id}`}${comunicacao.uf ? ` / ${comunicacao.uf}` : ''}`;
}

function origem(tipo) {
  return {
    ccpae: 'Emissão do CCPAE',
    reprovacao_sumaria: 'Reprovação sumária',
    reprovacao_analise: 'Reprovação após análise',
    notificacao: 'Cópia da notificação',
  }[tipo] || tipo;
}

function formatarData(data) {
  if (!data) return '';
  const [ano, mes, dia] = data.slice(0, 10).split('-');
  return `${dia}/${mes}/${ano}`;
}

function selecionarArquivo(evento) {
  form.comprovante = evento.target.files?.[0] || null;
}

function abrirFormulario(id) {
  ativa.value = id;
  form.clearErrors();
}

function registrar(id) {
  form.post(route('pae.comunicacoes.registrar', id), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      form.dt_envio = hoje;
      ativa.value = null;
      emit('atualizado');
    },
  });
}
</script>
