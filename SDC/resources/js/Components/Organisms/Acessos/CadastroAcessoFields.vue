<template>
  <div class="grid gap-3 md:grid-cols-2">
    <label v-for="field in fields" :key="field.key" class="block text-sm">{{ field.label }}
      <select v-if="field.key === 'tipo_documento'" v-model="form[field.key]" class="mt-1 w-full rounded border p-2 dark:bg-slate-800"><option value="masp">MASP</option><option value="militar">Militar</option><option value="outros">Outro</option></select>
      <input v-else v-model="form[field.key]" :type="field.type || 'text'" :required="field.required" :maxlength="field.max || 255" class="mt-1 w-full rounded border p-2 dark:bg-slate-800" />
      <span v-if="form.errors[field.key]" class="text-rose-600">{{ form.errors[field.key] }}</span>
    </label>
    <label class="block text-sm md:col-span-2">Observações de TI<textarea v-model="form.observacoes_ti" class="mt-1 w-full rounded border p-2 dark:bg-slate-800" /><span v-if="form.errors.observacoes_ti" class="text-rose-600">{{ form.errors.observacoes_ti }}</span></label>
  </div>
</template>

<script setup>
defineProps({ form: { type: Object, required: true } });
const fields = [
  { key: 'nome', label: 'Nome', required: true },
  { key: 'tipo_documento', label: 'Tipo de documento', required: true },
  { key: 'documento', label: 'Documento', required: true, max: 30 },
  { key: 'cpf', label: 'CPF (11 dígitos)', required: true, max: 11 },
  { key: 'login_ad', label: 'Login AD', max: 100 },
  { key: 'email_corporativo', label: 'E-mail corporativo', type: 'email' },
  { key: 'email_pessoal', label: 'E-mail pessoal', type: 'email' },
  { key: 'telefone_mesa', label: 'Telefone' },
  { key: 'telefone_whatsapp', label: 'WhatsApp' },
  { key: 'setor', label: 'Setor', max: 150 },
  { key: 'posto', label: 'Posto', max: 150 },
  { key: 'cargo', label: 'Cargo', max: 150 },
];
</script>
