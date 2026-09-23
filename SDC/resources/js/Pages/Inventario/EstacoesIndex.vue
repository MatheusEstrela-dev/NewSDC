<template>
  <Head title="Estações de trabalho" />
  <div class="p-6 space-y-5">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold">Estações de trabalho</h1>
        <p class="text-sm text-slate-500">Locais, ocupação e ponto de rede</p>
      </div>
      <button v-if="can('inventario.equipamentos.create')" class="rounded bg-orange-600 px-4 py-2 text-white" @click="edit(null)">Nova estação</button>
    </div>

    <form class="flex flex-wrap gap-2" @submit.prevent="search">
      <input v-model="query.search" aria-label="Buscar estação" class="rounded border p-2 dark:bg-slate-800" placeholder="Nome ou ponto de rede" />
      <select v-model="query.status" aria-label="Ocupação" class="rounded border p-2 dark:bg-slate-800">
        <option value="">Todas</option><option value="livre">Livre</option><option value="ocupada">Ocupada</option>
      </select>
      <button class="rounded border px-4 py-2">Buscar</button>
    </form>

    <div class="overflow-x-auto rounded border dark:border-slate-700">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-100 dark:bg-slate-800"><tr><th class="p-3">Estação</th><th class="p-3">Usuário</th><th class="p-3">Ponto de rede</th><th class="p-3">Equipamentos</th><th class="p-3">Status</th><th class="p-3">Ações</th></tr></thead>
        <tbody><tr v-for="item in estacoes.data" :key="item.id" class="border-t dark:border-slate-700">
          <td class="p-3">{{ item.nome }}</td><td class="p-3">{{ item.usuario?.name || '—' }}</td><td class="p-3">{{ item.ponto_rede || '—' }}</td><td class="p-3">{{ item.equipamentos_count }}</td><td class="p-3">{{ item.user_id ? 'Ocupada' : 'Livre' }}</td>
          <td class="p-3 space-x-2"><button v-if="can('inventario.equipamentos.edit')" class="text-sky-600" @click="edit(item)">Editar</button><button v-if="can('inventario.equipamentos.delete')" class="text-rose-600" @click="remove(item)">Remover</button></td>
        </tr></tbody>
      </table>
    </div>
    <p v-if="!estacoes.data.length" class="text-sm text-slate-500">Nenhuma estação encontrada.</p>
    <nav class="flex flex-wrap gap-2" aria-label="Paginação"><button v-for="link in estacoes.links" :key="link.label" :disabled="!link.url" class="rounded border px-3 py-1 disabled:opacity-40" :class="{ 'bg-orange-600 text-white': link.active }" @click="link.url && router.visit(link.url)"><span v-html="link.label" /></button></nav>

    <div v-if="formOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <form class="w-full max-w-lg space-y-3 rounded bg-white p-6 dark:bg-slate-900" @submit.prevent="save">
        <h2 class="text-xl font-semibold">{{ selected ? 'Editar estação' : 'Nova estação' }}</h2>
        <label class="block">Nome<input v-model="form.nome" required maxlength="120" class="w-full rounded border p-2 dark:bg-slate-800" /></label><p v-if="form.errors.nome" class="text-rose-600">{{ form.errors.nome }}</p>
        <label class="block">Ponto de rede<input v-model="form.ponto_rede" maxlength="120" class="w-full rounded border p-2 dark:bg-slate-800" /></label>
        <label class="block">Usuário<select v-model="form.user_id" class="w-full rounded border p-2 dark:bg-slate-800"><option value="">Livre</option><option v-for="user in usuarios" :key="user.id" :value="user.id">{{ user.name }}</option></select></label>
        <div class="flex justify-end gap-2"><button type="button" class="rounded border px-4 py-2" @click="formOpen = false">Cancelar</button><button :disabled="form.processing" class="rounded bg-orange-600 px-4 py-2 text-white">Salvar</button></div>
      </form>
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({ estacoes: Object, filters: Object, usuarios: Array });
const { can } = usePermissions();
const query = reactive({ search: props.filters?.search || '', status: props.filters?.status || '' });
const formOpen = ref(false);
const selected = ref(null);
const form = useForm({ nome: '', ponto_rede: '', user_id: '' });
function search() { router.get('/inventario/estacoes', query, { preserveState: true }); }
function edit(item) { selected.value = item; form.clearErrors(); form.nome = item?.nome || ''; form.ponto_rede = item?.ponto_rede || ''; form.user_id = item?.user_id || ''; formOpen.value = true; }
function save() {
  const url = selected.value ? `/inventario/estacoes/${selected.value.id}` : '/inventario/estacoes';
  form.transform(data => ({ ...data, user_id: data.user_id || null }));
  form[selected.value ? 'put' : 'post'](url, { onSuccess: () => { formOpen.value = false; } });
}
function remove(item) { if (window.confirm(`Remover a estação ${item.nome}?`)) router.delete(`/inventario/estacoes/${item.id}`); }
</script>
