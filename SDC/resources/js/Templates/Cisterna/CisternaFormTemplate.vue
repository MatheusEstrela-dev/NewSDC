<template>
  <Head :title="title" />

  <div class="cisternas-form">
    <PageHeader
      :title="title"
      :description="subtitle"
      :icon="CubeIcon"
      variant="gradient"
    >
      <template #actions>
        <Button variant="secondary" :icon="ArrowLeftIcon" @click="handleBack">
          Voltar
        </Button>
      </template>
    </PageHeader>

    <CisternaForm
      :form-data="formData"
      :errors="errors"
      :loading="loading"
      :submit-label="submitLabel"
      @submit="$emit('submit', $event)"
      @cancel="handleBack"
    />
  </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/Atoms/Button/Button.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CisternaForm from '@/Components/Organisms/Cisterna/CisternaForm.vue';

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  formData: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit']);

function handleBack() {
  router.visit(route('cisternas.index'));
}
</script>

<style scoped>
.cisternas-form {
  @apply w-full max-w-6xl mx-auto pb-8;
}
</style>
