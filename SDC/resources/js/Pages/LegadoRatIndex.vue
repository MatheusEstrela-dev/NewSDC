<template>
    <div>
        <Head title="RAT — Arquivo Morto" />

        <LegadoRatIndexTemplate
            :rats="ratsData"
            :pagination="pagination"
            :statistics="statistics"
            :filter-options="filterOptions"
            :filters="props.filters"
        />
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LegadoRatIndexTemplate from '@/Templates/Rat/LegadoRatIndexTemplate.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    rats:          { type: [Array, Object], default: () => [] },
    statistics:    { type: Object, default: () => ({ total: 0, municipios: 0, esteAno: 0 }) },
    filterOptions: { type: Object, default: () => ({ municipios: [], tipos: [], anos: [] }) },
    filters:       { type: Object, default: () => ({}) },
});

const ratsData = computed(() => (Array.isArray(props.rats) ? props.rats : props.rats?.data ?? []));

const pagination = computed(() => {
    if (props.rats && !Array.isArray(props.rats)) {
        const p = props.rats?.meta ?? props.rats;
        return {
            current_page: p.current_page ?? 1,
            last_page:    p.last_page ?? 1,
            per_page:     p.per_page ?? 15,
            total:        p.total ?? 0,
            from:         p.from ?? null,
            to:           p.to ?? null,
            links:        props.rats?.links ?? p.links ?? [],
        };
    }
    return null;
});

const statistics = computed(() => props.statistics ?? { total: 0, municipios: 0, esteAno: 0 });
const filterOptions = computed(() => props.filterOptions ?? { municipios: [], tipos: [], anos: [] });
</script>
