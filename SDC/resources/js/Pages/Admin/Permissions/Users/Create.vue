<template>
  <AuthenticatedLayout>
    <Head title="Criar Usuário" />
    <div class="w-full py-6">
      <!-- Page Header -->
      <div class="mb-8">


        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Criar Novo Usuário</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Preencha os dados abaixo para cadastrar um novo usuário no sistema</p>
          </div>
        </div>
      </div>

      <div class="max-w-4xl">
        <form @submit.prevent="submit" class="space-y-6">
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Informações Pessoais
              </h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Name -->
              <div class="space-y-1.5">
                <label for="name" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Nome Completo</label>
                <input
                  v-model="form.name"
                  id="name"
                  type="text"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100 transition-all outline-none"
                  placeholder="Ex: João Silva"
                />
                <div v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</div>
              </div>

              <!-- Email -->
              <div class="space-y-1.5">
                <label for="email" class="text-sm font-semibold text-slate-700 dark:text-slate-300">E-mail</label>
                <input
                  v-model="form.email"
                  id="email"
                  type="email"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100 transition-all outline-none"
                  placeholder="Ex: joao@email.com"
                />
                <div v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</div>
              </div>

              <!-- CPF -->
              <div class="space-y-1.5">
                <label for="cpf" class="text-sm font-semibold text-slate-700 dark:text-slate-300">CPF</label>
                <input
                  v-model="form.cpf"
                  id="cpf"
                  type="text"
                  v-mask="'###.###.###-##'"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100 transition-all outline-none"
                  placeholder="000.000.000-00"
                />
                <div v-if="form.errors.cpf" class="text-xs text-red-500 mt-1">{{ form.errors.cpf }}</div>
              </div>

              <!-- Roles -->
              <div class="space-y-1.5">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Cargos</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                   <div v-for="role in roles" :key="role.id" class="flex items-center gap-2 px-3 py-2 bg-slate-50 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-blue-500 transition-colors cursor-pointer" @click="toggleRole(role.id)">
                    <input
                      type="checkbox"
                      :id="'role-' + role.id"
                      :value="role.id"
                      v-model="form.roles"
                      @click.stop
                      class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 dark:bg-slate-800"
                    />
                    <label :for="'role-' + role.id" class="text-sm text-slate-700 dark:text-slate-300 cursor-pointer flex-1" @click.stop>{{ role.name }}</label>
                  </div>
                </div>
                <div v-if="form.errors.roles" class="text-xs text-red-500 mt-1">{{ form.errors.roles }}</div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
             <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Segurança
              </h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Password -->
              <div class="space-y-1.5">
                <label for="password" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Senha</label>
                <input
                  v-model="form.password"
                  id="password"
                  type="password"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100 transition-all outline-none"
                />
                <div v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</div>
              </div>

              <!-- Password Confirmation -->
              <div class="space-y-1.5">
                <label for="password_confirmation" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Confirmar Senha</label>
                <input
                  v-model="form.password_confirmation"
                  id="password_confirmation"
                  type="password"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100 transition-all outline-none"
                />
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="() => router.visit(route('admin.permissions.users.index'))"
              class="px-6 py-2.5 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-8 py-2.5 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/25 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <svg v-if="form.processing" class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ form.processing ? 'Processando...' : 'Salvar Usuário' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  roles: {
    type: Array,
    required: true
  }
});

const form = useForm({
  name: '',
  email: '',
  cpf: '',
  password: '',
  password_confirmation: '',
  roles: []
});

const toggleRole = (roleId) => {
  const index = form.roles.indexOf(roleId);
  if (index === -1) {
    form.roles.push(roleId);
  } else {
    form.roles.splice(index, 1);
  }
};

const submit = () => {
  form.post(route('admin.permissions.users.store'), {
    onSuccess: () => {
      // Logic handled by Laravel redirect
    }
  });
};
</script>
