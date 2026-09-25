<template>
  <section v-if="vigentes.length || podeBloquear" class="rounded-xl border p-5" :class="vigentes.length ? 'border-red-300 bg-red-50 dark:border-red-500/40 dark:bg-red-950/30' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800'" data-bloqueios-pedido>
    <h2 class="text-base font-bold" :class="vigentes.length ? 'text-red-800 dark:text-red-200' : 'text-slate-900 dark:text-white'">
      {{ vigentes.length ? 'Pedido suspenso por bloqueio' : 'Bloqueios' }}
    </h2>
    <p v-if="vigentes.length" class="mt-1 text-xs text-red-700 dark:text-red-300">Nenhuma etapa avança enquanto houver bloqueio vigente. As reservas ficam congeladas.</p>

    <ul v-if="bloqueios.length" class="mt-3 space-y-2">
      <li v-for="bloqueio in bloqueios" :key="bloqueio.id" class="rounded-lg bg-white/70 p-3 text-xs dark:bg-slate-900/50">
        <p class="font-semibold text-slate-900 dark:text-slate-100">
          {{ bloqueio.tipo === 'judicial' ? 'Judicial' : 'Administrativo' }} · {{ bloqueio.alvo === 'ente' ? 'ente inteiro' : 'este pedido' }} · {{ bloqueio.documento_origem }}
          <span v-if="bloqueio.encerrado_em" class="font-normal text-emerald-700 dark:text-emerald-300"> · encerrado</span>
        </p>
        <p class="mt-1 text-slate-600 dark:text-slate-300">{{ bloqueio.motivo }}</p>
        <p class="mt-1 text-slate-500 dark:text-slate-400">Registrado por {{ usuarios[bloqueio.registrado_por] ?? `#${bloqueio.registrado_por}` }}<template v-if="bloqueio.encerrado_em"> · encerrado: {{ bloqueio.encerramento_motivo }}</template></p>
        <form v-if="podeBloquear && !bloqueio.encerrado_em" class="mt-2 flex flex-wrap items-end gap-2" @submit.prevent="encerrar(bloqueio)">
          <div class="min-w-[14rem] flex-1"><FormField v-model="encerramentos[bloqueio.id]" label="Motivo do encerramento" /></div>
          <Button type="submit" variant="outline" size="sm">Encerrar bloqueio</Button>
        </form>
      </li>
    </ul>

    <details v-if="podeBloquear" class="mt-3">
      <summary class="cursor-pointer text-sm font-semibold text-red-700 dark:text-red-300">Registrar bloqueio</summary>
      <form class="mt-3 space-y-3" data-form-bloqueio @submit.prevent="bloquear">
        <div class="grid gap-3 sm:grid-cols-2">
          <FormSelect v-model="form.alvo" label="Alvo" :options="[{ value: 'pedido', label: 'Este pedido' }, { value: 'ente', label: 'Ente inteiro' }]" required />
          <FormSelect v-model="form.tipo" label="Tipo" :options="[{ value: 'judicial', label: 'Judicial' }, { value: 'administrativo', label: 'Administrativo' }]" required />
        </div>
        <FormField v-model="form.documento_origem" label="Documento de origem (processo, decisão)" required :error="form.errors.documento_origem" />
        <FormTextarea v-model="form.motivo" label="Motivo" :rows="2" required :error="form.errors.motivo" />
        <div class="flex justify-end"><Button type="submit" variant="danger" :loading="form.processing">Bloquear</Button></div>
      </form>
    </details>
  </section>
</template>

<script setup>
/**
 * Bloqueios judiciais/administrativos do pedido ou do ente (premissa P7).
 * Vigente = suspende o pedido na hora. Nunca apagado: so encerrado, com motivo.
 */
import { computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  pedido: { type: Object, required: true },
  bloqueios: { type: Array, default: () => [] },
  podeBloquear: { type: Boolean, default: false },
  usuarios: { type: Object, default: () => ({}) },
});

const vigentes = computed(() => props.bloqueios.filter((b) => !b.encerrado_em));
const form = useForm({ alvo: 'pedido', tipo: 'judicial', documento_origem: '', motivo: '' });
const encerramentos = reactive({});

function bloquear() {
  form.post(route('resgate.pedidos.bloquear', props.pedido.id), { preserveScroll: true, onSuccess: () => form.reset() });
}

function encerrar(bloqueio) {
  router.post(route('resgate.pedidos.bloqueios.encerrar', { pedido: props.pedido.id, bloqueio: bloqueio.id }), { motivo: encerramentos[bloqueio.id] ?? '' }, { preserveScroll: true });
}
</script>
