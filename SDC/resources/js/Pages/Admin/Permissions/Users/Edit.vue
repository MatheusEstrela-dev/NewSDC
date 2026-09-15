<template>

    <Head title="Editar Usuário" />
    <div>


      <PageHeader
        title="Editar Usuário"
        description="Atualize informações, cargos e permissões"
        :icon-image="moduleIcon('permissionamento')"
        variant="gradient"
        class="mb-6 md:mb-8"
      />

      <form @submit.prevent="submitForm" class="grid grid-cols-1 xl:grid-cols-4 gap-4 sm:gap-6 items-start pb-20 xl:pb-0">
        <!-- Main Content -->
        <div class="xl:col-span-3 space-y-6">
          
          <!-- Basic Info Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Informações Básicas
              </h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nome Completo</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 placeholder-slate-400"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.name }"
                  placeholder="Digite o nome completo"
                  required
                />
                <span v-if="errors.name" class="text-xs text-red-500 font-medium">{{ errors.name }}</span>
              </div>

              <div class="space-y-2">
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 placeholder-slate-400"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.email }"
                  placeholder="Digite o email"
                  required
                />
                <span v-if="errors.email" class="text-xs text-red-500 font-medium">{{ errors.email }}</span>
              </div>

              <div class="space-y-2 md:col-span-2">
                <label for="cpf" class="block text-sm font-medium text-slate-700 dark:text-slate-300">CPF</label>
                <input
                  id="cpf"
                  v-model="form.cpf"
                  type="text"
                  inputmode="numeric"
                  maxlength="11"
                  @input="form.cpf = form.cpf.replace(/\D/g, '').slice(0, 11)"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 placeholder-slate-400"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.cpf }"
                  placeholder="Somente números (11 dígitos)"
                  required
                />
                <span v-if="errors.cpf" class="text-xs text-red-500 font-medium">{{ errors.cpf }}</span>
              </div>
            </div>
          </div>

          <!-- Acoes Administrativas (gated) -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Ações Administrativas
              </h3>
              <p v-if="!canManageSensitive" class="text-xs text-amber-700 dark:text-amber-400 mt-2">
                Somente Administrador ou Desenvolvedor podem alterar estes campos.
              </p>
            </div>

            <div class="p-6 space-y-6">
              <!-- Mover usuario (orgao principal) -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Órgão Principal
                </label>
                <select
                  v-model.number="form.orgao_principal_id"
                  :disabled="!canManageSensitive"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.orgao_principal_id }"
                >
                  <option :value="null">— Nenhum —</option>
                  <option
                    v-for="orgao in availableOrgaos"
                    :key="orgao.id"
                    :value="orgao.id"
                  >
                    {{ orgao.nome }}<span v-if="orgao.municipio"> — {{ orgao.municipio.nome }}</span>
                  </option>
                </select>
                <p v-if="selectedOrgao?.municipio" class="text-xs text-slate-500 dark:text-slate-400">
                  Município: <span class="font-medium text-slate-700 dark:text-slate-300">{{ selectedOrgao.municipio.nome }}</span>
                </p>
                <span v-if="errors.orgao_principal_id" class="text-xs text-red-500 font-medium">{{ errors.orgao_principal_id }}</span>
              </div>

              <!-- Redefinir senha -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="space-y-2">
                  <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Nova senha
                  </label>
                  <div class="relative">
                    <input
                      id="password"
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      autocomplete="new-password"
                      :disabled="!canManageSensitive"
                      placeholder="Deixe em branco para manter"
                      class="w-full px-4 py-2 pr-12 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900 dark:text-slate-100 placeholder-slate-400 disabled:opacity-50 disabled:cursor-not-allowed"
                      :class="{ 'border-red-500 focus:ring-red-500': errors.password }"
                    />
                    <button
                      type="button"
                      class="absolute inset-y-0 right-0 z-20 flex w-11 items-center justify-center text-slate-400 transition-colors hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:pointer-events-none disabled:opacity-40"
                      :disabled="!canManageSensitive"
                      @click="showPassword = !showPassword"
                      :aria-label="showPassword ? 'Ocultar nova senha' : 'Mostrar nova senha'"
                      :title="showPassword ? 'Ocultar nova senha' : 'Mostrar nova senha'"
                    >
                      <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 4.24A10.7 10.7 0 0112 4c7 0 10 8 10 8a15.5 15.5 0 01-3.1 4.35M6.6 6.6C3.8 8.48 2 12 2 12s3 8 10 8a10.9 10.9 0 005.4-1.4" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3-8 10-8 10 8 10 8-3 8-10 8-10-8-10-8z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    </button>
                  </div>
                  <span v-if="errors.password" class="text-xs text-red-500 font-medium">{{ errors.password }}</span>
                </div>
                <div class="space-y-2">
                  <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Confirmar senha
                  </label>
                  <div class="relative">
                    <input
                      id="password_confirmation"
                      v-model="form.password_confirmation"
                      :type="showPasswordConfirmation ? 'text' : 'password'"
                      autocomplete="new-password"
                      :disabled="!canManageSensitive || !form.password"
                      placeholder="Repita a nova senha"
                      class="w-full px-4 py-2 pr-12 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900 dark:text-slate-100 placeholder-slate-400 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                    <button
                      type="button"
                      class="absolute inset-y-0 right-0 z-20 flex w-11 items-center justify-center text-slate-400 transition-colors hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:pointer-events-none disabled:opacity-40"
                      :disabled="!canManageSensitive || !form.password"
                      @click="showPasswordConfirmation = !showPasswordConfirmation"
                      :aria-label="showPasswordConfirmation ? 'Ocultar confirmacao de senha' : 'Mostrar confirmacao de senha'"
                      :title="showPasswordConfirmation ? 'Ocultar confirmacao de senha' : 'Mostrar confirmacao de senha'"
                    >
                      <svg v-if="showPasswordConfirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 4.24A10.7 10.7 0 0112 4c7 0 10 8 10 8a15.5 15.5 0 01-3.1 4.35M6.6 6.6C3.8 8.48 2 12 2 12s3 8 10 8a10.9 10.9 0 005.4-1.4" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3-8 10-8 10 8 10 8-3 8-10 8-10-8-10-8z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Status ativo/inativo (destacado) -->
              <div
                class="mt-4 rounded-xl border-2 p-4 sm:p-5 transition-colors"
                :class="form.active
                  ? 'border-green-300 dark:border-green-700 bg-green-50/60 dark:bg-green-900/15'
                  : 'border-red-400 dark:border-red-700 bg-red-50/70 dark:bg-red-900/20 ring-2 ring-red-200 dark:ring-red-900/40'"
              >
                <div class="flex items-center justify-between gap-4">
                  <div class="flex items-start gap-3 min-w-0">
                    <div
                      class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 border"
                      :class="form.active
                        ? 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 border-green-300 dark:border-green-700'
                        : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 border-red-300 dark:border-red-700'"
                    >
                      <svg v-if="form.active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                      </svg>
                    </div>
                    <div class="min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Status do usuário</p>
                        <span
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide"
                          :class="form.active
                            ? 'bg-green-600 text-white'
                            : 'bg-red-600 text-white animate-pulse'"
                        >
                          {{ form.active ? 'Ativo' : 'Desativado' }}
                        </span>
                      </div>
                      <p
                        class="text-xs mt-1 font-medium"
                        :class="form.active
                          ? 'text-green-700 dark:text-green-300'
                          : 'text-red-700 dark:text-red-300'"
                      >
                        {{ form.active
                          ? 'Pode acessar normalmente o sistema.'
                          : 'Conta bloqueada — usuário não conseguirá efetuar login.' }}
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="form.active"
                    :disabled="!canManageSensitive"
                    @click="canManageSensitive && (form.active = !form.active)"
                    class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                    :class="form.active ? 'bg-green-600' : 'bg-red-600'"
                  >
                    <span
                      class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"
                      :class="form.active ? 'translate-x-6' : 'translate-x-1'"
                    ></span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!--
            Plantao: transformar o usuario em plantonista sem sair da governanca.
            A alternativa era a tela do proprio modulo (/plantao/plantonistas),
            que busca por nome -- fluxo separado para uma decisao que se toma
            justamente aqui, ao cadastrar ou revisar a pessoa.
          -->
          <div
            v-if="canManagePlantonistas"
            class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden"
          >
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <ClockIcon class="w-5 h-5 text-blue-500" />
                Plantão
              </h3>
            </div>

            <div class="p-6 space-y-4">
              <label class="flex items-start gap-3 cursor-pointer">
                <input
                  v-model="form.plantonista"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-700"
                />
                <span class="min-w-0">
                  <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Pode ser escalado no plantão
                  </span>
                  <span class="block text-xs text-slate-500 dark:text-slate-400">
                    Passa a aparecer no seletor da escala mensal. Desmarcar tira
                    da lista, mas não apaga histórico: as vagas já escaladas
                    guardam o nome no momento da escala.
                  </span>
                </span>
              </label>

              <div v-if="form.plantonista" class="max-w-[10rem]">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                  Posto
                </label>
                <input
                  v-model="form.plantonista_posto"
                  type="text"
                  maxlength="20"
                  placeholder="Sgt, Ten, Cb..."
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  Usado no relatório de passagem: "Sgt Leandro".
                </p>
              </div>

              <p
                v-if="plantonistaChanged"
                class="rounded-md bg-blue-50 px-3 py-2 text-xs text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"
              >
                {{ form.plantonista
                  ? 'Ao salvar, este usuário passa a poder ser escalado.'
                  : 'Ao salvar, este usuário sai da lista de escaláveis.' }}
              </p>
            </div>
          </div>

          <!-- Roles Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Cargos
              </h3>
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                {{ selectedRolesCount }}
              </span>
            </div>

            <div class="p-6 space-y-3">
              <div
                v-for="role in availableRoles"
                :key="role.id"
                @click="!checkRoleDisabled(role) && toggleRole(role.id)"
                class="relative flex items-center gap-4 p-4 rounded-xl border-2 transition-all cursor-pointer"
                :class="[
                  form.roles.includes(role.id)
                    ? 'border-teal-500 bg-teal-50/30 dark:bg-teal-900/10'
                    : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600',
                  checkRoleDisabled(role) ? 'opacity-50 cursor-not-allowed' : ''
                ]"
              >
                <!-- Role Icon - Design Pattern from Roles/Index -->
                <div
                  class="w-10 h-10 sm:w-14 sm:h-14 rounded-lg sm:rounded-xl flex items-center justify-center flex-shrink-0 border transition-colors"
                  :class="getRoleIconClass(role.hierarchy_level, form.roles.includes(role.id))"
                >
                  <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                  </svg>
                </div>

                <!-- Role Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-slate-900 dark:text-slate-100">{{ role.name }}</h4>
                  </div>
                  <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-1">{{ role.description }}</p>
                  <div class="flex items-center gap-4 mt-2">
                    <span class="text-xs text-slate-400 dark:text-slate-500">Nivel {{ role.hierarchy_level }}</span>
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ role.permissions_count || 0 }} permissoes</span>
                  </div>
                </div>

                <!-- Checkbox -->
                <div class="flex-shrink-0">
                  <div
                    class="w-6 h-6 rounded-md border-2 flex items-center justify-center transition-colors"
                    :class="form.roles.includes(role.id)
                      ? 'bg-teal-600 border-teal-600'
                      : 'border-slate-300 dark:border-slate-600'"
                  >
                    <svg v-if="form.roles.includes(role.id)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Direct Permissions Card - Renderizado via ACL Config -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <div class="flex justify-between items-center">
                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                  <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Permissoes Efetivas
                </h3>
                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                  {{ totalPermissionsCount }}
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                <span class="text-blue-600 dark:text-blue-400 font-medium">cargo</span> = permissoes fixas do cargo (nao editaveis).
                <span class="text-purple-600 dark:text-purple-400 font-medium">extra</span> = permissoes adicionais personalizaveis.
              </p>
            </div>

            <div class="p-6 space-y-4">
              <!-- Itera sobre modulos do config/permissions.php via $page.props.acl.modules -->
              <div v-for="(groups, moduleName) in aclModules" :key="moduleName" class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <div
                  @click="toggleModule(moduleName)"
                  class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/30 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900/50 active:bg-slate-200 dark:active:bg-slate-800 transition-colors select-none min-h-[48px]"
                >
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-90 text-blue-500': expandedModules.includes(moduleName) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <h4 class="font-semibold text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">{{ moduleName }}</h4>
                  </div>
                  <div class="flex items-center gap-3">
                    <SelectAllCheckbox
                      :state="estadoDoModulo(moduleName)"
                      :disabled="!moduloTemSelecionavel(moduleName)"
                      :title="`Marcar/desmarcar todas as permissoes extras de ${moduleName}`"
                      @toggle="alternarModulo(moduleName)"
                    />
                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded">
                      {{ getModuleSelectedCount(moduleName) }}/{{ getModuleTotalCount(groups) }}
                    </span>
                  </div>
                </div>

                <div v-show="expandedModules.includes(moduleName)" class="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                  <!-- Itera sobre grupos dentro do modulo -->
                  <div v-for="(permissions, groupName) in groups" :key="groupName" class="p-4 border-b border-slate-100 dark:border-slate-700 last:border-b-0">
                    <div class="flex items-center gap-2 mb-3">
                      <SelectAllCheckbox
                        :state="estadoDoGrupo(permissions)"
                        :disabled="!grupoTemSelecionavel(permissions)"
                        :title="`Marcar/desmarcar todas as permissoes extras de ${groupName}`"
                        @toggle="alternarGrupo(permissions)"
                      />
                      <h5 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ groupName }}</h5>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
                      <!-- Itera sobre acoes/permissoes do grupo -->
                      <div v-for="(slug, action) in permissions" :key="slug" class="flex items-center gap-2 sm:gap-1 min-h-[40px] sm:min-h-0">
                        <input
                          :id="`perm-${slug}`"
                          type="checkbox"
                          :value="slug"
                          :checked="isFromRole(slug) || form.direct_permissions.includes(slug)"
                          :disabled="isFromRole(slug) || isImmutablePermission(slug)"
                          @change="toggleExtraPermission(slug, $event)"
                          class="w-5 h-5 sm:w-4 sm:h-4 rounded focus:ring-2 dark:ring-offset-slate-800 dark:bg-slate-700 dark:border-slate-600 flex-shrink-0"
                          :class="[
                            isFromRole(slug)
                              ? 'text-blue-600 bg-blue-100 border-blue-300 cursor-not-allowed opacity-75'
                              : 'text-purple-600 bg-slate-100 border-slate-300 focus:ring-purple-500 dark:focus:ring-purple-600',
                            isImmutablePermission(slug) ? 'opacity-50 cursor-not-allowed' : ''
                          ]"
                        />
                        <label
                          :for="`perm-${slug}`"
                          class="text-sm select-none flex-1"
                          :class="[
                            isFromRole(slug)
                              ? 'text-blue-700 dark:text-blue-400 cursor-not-allowed'
                              : 'text-slate-700 dark:text-slate-300 cursor-pointer',
                            isImmutablePermission(slug) ? 'opacity-50 cursor-not-allowed' : ''
                          ]"
                          :title="slug"
                        >
                          {{ formatActionName(action) }}
                        </label>
                        <span
                          v-if="isFromRole(slug)"
                          class="text-[10px] px-1 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium flex-shrink-0"
                          title="Permissao fixa do cargo (nao editavel)"
                        >
                          cargo
                        </span>
                        <span
                          v-else-if="form.direct_permissions.includes(slug)"
                          class="text-[10px] px-1 py-0.5 rounded bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 font-medium flex-shrink-0"
                          title="Permissao extra adicionada"
                        >
                          extra
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Sticky Save Bar -->
        <div class="xl:hidden fixed bottom-0 inset-x-0 z-30 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 px-4 py-3 safe-area-bottom">
          <div class="flex items-center gap-3">
            <div class="flex-1 text-xs text-slate-500 dark:text-slate-400">
              <span class="font-medium text-slate-700 dark:text-slate-200">{{ totalPermissionsCount }}</span> permissoes
              <span class="mx-1">|</span>
              <span class="font-medium text-slate-700 dark:text-slate-200">{{ selectedRolesCount }}</span> cargos
            </div>
            <button
              type="submit"
              class="flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm disabled:opacity-75 disabled:cursor-not-allowed"
              :disabled="form.processing"
            >
              <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-else>Salvar</span>
            </button>
          </div>
        </div>

        <!-- Sidebar Actions (desktop only) -->
        <div class="hidden xl:block xl:col-span-1" ref="sidebarContainer">
          <div 
            ref="sidebarContent"
            :class="[
              'transition-all duration-300',
              isSticky ? 'fixed top-24 z-10' : ''
            ]"
            :style="isSticky ? { width: sidebarWidth + 'px' } : {}"
          >
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden p-6">
              <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Resumo das Alterações</h3>
              
              <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-slate-700 pb-2">
                  <span class="text-slate-600 dark:text-slate-400">Cargos Selecionados</span>
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ selectedRolesCount }}</span>
                </div>
                <div class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-slate-700 pb-2">
                  <span class="text-blue-600 dark:text-blue-400">Permissoes do Cargo</span>
                  <span class="font-medium text-blue-600 dark:text-blue-400">{{ rolePermissionsCount }}</span>
                </div>
                <div class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-slate-700 pb-2">
                  <span class="text-purple-600 dark:text-purple-400">Permissoes Extras</span>
                  <span class="font-medium text-purple-600 dark:text-purple-400">{{ extraPermissionsCount }}</span>
                </div>
                <div class="flex justify-between items-center text-sm font-medium pt-1">
                  <span class="text-slate-800 dark:text-slate-200">Total de Permissões</span>
                  <span class="text-blue-600 dark:text-blue-400">{{ totalPermissionsCount }}</span>
                </div>

                <div v-if="canManageSensitive && (orgaoChanged || activeChanged || passwordWillChange)" class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-700 space-y-2">
                  <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wide">Ações pendentes</p>
                  <div v-if="orgaoChanged" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    Órgão alterado
                  </div>
                  <div v-if="passwordWillChange" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                    Senha será redefinida
                  </div>
                  <div v-if="activeChanged" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                    <span class="w-1.5 h-1.5 rounded-full" :class="form.active ? 'bg-green-500' : 'bg-red-500'"></span>
                    {{ form.active ? 'Usuário será ativado' : 'Usuário será desativado' }}
                  </div>
                </div>
              </div>

              <div class="space-y-3">
                <button
                  type="submit"
                  class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm disabled:opacity-75 disabled:cursor-not-allowed"
                  :disabled="form.processing"
                >
                  <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span v-else>Salvar Alterações</span>
                </button>
                
                <Link
                  :href="route('admin.permissions.users.show', user.id)"
                  class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg transition-colors"
                >
                  Cancelar
                </Link>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

