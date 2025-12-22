<template>
  <AuthenticatedLayout>
    <Head title="Protocolos PAE" />

    <PaeProtocolosIndexTemplate
      :use-mock="useMock"
      :loading="false"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PaeProtocolosIndexTemplate from '@/Templates/Pae/PaeProtocolosIndexTemplate.vue';

// Frontend-only: por enquanto, usar mocks. Depois é só trocar para false ou usar env flag.
const useMock = true;
</script>


