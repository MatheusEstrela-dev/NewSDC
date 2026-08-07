<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import FlashNotification from '@/Components/Molecules/FlashNotification.vue';
import ToastContainer from '@/Components/Atoms/Toast/ToastContainer.vue';

const page = usePage();
const cidadao = computed(() => page.props.auth?.cidadao ?? null);

const navLinks = [
  { label: 'Catálogo', route: 'portal.treinamento.catalogo' },
  { label: 'Minhas Inscrições', route: 'portal.treinamento.inscricoes.index' },
  { label: 'Certificados', route: 'portal.treinamento.certificados.index' },
];

function sair() {
  router.post(route('portal.treinamento.logout'));
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
    <ToastContainer />
    <FlashNotification />

    <header class="bg-[#06315c] text-white shadow-md">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <Link :href="route('portal.treinamento.catalogo')" class="flex items-center gap-3">
            <img src="/imgs/logo-defesa-civil.png" alt="Defesa Civil MG" class="h-9 w-auto" />
            <span class="font-semibold text-sm sm:text-base">Portal de Treinamentos</span>
          </Link>

          <nav class="hidden sm:flex items-center gap-6 text-sm font-medium">
            <Link
              v-for="link in navLinks"
              :key="link.route"
              :href="route(link.route)"
              class="opacity-80 hover:opacity-100 transition-opacity"
              :class="{ 'opacity-100 border-b-2 border-amber-400': route().current(link.route) }"
            >
              {{ link.label }}
            </Link>
          </nav>

          <div class="flex items-center gap-4">
            <span v-if="cidadao" class="hidden md:inline text-sm opacity-90">{{ cidadao.name }}</span>
            <button type="button" @click="sair" class="text-sm font-medium opacity-80 hover:opacity-100 transition-opacity">
              Sair
            </button>
          </div>
        </div>

        <nav class="flex sm:hidden items-center gap-4 pb-3 text-sm font-medium overflow-x-auto">
          <Link
            v-for="link in navLinks"
            :key="link.route"
            :href="route(link.route)"
            class="whitespace-nowrap opacity-80 hover:opacity-100 transition-opacity"
            :class="{ 'opacity-100 border-b-2 border-amber-400': route().current(link.route) }"
          >
            {{ link.label }}
          </Link>
        </nav>
      </div>
    </header>

    <main class="px-4 sm:px-6 lg:px-8 py-8">
      <slot />
    </main>

    <footer class="text-center text-xs text-slate-400 dark:text-slate-600 py-6">
      &copy; {{ new Date().getFullYear() }} Governo do Estado de Minas Gerais — Sistema Integrado de Defesa Civil
    </footer>
  </div>
</template>
