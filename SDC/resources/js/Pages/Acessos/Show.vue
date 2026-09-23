<template>
  <Head :title="`Acesso ${cadastro.nome}`" />
  <div class="p-6 space-y-5">
    <Link href="/acessos" class="text-sky-600">Voltar aos acessos</Link>
    <div class="flex items-center justify-between"><div><h1 class="text-2xl font-semibold">{{ cadastro.nome }}</h1><p class="text-sm text-slate-500">{{ cadastro.status }} · AD: {{ cadastro.status_ad }}</p></div><div v-if="can('acessos.cadastros.approve')" class="flex gap-2"><button v-if="cadastro.status === 'pendente'" class="rounded bg-emerald-600 px-4 py-2 text-white" @click="approve">Aprovar</button><button v-if="cadastro.status === 'aprovado' || cadastro.status === 'inativo'" class="rounded border px-4 py-2" @click="changeStatus('ativo')">Ativar cadastro</button><button v-if="cadastro.status === 'ativo'" class="rounded border px-4 py-2" @click="changeStatus('inativo')">Inativar cadastro</button><button v-if="cadastro.status === 'pendente'" class="rounded border px-4 py-2" @click="changeStatus('rejeitado')">Rejeitar</button></div></div>
    <div class="rounded border p-5 dark:border-slate-700"><form v-if="can('acessos.cadastros.edit')" class="space-y-3" @submit.prevent="save"><CadastroAcessoFields :form="form" /><button :disabled="form.processing" class="rounded bg-orange-600 px-4 py-2 text-white">Salvar alterações</button></form><dl v-else class="grid gap-3 md:grid-cols-2"><div v-for="field in visibleFields" :key="field.key"><dt class="text-sm text-slate-500">{{ field.label }}</dt><dd>{{ cadastro[field.key] || '—' }}</dd></div></dl></div>
    <section><h2 class="mb-2 text-lg font-semibold">Histórico</h2><ol class="space-y-2"><li v-for="event in auditoria" :key="event.id" class="rounded border p-3 dark:border-slate-700">{{ event.acao }} · {{ new Date(event.created_at).toLocaleString('pt-BR') }}</li></ol></section>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CadastroAcessoFields from '@/Components/Organisms/Acessos/CadastroAcessoFields.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({ cadastro: Object, auditoria: Array });
const { can } = usePermissions();
const fields = ['nome', 'tipo_documento', 'documento', 'cpf', 'login_ad', 'email_corporativo', 'email_pessoal', 'telefone_mesa', 'telefone_whatsapp', 'setor', 'posto', 'cargo', 'observacoes_ti'];
const form = useForm(Object.fromEntries(fields.map(key => [key, props.cadastro[key] || ''])));
const visibleFields = fields.map(key => ({ key, label: key.replaceAll('_', ' ') }));
function save() { form.put(`/acessos/${props.cadastro.id}`); }
function approve() { if (window.confirm('Aprovar este cadastro?')) router.post(`/acessos/${props.cadastro.id}/aprovar`); }
function changeStatus(status) { if (window.confirm(`Alterar status para ${status}?`)) router.post(`/acessos/${props.cadastro.id}/status`, { status }); }
</script>
