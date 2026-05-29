<script setup lang="ts">
import EmailButton from '../Atoms/EmailButton.vue';

interface Props {
    title: string;
    body: string;
    buttonHref: string;
    buttonLabel: string;
}

defineProps<Props>();
</script>

<template>
    <div
        :style="{
            background: '#FEF3C7',
            borderLeft: '4px solid #F59E0B',
            padding: '16px 20px',
            borderRadius: '4px',
            margin: '0 0 24px',
        }"
    >
        <p
            :style="{
                margin: '0 0 8px',
                fontSize: '14px',
                fontWeight: 'bold',
                color: '#92400E',
                fontFamily: 'Arial, Helvetica, sans-serif',
            }"
        >
            {{ title }}
        </p>
        <p
            :style="{
                margin: '0 0 12px',
                fontSize: '13px',
                lineHeight: '1.5',
                color: '#92400E',
                fontFamily: 'Arial, Helvetica, sans-serif',
            }"
        >
            {{ body }}
        </p>
        <EmailButton :href="buttonHref" :label="buttonLabel" />
    </div>
</template>
