<script setup lang="ts">
import EmailHeader from '../Molecules/EmailHeader.vue';
import EmailFooter from '../Molecules/EmailFooter.vue';

interface Props {
    title: string;
}

defineProps<Props>();
</script>

<template>
    <!--
        Full HTML email shell. Uses tables for layout (Outlook-safe),
        inline styles only, no flex/grid/rem/css-vars. The slot receives
        the per-mail body Organism (verification or notice).
    -->
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <title>{{ title }}</title>
    </head>
    <body
        :style="{
            margin: '0',
            padding: '0',
            background: '#0B1F3A',
            fontFamily: 'Arial, Helvetica, sans-serif',
            color: '#0B1F3A',
        }"
    >
        <table
            width="100%"
            cellpadding="0"
            cellspacing="0"
            border="0"
            :style="{ background: '#0B1F3A', padding: '40px 0' }"
        >
            <tr>
                <td align="center">
                    <table
                        width="560"
                        cellpadding="0"
                        cellspacing="0"
                        border="0"
                        :style="{
                            background: '#FFFFFF',
                            borderRadius: '8px',
                            overflow: 'hidden',
                        }"
                    >
                        <EmailHeader />
                        <tr>
                            <td :style="{ padding: '32px' }">
                                <slot />
                            </td>
                        </tr>
                        <EmailFooter />
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
</template>
