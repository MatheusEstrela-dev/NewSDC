<template>
  <section v-if="etapaAtiva" class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800" data-execucao-pedido>
    <h2 class="text-base font-bold text-slate-900 dark:text-white">Próximo passo</h2>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ORIENTACAO[pedido.status] }}</p>

    <!-- Termo: CEDEC registra o processo SEI e anexa o PDF do termo. -->
    <form v-if="pedido.status === 'aprovado' && podeAprovar && !autor" class="mt-4 space-y-3" data-form-termo @submit.prevent="enviar('termo')">
      <p class="text-xs text-slate-600 dark:text-slate-300">Processo SEI {{ pedido.processo_sei }} (informado na aprovação).</p>
      <FormField v-model="termo.documento_sei" label="Nº do documento do termo no SEI" required :error="termo.errors.documento_sei" />
      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
        Termo em PDF
        <input type="file" accept="application/pdf" class="mt-1 block w-full text-sm text-slate-600 dark:text-slate-300" @change="termo.termo = $event.target.files[0] ?? null" />
      </label>
      <p v-if="termo.errors.termo" class="text-xs text-red-600 dark:text-red-400">{{ termo.errors.termo }}</p>
      <Erro :form="termo" />
      <div class="flex justify-end"><Button type="submit" variant="primary" :loading="termo.processing">Registrar termo</Button></div>
    </form>

    <!-- Assinaturas: cada parte registra a sua, com o numero do documento SEI. -->
    <div v-else-if="pedido.status === 'termo_emitido'" class="mt-4 space-y-4">
      <ul class="space-y-1 text-sm">
        <li :class="pedido.assinado_estado_por ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-300'">Estado (CEDEC): {{ pedido.assinado_estado_por ? 'assinado' : 'pendente' }}</li>
        <li :class="pedido.assinado_municipio_por ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-300'">Município: {{ pedido.assinado_municipio_por ? 'assinado' : 'pendente' }}</li>
      </ul>
      <form v-if="parteQueAssina" class="space-y-3" data-form-assinatura @submit.prevent="enviar('assinar')">
        <FormField v-model="assinatura.documento_sei" :label="`Nº do documento SEI da assinatura (${parteQueAssina === 'estado' ? 'Estado' : 'município'})`" required :error="assinatura.errors.documento_sei" />
        <Erro :form="assinatura" />
        <div class="flex justify-end"><Button type="submit" variant="primary" :loading="assinatura.processing">Registrar assinatura {{ parteQueAssina === 'estado' ? 'do Estado' : 'do município' }}</Button></div>
      </form>
    </div>

    <!-- Entrega: unidade responsavel, com evidencias. -->
    <form v-else-if="['termo_assinado', 'contestado'].includes(pedido.status) && podeEntregar && !autor" class="mt-4 space-y-3" data-form-entrega @submit.prevent="enviar('entregar')">
      <FormTextarea v-model="entrega.observacao" label="Como foi a entrega" :rows="3" required :error="entrega.errors.observacao" />
      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
        Evidências (termo de recebimento, fotos) · PDF, JPG ou PNG
        <input type="file" multiple accept="application/pdf,image/jpeg,image/png" class="mt-1 block w-full text-sm text-slate-600 dark:text-slate-300" @change="entrega.evidencias = Array.from($event.target.files)" />
      </label>
      <p v-for="erro in errosDeArquivo(entrega, 'evidencias')" :key="erro" class="text-xs text-red-600 dark:text-red-400">{{ erro }}</p>
      <Erro :form="entrega" />
      <div class="flex justify-end"><Button type="submit" variant="primary" :loading="entrega.processing">Registrar entrega</Button></div>
    </form>

    <!-- Recebimento: o municipio confirma (debito definitivo) ou contesta. -->
    <div v-else-if="pedido.status === 'entregue' && agePeloEnte" class="mt-4 space-y-4">
      <form class="space-y-3" data-form-confirmacao @submit.prevent="enviar('confirmar')">
        <FormTextarea v-model="confirmacao.observacao" label="Confirmação de recebimento" :rows="2" required hint="Ao confirmar, os pontos são debitados em definitivo." :error="confirmacao.errors.observacao" />
        <Erro :form="confirmacao" />
        <div class="flex justify-end"><Button type="submit" variant="success" :loading="confirmacao.processing">Confirmar recebimento</Button></div>
      </form>
      <details class="rounded-lg border border-orange-200 p-3 dark:border-orange-500/30">
        <summary class="cursor-pointer text-sm font-semibold text-orange-700 dark:text-orange-300">O que foi entregue não confere? Contestar</summary>
        <form class="mt-3 space-y-3" data-form-contestacao @submit.prevent="enviar('contestar')">
          <FormTextarea v-model="contestacao.motivo" label="Motivo" :rows="2" required :error="contestacao.errors.motivo" />
          <input type="file" multiple accept="application/pdf,image/jpeg,image/png" class="block w-full text-sm text-slate-600 dark:text-slate-300" @change="contestacao.anexos = Array.from($event.target.files)" />
          <p v-for="erro in errosDeArquivo(contestacao, 'anexos')" :key="erro" class="text-xs text-red-600 dark:text-red-400">{{ erro }}</p>
          <Erro :form="contestacao" />
          <div class="flex justify-end"><Button type="submit" variant="danger" :loading="contestacao.processing">Contestar entrega</Button></div>
        </form>
      </details>
    </div>

    <p v-else class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600 dark:bg-slate-900/60 dark:text-slate-300" data-execucao-aguardando>
      {{ AGUARDANDO[pedido.status] }}
    </p>

    <!-- Saida administrativa antes da conclusao (decisao com documento). -->
    <details v-if="podeAprovar" class="mt-4 rounded-lg border border-red-200 p-3 dark:border-red-500/30">
      <summary class="cursor-pointer text-sm font-semibold text-red-700 dark:text-red-300">Anular pedido (decisão administrativa ou judicial)</summary>
      <form class="mt-3 space-y-3" data-form-anulacao @submit.prevent="enviar('anular')">
        <FormField v-model="anulacao.documento_origem" label="Documento de origem (processo, decisão)" required :error="anulacao.errors.documento_origem" />
        <FormTextarea v-model="anulacao.motivo" label="Motivo" :rows="2" required :error="anulacao.errors.motivo" />
        <Erro :form="anulacao" />
        <div class="flex justify-end"><Button type="submit" variant="danger" :loading="anulacao.processing">Anular e liberar pontos</Button></div>
      </form>
    </details>
  </section>
