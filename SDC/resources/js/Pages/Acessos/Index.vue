<template>
  <Head title="Acessos" />
  <div class="p-6 space-y-5">
    <div class="flex items-center justify-between"><div><h1 class="text-2xl font-semibold">Acessos</h1><p class="text-sm text-slate-500">{{ ativos }} cadastros ativos</p></div><button v-if="can('acessos.cadastros.create')" class="rounded bg-orange-600 px-4 py-2 text-white" @click="formOpen = true">Novo cadastro</button></div>
    <form class="grid gap-2 md:grid-cols-5" @submit.prevent="search"><input v-model="query.nome" aria-label="Nome" class="rounded border p-2 dark:bg-slate-800" placeholder="Nome" /><input v-model="query.cpf" aria-label="CPF" class="rounded border p-2 dark:bg-slate-800" placeholder="CPF, 11 dígitos" /><input v-model="query.setor" aria-label="Setor" class="rounded border p-2 dark:bg-slate-800" placeholder="Setor" /><select v-model="query.status" aria-label="Status" class="rounded border p-2 dark:bg-slate-800"><option value="">Todos</option><option value="pendente">Pendente</option><option value="aprovado">Aprovado</option><option value="ativo">Ativo</option><option value="inativo">Inativo</option><option value="rejeitado">Rejeitado</option></select><button class="rounded border px-4 py-2">Buscar</button></form>
    <div class="overflow-x-auto rounded border dark:border-slate-700"><table class="w-full text-left text-sm"><thead class="bg-slate-100 dark:bg-slate-800"><tr><th class="p-3">Nome</th><th class="p-3">Login AD</th><th class="p-3">CPF final</th><th class="p-3">Setor</th><th class="p-3">Cargo</th><th class="p-3">Status</th><th class="p-3">Ação</th></tr></thead><tbody><tr v-for="item in cadastros.data" :key="item.id" class="border-t dark:border-slate-700"><td class="p-3">{{ item.nome }}</td><td class="p-3">{{ item.login_ad || '—' }}</td><td class="p-3">•••{{ item.cpf_final }}</td><td class="p-3">{{ item.setor || '—' }}</td><td class="p-3">{{ item.cargo || '—' }}</td><td class="p-3">{{ item.status }}</td><td class="p-3"><Link :href="`/acessos/${item.id}`" class="text-sky-600">Abrir</Link></td></tr></tbody></table></div>
    <p v-if="!cadastros.data.length" class="text-sm text-slate-500">Nenhum cadastro encontrado.</p>
    <nav class="flex flex-wrap gap-2" aria-label="Paginação"><button v-for="link in cadastros.links" :key="link.label" :disabled="!link.url" class="rounded border px-3 py-1 disabled:opacity-40" :class="{ 'bg-orange-600 text-white': link.active }" @click="link.url && router.visit(link.url)"><span v-html="link.label" /></button></nav>
    <div v-if="formOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"><form class="max-h-[90vh] w-full max-w-2xl space-y-3 overflow-y-auto rounded bg-white p-6 dark:bg-slate-900" @submit.prevent="save"><h2 class="text-xl font-semibold">Novo cadastro</h2><CadastroAcessoFields :form="form" /><div class="flex justify-end gap-2"><button type="button" class="rounded border px-4 py-2" @click="formOpen = false">Cancelar</button><button :disabled="form.processing" class="rounded bg-orange-600 px-4 py-2 text-white">Salvar</button></div></form></div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CadastroAcessoFields from '@/Components/Organisms/Acessos/CadastroAcessoFields.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({ cadastros: Object, filters: Object, ativos: Number });
const { can } = usePermissions();
const query = reactive({ nome: props.filters?.nome || '', cpf: props.filters?.cpf || '', setor: props.filters?.setor || '', status: props.filters?.status || '' });
const formOpen = ref(false);
const form = useForm({ nome: '', tipo_documento: 'masp', documento: '', cpf: '', login_ad: '', email_corporativo: '', email_pessoal: '', telefone_mesa: '', telefone_whatsapp: '', setor: '', posto: '', cargo: '', observacoes_ti: '' });
function search() { router.get('/acessos', query, { preserveState: true }); }
function save() { form.post('/acessos', { onSuccess: () => { formOpen.value = false; form.reset(); } }); }
</script>
