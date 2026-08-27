<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Services;

use App\Models\User;
use App\Models\UserIntegration;

/**
 * Responde, para um usuario, quais canais de notificacao ele pode usar de fato e
 * o que falta para liberar os demais.
 *
 * Existe para a tela nunca oferecer um canal que o backend vai ignorar. Antes o
 * SettingsModal tinha tres checkboxes fixos: Telegram, que funciona desde a
 * integracao, nao aparecia; E-mail e Push apareciam sem nenhum channel por tras,
 * gravavam a preferencia e nao mudavam nada.
 *
 * O motivo viaja junto com a disponibilidade porque desabilitar sem explicar
 * apenas troca uma mentira por um misterio: o usuario precisa saber que falta
 * vincular o Telegram, nao que "nao pode".
 *
 * Custo: no maximo uma query (a de Telegram), so quando ha usuario.
 */
class CanaisDisponiveis
{
    /**
     * Catalogo de canais do usuario, na ordem declarada em config.
     *
     * @return list<array{id: string, label: string, descricao: string, disponivel: bool, motivo: string|null}>
     */
    public function paraUsuario(User $user): array
    {
        $estado = $this->estadoDosCanais($user);

        $canais = [];

        foreach ((array) config('notificacoes.canais', []) as $id => $meta) {
            [$disponivel, $motivo] = $estado[$id] ?? [false, 'Canal indisponivel.'];

            $canais[] = [
                'id' => $id,
                'label' => $meta['label'] ?? $id,
                'descricao' => $meta['descricao'] ?? '',
                'disponivel' => $disponivel,
                // Motivo so faz sentido quando bloqueia; mandar sempre poluiria a tela.
                'motivo' => $disponivel ? null : $motivo,
            ];
        }

        return $canais;
    }

    /**
     * Ids dos canais que este usuario pode efetivamente usar.
     *
     * @return list<string>
     */
    public function idsDisponiveis(User $user): array
    {
        return array_values(array_map(
            fn (array $canal): string => $canal['id'],
            array_filter($this->paraUsuario($user), fn (array $canal): bool => $canal['disponivel']),
        ));
    }

    /**
     * Regra de disponibilidade de cada canal: [disponivel, motivo].
     *
     * E-mail exige apenas endereco cadastrado, e nao email_verified_at: hoje so
     * 63 dos 1106 usuarios estao verificados, entao exigir verificacao
     * desligaria o canal para praticamente todo mundo. A protecao contra envio
     * indesejado ja e o opt-in -- canal_email nasce false.
     *
     * @return array<string, array{0: bool, 1: string|null}>
     */
    private function estadoDosCanais(User $user): array
    {
        return [
            'canal_sistema' => [true, null],

            'canal_email' => [
                is_string($user->email) && $user->email !== '',
                'Cadastre um e-mail em Meu Perfil.',
            ],

            'canal_push' => [
                $this->vapidConfigurado(),
                'Push nao esta configurado neste servidor.',
            ],

            'canal_telegram' => [
                $this->temTelegramVinculado((int) $user->getKey()),
                'Conecte sua conta em Configuracoes > Integracoes.',
            ],
        ];
    }

    /**
     * Mesmo criterio do TelegramChannel: integration ativa E verificada. Se os
     * dois divergissem, a tela ofereceria um canal que o channel descartaria em
     * silencio.
     */
    private function temTelegramVinculado(int $userId): bool
    {
        return UserIntegration::query()
            ->forUser($userId)
            ->provider(UserIntegration::PROVIDER_TELEGRAM)
            ->active()
            ->verified()
            ->exists();
    }

    /**
     * Sem par de chaves VAPID o navegador nao consegue nem se inscrever, entao o
     * canal esta indisponivel para todos os usuarios deste servidor.
     */
    private function vapidConfigurado(): bool
    {
        return is_string(config('webpush.vapid.public_key'))
            && config('webpush.vapid.public_key') !== ''
            && is_string(config('webpush.vapid.private_key'))
            && config('webpush.vapid.private_key') !== '';
    }
}
