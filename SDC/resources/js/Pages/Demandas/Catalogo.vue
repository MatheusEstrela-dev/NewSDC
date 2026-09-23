<template>
  <Head title="Catálogo de demandas" />
  <div class="p-6 space-y-6">
    <div><h1 class="text-2xl font-semibold">Catálogo de demandas</h1><p class="text-sm text-slate-500">Categorias e assuntos usados na abertura de chamados</p></div>
    <div class="grid gap-6 lg:grid-cols-2">
      <section class="space-y-4 rounded border p-4 dark:border-slate-700">
        <h2 class="text-lg font-semibold">Categorias</h2>
        <form class="grid gap-2" @submit.prevent="saveCategoria">
          <input v-model="categoriaForm.nome" required maxlength="100" aria-label="Nome da categoria" placeholder="Nova categoria" class="rounded border p-2 dark:bg-slate-800" />
          <input v-model="categoriaForm.descricao" maxlength="2000" aria-label="Descrição da categoria" placeholder="Descrição" class="rounded border p-2 dark:bg-slate-800" />
          <select v-model="categoriaForm.parent_id" aria-label="Categoria pai" class="rounded border p-2 dark:bg-slate-800"><option value="">Categoria principal</option><option v-for="item in categoriasPrincipais" :key="item.id" :value="item.id">Subcategoria de {{ item.nome }}</option></select>
          <p v-for="(error, field) in categoriaForm.errors" :key="field" class="text-sm text-rose-600">{{ error }}</p>
          <button :disabled="categoriaForm.processing" class="rounded bg-orange-600 px-4 py-2 text-white">Adicionar categoria</button>
        </form>
        <ul class="divide-y dark:divide-slate-700"><li v-for="item in categorias" :key="item.id" class="flex items-center justify-between gap-2 py-2"><span>{{ item.parent_id ? '↳ ' : '' }}{{ item.nome }} <span v-if="!item.ativo" class="text-xs text-slate-500">(inativa)</span></span><button class="text-sm text-sky-600" @click="toggleCategoria(item)">{{ item.ativo ? 'Desativar' : 'Ativar' }}</button></li></ul>
      </section>
      <section class="space-y-4 rounded border p-4 dark:border-slate-700">
        <h2 class="text-lg font-semibold">Assuntos</h2>
        <form class="grid gap-2" @submit.prevent="saveAssunto">
          <input v-model="assuntoForm.nome" required maxlength="150" aria-label="Nome do assunto" placeholder="Novo assunto" class="rounded border p-2 dark:bg-slate-800" />
          <select v-model="assuntoForm.categoria_id" aria-label="Categoria do assunto" class="rounded border p-2 dark:bg-slate-800"><option value="">Sem categoria</option><option v-for="item in categoriasPrincipais" :key="item.id" :value="item.id">{{ item.nome }}</option></select>
          <p v-for="(error, field) in assuntoForm.errors" :key="field" class="text-sm text-rose-600">{{ error }}</p>
          <button :disabled="assuntoForm.processing" class="rounded bg-orange-600 px-4 py-2 text-white">Adicionar assunto</button>
        </form>
        <ul class="divide-y dark:divide-slate-700"><li v-for="item in assuntos" :key="item.id" class="flex items-center justify-between gap-2 py-2"><span>{{ item.nome }} <span v-if="!item.ativo" class="text-xs text-slate-500">(inativo)</span></span><button class="text-sm text-sky-600" @click="toggleAssunto(item)">{{ item.ativo ? 'Desativar' : 'Ativar' }}</button></li></ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({ categorias: Array, assuntos: Array });
const categoriasPrincipais = computed(() => props.categorias.filter(item => !item.parent_id && item.ativo));
const categoriaForm = useForm({ nome: '', descricao: '', parent_id: '' });
const assuntoForm = useForm({ nome: '', categoria_id: '' });
function saveCategoria() { categoriaForm.transform(data => ({ ...data, parent_id: data.parent_id || null })).post('/admin/demandas/categorias', { onSuccess: () => categoriaForm.reset() }); }
function saveAssunto() { assuntoForm.transform(data => ({ ...data, categoria_id: data.categoria_id || null })).post('/admin/demandas/assuntos', { onSuccess: () => assuntoForm.reset() }); }
function toggleCategoria(item) { router.put(`/admin/demandas/categorias/${item.id}`, { ativo: !item.ativo }); }
function toggleAssunto(item) { router.put(`/admin/demandas/assuntos/${item.id}`, { ativo: !item.ativo }); }
</script>
