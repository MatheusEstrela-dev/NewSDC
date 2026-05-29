<script setup lang="ts">
import EmailHeading from '../Atoms/EmailHeading.vue';
import EmailParagraph from '../Atoms/EmailParagraph.vue';
import EmailStrong from '../Atoms/EmailStrong.vue';
import EmailGreeting from '../Molecules/EmailGreeting.vue';
import EmailWarningBox from '../Molecules/EmailWarningBox.vue';

interface Props {
    name: string;
    newEmailMasked: string;
    /** Null when the change was self-requested. */
    byAdminName: string | null;
    passwordResetUrl: string;
}

defineProps<Props>();
</script>

<template>
    <EmailHeading text="Pedido de troca do seu e-mail" />

    <EmailGreeting :name="name" />

    <EmailParagraph v-if="byAdminName">
        O administrador <EmailStrong>{{ byAdminName }}</EmailStrong> solicitou a troca do
        e-mail da sua conta no SDC para <EmailStrong>{{ newEmailMasked }}</EmailStrong>.
    </EmailParagraph>
    <EmailParagraph v-else>
        Recebemos um pedido para trocar o e-mail da sua conta para
        <EmailStrong>{{ newEmailMasked }}</EmailStrong>.
    </EmailParagraph>

    <EmailParagraph>
        <EmailStrong>Se foi voce:</EmailStrong> use o codigo de 6 digitos que enviamos para o novo endereco
        para confirmar a troca.
    </EmailParagraph>

    <EmailWarningBox
        title="Nao foi voce?"
        body="Seu e-mail atual continua valido e voce continua recebendo as notificacoes normalmente. Recomendamos trocar sua senha imediatamente."
        :button-href="passwordResetUrl"
        button-label="Trocar senha agora"
    />

    <EmailParagraph :size="12" color="#64748B" margin="0">
        Voce esta recebendo este e-mail porque ele e o endereco atual cadastrado na sua conta.
    </EmailParagraph>
</template>
