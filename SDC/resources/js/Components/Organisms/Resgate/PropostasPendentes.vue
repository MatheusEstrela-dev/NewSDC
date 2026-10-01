<template>
  <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 dark:border-amber-500/30 dark:bg-amber-950/20" aria-labelledby="propostas-titulo" data-propostas-pendentes>
    <h2 id="propostas-titulo" class="text-base font-bold text-slate-900 dark:text-white">Propostas aguardando decisão · {{ propostas.length }}</h2>
    <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">Quatro olhos: ninguém decide a própria proposta.</p>

    <ul class="mt-4 space-y-3">
      <li v-for="proposta in propostas" :key="proposta.id" class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800" data-proposta>
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              #{{ proposta.id }} · {{ ROTULO_ACAO[proposta.acao] ?? proposta.acao }} · {{ proposta.codigo }}
              <span v-if="proposta.dados?.titulo" class="font-normal text-slate-600 dark:text-slate-300">— {{ proposta.dados.titulo }}</span>
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              Proposto por {{ nomes[proposta.proposto_por] ?? `#${proposta.proposto_por}` }} em {{ dataHora(proposta.proposto_em) }}
            </p>
            <p class="mt-2 text-xs text-slate-700 dark:text-slate-200">{{ proposta.justificativa }}</p>
            <p v-if="proposta.dados?.faixa_minima" class="mt-2 text-xs text-slate-600 dark:text-slate-300">
              {{ rotuloFaixa(proposta.dados.faixa_minima) }} · {{ proposta.dados.custo_pontos > 0 ? `${numero(proposta.dados.custo_pontos)} pontos` : 'só pela faixa' }} · {{ proposta.dados.base_normativa }}
            </p>
          </div>

          <div v-if="podeAprovar && decisao?.proposta.id !== proposta.id" class="flex shrink-0 gap-2">
            <p v-if="proposta.proposto_por === usuarioId" class="text-xs font-semibold text-slate-500 dark:text-slate-400">Sua proposta: outra pessoa decide</p>
            <template v-else>
              <Button variant="outline" size="sm" data-proposta-recusar @click="abrir(proposta, false)">Recusar</Button>
              <Button variant="primary" size="sm" data-proposta-aprovar @click="abrir(proposta, true)">Aprovar</Button>
            </template>
          </div>
        </div>

        <!-- Decisao no proprio card, sem modal. -->
        <form v-if="decisao?.proposta.id === proposta.id" class="mt-4 space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700" data-proposta-decisao @submit.prevent="decidir">
          <p class="text-xs font-semibold" :class="decisao.aprovar ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'">
            {{ decisao.aprovar ? 'Ao aprovar, a versão é publicada no catálogo agora e a anterior deixa de valer.' : 'A proposta fica registrada como recusada, com a sua justificativa.' }}
          </p>
          <FormTextarea v-model="form.justificativa" label="Justificativa da decisão" :rows="3" required :error="form.errors.justificativa" />
          <p v-if="form.errors.catalogo" role="alert" class="text-sm font-semibold text-red-600 dark:text-red-400">{{ form.errors.catalogo }}</p>
          <div class="flex justify-end gap-2">
            <Button variant="outline" size="sm" :disabled="form.processing" @click="decisao = null">Cancelar</Button>
            <Button type="submit" size="sm" :variant="decisao.aprovar ? 'primary' : 'danger'" :loading="form.processing">{{ decisao.aprovar ? 'Aprovar e publicar' : 'Recusar' }}</Button>
          </div>
        </form>
      </li>
    </ul>
    <p v-if="!propostas.length" class="mt-4 text-sm text-slate-500 dark:text-slate-400">Nenhuma proposta aguardando decisão.</p>
  </section>
</template>

<script setup>
/**
 * Fila de propostas do catalogo (pagina propria Resgate/Propostas; decisao no
 * proprio card, sem modal). Quem pode aprovar decide as propostas de
 * OUTRAS pessoas; a propria aparece sem botoes. O backend recusa de novo se
 * alguem tentar decidir a propria (servico e CHECK no banco).
 */
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import { rotuloFaixa } from '@/Support/resgateCatalogo';

defineProps({
  propostas: { type: Array, default: () => [] },
  nomes: { type: Object, default: () => ({}) },
  podeAprovar: { type: Boolean, default: false },
  usuarioId: { type: Number, default: 0 },
});

const ROTULO_ACAO = { criar: 'Novo item', nova_versao: 'Nova versão', encerrar: 'Encerrar' };
const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
const dataHora = (valor) => (valor ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(valor)) : '');

const decisao = ref(null);
const form = useForm({ aprovar: true, justificativa: '' });

function abrir(proposta, aprovar) {
  form.reset();
  form.clearErrors();
  decisao.value = { proposta, aprovar };
}

function decidir() {
  form.aprovar = decisao.value.aprovar;
  form.post(route('resgate.catalogo.decidir', decisao.value.proposta.id), {
    preserveScroll: true,
    onSuccess: () => { decisao.value = null; },
  });
}
</script>
