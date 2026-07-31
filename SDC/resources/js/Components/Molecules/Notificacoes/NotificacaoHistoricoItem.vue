<template>
    <article
        class="flex gap-4 p-4 transition-colors"
        :class="notificacao.read ? '' : 'bg-blue-50/50 dark:bg-slate-800/40'"
    >
        <div
            class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center ring-1 ring-inset"
            :class="estiloIcone"
        >
            <component :is="icone" class="w-4 h-4" />
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    <!-- Uma linha agrupada representa varios eventos do mesmo assunto,
                         absorvidos dentro da janela de agrupamento. -->
                    <span
                        v-if="ehAgrupada"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 mr-1.5"
                    >
                        {{ notificacao.group_count }} eventos
                    </span>
                    {{ notificacao.title }}
                </h3>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <span
                        v-if="!notificacao.read"
                        class="w-2 h-2 rounded-full bg-blue-500"
                        title="Não lida"
                    ></span>
                    <time class="text-[11px] text-slate-400 whitespace-nowrap">{{ dataFormatada }}</time>
                </div>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                {{ notificacao.message }}
            </p>

            <a
                v-if="notificacao.action_url"
                :href="notificacao.action_url"
                class="inline-block mt-2 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline"
            >
                {{ notificacao.action_text || 'Visualizar' }}
            </a>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import {
    BellAlertIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    notificacao: { type: Object, required: true },
});

const ehAgrupada = computed(() => (props.notificacao.group_count ?? 1) > 1);

const dataFormatada = computed(() =>
    new Date(props.notificacao.created_at).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
);

const icone = computed(
    () =>
        ({
            urgent: BellAlertIcon,
            error: XCircleIcon,
            warning: ExclamationTriangleIcon,
            success: CheckCircleIcon,
        })[props.notificacao.type] ?? InformationCircleIcon
);

const estiloIcone = computed(
    () =>
        ({
            urgent: 'bg-red-500/10 text-red-500 ring-red-500/20',
            error: 'bg-red-500/10 text-red-500 ring-red-500/20',
            warning: 'bg-amber-500/10 text-amber-500 ring-amber-500/20',
            success: 'bg-green-500/10 text-green-500 ring-green-500/20',
        })[props.notificacao.type] ?? 'bg-blue-500/10 text-blue-500 ring-blue-500/20'
);
</script>
