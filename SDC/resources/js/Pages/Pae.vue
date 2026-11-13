<template>
    <div>
        <Head title="Gestão de PAE" />

        <div :class="props.protocolo?.id ? 'w-full min-w-0 pb-6' : 'pae-container'">
            <!-- Com protocolo, o cabecalho e o mesmo das demais telas da pasta. -->
            <DetalheHeader
                v-if="props.protocolo?.id"
                :title="secao === 'anexos' ? 'Anexos do protocolo' : 'Relatório de análise'"
                :icon="secao === 'anexos' ? PaperClipIcon : DocumentTextIcon"
                subtitle="PAE · Resolução GMG nº 83/2024"
                :status-label="statusLabel"
                context-label="Protocolo"
                :context-value="props.protocolo.num_protocolo"
            />
            <PaeHeader
                v-else
                :empreendimento="empreendimento"
                :protocolo="props.protocolo"
            />

            <PaeProtocoloPastaLayout
                v-if="props.protocolo?.id"
                :protocolo="props.protocolo"
                :atual="secao"
                :subitens="subitensAnexos"
                :subitem-ativo="categoriaAnexo ?? 'todas'"
                @subitem="selecionarCategoria"
            >
                <PaeForm
                    :categoria-anexo="categoriaAnexo"
                    :empreendimento="empreendimento"
                    :municipios="props.municipios"
                    :formulario="props.formulario"
                    :protocolo="props.protocolo"
                    :read-only="props.readOnly"
                    :secao="secao"
                    :categorias-anexo="props.categoriasAnexo"
                />
            </PaeProtocoloPastaLayout>

            <PaeForm
                v-else
                :empreendimento="empreendimento"
                :municipios="props.municipios"
                :formulario="props.formulario"
                :protocolo="props.protocolo"
                :read-only="props.readOnly"
                :categorias-anexo="props.categoriasAnexo"
            />
        </div>
    </div>
</template>

<script setup>
import PaeForm from '@/Components/Pae/PaeForm.vue';
import DetalheHeader from '@/Components/Molecules/DetalheHeader.vue';
import PaeHeader from '@/Components/Pae/PaeHeader.vue';
import { DocumentTextIcon, PaperClipIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PaeProtocoloPastaLayout from '@/Templates/Pae/PaeProtocoloPastaLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
    categoriasAnexo: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const empreendimento = computed(() => props.empreendimento ?? {});

// A mesma pagina serve dois itens da pasta: o relatorio de analise e os anexos.
const secao = computed(() =>
    new URL(page.url, window.location.origin).searchParams.get('secao') === 'anexos' ? 'anexos' : 'relatorio'
);

const statusLabel = computed(() =>
    props.protocolo?.versoes?.find((versao) => versao.atual)?.status_label ?? ''
);

// Categoria aberta na pasta de anexos; null mostra todas.
const categoriaAnexo = ref(null);

const subitensAnexos = computed(() => {
    if (secao.value !== 'anexos') return [];

    const anexos = props.formulario?.anexos ?? [];
    const contagem = anexos.reduce((acc, anexo) => {
        acc[anexo.categoria] = (acc[anexo.categoria] ?? 0) + 1;
        return acc;
    }, {});

    // Pasta com filhas (o Anexo B) soma as filhas e so as expande quando ela,
    // ou uma filha, esta aberta: a arvore nao vira uma lista de 18 itens.
    const paiDe = Object.fromEntries(props.categoriasAnexo.map((c) => [c.value, c.pai]));
    const ramoAberto = paiDe[categoriaAnexo.value] ?? categoriaAnexo.value;
    const total = (valor) => props.categoriasAnexo
        .filter((c) => c.value === valor || c.pai === valor)
        .reduce((soma, c) => soma + (contagem[c.value] ?? 0), 0);

    return [
        { id: 'todas', label: 'Todas', badge: anexos.length },
        ...props.categoriasAnexo
            .filter((categoria) => !categoria.pai || categoria.pai === ramoAberto)
            .map((categoria) => ({
                id: categoria.value,
                label: categoria.label,
                badge: categoria.pai ? (contagem[categoria.value] ?? 0) : total(categoria.value),
                filho: !!categoria.pai,
            })),
    ];
});

function selecionarCategoria(id) {
    categoriaAnexo.value = id === 'todas' ? null : id;
}
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
