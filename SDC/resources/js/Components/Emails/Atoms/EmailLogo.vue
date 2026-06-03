<script setup lang="ts">
// CEDEC logo embedded as inline CID attachment by the Mailable.
// The Mailable embeds public/imgs/logo_dc.png as `logo-cedec`.
</script>

<template>
    <img
        src="cid:logo-cedec"
        alt="CEDEC"
        width="64"
        :style="{ display: 'block', border: '0' }"
    />
</template>
