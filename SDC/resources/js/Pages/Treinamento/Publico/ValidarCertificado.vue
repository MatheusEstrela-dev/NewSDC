<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PublicoInstitucionalLayout from '@/Layouts/PublicoInstitucionalLayout.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: PublicoInstitucionalLayout });

const props = defineProps({
  codigo: { type: String, default: null },
  consultado: { type: Boolean, default: false },
  resultado: { type: Object, default: null },
});

const form = useForm({ codigo: props.codigo ?? '' });
const iconeModulo = moduleIcon('treinamento');

// Classes literais: o Tailwind so enxerga strings completas.
const SITUACOES = {
  VALIDO: {
    titulo: 'Certificado válido',
    detalhe: 'Este certificado foi emitido pela CEDEC-MG e é autêntico.',
    ok: true,
    moldura: 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10',
    selo: 'bg-emerald-600 text-white',
  },
  REEMITIDO: {
    titulo: 'Certificado válido',
    detalhe: 'Este certificado foi emitido pela CEDEC-MG e é autêntico. Trata-se de uma reemissão.',
    ok: true,
    moldura: 'border-blue-300 bg-blue-50 dark:border-blue-500/30 dark:bg-blue-500/10',
    selo: 'bg-blue-600 text-white',
  },
  CANCELADO: {
    titulo: 'Certificado cancelado',
    detalhe: 'O código existe, mas este certificado foi cancelado e não tem mais validade.',
    ok: false,
    moldura: 'border-red-300 bg-red-50 dark:border-red-500/30 dark:bg-red-500/10',
    selo: 'bg-red-600 text-white',
  },
};

const situacao = computed(() => SITUACOES[props.resultado?.situacao] ?? null);

const formatarData = (iso) => (iso ? new Date(`${iso}T00:00:00`).toLocaleDateString('pt-BR') : null);

const campos = computed(() => {
  const r = props.resultado;
  if (!r) return [];
  const instrutor = r.papel === 'INSTRUTOR';
  return [
    { rotulo: 'Tipo de certificado', valor: instrutor ? 'Instrutor' : 'Conclusão' },
    { rotulo: 'Nome', valor: r.nome, largo: true },
    { rotulo: 'CPF', valor: r.cpf_mascarado },
    { rotulo: 'Capacitação', valor: r.versao_rotulo ? `${r.curso} - ${r.versao_rotulo}` : r.curso, largo: true },
    { rotulo: 'Carga horária', valor: r.carga_horaria != null ? `${r.carga_horaria} horas` : null },
    { rotulo: 'Modalidade', valor: r.modalidade },
    { rotulo: instrutor ? 'Término do curso' : 'Data de conclusão', valor: formatarData(r.concluido_em) },
    { rotulo: 'Aproveitamento', valor: r.aproveitamento != null ? `${Number(r.aproveitamento).toLocaleString('pt-BR')}%` : null },
    { rotulo: 'Código de verificação', valor: props.codigo, mono: true },
  ].filter((campo) => campo.valor);
});

function buscar() {
  form.post(route('certificados.validar.buscar'));
}
</script>

<template>
  <Head title="Validação de certificado" />

  <div class="space-y-6">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
      <div class="flex items-start gap-3">
        <img v-if="iconeModulo" :src="iconeModulo" alt="" class="h-10 w-10 shrink-0" />
        <div class="min-w-0">
          <Heading :level="1" class="!text-2xl text-[#093871] dark:!text-blue-200 sm:!text-3xl">Validação de certificado</Heading>
          <Text size="sm" color="muted" class="mt-1">
            Confira a autenticidade de um certificado emitido pela Coordenadoria Estadual de Defesa Civil de Minas Gerais (CEDEC-MG).
          </Text>
        </div>
      </div>

      <form class="mt-5 flex flex-col gap-2 sm:flex-row" @submit.prevent="buscar">
        <TextInput
          v-model="form.codigo"
          maxlength="40"
          placeholder="CEDEC-XXXX-XXXX"
          :error="!!form.errors.codigo"
          class="flex-1 uppercase"
          aria-label="Código de verificação"
          autocomplete="off"
        />
        <Button type="submit" :loading="form.processing" :disabled="!form.codigo" class="sm:shrink-0">Validar</Button>
      </form>
      <Text v-if="form.errors.codigo" size="xs" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.codigo }}</Text>
      <Text size="xs" color="muted" class="mt-2">
        O código (CEDEC-XXXX-XXXX) fica impresso no certificado, junto ao QR Code. Ao ler o QR Code, o código é preenchido automaticamente.
      </Text>
    </section>

    <div aria-live="polite" role="status">
      <section
        v-if="resultado && situacao"
        :class="['rounded-xl border p-5 shadow-sm sm:p-6', situacao.moldura]"
        data-testid="resultado-validacao"
      >
        <div class="flex items-center gap-3">
          <span :class="['flex h-12 w-12 shrink-0 items-center justify-center rounded-full', situacao.selo]">
            <svg v-if="situacao.ok" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          </span>
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <Heading :level="2" class="!text-xl">{{ situacao.titulo }}</Heading>
              <Badge v-if="resultado.situacao === 'REEMITIDO'" cor="blue">Reemitido</Badge>
            </div>
            <Text size="sm" color="muted">{{ situacao.detalhe }}</Text>
          </div>
        </div>

        <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 rounded-lg bg-white/80 p-4 dark:bg-slate-900/60 sm:grid-cols-2">
          <div v-for="campo in campos" :key="campo.rotulo" :class="['min-w-0', campo.largo ? 'sm:col-span-2' : '']">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ campo.rotulo }}</dt>
            <dd :class="['mt-0.5 break-words text-sm font-semibold text-slate-800 dark:text-slate-100', campo.mono ? 'font-mono' : '']">
              {{ campo.valor }}
            </dd>
          </div>
        </dl>
      </section>

      <section
        v-else-if="consultado"
        class="rounded-xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10 sm:p-6"
        data-testid="nao-encontrado"
      >
        <Heading :level="2" class="!text-lg">Certificado não encontrado</Heading>
        <Text size="sm" color="muted" class="mt-1">
          Não localizamos nenhum certificado com este código. Isso pode ser apenas um erro de digitação.
        </Text>
        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700 dark:text-slate-300">
          <li>Confira o código impresso no certificado (formato CEDEC-XXXX-XXXX).</li>
          <li>Se o código estiver correto, entre em contato com a CEDEC-MG para confirmar a emissão.</li>
        </ul>
      </section>

      <section
        v-else
        class="rounded-xl border border-dashed border-slate-300 p-5 text-center dark:border-slate-700 sm:p-6"
        data-testid="instrucoes-validacao"
      >
        <Text size="sm" color="muted">
          Informe o código de verificação acima para consultar o certificado. O resultado aparece aqui.
        </Text>
      </section>
    </div>
  </div>
</template>
