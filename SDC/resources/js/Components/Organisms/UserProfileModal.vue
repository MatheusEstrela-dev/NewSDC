<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => auth.value?.user || {});

// Form para edição (apenas nome e email conforme solicitação)
const form = useForm({
    name: user.value.name || '',
    email: user.value.email || '',
});

// Atualizar o form se o usuário mudar (embora raro numa sessão SPA, boa prática)
watch(() => user.value, (newUser) => {
    if (newUser) {
        form.name = newUser.name || '';
        form.email = newUser.email || '';
    }
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const closeModal = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const userInitials = computed(() => {
    const name = user.name || '';
    return name
        .split(' ')
        .map(n => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});
</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-6">
                Meu Perfil
            </h2>

            <!-- Header com Avatar -->
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xl font-bold shadow-lg">
                    {{ userInitials }}
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-slate-900 dark:text-white">{{ user.name }}</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">{{ user.email }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Nome -->
                <div>
                    <InputLabel for="name" value="Nome Completo" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div>
                    <InputLabel for="email" value="E-mail" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <!-- Campos Somente Leitura -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <!-- CPF/Código -->
                    <div>
                        <InputLabel for="cpf" value="Código de Usuário (CPF)" />
                        <div class="mt-1 px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-md text-slate-600 dark:text-slate-400 font-mono text-sm">
                           {{ user.cpf || 'Não informado' }}
                        </div>
                    </div>

                    <!-- Órgão -->
                    <div>
                        <InputLabel value="Órgão Principal" />
                        <div class="mt-1 px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-md text-slate-600 dark:text-slate-400 text-sm truncate" :title="user.orgao_principal_id">
                            <!-- TODO: Exibir nome do órgão se disponível no payload -->
                            ID: {{ user.orgao_principal_id || 'Não vinculado' }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Salvar Alterações
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
