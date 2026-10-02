<template>
  <Head :title="`Acesso ${cadastro.nome}`" />
  <AcessoShowTemplate
    :cadastro="cadastro"
    :auditoria="auditoria"
    :pode="pode"
    @voltar="router.visit('/acessos')"
  />
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AcessoShowTemplate from '@/Templates/Acessos/AcessoShowTemplate.vue';
import { usePermissions } from '@/Composables/usePermissions';

defineOptions({ layout: AuthenticatedLayout });

defineProps({ cadastro: Object, auditoria: Array });
const { can } = usePermissions();

const pode = computed(() => ({
  editar: can('acessos.cadastros.edit'),
  aprovar: can('acessos.cadastros.approve'),
}));
</script>
