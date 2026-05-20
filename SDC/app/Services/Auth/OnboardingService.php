<?php

namespace App\Services\Auth;

use App\Mail\UserOnboardingMail;
use App\Models\User;
use App\Models\UserStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Service responsavel pelo fluxo de onboarding de usuarios criados pelo admin:
 *  - Gera senha provisoria forte;
 *  - Persiste o usuario com status='pending', must_change_password=true e
 *    pending_expires_at = now()+24h;
 *  - Dispara o e-mail de boas-vindas com as credenciais;
 *  - Conclui o primeiro acesso (troca da senha) promovendo a conta para 'active';
 *  - Marca a conclusao do tour de boas-vindas.
 *
 * Mantido enxuto e com responsabilidade unica (SRP): nao decide quem pode criar,
 * nao monta payload de e-mail, nao redireciona. Coordena modelo + Mail + history.
 */
class OnboardingService
{
    private const PROVISIONAL_PASSWORD_LENGTH = 12;
    private const PENDING_TTL_HOURS = 24;

    /**
     * Cria usuario com status='pending' e dispara o e-mail de onboarding.
     *
     * @param array{name:string,email:string,cpf:string,orgao_principal_id?:int|null} $data
     */
    public function createPendingUser(array $data): User
    {
        $plainPassword = $this->generateProvisionalPassword();

        return DB::transaction(function () use ($data, $plainPassword) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'cpf' => $data['cpf'],
                'orgao_principal_id' => $data['orgao_principal_id'] ?? null,
                'password' => Hash::make($plainPassword),
                'status' => 'pending',
                'active' => true,
                'must_change_password' => true,
                'pending_expires_at' => now()->addHours(self::PENDING_TTL_HOURS),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            UserStatusHistory::logStatusChange(
                $user,
                'new',
                'pending',
                'Cadastro inicial — aguardando primeiro acesso e troca de senha provisoria'
            );

            Mail::to($user->email)->queue(new UserOnboardingMail($user, $plainPassword));

            return $user;
        });
    }

    /**
     * Conclui o primeiro acesso: persiste nova senha e promove status para 'active'.
     *
     * email_verified_at e setado aqui propositalmente: o usuario so chegou a esse
     * ponto porque (a) recebeu a senha provisoria no email cadastrado, (b) logou
     * com ela e (c) esta trocando para uma senha pessoal. Isso e prova de posse
     * do email — equivalente a um link de verificacao classico, sem step extra.
     */
    public function completeFirstAccess(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword) {
            $oldStatus = $user->status;
            $wasUnverified = $user->email_verified_at === null;

            $user->forceFill([
                'password' => Hash::make($newPassword),
                'remember_token' => Str::random(60),
                'must_change_password' => false,
                'password_changed_at' => now(),
                'pending_expires_at' => null,
                'status' => 'active',
                'active' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            if ($oldStatus !== 'active') {
                $reason = 'Primeiro acesso concluido — senha provisoria substituida';
                if ($wasUnverified) {
                    $reason .= ' e email verificado (login com senha enviada por email)';
                }

                UserStatusHistory::logStatusChange(
                    $user,
                    $oldStatus,
                    'active',
                    $reason
                );
            }
        });
    }

    /**
     * Marca a conclusao (ou skip) do tour de boas-vindas.
     */
    public function completeTour(User $user): void
    {
        if ($user->welcome_tour_completed_at !== null) {
            return;
        }

        $user->forceFill(['welcome_tour_completed_at' => now()])->save();
    }

    /**
     * Gera senha provisoria forte: letras + numeros + simbolos.
     */
    private function generateProvisionalPassword(): string
    {
        return Str::password(
            length: self::PROVISIONAL_PASSWORD_LENGTH,
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false,
        );
    }
}
