import { onBeforeUnmount } from 'vue';
import axios from 'axios';
import Shepherd from 'shepherd.js';
import 'shepherd.js/dist/css/shepherd.css';
import { route } from 'ziggy-js';

/**
 * Composable que monta o tour de boas-vindas (Shepherd.js) exibido apenas
 * para usuarios cujo onboarding ainda nao foi concluido (vide flag Inertia
 * `auth.show_welcome_tour` compartilhada em HandleInertiaRequests::share).
 *
 * Steps usam seletores CSS para destacar pontos da UI. Se um seletor nao
 * existir na pagina, Shepherd ainda exibe o step como modal centralizado.
 *
 * Conclusao/skip dispara POST /onboarding/tour/complete para gravar
 * users.welcome_tour_completed_at = now() e impedir que o tour reapareca.
 */
export function useWelcomeTour({ userName = '' } = {}) {
  let tour = null;

  const buttons = {
    skip: {
      classes: 'shepherd-button-secondary',
      text: 'Pular',
      action() {
        this.cancel();
      },
    },
    back: {
      classes: 'shepherd-button-secondary',
      text: 'Voltar',
      action() {
        this.back();
      },
    },
    next: {
      classes: 'shepherd-button-primary',
      text: 'Proximo',
      action() {
        this.next();
      },
    },
    finish: {
      classes: 'shepherd-button-primary',
      text: 'Pronto',
      action() {
        this.complete();
      },
    },
  };

  function buildSteps() {
    const greeting = userName ? `, ${userName.split(' ')[0]}` : '';
    return [
      {
        id: 'welcome',
        title: `Bem-vindo${greeting}!`,
        text:
          'Sua conta esta ativa. Em menos de um minuto, vamos passar pelos pontos principais do SDC.',
        buttons: [buttons.skip, buttons.next],
      },
      {
        id: 'navigation',
        title: 'Menu de navegacao',
        text: 'A barra lateral concentra todos os modulos que voce tem acesso (PAE, COMPDEC, RAT, etc.).',
        attachTo: { element: 'nav, aside, [data-tour="sidebar"]', on: 'right' },
        buttons: [buttons.back, buttons.next],
      },
      {
        id: 'dashboard',
        title: 'Painel principal',
        text:
          'Aqui ficam seus widgets favoritos. Arraste para reordenar e use o botao de configuracoes para personalizar.',
        attachTo: { element: '[data-tour="dashboard-grid"], main', on: 'bottom' },
        buttons: [buttons.back, buttons.next],
      },
      {
        id: 'profile',
        title: 'Sua conta',
        text:
          'No menu do seu nome voce acessa perfil, troca de senha e logout. Mantenha seus dados atualizados.',
        attachTo: { element: '[data-tour="user-menu"]', on: 'bottom' },
        buttons: [buttons.back, buttons.next],
      },
      {
        id: 'finish',
        title: 'Tudo pronto',
        text:
          'Voce pode rever este tour pedindo ao suporte. Boa jornada na Defesa Civil!',
        buttons: [buttons.back, buttons.finish],
      },
    ];
  }

  function recordCompletion() {
    // Best-effort: se a request falhar, o tour reapareceria no proximo login,
    // o que e aceitavel. Nao mostramos erro pro usuario.
    axios
      .post(route('onboarding.tour.complete'))
      .catch(() => {});
  }

  function startWelcomeTour() {
    if (tour) return tour;

    tour = new Shepherd.Tour({
      useModalOverlay: true,
      defaultStepOptions: {
        scrollTo: { behavior: 'smooth', block: 'center' },
        cancelIcon: { enabled: true },
        classes: 'shepherd-sdc',
      },
    });

    buildSteps().forEach((step) => tour.addStep(step));

    tour.on('complete', recordCompletion);
    tour.on('cancel', recordCompletion);

    tour.start();
    return tour;
  }

  onBeforeUnmount(() => {
    if (tour && tour.isActive?.()) {
      tour.cancel();
    }
    tour = null;
  });

  return { startWelcomeTour };
}