</template>

<script setup>
import { useHierarchy } from '@/Composables/useHierarchy';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import SelectAllCheckbox from '@/Components/Atoms/Input/SelectAllCheckbox.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  availableRoles: {
    type: Array,
    default: () => []
  },
  availablePermissions: {
    type: Array,
    default: () => []
  },
  availableOrgaos: {
    type: Array,
    default: () => []
  },
  canEditSuperAdmin: {
    type: Boolean,
    default: false
  },
  canManageSensitive: {
    type: Boolean,
    default: false
  },
  // Estado no modulo Plantao. Chega pronto do servidor via PlantonistaService:
  // esta tela nao conhece a tabela plantao_plantonistas.
  plantonista: {
    type: Object,
    default: () => ({ marcado: false, posto: null, ativo: false })
  },
  // Permissao PROPRIA do Plantao. Quem administra contas nao necessariamente
  // decide quem faz plantao, entao a secao some para quem nao tem o slug.
  canManagePlantonistas: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const page = usePage();
const { isSuperAdmin, canAssignRole, isImmutablePermission: checkImmutable } = useHierarchy();

const aclModules = computed(() => page.props.acl?.modules ?? {});
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const checkRoleDisabled = (role) => {
  if (isSuperAdmin.value) return false;
  if (role.slug === 'super-admin' && !props.canEditSuperAdmin) return true;
  return !canAssignRole(role);
};

