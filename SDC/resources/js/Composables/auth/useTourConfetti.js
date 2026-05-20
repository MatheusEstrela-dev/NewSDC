/**
 * Dispara um efeito de confete CSS no passo final do tour de boas-vindas.
 * Implementacao zero-dependencia: ~24 <div>s posicionados com animacao
 * declarada em shepherd-sdc.css. Auto-cleanup apos a duracao da animacao.
 */
const COLORS = [
  '#10b981', // emerald
  '#06b6d4', // cyan
  '#f59e0b', // amber
  '#ef4444', // red
  '#8b5cf6', // violet
  '#f1f5f9', // slate-100
];

const PIECE_COUNT = 60;
const ANIMATION_MS = 3300;

export function useTourConfetti() {
  function fireConfetti() {
    if (typeof document === 'undefined') return;

    const container = document.createElement('div');
    container.className = 'sdc-confetti-container';
    container.setAttribute('aria-hidden', 'true');

    for (let i = 0; i < PIECE_COUNT; i++) {
      const piece = document.createElement('div');
      piece.className = 'sdc-confetti-piece';

      const left = Math.random() * 100;
      const color = COLORS[Math.floor(Math.random() * COLORS.length)];
      const delay = Math.random() * 0.4;
      const duration = 2.6 + Math.random() * 1.2;
      const drift = (Math.random() - 0.5) * 200;
      const rotate = Math.random() * 360;
      const scale = 0.7 + Math.random() * 0.8;

      piece.style.left = `${left}%`;
      piece.style.background = color;
      piece.style.animationDelay = `${delay}s`;
      piece.style.animationDuration = `${duration}s`;
      piece.style.transform = `translateX(${drift}px) rotate(${rotate}deg) scale(${scale})`;

      container.appendChild(piece);
    }

    document.body.appendChild(container);

    setTimeout(() => {
      container.remove();
    }, ANIMATION_MS);
  }

  return { fireConfetti };
}
