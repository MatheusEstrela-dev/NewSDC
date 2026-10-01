<template>
  <section class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
    <form class="space-y-4 p-6" data-unidade-bem @submit.prevent="enviar">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-model="form.patrimonio" label="Patrimônio" required :error="form.errors.patrimonio" />
        <FormField v-model="form.numero_serie" label="Número de série" :error="form.errors.numero_serie" />
      </div>
      <FormField v-model="form.descricao" label="Descrição" required :error="form.errors.descricao" />
      <div class="grid gap-4 sm:grid-cols-3">
        <FormField v-model="form.placa" label="Placa" placeholder="ABC1D23" :error="form.errors.placa" />
        <FormField v-model="form.renavam" label="RENAVAM" :error="form.errors.renavam" />
        <FormField v-model="form.chassi" label="Chassi" :error="form.errors.chassi" />
      </div>
      <FormField v-model="form.inventario_equipamento_id" type="number" label="Id no Inventário (opcional)" hint="Só para equipamento já inventariado." :error="form.errors.inventario_equipamento_id" />
      <p v-if="form.errors.codigo" role="alert" class="text-sm font-semibold text-red-600 dark:text-red-400">{{ form.errors.codigo }}</p>
      <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
        <Link :href="route('resgate.catalogo')" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancelar</Link>
        <Button type="submit" variant="primary" :loading="form.processing">Cadastrar</Button>
      </div>
    </form>
  </section>
</template>

<script setup>
/** Cadastro de unidade de bem permanente (viatura, drone...), pagina propria Resgate/UnidadeNova. */
import { Link, useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';

const props = defineProps({
  item: { type: Object, required: true },
});

const form = useForm({ codigo: props.item.codigo, patrimonio: '', descricao: '', placa: '', renavam: '', chassi: '', numero_serie: '', inventario_equipamento_id: '' });

function enviar() {
  form.transform((dados) => Object.fromEntries(Object.entries(dados).map(([campo, valor]) => [campo, valor === '' ? null : valor])))
    .post(route('resgate.catalogo.unidades'), { preserveScroll: true });
}
</script>
