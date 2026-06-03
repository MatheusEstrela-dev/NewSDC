<script setup lang="ts">
import EmailParagraph from '../Atoms/EmailParagraph.vue';
import EmailStrong from '../Atoms/EmailStrong.vue';

interface Props {
    name: string;
}

defineProps<Props>();
</script>

<template>
    <EmailParagraph>
        Ola <EmailStrong>{{ name }}</EmailStrong>,
    </EmailParagraph>
</template>
