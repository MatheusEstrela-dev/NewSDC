import { computed, ref } from 'vue';

/**
 * Lista curta com "ver todos": mostra os primeiros `limite` itens e deixa o
 * usuario abrir o resto. Fonte unica dos paineis de pessoas por curso e por
 * municipio, que recebem a lista inteira do servidor.
 */
export function useListaVerTodos(itens, limite = 8) {
  const aberta = ref(false);

  const visiveis = computed(() => (aberta.value ? itens() : itens().slice(0, limite)));
  const temMais = computed(() => itens().length > limite);

  const alternar = () => { aberta.value = !aberta.value; };

  return { aberta, visiveis, temMais, alternar };
}
