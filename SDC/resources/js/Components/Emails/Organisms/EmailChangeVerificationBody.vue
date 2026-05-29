<script setup lang="ts">
import EmailHeading from '../Atoms/EmailHeading.vue';
import EmailParagraph from '../Atoms/EmailParagraph.vue';
import EmailStrong from '../Atoms/EmailStrong.vue';
import EmailGreeting from '../Molecules/EmailGreeting.vue';
import EmailCodeBox from '../Molecules/EmailCodeBox.vue';

interface Props {
    name: string;
    newEmail: string;
    code: string;
    /** Pre-formatted "d/m/Y H:i" string from PHP side. */
    expiresAt: string;
    ttlMin: number;
}

defineProps<Props>();
</script>

<template>
    <EmailHeading text="Confirme seu novo e-mail" />

    <EmailGreeting :name="name" />

    <EmailParagraph margin="0 0 24px">
        Recebemos um pedido para usar este endereco
        (<EmailStrong>{{ newEmail }}</EmailStrong>) como
        e-mail principal da sua conta no Sistema Integrado de Defesa Civil.
        Para confirmar a posse deste endereco, digite o codigo abaixo no SDC.
    </EmailParagraph>

    <EmailCodeBox
        :code="code"
        :expires-at="expiresAt"
        :ttl-min="ttlMin"
    />

    <EmailParagraph :size="13" color="#64748B" margin="0 0 8px">
        Se voce nao solicitou esta troca, ignore este e-mail e troque sua senha imediatamente.
        Seu e-mail atual continua valido ate a confirmacao.
    </EmailParagraph>
</template>
