<template>
  <button
    @click="$emit('click')"
    class="hamburger-button"
    :class="{ 'is-open': isOpen }"
    :aria-label="isOpen ? 'Fechar menu' : 'Abrir menu'"
    :aria-expanded="isOpen"
    type="button"
  >
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
  </button>
</template>

<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['click']);
</script>

<style scoped>
.hamburger-button {
  position: relative;
  width: 44px;
  height: 44px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 10px;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.hamburger-button:hover {
  background: rgba(0, 0, 0, 0.05);
}

.hamburger-button:active {
  transform: scale(0.95);
}

.hamburger-line {
  width: 24px;
  height: 2px;
  background-color: currentColor;
  border-radius: 2px;
  transition: all 0.3s ease;
  transform-origin: center;
}

/* Animação quando aberto */
.hamburger-button.is-open .hamburger-line:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}

.hamburger-button.is-open .hamburger-line:nth-child(2) {
  opacity: 0;
  transform: scaleX(0);
}

.hamburger-button.is-open .hamburger-line:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .hamburger-button:hover {
    background: rgba(255, 255, 255, 0.1);
  }
}
</style>
