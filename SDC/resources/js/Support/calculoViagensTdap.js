/**
 * Espelho de App\Modules\Tdap\Support\CalculoDeViagens, para o modal de
 * alocacao mostrar o numero antes de salvar. A fonte da regra e o backend: no
 * modo automatico o servidor recalcula e grava o valor dele.
 *
 *   viagens = agua_prevista / capacidade, arredondado:
 *     parte decimal abaixo de 0,5 (0,4 inclusive) -> para baixo
 *     parte decimal de 0,5 em diante              -> para cima
 *     nunca menos que 1
 */
export const MINIMO_DE_VIAGENS = 1;

// Mesma limpeza do backend: 0.7 / 0.2 = 3.4999999999999996 tem de virar 4.
const CASAS_DE_PRECISAO = 6;

export function viagensNecessarias(aguaPrevistaM3, capacidadeM3) {
  const agua = Number(aguaPrevistaM3) || 0;
  const capacidade = Number(capacidadeM3) || 0;

  if (capacidade <= 0) {
    return MINIMO_DE_VIAGENS;
  }

  const razao = Number((agua / capacidade).toFixed(CASAS_DE_PRECISAO));

  // Math.round arredonda .5 para cima em numeros positivos -- o mesmo
  // PHP_ROUND_HALF_UP do backend, ja que agua e capacidade nunca sao negativas.
  return Math.max(MINIMO_DE_VIAGENS, Math.round(razao));
}
