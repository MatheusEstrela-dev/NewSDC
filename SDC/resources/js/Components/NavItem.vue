<template>
  <Link
    :href="href"
    :class="[
      'nav-item',
      {
        'is-active': active,
        'is-submenu': isSubmenu,
        'is-collapsed': collapsed,
      }
    ]"
    :title="collapsed ? tooltipText : ''"
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
    <svg v-else class="nav-item-icon" fill="currentColor" viewBox="0 0 8 8">
      <circle cx="4" cy="4" r="3" />
    </svg>
    <span v-show="!collapsed" class="nav-item-text">
      <slot />
    </span>
    <span v-if="active && !isSubmenu && !collapsed" class="nav-item-dot"></span>
  </Link>
</template>

<script setup>
import { computed, useSlots } from 'vue';
import { Link } from '@inertiajs/vue3';

const slots = useSlots();

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
</script>

<style scoped>
.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.2s;
  position: relative;
  font-size: 0.9375rem;
  justify-content: flex-start;
}

.nav-item.is-collapsed {
  padding: 0.75rem;
  justify-content: center;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: white;
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
</style>

