<script setup lang="ts">
import EmailHeading from '../Atoms/EmailHeading.vue';
import EmailParagraph from '../Atoms/EmailParagraph.vue';
import EmailStrong from '../Atoms/EmailStrong.vue';

interface Props {
    dataLote: string;
    totalItens: number;
    pessoas: string[];
}

defineProps<Props>();
</script>

<template>
    <EmailHeading text="Remanejamento de estações de trabalho" />

    <EmailParagraph>Prezados Senhores,</EmailParagraph>

    <EmailParagraph>
        Gentileza fazer o desbloqueio dos pontos de rede relacionados na planilha de remanejamento em anexo.
    </EmailParagraph>

    <EmailParagraph>
        Lote de <EmailStrong>{{ dataLote }}</EmailStrong>: {{ totalItens }} equipamento(s) de
        {{ pessoas.join(', ') }}.
    </EmailParagraph>

    <EmailParagraph :size="12" color="#64748B" margin="0">
        Mensagem enviada pelo SDC - Defesa Civil de Minas Gerais.
    </EmailParagraph>
</template>