const isImmutablePermission = (slug) => {
  return checkImmutable(slug);
};

const getRoleIconClass = (hierarchyLevel, isSelected) => {
  const baseClasses = {
    0: 'bg-red-50 dark:bg-red-900/20 text-red-500 border-red-200 dark:border-red-800',
    1: 'bg-amber-50 dark:bg-amber-900/20 text-amber-500 border-amber-200 dark:border-amber-800',
    2: 'bg-blue-50 dark:bg-blue-900/20 text-blue-500 border-blue-200 dark:border-blue-800',
    3: 'bg-purple-50 dark:bg-purple-900/20 text-purple-500 border-purple-200 dark:border-purple-800',
    4: 'bg-teal-50 dark:bg-teal-900/20 text-teal-500 border-teal-200 dark:border-teal-800',
    5: 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 border-indigo-200 dark:border-indigo-800',
    6: 'bg-pink-50 dark:bg-pink-900/20 text-pink-500 border-pink-200 dark:border-pink-800',
  };

  if (isSelected) {
    return 'bg-teal-100 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 border-teal-300 dark:border-teal-700';
  }

  return baseClasses[hierarchyLevel] || 'bg-slate-50 dark:bg-slate-900/20 text-slate-500 border-slate-200 dark:border-slate-700';
};

