<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

defineOptions({ layout: GuestLayout });

const props = defineProps({
  codigo: { type: String, default: null },
  consultado: { type: Boolean, default: false },
  resultado: { type: Object, default: null },
});

const form = useForm({ codigo: props.codigo ?? '' });

const SITUACOES = {
  VALIDO: { label: 'Certificado válido', color: 'green' },
  REEMITIDO: { label: 'Certificado válido (reemitido)', color: 'blue' },
  CANCELADO: { label: 'Certificado cancelado', color: 'red' },
};

const situacao = computed(() => SITUACOES[props.resultado?.situacao] ?? null);

const linhas = computed(() => {
  const r = props.resultado;
  if (!r) return [];
  return [
    ['Nome', r.nome],
    ['CPF', r.cpf_mascarado],
    ['Curso', r.curso],
    ['Carga horária', r.carga_horaria != null ? `${r.carga_horaria} horas` : null],
    ['Modalidade', r.modalidade],
    ['Data de conclusão', r.concluido_em ? new Date(`${r.concluido_em}T00:00:00`).toLocaleDateString('pt-BR') : null],
    ['Aproveitamento', r.aproveitamento != null ? `${Number(r.aproveitamento).toLocaleString('pt-BR')}%` : null],
  ].filter(([, valor]) => valor);
});

function buscar() {
  form.post(route('certificados.validar.buscar'));
}
</script>

<template>
  <Head title="Validar certificado" />

  <div>
    <Heading :level="1" class="text-xl font-bold text-slate-800 dark:text-slate-100 mb-1">Validar certificado</Heading>
    <Text size="sm" color="muted" class="mb-4">Informe o código impresso no certificado ou leia o QR Code.</Text>

    <form class="flex gap-2" @submit.prevent="buscar">
      <input
        v-model="form.codigo"
        type="text"
        maxlength="40"
        autocomplete="off"
        placeholder="CEDEC-XXXX-XXXX"
        aria-label="Código de verificação"
        class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm uppercase dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
      />
      <button
        type="submit"
        :disabled="form.processing || !form.codigo"
        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
      >
        Validar
      </button>
    </form>
    <Text v-if="form.errors.codigo" size="xs" class="mt-1 text-red-600">{{ form.errors.codigo }}</Text>

    <div v-if="resultado && situacao" class="mt-6" data-testid="resultado-validacao">
      <Badge :cor="situacao.color">{{ situacao.label }}</Badge>
      <dl class="mt-4 space-y-2">
        <div v-for="[rotulo, valor] in linhas" :key="rotulo">
          <dt class="text-xs text-slate-500 dark:text-slate-400">{{ rotulo }}</dt>
          <dd class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ valor }}</dd>
        </div>
      </dl>
    </div>

    <Text v-else-if="consultado" size="sm" color="muted" class="mt-6" data-testid="nao-encontrado">
      Certificado não encontrado. Confira o código e tente novamente.
    </Text>
  </div>
</template>
