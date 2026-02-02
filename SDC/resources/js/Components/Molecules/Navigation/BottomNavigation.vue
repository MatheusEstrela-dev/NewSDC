<script setup>
import { computed, inject } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  HomeIcon,
  DocumentTextIcon,
  ClipboardDocumentCheckIcon,
  ShieldExclamationIcon,
  Bars3Icon,
} from '@heroicons/vue/24/outline';
import {
  HomeIcon as HomeIconSolid,
  DocumentTextIcon as DocumentTextIconSolid,
  ClipboardDocumentCheckIcon as ClipboardDocumentCheckIconSolid,
  ShieldExclamationIcon as ShieldExclamationIconSolid,
} from '@heroicons/vue/24/solid';

const openSidebar = inject('openSidebar', () => {});

const page = usePage();

const navigationItems = computed(() => {
  const items = [
    {
      name: 'Inicio',
      href: route('dashboard'),
      icon: HomeIcon,
      iconActive: HomeIconSolid,
      active: route().current('dashboard'),
      show: true,
    },
    {
      name: 'RAT',
      href: route().has('rat.index') ? route('rat.index') : route('dashboard'),
      icon: DocumentTextIcon,
      iconActive: DocumentTextIconSolid,
      active: route().current('rat.*'),
      show: route().has('rat.index') || route().has('rat.create'),
    },
    {
      name: 'Demandas',
      href: route().has('demandas.index') ? route('demandas.index') : route('dashboard'),
      icon: ClipboardDocumentCheckIcon,
      iconActive: ClipboardDocumentCheckIconSolid,
      active: route().current('demandas.*'),
      show: route().has('demandas.index'),
    },
    {
      name: 'PAE',
      href: route().has('pae.protocolos.index') ? route('pae.protocolos.index') : route('dashboard'),
      icon: ShieldExclamationIcon,
      iconActive: ShieldExclamationIconSolid,
      active: route().current('pae.*'),
      show: route().has('pae.protocolos.index') || route().has('pae.index'),
    },
  ];

  return items.filter(item => item.show);
});

function handleMenuClick() {
  openSidebar();
}
</script>

<template>
  <nav class="bottom-nav">
    <div class="bottom-nav-container">
      <Link
        v-for="item in navigationItems"
        :key="item.name"
        :href="item.href"
        class="bottom-nav-item"
        :class="{ 'is-active': item.active }"
      >
        <component
          :is="item.active ? item.iconActive : item.icon"
          class="bottom-nav-icon"
        />
        <span class="bottom-nav-label">{{ item.name }}</span>
      </Link>

      <button
        type="button"
        class="bottom-nav-item"
        @click="handleMenuClick"
      >
        <Bars3Icon class="bottom-nav-icon" />
        <span class="bottom-nav-label">Menu</span>
      </button>
    </div>
  </nav>
</template>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 45;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 1) 100%);
  border-top: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding-bottom: env(safe-area-inset-bottom, 0);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
}

/* Dark mode */
:global(.dark) .bottom-nav {
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.98) 0%, rgba(15, 23, 42, 1) 100%);
  border-top-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
}

.bottom-nav-container {
  display: flex;
  justify-content: space-around;
  align-items: center;
  max-width: 100%;
  padding: 0.5rem 0.25rem;
}

.bottom-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 64px;
  min-height: 48px;
  padding: 0.375rem 0.75rem;
  border-radius: 12px;
  color: #64748b;
  text-decoration: none;
  transition: all 0.2s ease;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
  background: transparent;
  border: none;
  cursor: pointer;
}

.bottom-nav-item:active {
  transform: scale(0.95);
}

.bottom-nav-item.is-active {
  color: #2563eb;
  background: rgba(37, 99, 235, 0.1);
}

:global(.dark) .bottom-nav-item {
  color: #94a3b8;
}

:global(.dark) .bottom-nav-item.is-active {
  color: #60a5fa;
  background: rgba(96, 165, 250, 0.15);
}

.bottom-nav-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.bottom-nav-item.is-active .bottom-nav-icon {
  transform: scale(1.1);
}

.bottom-nav-label {
  font-size: 0.625rem;
  font-weight: 500;
  margin-top: 0.125rem;
  letter-spacing: 0.01em;
  white-space: nowrap;
}

/* Responsividade: Esconder em telas maiores que mobile */
@media (min-width: 768px) {
  .bottom-nav {
    display: none;
  }
}

/* Ajustes para telas muito pequenas */
@media (max-width: 360px) {
  .bottom-nav-item {
    min-width: 56px;
    padding: 0.25rem 0.5rem;
  }

  .bottom-nav-icon {
    width: 22px;
    height: 22px;
  }

  .bottom-nav-label {
    font-size: 0.5625rem;
  }
}
</style>