const toggleRole = (roleId) => {
  const index = form.roles.indexOf(roleId);
  if (index > -1) {
    form.roles.splice(index, 1);
  } else {
    form.roles.push(roleId);
  }
};

const rolePermissionsList = computed(() => props.user.role_permissions || []);

const getInitialDirectPermissions = () => {
  const diretas = props.user.direct_permissions?.map(p => p.name) || [];
  return diretas.filter(perm => !rolePermissionsList.value.includes(perm));
};

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  cpf: props.user.cpf ?? '',
  roles: props.user.roles?.map(r => r.id) || [],
  direct_permissions: getInitialDirectPermissions(),
  password: '',
  password_confirmation: '',
  active: props.user.active ?? true,
  orgao_principal_id: props.user.orgao_principal_id ?? null,
  plantonista: props.plantonista?.marcado ?? false,
  plantonista_posto: props.plantonista?.posto ?? ''
});

const initialOrgaoId = props.user.orgao_principal_id ?? null;
const initialActive = props.user.active ?? true;
const initialPlantonista = props.plantonista?.marcado ?? false;

const selectedOrgao = computed(() =>
  props.availableOrgaos.find(o => o.id === form.orgao_principal_id) ?? null
);

const orgaoChanged = computed(() => form.orgao_principal_id !== initialOrgaoId);
const activeChanged = computed(() => form.active !== initialActive);
const plantonistaChanged = computed(() => form.plantonista !== initialPlantonista);
const passwordWillChange = computed(() => form.password && form.password.length > 0);

