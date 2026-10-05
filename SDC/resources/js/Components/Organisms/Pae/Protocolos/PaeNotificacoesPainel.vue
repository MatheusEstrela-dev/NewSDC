<template>
  <div class="space-y-3">
    <p v-if="ciclosEsgotadosEm" class="rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-500/10 dark:text-red-300">
      Ciclos automáticos esgotados em {{ dataBR(ciclosEsgotadosEm) }}. Decisão da CEDEC: suspender, reprovar ou notificar novamente.
    </p>

    <form v-if="canEdit && !cicloAberto" class="modal-serie-cartao space-y-3 rounded-xl p-4" @submit.prevent="emitir">
      <FormField v-model="emissao.num_sei" label="Número SEI da notificação" required :error="emissao.errors.num_sei || emissao.errors.notificacao" />
      <FormTextarea v-model="emissao.obs" label="Observação" :error="emissao.errors.obs" />
      <div class="flex justify-end">
        <Button type="submit" variant="warning" :disabled="emissao.processing || !emissao.num_sei">Emitir notificação</Button>
      </div>
    </form>

    <div v-for="n in notificacoesDesc" :key="n.id" class="modal-serie-cartao min-w-0 rounded-xl p-4">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
          <h4 class="text-base font-semibold modal-serie-titulo">Ciclo {{ n.ciclo }} • SEI {{ n.num_sei }}</h4>
          <p class="mt-1 text-sm modal-serie-apoio">
            Emitida em {{ dataBR(n.dt_notificacao) }} • vence em {{ dataBR(n.prazo_final) }}
            <span v-if="n.dias_dilacao">(+{{ n.dias_dilacao }} dias de dilação)</span>
          </p>
          <p class="mt-1 text-sm modal-serie-valor">
            {{ n.dt_devolutiva ? `Devolutiva em ${dataBR(n.dt_devolutiva)}` : 'Aguardando devolutiva' }}
          </p>
        </div>
        <Badge :variant="n.dt_devolutiva ? 'success' : (n.vencida ? 'danger' : 'warning')" size="sm">
          {{ n.dt_devolutiva ? 'Respondida' : (n.vencida ? 'Vencida' : 'Em prazo') }}
        </Badge>
      </div>

      <ul v-if="n.dilacoes?.length" class="mt-3 space-y-1 text-xs modal-serie-apoio">
        <li v-for="d in n.dilacoes" :key="d.id">
          +{{ d.dias_adicionais }} dias em {{ dataBR(d.registrada_em) }} ({{ d.aprovado_por || 'Sistema' }}): {{ d.justificativa }}
        </li>
      </ul>

      <div v-if="canEdit && !n.dt_devolutiva && n.id === ultimaId" class="mt-3 flex flex-wrap gap-2">
        <Button size="sm" variant="success" @click="abrir('devolutiva', n.id)">Registrar devolutiva</Button>
        <Button size="sm" variant="secondary" @click="abrir('dilacao', n.id)">Registrar dilação</Button>
      </div>

      <form v-if="acao === 'devolutiva' && alvo === n.id" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="registrarDevolutiva(n.id)">
        <div class="min-w-0 flex-1">
          <FormDateField v-model="devolutiva.dt_devolutiva" label="Data da devolutiva" required :error="devolutiva.errors.dt_devolutiva || devolutiva.errors.devolutiva" />
        </div>
        <Button type="submit" variant="success" :disabled="devolutiva.processing || !devolutiva.dt_devolutiva">Salvar</Button>
      </form>

      <form v-if="acao === 'dilacao' && alvo === n.id" class="mt-3 space-y-3" @submit.prevent="registrarDilacao(n.id)">
        <FormField v-model="dilacao.dias_adicionais" type="number" label="Dias adicionais" required :error="dilacao.errors.dias_adicionais || dilacao.errors.dilacao" />
        <FormTextarea v-model="dilacao.justificativa" label="Justificativa" required :error="dilacao.errors.justificativa" />
        <div class="flex justify-end">
          <Button type="submit" variant="primary" :disabled="dilacao.processing || !dilacao.dias_adicionais || !dilacao.justificativa">Salvar dilação</Button>
        </div>
      </form>
    </div>

    <div v-if="!notificacoes.length" class="py-10 text-center modal-serie-apoio">Nenhuma notificação registrada.</div>
  </div>
</template>

<script setup>
/**
 * Ciclos de notificacao do protocolo (Art. 11): emissao, devolutiva e dilacao.
 * Regras e prazos vem do servidor (PaeNotificacaoService::listarPorProtocolo).
 */
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import { hojeISO } from '@/Support/dataLocal';

const props = defineProps({
  notificacoes: { type: Array, default: () => [] },
  protocoloId: { type: Number, default: null },
  canEdit: { type: Boolean, default: false },
  ciclosEsgotadosEm: { type: String, default: null },
});

const emit = defineEmits(['atualizado']);

const notificacoesDesc = computed(() => [...props.notificacoes].reverse());
// So a ultima notificacao recebe acoes: as anteriores sem devolutiva foram
// superadas por renovacao automatica e nao estao mais em aberto.
const ultima = computed(() => props.notificacoes[props.notificacoes.length - 1] ?? null);
const ultimaId = computed(() => ultima.value?.id ?? null);
const cicloAberto = computed(() => !!ultima.value && !ultima.value.dt_devolutiva);

const acao = ref(null);
const alvo = ref(null);

const emissao = useForm({ num_sei: '', obs: '' });
const devolutiva = useForm({ dt_devolutiva: hojeISO() });
const dilacao = useForm({ dias_adicionais: '', justificativa: '' });

const opcoes = (onDone) => ({
  preserveScroll: true,
  preserveState: true,
  onSuccess: () => {
    onDone();
    acao.value = null;
    alvo.value = null;
    emit('atualizado');
  },
});

function abrir(qual, id) {
  acao.value = acao.value === qual && alvo.value === id ? null : qual;
  alvo.value = acao.value ? id : null;
  if (acao.value === 'devolutiva') devolutiva.dt_devolutiva = hojeISO();
}

function emitir() {
  emissao.post(route('pae.protocolo.notificacoes.store', props.protocoloId), opcoes(() => emissao.reset()));
}

function registrarDevolutiva(id) {
  devolutiva.post(route('pae.notificacoes.devolutiva', id), opcoes(() => devolutiva.reset()));
}

function registrarDilacao(id) {
  dilacao.post(route('pae.notificacoes.dilacoes.store', id), opcoes(() => dilacao.reset()));
}

function dataBR(iso) {
  if (!iso) return '';
  const [a, m, d] = String(iso).slice(0, 10).split('-');
  return `${d}/${m}/${a}`;
}
</script>
