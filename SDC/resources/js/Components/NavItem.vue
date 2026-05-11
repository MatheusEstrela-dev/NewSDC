<template>
  <component
    :is="external ? 'a' : Link"
    :href="href"
    :target="external ? '_blank' : undefined"
    :prefetch="!external"
    :cache-for="!external ? '30s' : undefined"
    :class="[
      'nav-item',
      {
        'is-active': active,
        'is-submenu': isSubmenu,
        'is-collapsed': collapsed,
      }
    ]"
    :title="collapsed ? tooltipText : ''"
    @click="handleClick"
  >
    <svg v-if="icon === 'dashboard'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
    </svg>
    <svg v-else-if="icon === 'document'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <svg v-else-if="icon === 'book'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
    </svg>
    <svg v-else-if="icon === 'checkbadge'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
    </svg>
    <svg v-else-if="icon === 'document-check'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
    </svg>
    <svg v-else-if="icon === 'scale'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
    </svg>
    <svg v-else-if="icon === 'heart'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
    </svg>
    <svg v-else-if="icon === 'building'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
    </svg>
    <svg v-else-if="icon === 'academic'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
    </svg>
    <svg v-else-if="icon === 'clock'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <svg v-else-if="icon === 'cloud'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
    </svg>
    <svg v-else-if="icon === 'lock'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
    </svg>
    <svg v-else-if="icon === 'shield'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
    </svg>
    <svg v-else-if="icon === 'code'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
    </svg>
    <svg v-else-if="icon === 'map'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 4m0 16l6-3m-6 3V4m6 16l5.447 2.724A1 1 0 0021 21.382V10.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 4" />
    </svg>
    <svg v-else-if="icon === 'server'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
    </svg>
    <svg v-else-if="icon === 'cisterna'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7c0-1.657 2.686-3 6-3s6 1.343 6 3-2.686 3-6 3-6-1.343-6-3z" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7v10c0 1.657 2.686 3 6 3s6-1.343 6-3V7" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12c0 1.657 2.686 3 6 3s6-1.343 6-3" />
    </svg>
    <svg v-else-if="icon === 'inventory'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25l-9-5.25-9 5.25 9 5.25 9-5.25z" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8.25v7.5l9 5.25 9-5.25v-7.5" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13.5V21" />
    </svg>
    <svg v-else-if="icon === 'rat-clipboard'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
    </svg>
    <svg v-else-if="icon === 'pae-bolt'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
    </svg>
    <svg v-else-if="icon === 'logs-list'" class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h8" />
    </svg>
    <svg v-else class="nav-item-icon" fill="currentColor" viewBox="0 0 8 8">
      <circle cx="4" cy="4" r="3" />
    </svg>
    <span v-show="!collapsed" class="nav-item-text">
      <slot />
    </span>
    <span v-if="active && !isSubmenu && !collapsed" class="nav-item-dot"></span>
  </component>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, inject, useSlots } from 'vue';

const slots = useSlots();
const onNavItemClick = inject('onNavItemClick', null);

const props = defineProps({
  href: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
  icon: {
    type: String,
    default: 'dot',
  },
  isSubmenu: {
    type: Boolean,
    default: false,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
  external: {
    type: Boolean,
    default: false,
  },
});

const tooltipText = computed(() => {
  if (!props.collapsed || !slots.default) return '';
  const slotContent = slots.default();
  if (slotContent && slotContent[0] && slotContent[0].children) {
    return typeof slotContent[0].children === 'string'
      ? slotContent[0].children
      : slotContent[0].children.toString();
  }
  return '';
});

function handleClick(event) {
  createRipple(event);
  if (onNavItemClick) {
    onNavItemClick();
  }
}

function createRipple(event) {
  const button = event.currentTarget;
  const circle = document.createElement('span');
  const diameter = Math.max(button.clientWidth, button.clientHeight);
  const radius = diameter / 2;

  circle.style.width = circle.style.height = `${diameter}px`;
  circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
  circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
  circle.classList.add('ripple');

  button.appendChild(circle);

  setTimeout(() => circle.remove(), 600);
}
</script>

<style scoped>
.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  font-size: 0.9375rem;
  justify-content: flex-start;
  overflow: hidden;
}