const isFromRole = (slug) => {
  return rolePermissionsList.value.includes(slug);
};

const toggleExtraPermission = (slug, event) => {
  if (isFromRole(slug)) return;

  const isChecked = event.target.checked;
  const index = form.direct_permissions.indexOf(slug);

  if (isChecked && index === -1) {
    form.direct_permissions.push(slug);
  } else if (!isChecked && index > -1) {
    form.direct_permissions.splice(index, 1);
  }
};

// Todos contraidos na abertura: com o "marcar tudo" no cabecalho, liberar um
// modulo inteiro nao exige mais expandi-lo, e a lista completa cabe na tela.
const expandedModules = ref([]);

const selectedRolesCount = computed(() => form.roles.length);

const rolePermissionsCount = computed(() => rolePermissionsList.value.length);

const extraPermissionsCount = computed(() => form.direct_permissions.length);

const totalPermissionsCount = computed(() => {
  return rolePermissionsCount.value + extraPermissionsCount.value;
});

const toggleModule = (moduleName) => {
  const index = expandedModules.value.indexOf(moduleName);
  if (index > -1) {
    expandedModules.value.splice(index, 1);
  } else {
    expandedModules.value.push(moduleName);
  }
};

const getModuleTotalCount = (groups) => {
  let count = 0;
  for (const groupName in groups) {
    count += Object.keys(groups[groupName]).length;
  }
  return count;
};

