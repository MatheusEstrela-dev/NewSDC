<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import OtpInput from '@/Components/OtpInput.vue';

const props = defineProps({
    pendingChange: { type: Object, required: true },
});

const page = usePage();

const form = useForm({ code: '' });
const resendForm = useForm({});
const cancelForm = useForm({});

const now = ref(Date.now());
setInterval(() => { now.value = Date.now(); }, 1000);

const resendAvailable = computed(() => {
    if (!props.pendingChange.resend_available_at) return true;
    return now.value >= Date.parse(props.pendingChange.resend_available_at);
});

const resendCountdown = computed(() => {
    if (resendAvailable.value) return '';
    const ms = Date.parse(props.pendingChange.resend_available_at) - now.value;
    const s = Math.max(0, Math.ceil(ms / 1000));
    return `(${s}s)`;
});

const expiryHuman = computed(() => {
    const ms = Date.parse(props.pendingChange.expires_at) - now.value;
    if (ms <= 0) return 'expirado';
    const min = Math.floor(ms / 60000);
    const sec = Math.floor((ms % 60000) / 1000);
    return `${min}min ${sec.toString().padStart(2, '0')}s`;
});

function submit() {
    if (form.code.length !== 6) return;
    form.post(route('profile.email.verify'), {
        preserveScroll: true,
        onSuccess: () => form.reset('code'),
    });
}

function resend() {
    resendForm.post(route('profile.email.resend'), { preserveScroll: true });
}

function cancel() {
    if (!confirm('Cancelar o pedido de troca de e-mail?')) return;
    cancelForm.post(route('profile.email.cancel'), { preserveScroll: true });
}
</script>

<template>
    <Modal :show="true" :closeable="false" max-width="md">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">
                Confirme seu novo e-mail
            </h2>

            <p v-if="pendingChange.requested_by_admin" class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                Um administrador alterou seu e-mail para
                <strong>{{ pendingChange.new_email_masked }}</strong>.
                Enviamos um codigo de 6 digitos para confirmar.
            </p>
            <p v-else class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                Voce pediu para trocar seu e-mail para
                <strong>{{ pendingChange.new_email_masked }}</strong>.
                Enviamos um codigo de 6 digitos para esse endereco.
            </p>

            <OtpInput v-model="form.code" :length="6" @complete="submit" class="mb-4" />

            <InputError class="mt-2 text-center" :message="form.errors.code" />
            <InputError class="mt-2 text-center" :message="resendForm.errors.resend" />

            <p class="text-xs text-slate-500 dark:text-slate-400 text-center mt-4">
                Tentativas restantes: {{ pendingChange.attempts_remaining }} -
                Codigo expira em {{ expiryHuman }}
            </p>

            <div class="flex justify-between items-center mt-6">
                <SecondaryButton type="button" @click="cancel">Cancelar troca</SecondaryButton>

                <div class="flex gap-2">
                    <button
                        type="button"
                        :disabled="!resendAvailable || resendForm.processing"
                        @click="resend"
                        class="px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:underline disabled:opacity-50 disabled:no-underline"
                    >
                        Reenviar {{ resendCountdown }}
                    </button>
                    <PrimaryButton
                        type="button"
                        :disabled="form.code.length !== 6 || form.processing"
                        @click="submit"
                    >
                        Confirmar
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
