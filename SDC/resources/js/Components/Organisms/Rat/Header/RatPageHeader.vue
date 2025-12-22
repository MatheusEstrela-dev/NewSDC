<template>
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
      <Heading level="1" color="white" class="mb-2">
        Gestão de RAT
      </Heading>
      <Text size="sm" color="muted">
        Visualize e gerencie todos os Registros de Atendimento Técnico
      </Text>
    </div>
    <Link :href="route('rat.create')">
      <Button
        variant="primary"
        size="md"
        :icon="PlusIcon"
        icon-position="left"
      >
        Novo RAT
      </Button>
    </Link>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Heading from '../../../Atoms/Typography/Heading.vue';
import Text from '../../../Atoms/Typography/Text.vue';
import Button from '../../../Atoms/Button/Button.vue';
import PlusIcon from '../../../Icons/PlusIcon.vue';
</script>