/**
 * Slugs que o "marcar tudo" pode de fato alternar.
 *
 * O que vem do CARGO e o que e imutavel fica de fora: sao os mesmos dois casos
 * que ja desabilitam o checkbox individual. Se entrassem aqui, o botao de
 * modulo tentaria adicionar em `direct_permissions` uma permissao que a tela
 * mostra como fixa -- duplicando no payload o que o cargo ja concede.
 *
 * @param {Object} grupos mapa grupo -> { acao: slug }
 * @returns {string[]}
 */
const slugsSelecionaveis = (grupos) => {
  const slugs = [];
  for (const grupo in grupos) {
    for (const acao in grupos[grupo]) {
      const slug = grupos[grupo][acao];
      if (!isFromRole(slug) && !isImmutablePermission(slug)) {
        slugs.push(slug);
      }
    }
  }
  return slugs;
};

/** 'all' | 'some' | 'none' de um conjunto de slugs contra as extras marcadas. */
const estadoDaSelecao = (slugs) => {
  if (slugs.length === 0) return 'none';

  const marcados = slugs.filter(slug => form.direct_permissions.includes(slug)).length;

  if (marcados === 0) return 'none';
  return marcados === slugs.length ? 'all' : 'some';
};

/**
 * Marca o conjunto inteiro; se ja estava inteiro marcado, desmarca.
 *
 * O "some" cai no ramo de marcar, e nao no de desmarcar: quem clica no
 * cabecalho com metade selecionada quer completar, nao perder o que ja tinha.
 */
