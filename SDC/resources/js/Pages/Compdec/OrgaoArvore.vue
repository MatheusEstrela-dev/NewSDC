<template>
    <div class="p-6">
        <h1 class="text-xl font-semibold text-gray-900">Arvore COMPDEC</h1>

        <div class="mt-4 space-y-4">
            <section v-for="cedec in arvore" :key="cedec.id" class="border border-gray-200 p-4">
                <h2 class="font-medium text-gray-900">{{ cedec.nome }}</h2>
                <p class="text-sm text-gray-500">{{ cedec.codigo }}</p>

                <div class="mt-3 space-y-3">
                    <div v-for="redec in cedec.redecs" :key="redec.id" class="pl-4">
                        <h3 class="font-medium text-gray-800">{{ redec.nome }}</h3>
                        <p class="text-sm text-gray-500">{{ redec.codigo }}</p>

                        <ul class="mt-2 list-disc pl-5 text-sm text-gray-700">
                            <li v-for="compdec in redec.compdecs" :key="compdec.id">
                                {{ compdec.nome }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    arvore: {
        type: Array,
        default: () => [],
    },
});
</script>
