import { ref, watch } from 'vue';

// Estado reativo compartilhado (module-level)
const isDarkMode = ref(true); // Default: dark (sistema atual)
const isInitialized = ref(false);

/**
 * Aplica o tema ao documento
 */
function applyTheme() {
  if (typeof document === 'undefined') return;

  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
}

/**
 * Inicializa o tema baseado em localStorage ou preferência do sistema
 */
function initTheme() {
  if (isInitialized.value) return;
  if (typeof window === 'undefined') return;

  const savedTheme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

  // Prioridade: localStorage > system preference > default dark
  isDarkMode.value = savedTheme ? savedTheme === 'dark' : prefersDark;

  applyTheme();
  isInitialized.value = true;
}

// Observar mudanças no isDarkMode
watch(isDarkMode, applyTheme);

export function useTheme() {
  // Inicializar imediatamente na primeira chamada
  if (!isInitialized.value && typeof window !== 'undefined') {
    initTheme();
  }

  /**
   * Alterna entre dark/light mode
   */
  function toggleTheme() {
    console.log('🎨 Toggle theme called! Current:', isDarkMode.value);
    isDarkMode.value = !isDarkMode.value;
    console.log('🎨 New theme:', isDarkMode.value ? 'dark' : 'light');
    localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light');
    applyTheme();
    console.log('🎨 HTML class:', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
  }

  return {
    isDarkMode,
    toggleTheme,
    initTheme
  };
}