</template>

<script setup>
/**
 * Execucao do pedido aprovado (Fase 4), na propria pagina, sem modal: mostra
 * so a acao que cabe a quem esta vendo na etapa atual. O backend reavalia
 * permissao e segregacao em cada envio.
 */
import { computed, defineComponent, h } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  pedido: { type: Object, required: true },
  podeAprovar: { type: Boolean, default: false },
  podeEntregar: { type: Boolean, default: false },
  agePeloEnte: { type: Boolean, default: false },
  usuarioId: { type: Number, default: 0 },
});

// Erro de regra de negocio devolvido pelo backend (campo pedido/arquivo).
const Erro = defineComponent({
  props: { form: { type: Object, required: true } },
  setup: (p) => () => (p.form.errors.pedido || p.form.errors.arquivo
    ? h('p', { role: 'alert', class: 'text-sm font-semibold text-red-600 dark:text-red-400' }, p.form.errors.pedido || p.form.errors.arquivo)
    : null),
});

const ORIENTACAO = {
  aprovado: 'A CEDEC registra o termo no SEI e anexa o PDF.',
  termo_emitido: 'Estado e município assinam o termo no SEI e registram aqui o número do documento.',
  termo_assinado: 'A unidade responsável entrega o item e registra as evidências.',
  contestado: 'O município contestou a entrega; a unidade responsável precisa refazê-la.',
  entregue: 'O município confere e confirma o recebimento, ou contesta.',
};
const AGUARDANDO = {
  aprovado: 'Aguardando a CEDEC registrar o termo.',
  termo_emitido: 'Aguardando a assinatura da outra parte.',
  termo_assinado: 'Aguardando a unidade responsável registrar a entrega.',
  contestado: 'Aguardando a unidade responsável refazer a entrega.',
  entregue: 'Aguardando o município confirmar o recebimento.',
};

const etapaAtiva = computed(() => Object.prototype.hasOwnProperty.call(ORIENTACAO, props.pedido.status));
const autor = computed(() => props.pedido.solicitado_por === props.usuarioId);
const parteQueAssina = computed(() => {
  if (props.podeAprovar && !autor.value && !props.pedido.assinado_estado_por) return 'estado';
  if (props.agePeloEnte && !props.pedido.assinado_municipio_por) return 'municipio';
  return null;
});

const termo = useForm({ documento_sei: '', termo: null });
const assinatura = useForm({ parte: 'estado', documento_sei: '' });
const entrega = useForm({ observacao: '', evidencias: [] });
const confirmacao = useForm({ observacao: '' });
const contestacao = useForm({ motivo: '', anexos: [] });
const anulacao = useForm({ documento_origem: '', motivo: '' });

// Erros do campo de arquivos: o geral e os de cada arquivo (campo.0, campo.1...).
function errosDeArquivo(form, campo) {
  return Object.entries(form.errors).filter(([chave]) => chave === campo || chave.startsWith(`${campo}.`)).map(([, mensagem]) => mensagem);
}

const ROTAS = {
  termo: [termo, 'resgate.pedidos.termo'],
  assinar: [assinatura, 'resgate.pedidos.assinar'],
  entregar: [entrega, 'resgate.pedidos.entregar'],
  confirmar: [confirmacao, 'resgate.pedidos.confirmar'],
  contestar: [contestacao, 'resgate.pedidos.contestar'],
  anular: [anulacao, 'resgate.pedidos.anular'],
};

function enviar(acao) {
  const [form, rota] = ROTAS[acao];
  if (acao === 'assinar') form.parte = parteQueAssina.value;
  form.post(route(rota, props.pedido.id), { preserveScroll: true, forceFormData: ['termo', 'entregar', 'contestar'].includes(acao), onSuccess: () => form.reset() });
}
</script>
