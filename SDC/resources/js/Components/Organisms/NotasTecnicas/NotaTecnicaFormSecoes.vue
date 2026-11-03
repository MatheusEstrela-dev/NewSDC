<template>
  <div class="space-y-6">
    <CollapsibleSection
      namespace="notas-tecnicas-form"
      section-id="identificacao"
      title="Identificação"
      subtitle="Título, pasta e resumo do procedimento"
      :icon="DocumentTextIcon"
      tom="info"
      sanfona-no-mobile
    >
      <div class="grid grid-cols-1 gap-4">
        <FormField
          v-model="form.titulo"
          label="Título"
          placeholder="Ex.: Fluxo de abertura de RAT"
          required
          :maxlength="255"
          :error="errors.titulo"
        />
        <FormSelect
          v-model="form.categoria_id"
          label="Pasta"
          :options="categorias"
          placeholder="Selecione a pasta"
          required
          :error="errors.categoria_id"
        />
        <FormTextarea
          v-model="form.resumo"
          label="Resumo"
          placeholder="Em poucas linhas: para que serve e a quem se aplica."
          :rows="4"
          :maxlength="5000"
          :error="errors.resumo"
        />
      </div>
    </CollapsibleSection>

    <CollapsibleSection
      namespace="notas-tecnicas-form"
      section-id="vigencia"
      title="Vigência e telas relacionadas"
      subtitle="A partir de quando vale e onde o fluxo acontece no sistema"
      :icon="CalendarDaysIcon"
      tom="success"
      sanfona-no-mobile
    >
      <div class="grid grid-cols-1 gap-4">
        <FormDateField
          v-model="form.vigencia_inicio"
          label="Início da vigência"
          hint="Em branco, vale a data em que a nota for publicada."
          :error="errors.vigencia_inicio"
        />

        <fieldset v-if="telas.length">
          <legend class="text-sm font-medium text-slate-700 dark:text-slate-300">Telas relacionadas</legend>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Aparecem como atalho no painel da nota.</p>
          <div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
            <label
              v-for="tela in telas"
              :key="tela.value"
              class="flex min-h-[2.5rem] cursor-pointer items-center gap-2 rounded-lg px-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/50"
            >
              <input
                v-model="form.rotas_relacionadas"
                type="checkbox"
                :value="tela.value"
                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800"
              />
              {{ tela.label }}
            </label>
          </div>
        </fieldset>
      </div>
    </CollapsibleSection>

    <CollapsibleSection
      v-if="comArquivo"
      namespace="notas-tecnicas-form"
      section-id="arquivo"
      title="Documento"
      :subtitle="`PDF de até ${limiteMb} MB`"
      :icon="PaperClipIcon"
      tom="warning"
      sanfona-no-mobile
    >
      <NotaTecnicaCamposVersao :form="form" :errors="errors" :limite-mb="limiteMb" />
    </CollapsibleSection>
  </div>
</template>

<script setup>
/**
 * Secoes do formulario de nota tecnica, compartilhadas por Create e Edit. O PDF
 * so aparece no cadastro (comArquivo); na edicao, PDF novo e "nova versao".
 */
import { CalendarDaysIcon, DocumentTextIcon, PaperClipIcon } from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import NotaTecnicaCamposVersao from '@/Components/Molecules/NotasTecnicas/NotaTecnicaCamposVersao.vue';

defineProps({
  /** useForm do Inertia: titulo, categoria_id, resumo, vigencia_inicio, rotas_relacionadas (+ arquivo, numero, changelog). */
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  categorias: { type: Array, default: () => [] },
  telas: { type: Array, default: () => [] },
  comArquivo: { type: Boolean, default: false },
  limiteMb: { type: Number, default: 30 },
});
</script>
