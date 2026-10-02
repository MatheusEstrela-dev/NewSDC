<template>
  <SectionCard>
    <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-slate-100">Dados do cadastro</h2>

    <form v-if="podeEditar" class="space-y-6" @submit.prevent="salvar">
      <CadastroAcessoFields :form="form" />
      <div class="flex justify-end border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <Button type="submit" variant="primary" :disabled="form.processing" :loading="form.processing">Salvar alterações</Button>
      </div>
    </form>

    <!-- Leitura: o backend ja mascara documento e CPF para quem nao edita. -->
    <dl v-else class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
      <div v-for="campo in CAMPOS_ACESSO" :key="campo.key" class="min-w-0" :class="{ 'sm:col-span-2': campo.textarea }">
        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ campo.rotulo }}</dt>
        <dd class="break-words text-slate-900 dark:text-slate-100">{{ cadastro[campo.key] || '—' }}</dd>
      </div>
    </dl>
  </SectionCard>
</template>

<script setup>
import SectionCard from '@/Components/Atoms/Card/SectionCard.vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import CadastroAcessoFields from '@/Components/Organisms/Acessos/CadastroAcessoFields.vue';
import { CAMPOS_ACESSO, dadosFormularioAcesso } from '@/Support/acessos';

const props = defineProps({
  cadastro: { type: Object, required: true },
  podeEditar: { type: Boolean, default: false },
});

const form = useForm(dadosFormularioAcesso(props.cadastro));

function salvar() {
  form.put(`/acessos/${props.cadastro.id}`);
}
</script>
