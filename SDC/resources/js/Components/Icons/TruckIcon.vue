<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    :class="className"
  >
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H10.5a4.5 4.5 0 00-1.41-.84m-2 .25a4.5 4.5 0 00-2.09-.75H3.375c-.621 0-1.125.504-1.125 1.125v3.75m0 0v3.75c0 .621.504 1.125 1.125 1.125h1.5m12-9v-6a.75.75 0 00-.75-.75h-4.5a.75.75 0 00-.75.75v6m3.75 0v-3a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75v3"
    />
  </svg>
</template>

<script setup>
defineProps({
  className: {
    type: String,
    default: 'w-5 h-5',
  },
});
</script>

