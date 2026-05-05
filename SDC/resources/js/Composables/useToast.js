export function useToast() {
  const show = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
  };

  return { show, toast: show };
}
