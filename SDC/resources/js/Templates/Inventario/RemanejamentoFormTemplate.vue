<template>
  <div ref="raiz" class="min-w-0 space-y-6 pb-8">
    <PageHeader
      :title="editando ? 'Editar remanejamento' : 'Novo remanejamento'"
      description="Pessoas que mudam de estação levando seus equipamentos"
      :icon-image="moduleIcon('inventario')"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <Button variant="outline" size="md" :icon="ArrowLeftIcon" icon-position="left" @click="$emit('voltar')">Voltar</Button>
      </template>
    </PageHeader>

    <div
      v-if="erroGeral"
      role="alert"
      class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
    >
      <p class="break-words">{{ erroGeral }}</p>
    </div>

    <form class="space-y-6" novalidate @submit.prevent="enviarFormulario">
      <PessoaRemanejamentoBloco
        v-for="(bloco, indice) in form.pessoas"
        :key="bloco.chave"
        :bloco="bloco"
        :indice="indice"
        :opcoes="opcoes"
        :erros="errosDoBloco(indice)"
        :pode-remover="form.pessoas.length > 1"
        :equipamentos-do-usuario="equipamentosDoUsuario(bloco.usuario_id)"
        :equipamentos-por-id="equipamentosPorId"
        :liberados="liberados(bloco)"
        :em-outros-blocos="idsEmOutrosBlocos(bloco)"
        @selecionar-pessoa="selecionarPessoa"
        @atualizar="atualizarCampo"
        @alternar-equipamento="alternarEquipamento"
        @remover="removerPessoa"
      />

      <div class="flex flex-wrap gap-2">
        <Button type="button" variant="secondary" size="md" :icon="PlusIcon" icon-position="left" @click="adicionarPessoa">
          Adicionar pessoa
        </Button>
      </div>

      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-700/50 dark:bg-slate-900/60">
        <FormTextarea v-model="form.observacao" label="Observação" :rows="3" :error="form.errors.observacao ?? ''" />
      </section>

      <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <Button type="button" variant="outline" :disabled="form.processing" @click="$emit('voltar')">Cancelar</Button>
        <Button type="submit" variant="primary" :loading="form.processing">
          {{ editando ? 'Salvar alterações' : 'Registrar remanejamento' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { moduleIcon } from '@/Support/moduleIcons';
import { computed, nextTick, ref } from 'vue';
import { ArrowLeftIcon, PlusIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PessoaRemanejamentoBloco from '@/Components/Organisms/Inventario/Remanejamentos/PessoaRemanejamentoBloco.vue';
import { useRemanejamentoForm } from '@/Composables/inventario/useRemanejamentoForm';
import { focarPrimeiroErro } from '@/utils/focarPrimeiroErro';

const props = defineProps({
  remanejamento: { type: Object, default: null },
  opcoes: { type: Object, required: true },
});
defineEmits(['voltar']);

const {
  form, editando, equipamentosPorId, equipamentosDoUsuario, selecionarPessoa, atualizarCampo,
  adicionarPessoa, removerPessoa, alternarEquipamento, liberados, errosDoBloco, idsEmOutrosBlocos, enviar,
} = useRemanejamentoForm(props.remanejamento, props.opcoes);

// Erros que nao pertencem a um bloco: lote vazio, lote ja desfeito, item
// movimentado depois (editar) e configuracao.
const erroGeral = computed(() => form.errors.pessoas || form.errors.remanejamento || form.errors.configuracao || '');

// Envio recusado: o preserveScroll deixa a tela no botao; leva ao primeiro erro.
const raiz = ref(null);
function enviarFormulario() {
  enviar({ aoFalhar: () => nextTick(() => focarPrimeiroErro(raiz.value)) });
}
</script>
