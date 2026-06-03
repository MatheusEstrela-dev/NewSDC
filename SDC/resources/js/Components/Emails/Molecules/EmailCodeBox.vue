<script setup lang="ts">
interface Props {
    code: string;
    expiresAt: string;
    ttlMin: number;
}

defineProps<Props>();
</script>

<template>
    <div
        :style="{
            background: '#F5F7FA',
            border: '1px solid #E5E9F0',
            borderRadius: '8px',
            padding: '24px',
            textAlign: 'center',
            margin: '0 0 24px',
        }"
    >
        <p
            :style="{
                margin: '0 0 8px',
                fontSize: '13px',
                color: '#64748B',
                letterSpacing: '1px',
                fontFamily: 'Arial, Helvetica, sans-serif',
            }"
        >
            SEU CODIGO
        </p>
        <p
            :style="{
                margin: '0',
                fontFamily: '\'Courier New\', monospace',
                fontSize: '32px',
                letterSpacing: '8px',
                color: '#0B1F3A',
                fontWeight: 'bold',
            }"
        >
            {{ code }}
        </p>
        <p
            :style="{
                margin: '8px 0 0',
                fontSize: '12px',
                color: '#64748B',
                fontFamily: 'Arial, Helvetica, sans-serif',
            }"
        >
            Expira em {{ ttlMin }} minutos ({{ expiresAt }})
        </p>
    </div>
</template>
