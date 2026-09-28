<template>
  <div class="space-y-4">
    <div class="space-y-2">
      <div v-for="(campo, i) in form.campos_dinamicos" :key="i" class="flex flex-wrap items-start gap-2">
        <div class="min-w-0 flex-1">
          <input v-model="campo.label" type="text" maxlength="100" placeholder="Ex.: Login AD"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
          <p v-if="form.errors[`campos_dinamicos.${i}.label`]" class="mt-1 text-xs text-red-500">{{ form.errors[`campos_dinamicos.${i}.label`] }}</p>
        </div>
        <select v-model="campo.tipo" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
          <option value="text">Texto</option>
          <option value="checkbox">Checkbox</option>
        </select>
        <Button type="button" variant="danger" size="sm" @click="form.campos_dinamicos.splice(i, 1)">Remover</Button>
      </div>
      <Button type="button" variant="secondary" size="sm" @click="form.campos_dinamicos.push({ label: '', tipo: 'text' })">Adicionar campo</Button>
    </div>

    <fieldset class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
      <legend class="px-1 text-sm font-semibold text-slate-700 dark:text-slate-200">Automação AD</legend>
      <div class="flex flex-wrap gap-2">
        <select v-model="acao" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
          <option value="">Sem automação</option>
          <option value="desbloquear">Desbloquear conta</option>
          <option value="ativar">Ativar conta</option>
          <option value="resetar">Resetar senha</option>
        </select>
        <select v-if="acao" v-model="campoLogin" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
          <option value="">Campo com o login</option>
          <option v-for="c in camposTexto" :key="c.label" :value="c.label">{{ c.label }}</option>
        </select>
      </div>
      <p v-if="form.errors['form_automacao.campo_login']" class="mt-1 text-xs text-red-500">{{ form.errors['form_automacao.campo_login'] }}</p>
    </fieldset>

    <!-- Demais erros de validacao (ex.: limite de 20 campos, tipo invalido, acao invalida) --
         os que ja tem exibicao dedicada (label por linha e campo_login) ficam de fora daqui
         para nao duplicar a mensagem. -->
    <p v-for="[campo, erro] in outrosErros" :key="campo" class="text-xs text-red-500">{{ erro }}</p>

    <Button type="button" variant="primary" size="sm" :disabled="form.processing" @click="salvar">Salvar assunto</Button>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({ assunto: { type: Object, required: true } });

const form = useForm({
  campos_dinamicos: (props.assunto.campos_dinamicos ?? []).map((c) => ({ ...c })),
  form_automacao: props.assunto.form_automacao ?? null,
});
const acao = ref(props.assunto.form_automacao?.acao ?? '');
const campoLogin = ref(props.assunto.form_automacao?.campo_login ?? '');
const camposTexto = computed(() => form.campos_dinamicos.filter((c) => c.tipo === 'text' && c.label.trim()));

// Erro com chave ja mostrada perto do campo (label por linha, campo_login no fieldset)
// nao entra aqui, senao a mensagem aparece duplicada na tela.
const ERRO_LABEL_POR_LINHA = /^campos_dinamicos\.\d+\.label$/;
const outrosErros = computed(() =>
  Object.entries(form.errors).filter(([chave]) => chave !== 'form_automacao.campo_login' && !ERRO_LABEL_POR_LINHA.test(chave)),
);

// Se o campo referenciado pela automacao for removido, renomeado ou virar checkbox,
// a selecao antiga fica invalida -- limpa para nunca submeter um login inexistente.
watch(camposTexto, (lista) => {
  if (campoLogin.value && !lista.some((c) => c.label === campoLogin.value)) {
    campoLogin.value = '';
  }
});

function salvar() {
  form.form_automacao = acao.value ? { acao: acao.value, campo_login: campoLogin.value } : null;
  form.campos_dinamicos = form.campos_dinamicos.filter((c) => c.label.trim());
  form.put(route('admin.demandas.assuntos.update', props.assunto.id), { preserveScroll: true });
}
</script>
