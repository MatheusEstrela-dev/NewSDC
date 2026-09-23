<template>
  <Head title="Movimentações de equipamentos" />
  <div class="p-6 space-y-5">
    <div class="flex items-center justify-between"><div><h1 class="text-2xl font-semibold">Movimentações</h1><p class="text-sm text-slate-500">Empréstimos, remanejamentos e devoluções</p></div><button v-if="can('inventario.emprestimos.create')" class="rounded bg-orange-600 px-4 py-2 text-white" @click="formOpen = true">Nova movimentação</button></div>
    <form class="flex gap-2" @submit.prevent="search"><input v-model="query.search" aria-label="Buscar equipamento" class="rounded border p-2 dark:bg-slate-800" placeholder="Equipamento ou patrimônio" /><select v-model="query.status" aria-label="Status" class="rounded border p-2 dark:bg-slate-800"><option value="">Todos</option><option value="ativo">Ativo</option><option value="devolvido">Devolvido</option></select><button class="rounded border px-4 py-2">Buscar</button></form>
    <div class="overflow-x-auto rounded border dark:border-slate-700"><table class="w-full text-left text-sm"><thead class="bg-slate-100 dark:bg-slate-800"><tr><th class="p-3">Saída</th><th class="p-3">Equipamento</th><th class="p-3">Patrimônio</th><th class="p-3">Tipo</th><th class="p-3">Quantidade</th><th class="p-3">Status</th><th class="p-3">Ação</th></tr></thead><tbody><tr v-for="item in movimentacoes.data" :key="item.id" class="border-t dark:border-slate-700"><td class="p-3">{{ new Date(item.data_saida).toLocaleString('pt-BR') }}</td><td class="p-3">{{ item.equipamento?.nome }}</td><td class="p-3">{{ item.equipamento?.patrimonio }}</td><td class="p-3">{{ item.tipo }}</td><td class="p-3">{{ item.quantidade }}</td><td class="p-3">{{ item.status }}</td><td class="p-3"><button v-if="item.status === 'ativo' && can('inventario.emprestimos.return')" class="text-emerald-600" @click="devolver(item)">Devolver</button></td></tr></tbody></table></div>
    <p v-if="!movimentacoes.data.length" class="text-sm text-slate-500">Nenhuma movimentação encontrada.</p>
    <nav class="flex flex-wrap gap-2" aria-label="Paginação"><button v-for="link in movimentacoes.links" :key="link.label" :disabled="!link.url" class="rounded border px-3 py-1 disabled:opacity-40" :class="{ 'bg-orange-600 text-white': link.active }" @click="link.url && router.visit(link.url)"><span v-html="link.label" /></button></nav>
    <div v-if="formOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"><form class="w-full max-w-lg space-y-3 rounded bg-white p-6 dark:bg-slate-900" @submit.prevent="save"><h2 class="text-xl font-semibold">Nova movimentação</h2>
      <label class="block">Equipamento<select v-model="form.equipamento_id" required class="w-full rounded border p-2 dark:bg-slate-800"><option value="">Selecione</option><option v-for="item in equipamentos" :key="item.id" :value="item.id">{{ item.nome }} — {{ item.patrimonio }} ({{ item.quantidade }})</option></select></label>
      <label class="block">Tipo<select v-model="form.tipo" class="w-full rounded border p-2 dark:bg-slate-800"><option value="emprestimo">Empréstimo</option><option value="remanejamento">Remanejamento</option></select></label>
      <label class="block">Quantidade<input v-model.number="form.quantidade" type="number" min="1" required class="w-full rounded border p-2 dark:bg-slate-800" /></label>
      <label class="block">Usuário de destino<select v-model="form.usuario_destino_id" class="w-full rounded border p-2 dark:bg-slate-800"><option value="">Nenhum</option><option v-for="item in usuarios" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
      <label class="block">Estação de destino<select v-model="form.estacao_destino_id" class="w-full rounded border p-2 dark:bg-slate-800"><option value="">Nenhuma</option><option v-for="item in estacoes" :key="item.id" :value="item.id">{{ item.nome }}</option></select></label>
      <label class="block">Devolução prevista<input v-model="form.data_prevista_devolucao" type="date" class="w-full rounded border p-2 dark:bg-slate-800" /></label>
      <label class="block">Observação<textarea v-model="form.observacao" class="w-full rounded border p-2 dark:bg-slate-800" /></label>
      <p v-for="(error, field) in form.errors" :key="field" class="text-sm text-rose-600">{{ error }}</p>
      <div class="flex justify-end gap-2"><button type="button" class="rounded border px-4 py-2" @click="formOpen = false">Cancelar</button><button :disabled="form.processing" class="rounded bg-orange-600 px-4 py-2 text-white">Registrar</button></div>
    </form></div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';
defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({ movimentacoes: Object, filters: Object, equipamentos: Array, usuarios: Array, estacoes: Array });
const { can } = usePermissions();
const query = reactive({ search: props.filters?.search || '', status: props.filters?.status || '' });
const formOpen = ref(false);
const form = useForm({ equipamento_id: '', tipo: 'emprestimo', quantidade: 1, usuario_destino_id: '', estacao_destino_id: '', data_prevista_devolucao: '', observacao: '' });
watch(() => form.equipamento_id, id => { form.quantidade = props.equipamentos.find(item => item.id === id)?.quantidade || 1; });
function search() { router.get('/inventario/movimentacoes', query, { preserveState: true }); }
function save() { form.transform(data => ({ ...data, usuario_destino_id: data.usuario_destino_id || null, estacao_destino_id: data.estacao_destino_id || null, data_prevista_devolucao: data.data_prevista_devolucao || null })).post('/inventario/movimentacoes', { onSuccess: () => { formOpen.value = false; form.reset(); } }); }
function devolver(item) { if (window.confirm('Confirmar devolução?')) router.post(`/inventario/movimentacoes/${item.id}/devolver`); }
</script>
