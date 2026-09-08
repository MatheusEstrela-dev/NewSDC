<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <form @submit.prevent="salvar">
      <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
          <PencilIcon class="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Editar camada</h2>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            {{ camada?.nome }}
          </p>
        </div>

        <button
          type="button"
          class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
          aria-label="Fechar"
          @click="fechar"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </header>

      <div class="px-5 py-4">
        <!--
          O aviso vem ANTES dos campos, e nao como nota de pe.

          A pessoa abre "Editar" esperando poder corrigir a area desenhada, que
          e o erro mais provavel num KML. Descobrir isso depois de preencher
          cinco campos e pior do que ler uma linha antes.
        -->
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:border-slate-700/50 dark:bg-slate-800/40 dark:text-slate-300">
          A <strong>geometria nao se edita aqui</strong>: ela vem do arquivo e e a
          identidade da camada. Para mudar a area desenhada, envie um KML novo.
          Aqui mudam so os dados de identificacao.
        </div>

        <div v-if="erroGeral" class="mb-4 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300">
          {{ erroGeral }}
        </div>

        <div class="space-y-3">
          <FormField
            v-model="form.nome"
            label="Nome da camada"
            required
            :error="form.errors.nome"
            hint="Como a camada aparece na lista e na legenda do mapa"
          />

          <div class="grid gap-3 sm:grid-cols-2">
            <FormSelect
              v-model="form.dominio"
              label="Dominio"
              :options="opcoesDominio"
              required
              :error="form.errors.dominio"
              @update:model-value="trocarDominio"
            />

            <FormSelect
              v-model="form.nivel"
              label="Nivel"
              :options="opcoesNivel"
              required
              :error="form.errors.nivel"
              hint="O nivel pertence ao vocabulario do dominio"
            />
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <FormDateField
              v-model="form.emitido_em"
              label="Emitido em"
              required
              :error="form.errors.emitido_em"
              hint="Data do mapeamento, nao a de hoje"
            />

            <FormDateField
              v-model="form.valido_ate"
              label="Valido ate"
              :error="form.errors.valido_ate"
              hint="Vencida, a camada sai do mapa automaticamente"
            />
          </div>
        </div>
      </div>

      <footer class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800/40">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
          @click="fechar"
        >
          Cancelar
        </button>
        <button
          type="submit"
          class="rounded-lg bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-60"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Salvando...' : 'Salvar' }}
        </button>
      </footer>
    </form>
  </Modal>
</template>

<script setup>
/**
 * Edicao dos metadados de uma camada.
 *
 * Componente proprio porque as TRES telas do modulo editam camada: a lista de
 * /geoespacial, o detalhe de /geoespacial/{id} e "Meus envios" em
 * /geoespacial/enviar. Repetir o formulario nas tres faria a validacao de nivel
 * por dominio divergir na primeira mudanca -- e foi exatamente isso que
 * aconteceu com as regras do backend antes de virarem RegrasDeMetadado.
 */
import { PencilIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  camada: { type: Object, default: null },
  dominios: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close']);

const form = useForm({
  nome: '',
  dominio: '',
  nivel: '',
  emitido_em: '',
  valido_ate: '',
});

const opcoesDominio = computed(() => Object.entries(props.dominios)
  .map(([valor, config]) => ({ value: valor, label: config.rotulo ?? valor })));

const opcoesNivel = computed(() => (props.dominios[form.dominio]?.niveis ?? [])
  .map((nivel) => ({ value: nivel, label: rotularNivel(nivel) })));

/*
 * O erro de conflito chega na chave 'camada', e nao num campo: e o caso de
 * outra pessoa ter arquivado a camada com esta tela aberta. Sem exibi-lo, o
 * modal fecharia sem salvar e sem dizer nada.
 */
const erroGeral = computed(() => form.errors.camada ?? null);

// Carrega o formulario quando o modal abre. Fora do watch, uma segunda edicao
// abriria com os valores da primeira.
watch(() => [props.show, props.camada], () => {
  if (! props.show || props.camada === null) {
    return;
  }

  form.clearErrors();
  form.nome = props.camada.nome ?? '';
  form.dominio = props.camada.dominio ?? '';
  form.nivel = props.camada.nivel ?? '';
  form.emitido_em = comoData(props.camada.emitido_em);
  form.valido_ate = comoData(props.camada.valido_ate);
}, { immediate: true });

/*
 * O input date exige exatamente YYYY-MM-DD.
 *
 * O Postgres devolve a coluna `date` como '2026-02-28', mas basta um driver ou
 * um cast mudar para '2026-02-28T00:00:00' e o campo aparece VAZIO, sem erro
 * nenhum -- e ai salvar em cima apagaria a data que estava certa.
 */
function comoData(valor) {
  if (! valor) {
    return '';
  }

  return String(valor).slice(0, 10);
}

function rotularNivel(nivel) {
  return String(nivel ?? '').replace(/_/g, ' ');
}

// Nivel e vocabulario do dominio: trocar de dominio sem trocar o nivel deixaria
// no formulario um valor que o select nem mostra mais -- e o servidor recusa,
// porque RegrasDeMetadado valida o nivel DENTRO do dominio.
function trocarDominio(valor) {
  form.dominio = valor;
  form.nivel = props.dominios[valor]?.niveis?.[0] ?? '';
}

function fechar() {
  form.clearErrors();
  emit('close');
}

function salvar() {
  form.put(route('geoespacial.update', props.camada.id), {
    preserveScroll: true,
    onSuccess: () => emit('close'),
  });
}
</script>