.nav-item::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 0;
  height: 100%;
  background: #3b82f6;
  transition: width 0.3s ease;
  z-index: 1;
}

.nav-item:hover::before {
  width: 4px;
}

.nav-item.is-collapsed {
  padding: 0.75rem;
  justify-content: center;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: white;
  padding-left: calc(1.25rem + 4px);
}

.nav-item.is-collapsed:hover {
  padding-left: 0.75rem;
}

.nav-item.is-active {
  background: rgba(59, 130, 246, 0.15);
  color: #3b82f6;
  border-left: 3px solid #3b82f6;
  padding-left: calc(1.25rem - 3px);
}

.nav-item.is-active.is-collapsed {
  border-left: none;
  padding-left: 0.75rem; /* mantém alinhamento central no modo retraído */
  box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.35);
  border-radius: 12px;
  margin: 0 0.5rem;
}

.nav-item.is-submenu {
  padding-left: 2.5rem;
  font-size: 0.875rem;
}

.nav-item.is-submenu.is-active {
  padding-left: calc(2.5rem - 3px);
}

.nav-item-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.nav-item-text {
  flex: 1;
}

.nav-item-dot {
  width: 8px;
  height: 8px;
  background: #3b82f6;
  border-radius: 50%;
  flex-shrink: 0;
}

/* Tablet (768px - 1023px): Estilo controlado pelo parent Sidebar */
@media (min-width: 768px) and (max-width: 1023px) {
  /* Estado collapsed padrao */
  :global(.sidebar:not(.is-mobile-open)) .nav-item {
    padding: 0.75rem;
    justify-content: center;
  }

  :global(.sidebar:not(.is-mobile-open)) .nav-item-text {
    display: none;
    opacity: 0;
    visibility: hidden;
  }

  :global(.sidebar:not(.is-mobile-open)) .nav-item-dot {
    display: none;
  }

  :global(.sidebar:not(.is-mobile-open)) .nav-item.is-submenu {
    padding: 0.75rem;
  }

  :global(.sidebar:not(.is-mobile-open)) .nav-item.is-active {
    border-left: none;
    padding: 0.75rem;
    box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.35);
    border-radius: 12px;
    margin: 0 0.5rem;
  }

  :global(.sidebar:not(.is-mobile-open)) .nav-item-icon {
    margin: 0;
  }

  /* Estado expandido quando drawer aberto */
  :global(.sidebar.is-mobile-open) .nav-item {
    padding: 0.75rem 1.25rem;
    justify-content: flex-start;
    gap: 0.75rem;
  }

  :global(.sidebar.is-mobile-open) .nav-item-text {
    display: block;
    opacity: 1;
    visibility: visible;
  }

  :global(.sidebar.is-mobile-open) .nav-item-dot {
    display: block;
  }

  :global(.sidebar.is-mobile-open) .nav-item.is-submenu {
    padding-left: 2.5rem;
  }

  :global(.sidebar.is-mobile-open) .nav-item.is-active {
    border-left: 3px solid #3b82f6;
    padding-left: calc(1.25rem - 3px);
    box-shadow: none;
    border-radius: 0;
    margin: 0;
  }

  :global(.sidebar.is-mobile-open) .nav-item.is-submenu.is-active {
    padding-left: calc(2.5rem - 3px);
  }
}

:global(.ripple) {
  position: absolute;
  border-radius: 50%;
  transform: scale(0);
  animation: ripple 0.6s linear;
  background-color: rgba(255, 255, 255, 0.3);
  pointer-events: none;
}

@keyframes ripple {
  to {
    transform: scale(4);
    opacity: 0;
  }
}
</style>

