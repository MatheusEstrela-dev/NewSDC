<template>
  <Head title="Nova demanda" />

  <DemandasFormTemplate :form="form" :assuntos="assuntos" :campos="campos" :prioridades="prioridades" :usuarios="usuarios" :pode-gerir="pode.gerir"
    @enviar="form.post(route('demandas.store'))" @cancelar="router.visit(route('demandas.index'))" />
</template>

<script setup>
import { computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasFormTemplate from '@/Templates/Demandas/DemandasFormTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  assuntos: { type: Array, default: () => [] },
  prioridades: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  pode: { type: Object, default: () => ({ gerir: false }) },
});

const form = useForm({
  titulo: '', descricao: '', assunto_id: '', prioridade_simples: 'media',
  solicitante_id: '', responsavel_id: '', campos_customizados: {},
});

const campos = computed(() => props.assuntos.find((a) => a.value === Number(form.assunto_id))?.campos ?? []);

// Mesmo comportamento do legado: trocar o assunto zera os campos, com checkbox em false.
watch(campos, (lista) => {
  form.campos_customizados = Object.fromEntries(lista.map((c) => [c.label, c.tipo === 'checkbox' ? false : '']));
});

form.transform((dados) => Object.fromEntries(Object.entries(dados).filter(([, v]) => v !== '')));
</script>
