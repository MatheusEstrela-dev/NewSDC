<template>
    <div>
        <Head title="Gestão de PAE" />

        <div class="pae-container">
            <PaeHeader
                :empreendimento="empreendimento"
                :protocolo="props.protocolo"
            />

<PaeForm
                :empreendimento="empreendimento"
                :municipios="props.municipios"
                :formulario="props.formulario"
                :protocolo="props.protocolo"
                :read-only="props.readOnly"
            />
        </div>
    </div>
</template>

<script setup>
import PaeForm from '@/Components/Pae/PaeForm.vue';
import PaeHeader from '@/Components/Pae/PaeHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import '../../css/pages/pae/pae.css';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    empreendimento: {
        type: Object,
        default: () => ({}),
    },
    municipios: {
        type: Object,
        default: () => ({}),
    },
    formulario: {
        type: Object,
        default: null,
    },
    protocolo: {
        type: Object,
        default: null,
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
});

const empreendimento = computed(() => props.empreendimento ?? {});
</script>

<style scoped>
.pae-container {
    @apply w-full min-h-screen bg-transparent;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    box-sizing: border-box;
}
</style>
