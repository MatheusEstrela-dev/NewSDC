<template>
  <div
    class="relative border-b border-slate-200 dark:border-slate-800/50 last:border-0 transition-colors duration-200"
    :class="{
      'bg-white dark:bg-slate-900': notification.read,
      'bg-blue-50/40 hover:bg-blue-50/70 dark:bg-slate-800/30 dark:hover:bg-slate-800/50': !notification.read
    }"
  >
    <!-- Priority Indicator Strip -->
    <div
      v-if="!notification.read"
      class="absolute left-0 top-0 bottom-0 w-1"
      :class="priorityColor"
    ></div>

    <div class="p-4 pl-5 flex gap-3">
      <!-- Icon -->
      <div class="flex-shrink-0 mt-0.5">
        <div 
            class="w-8 h-8 rounded-full flex items-center justify-center ring-1 ring-inset"
            :class="iconStyles"
        >
            <component :is="iconComponent" class="w-4 h-4" />
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
            <div>
                <h4
                    class="text-sm font-semibold truncate pr-2"
                    :class="notification.read ? 'text-slate-500 dark:text-slate-400' : 'text-slate-900 dark:text-slate-200'"
                >
                    <span v-if="ehAgrupada" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 mr-1.5">
                        {{ notification.group_count }} novos
                    </span>
                    {{ title }}
                </h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed line-clamp-2">
                    {{ message }}
                </p>
            </div>
            <span class="text-[10px] text-slate-400 dark:text-slate-500 flex-shrink-0 whitespace-nowrap">
                {{ timeAgo }}
            </span>
        </div>

        <!-- Action Buttons -->
        <div v-if="hasAction || !notification.read" class="mt-3 flex items-center gap-2">
            <button
                v-if="hasAction"
                @click.stop="handleAction"
                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                :class="actionButtonStyles"
            >
                {{ actionText }}
            </button>
            <button
                v-if="!notification.read"
                @click.stop="emit('mark-read', notification)"
                class="px-3 py-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-700/50 rounded-md transition-colors"
            >
                Marcar como lida
            </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useNotifications } from '@/Composables/useNotifications';
import { router } from '@inertiajs/vue3';

// Icons
import {
    ExclamationTriangleIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    BellAlertIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  notification: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['mark-read']);
const { formatTimeAgo } = useNotifications();

// O payload do inbox e plano e o agrupamento ja vem resolvido do banco:
// group_count > 1 significa que esta linha representa varios eventos do mesmo
// assunto, absorvidos dentro da janela de agrupamento.
const type = computed(() => props.notification.type || 'info');
const ehAgrupada = computed(() => (props.notification.group_count ?? 1) > 1);

const title = computed(() => props.notification.title || 'Novas atualizações');

const message = computed(() => {
    if (ehAgrupada.value) {
        return `Você tem ${props.notification.group_count} notificações agrupadas neste tópico.`;
    }
    return props.notification.message;
});

const timeAgo = computed(() => formatTimeAgo(props.notification.created_at));

const hasAction = computed(() => !!props.notification.action_url);
const actionText = computed(() => props.notification.action_text || 'Visualizar');

const handleAction = () => {
    if (!props.notification.action_url) return;

    emit('mark-read', props.notification); // marca lida ao agir
    router.visit(props.notification.action_url);
};

// Styles based on Type/Priority
//
// Os cinco tipos de NotificacaoSpec::TIPOS precisam estar TODOS aqui. O tipo 'error'
// faltava e caia no default azul, enquanto o card do historico
// (NotificacaoHistoricoItem) ja o pintava de vermelho: a mesma notificacao aparecia
// com duas cores diferentes dependendo da tela. 'warning' tambem faltava no botao.
//
// error e urgent compartilham o vermelho; o que os separa e o glow, reservado para
// urgent, e o icone.
const priorityColor = computed(() => {
    switch (type.value) {
        case 'urgent': return 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]'; // Pulsing effect simulated via shadow
        case 'error': return 'bg-red-500';
        case 'success': return 'bg-green-500';
        case 'warning': return 'bg-amber-500';
        default: return 'bg-blue-500';
    }
});

const iconComponent = computed(() => {
    switch (type.value) {
        case 'urgent': return BellAlertIcon;
        case 'error': return XCircleIcon;
        case 'success': return CheckCircleIcon;
        case 'warning': return ExclamationTriangleIcon;
        default: return InformationCircleIcon;
    }
});

const iconStyles = computed(() => {
    switch (type.value) {
        case 'urgent':
        case 'error': return 'bg-red-500/10 text-red-500 ring-red-500/20';
        case 'success': return 'bg-green-500/10 text-green-500 ring-green-500/20';
        case 'warning': return 'bg-amber-500/10 text-amber-500 ring-amber-500/20';
        default: return 'bg-blue-500/10 text-blue-500 ring-blue-500/20';
    }
});

const actionButtonStyles = computed(() => {
    switch (type.value) {
        case 'urgent':
        case 'error': return 'bg-red-600 text-white hover:bg-red-500 shadow-sm';
        case 'success': return 'bg-green-600 text-white hover:bg-green-500 shadow-sm';
        case 'warning': return 'bg-amber-600 text-white hover:bg-amber-500 shadow-sm';
        default: return 'bg-blue-600 text-white hover:bg-blue-500 shadow-sm';
    }
});
</script>