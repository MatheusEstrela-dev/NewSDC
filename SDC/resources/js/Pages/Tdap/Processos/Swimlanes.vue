<template>
  <Head title="TDAP — Swimlanes" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Swimlanes — Processos abertos"
      description="Kanban por raia/responsável (Fomentação → Encerrado)"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link :href="route('tdap.processos.index')">
          <SecondaryButton>Ver lista</SecondaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <SwimlaneBoard :boards="boards" />
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import SwimlaneBoard from '@/Components/Organisms/Tdap/SwimlaneBoard.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: TdapLayout });

defineProps({
  boards: { type: Array, default: () => [] },
});
</script>
