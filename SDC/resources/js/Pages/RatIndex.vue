<template>
    <div>
        <Head title="Gestão de RAT" />

        <RatIndexTemplate
            :statistics="statistics"
            :rats="ratsData"
            :filters="props.filters"
            :pagination="pagination"
            :municipalities="props.municipalities"
            :cobrade-types="props.cobradeTypes"
            :years="props.years"
            :loading="false"
            :use-mock="false"
            :can-create="can('rat.protocolos.create')"
            :can-edit="can('rat.protocolos.edit')"
            :can-delete="can('rat.protocolos.delete')"
            :can-export="can('rat.protocolos.export')"
            :can-finalize="can('rat.protocolos.finalize')"
        />
    </div>
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RatIndexTemplate from '@/Templates/Rat/RatIndexTemplate.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
    statistics:    { type: Object, default: () => ({ total: 0, hoje: 0, esteMes: 0, esteAno: 0 }) },
    rats:          { type: [Array, Object], default: () => [] },
    filters:       { type: Object, default: () => ({}) },
    pagination:    { type: Object, default: null },
    municipalities:{ type: Array, default: () => [] },
    cobradeTypes:  { type: Array, default: () => [] },
    years:         { type: Array, default: () => [] },
});

// Normalize paginated resource collection or plain array
const ratsData = computed(() => {
    if (Array.isArray(props.rats)) return props.rats;
    return props.rats?.data ?? [];
});

const pagination = computed(() => {
    if (props.pagination) return props.pagination;
    if (props.rats && !Array.isArray(props.rats)) {
        const p = props.rats?.meta ?? props.rats;
        return {
            current_page: p.current_page ?? 1,
            last_page:    p.last_page    ?? 1,
            per_page:     p.per_page     ?? 15,
            total:        p.total        ?? 0,
            from:         p.from         ?? null,
            to:           p.to           ?? null,
            links:        props.rats?.links ?? p.links ?? [],
        };
    }
    return null;
});

const statistics = computed(() => props.statistics ?? { total: 0, hoje: 0, esteMes: 0, esteAno: 0 });
</script>
