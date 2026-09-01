<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { useBloqueioDeRolagem } from '@/Composables/ui/useBloqueioDeRolagem';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

// O bloqueio da rolagem de fundo e contado no composable: varios modais ficam
// montados na mesma pagina e cada um limpava o estado global do outro.
useBloqueioDeRolagem(() => props.show);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
    }[props.maxWidth];
});
</script>

<template>
    <Teleport to="body">
        <Transition leave-active-class="duration-200">
            <!--
              O painel e limitado a ALTURA QUE SOBRA, nao a uma fracao da tela
              inteira. Este container empurra 64px no topo (`pt-16`, para nao
              cobrir a TopBar) e reserva 16px embaixo: 80px = 5rem. Um filho
              pedindo `max-h-[90vh]` media 702px numa tela de 780 e terminava
              em 766 + 16 de padding = 782 -- 26px fora da tela, alcancaveis
              so rolando ESTE container, um segundo contexto de rolagem. No
              telefone isso se sente como "a rolagem esta cortada": o conteudo
              chega ao fim e a borda do painel continua escondida.

              `dvh` e nao `vh` de proposito: no celular a barra de endereco
              entra e sai, e `vh` congela na altura maior.
            -->
            <div 
                v-show="show" 
                class="fixed inset-0 overflow-y-auto overscroll-contain scrollbar-hide px-3 py-4 pt-16 sm:px-0 sm:pt-20" 
                style="z-index: 9999 !important; position: fixed !important; isolation: isolate !important;" 
                scroll-region
            >
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 transform transition-all z-[9998]" style="z-index: 9998 !important;" @click="close">
                        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75 backdrop-blur-md" />
                    </div>
                </Transition>

                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        class="relative flex max-h-[calc(100dvh-5rem)] flex-col overflow-y-auto overflow-x-hidden overscroll-contain rounded-lg bg-white shadow-xl transition-all transform sm:mx-auto sm:max-h-[calc(100dvh-6rem)] sm:w-full dark:bg-gray-800 z-[10000]"
                        style="z-index: 10000 !important;"
                        :class="maxWidthClass"
                    >
                        <slot v-if="show" />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>
