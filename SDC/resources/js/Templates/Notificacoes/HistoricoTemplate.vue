<template>
    <div>
        <PageHeader
            title="Notificações"
            description="Histórico completo dos seus alertas, com filtro por módulo e severidade."
            :icon="BellIcon"
            :icon-image="moduleIcon('notificacoes')"
            variant="gradient"
        >
            <template #actions>
                <button
                    v-if="totalNaoLidas > 0"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition-colors disabled:opacity-60"
                    :disabled="marcandoTodas"
                    @click="marcarTodas"
                >
                    <CheckIcon class="w-4 h-4" />
                    {{ marcandoTodas ? 'Marcando...' : `Marcar ${totalNaoLidas} como lidas` }}
                </button>
            </template>
        </PageHeader>

        <!-- Resumo -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
            <StatCard
                v-for="cartao in resumo"
                :key="cartao.title"
                :title="cartao.title"
                :value="cartao.value"
                :icon="cartao.icon"
                :variant="cartao.variant"
            />
        </div>

        <div class="mt-6">
            <NotificacaoFiltros v-model="filtrosLocais" :modulos="modulos" />
        </div>

        <!-- Lista -->
        <div class="mt-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                <BellIcon class="w-4 h-4 text-slate-400" />
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Notificações
                    <span class="font-normal text-slate-400">({{ notificacoes.meta?.total ?? notificacoes.data.length }} registros)</span>
                </h2>
            </div>

            <div
                v-if="notificacoes.data.length === 0"
                class="flex flex-col items-center justify-center py-16 px-6 text-center"
            >
                <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-full mb-4">
                    <BellIcon class="w-8 h-8 text-slate-400 dark:text-slate-500" />
                </div>
                <p class="text-slate-700 dark:text-slate-300 text-sm font-medium">Nenhuma notificação encontrada</p>
                <p class="text-slate-500 text-xs mt-1">
                    {{ temFiltro ? 'Tente ajustar os filtros acima.' : 'Você ainda não recebeu notificações.' }}
                </p>
            </div>

            <div v-else class="divide-y divide-slate-200 dark:divide-slate-800">
                <NotificacaoHistoricoItem
                    v-for="notificacao in notificacoes.data"
                    :key="notificacao.id"
                    :notificacao="notificacao"
                />
            </div>
        </div>

        <!-- Paginação -->
        <nav v-if="(notificacoes.meta?.last_page ?? 1) > 1" class="mt-6 flex flex-wrap gap-1">
            <component
                :is="link.url ? Link : 'span'"
                v-for="(link, indice) in notificacoes.meta.links"
                :key="indice"
                :href="link.url"
                preserve-scroll
                class="px-3 py-1.5 text-sm rounded-lg border transition-colors"
                :class="link.active
                    ? 'bg-blue-600 border-blue-600 text-white'
                    : link.url
                        ? 'border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                        : 'border-slate-200 dark:border-slate-700 text-slate-400 cursor-default'"
                v-html="link.label"
            />
        </nav>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { BellAlertIcon, BellIcon, CheckIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import NotificacaoFiltros from '@/Components/Molecules/Notificacoes/NotificacaoFiltros.vue';
import NotificacaoHistoricoItem from '@/Components/Molecules/Notificacoes/NotificacaoHistoricoItem.vue';
import { useNotifications } from '@/Composables/useNotifications';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
    notificacoes: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    modulos: { type: Array, default: () => [] },
});

const { unreadCount, markAllAsRead } = useNotifications();

const filtrosLocais = ref({
    modulo: props.filtros.modulo ?? null,
    tipo: props.filtros.tipo ?? null,
    apenas_nao_lidas: props.filtros.apenas_nao_lidas ?? false,
});

const temFiltro = computed(
    () => !!filtrosLocais.value.modulo || !!filtrosLocais.value.tipo || filtrosLocais.value.apenas_nao_lidas
);

const totalNaoLidas = computed(() => unreadCount.value);

const resumo = computed(() => {
    const itens = props.notificacoes.data ?? [];
    const contar = (tipo) => itens.filter((n) => n.type === tipo).length;

    return [
        { title: 'Não lidas', value: totalNaoLidas.value, icon: BellIcon, variant: 'info' },
        { title: 'Urgentes nesta página', value: contar('urgent'), icon: BellAlertIcon, variant: 'danger' },
        { title: 'Atenção nesta página', value: contar('warning'), icon: ExclamationTriangleIcon, variant: 'warning' },
    ];
});

// O filtro recarrega apenas as props da listagem, sem remontar a pagina inteira.
watch(
    filtrosLocais,
    (valor) => {
        router.get('/notificacoes', valor, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['notificacoes', 'filtros'],
        });
    },
    { deep: true }
);

const marcandoTodas = ref(false);

const marcarTodas = async () => {
    marcandoTodas.value = true;

    try {
        await markAllAsRead();
        // Recarrega a listagem para os cards refletirem o novo estado de leitura.
        router.reload({ only: ['notificacoes'] });
    } finally {
        marcandoTodas.value = false;
    }
};
</script>
