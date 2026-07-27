<template>
    <div>
        <Head :title="`RAT Arquivo Morto ${rat.num_ocorrencia ?? ''}`" />
        <LegadoRatShowTemplate :rat="rat" :anexos="anexos" />
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LegadoRatShowTemplate from '@/Templates/Rat/LegadoRatShowTemplate.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    rat: { type: Object, required: true },
    anexos: { type: Array, default: () => [] },
});
</script>