const alternarTodos = (slugs) => {
  if (slugs.length === 0) return;

  if (estadoDaSelecao(slugs) === 'all') {
    form.direct_permissions = form.direct_permissions.filter(slug => !slugs.includes(slug));
    return;
  }

  const faltando = slugs.filter(slug => !form.direct_permissions.includes(slug));
  form.direct_permissions = [...form.direct_permissions, ...faltando];
};

const estadoDoModulo = (moduleName) => estadoDaSelecao(slugsSelecionaveis(aclModules.value[moduleName] ?? {}));

const alternarModulo = (moduleName) => alternarTodos(slugsSelecionaveis(aclModules.value[moduleName] ?? {}));

const moduloTemSelecionavel = (moduleName) => slugsSelecionaveis(aclModules.value[moduleName] ?? {}).length > 0;

const estadoDoGrupo = (permissions) => estadoDaSelecao(slugsSelecionaveis({ grupo: permissions }));

const alternarGrupo = (permissions) => alternarTodos(slugsSelecionaveis({ grupo: permissions }));

const grupoTemSelecionavel = (permissions) => slugsSelecionaveis({ grupo: permissions }).length > 0;

const getModuleSelectedCount = (moduleName) => {
  const groups = aclModules.value[moduleName] ?? {};
  let count = 0;
  for (const groupName in groups) {
    for (const action in groups[groupName]) {
      const slug = groups[groupName][action];
      if (isFromRole(slug) || form.direct_permissions.includes(slug)) {
        count++;
      }
    }
  }
  return count;
};

const formatActionName = (action) => {
  const actionNames = {
    view: 'Visualizar',
    create: 'Criar',
    edit: 'Editar',
    delete: 'Deletar',
    manage: 'Gerenciar',
    approve: 'Aprovar',
    finalize: 'Finalizar',
    execute: 'Executar',
    export: 'Exportar',
    send: 'Enviar',
    logs: 'Ver Logs',
    cache: 'Cache',
    settings: 'Config'
  };
  return actionNames[action] || action.charAt(0).toUpperCase() + action.slice(1);
};

const submitForm = () => {
  form.put(route('admin.permissions.users.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Success
    }
  });
};

// Scroll Sticky Logic
import { onMounted, onUnmounted } from 'vue';

const sidebarContainer = ref(null);
const sidebarContent = ref(null);
const isSticky = ref(false);
const sidebarWidth = ref(0);

const handleScroll = () => {
  if (!sidebarContainer.value) return;
  
  const rect = sidebarContainer.value.getBoundingClientRect();
  // 96px = 24px (top-6) + 64px (navbar) + margin safety
  isSticky.value = rect.top < 96;
};

const updateWidth = () => {
  if (sidebarContainer.value) {
    sidebarWidth.value = sidebarContainer.value.clientWidth;
  }
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  window.addEventListener('resize', updateWidth);
  updateWidth();
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', updateWidth);
});
</script>
