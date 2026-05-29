<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\EmailChange\CodeExpiredException;
use App\Exceptions\Auth\EmailChange\EmailAlreadyInUseException;
use App\Exceptions\Auth\EmailChange\InvalidCodeException;
use App\Exceptions\Auth\EmailChange\MaxResendsReachedException;
use App\Exceptions\Auth\EmailChange\ResendCooldownException;
use App\Exceptions\Auth\EmailChange\SameEmailException;
use App\Exceptions\Auth\EmailChange\TooManyAttemptsException;
use App\Mail\EmailChangeNoticeMail;
use App\Mail\EmailChangeVerificationCodeMail;
use App\Models\AuditLog;
use App\Models\EmailChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Orquestra o ciclo de vida de uma troca de e-mail com magic code.
 * - requestChange: registra pedido, gera codigo, dispara 2 e-mails
 * - confirmChange: valida codigo (constant-time) e promove o e-mail
 * - resendCode: regera codigo respeitando cooldown e teto de reenvios
 *
 * Mantido enxuto (SRP) seguindo o padrao de OnboardingService.
 */
class EmailChangeService
{
    public function requestChange(
        User $user,
        string $newEmail,
        Request $request,
        ?User $byAdmin = null,
    ): EmailChangeRequest {
        $newEmail = strtolower(trim($newEmail));

        if ($newEmail === strtolower($user->email)) {
            throw new SameEmailException();
        }

        $emailTaken = User::where('email', $newEmail)
            ->where('id', '!=', $user->id)
            ->exists();
        if ($emailTaken) {
            throw new EmailAlreadyInUseException();
        }

        return DB::transaction(function () use ($user, $newEmail, $request, $byAdmin) {
            // Invalida pedidos ativos anteriores (apenas 1 pending por user)
            EmailChangeRequest::activeFor($user->id)
                ->update(['cancelled_at' => now()]);

            $code = $this->generateCode();

            $ecr = EmailChangeRequest::create([
                'user_id'               => $user->id,
                'current_email'         => $user->email,
                'new_email'             => $newEmail,
                'code_hash'             => Hash::make($code),
                'expires_at'            => now()->addMinutes(EmailChangeRequest::TTL_MINUTES),
                'requested_ip'          => $request->ip(),
                'requested_user_agent'  => $request->userAgent(),
                'requested_by_admin_id' => $byAdmin?->id,
            ]);

            // Mailables recebem PRIMITIVOS (padrao UserOnboardingMail) +
            // afterCommit() pra so dispatch apos o COMMIT da transacao.
            Mail::to($newEmail)->queue(
                EmailChangeVerificationCodeMail::for($user, $newEmail, $code, $ecr->expires_at)
                    ->afterCommit()
            );

            Mail::to($user->email)->queue(
                EmailChangeNoticeMail::for($user, $newEmail, $byAdmin)
                    ->afterCommit()
            );

            AuditLog::log(
                AuditLog::EVENT_UPDATE,
                'email_change_requests',
                $ecr->id,
                null,
                [
                    'event' => 'requested',
                    'from'  => $user->email,
                    'to'    => $newEmail,
                    'by_admin_id' => $byAdmin?->id,
                ],
                $byAdmin?->id ?? $user->id,
            );

            return $ecr;
        });
    }

    public function confirmChange(User $user, string $providedCode): EmailChangeRequest
    {
        $providedCode = trim($providedCode);

        return DB::transaction(function () use ($user, $providedCode) {
            $ecr = EmailChangeRequest::activeFor($user->id)
                ->lockForUpdate()       // anti-race: 2 abas validando em paralelo
                ->latest()
                ->firstOrFail();

            if ($ecr->expires_at->isPast()) {
                throw new CodeExpiredException();
            }

            if ($ecr->code_attempts >= EmailChangeRequest::MAX_ATTEMPTS) {
                throw new TooManyAttemptsException();
            }

            if (! Hash::check($providedCode, $ecr->code_hash)) {
                $ecr->increment('code_attempts');
                $remaining = EmailChangeRequest::MAX_ATTEMPTS - $ecr->code_attempts;

                if ($ecr->code_attempts >= EmailChangeRequest::MAX_ATTEMPTS) {
                    $ecr->update(['cancelled_at' => now()]);

                    AuditLog::log(
                        AuditLog::EVENT_UPDATE,
                        'email_change_requests',
                        $ecr->id,
                        null,
                        ['event' => 'cancelled', 'reason' => 'max_attempts'],
                        $user->id,
                    );
                }

                throw new InvalidCodeException(remaining: $remaining);
            }

            $oldEmail = $user->email;

            $user->forceFill([
                'email'             => $ecr->new_email,
                'email_verified_at' => now(),
            ])->save();

            $ecr->update(['used_at' => now()]);

            AuditLog::log(
                AuditLog::EVENT_UPDATE,
                'email_change_requests',
                $ecr->id,
                null,
                ['event' => 'confirmed', 'from' => $oldEmail, 'to' => $ecr->new_email],
                $user->id,
            );

            return $ecr;
        });
    }

    public function resendCode(User $user): EmailChangeRequest
    {
        return DB::transaction(function () use ($user) {
            $ecr = EmailChangeRequest::activeFor($user->id)
                ->lockForUpdate()
                ->latest()
                ->firstOrFail();

            if ($ecr->expires_at->isPast()) {
                throw new CodeExpiredException();
            }

            if ($ecr->resend_count >= EmailChangeRequest::MAX_RESENDS_PER_REQUEST) {
                throw new MaxResendsReachedException();
            }

            if ($ecr->last_resend_at !== null) {
                $cooldown = EmailChangeRequest::RESEND_COOLDOWN_SECONDS;
                $elapsed = $ecr->last_resend_at->diffInSeconds(now());
                if ($elapsed < $cooldown) {
                    throw new ResendCooldownException(
                        secondsRemaining: $cooldown - (int) $elapsed
                    );
                }
            }

            $code = $this->generateCode();

            $ecr->update([
                'code_hash'      => Hash::make($code),
                'code_attempts'  => 0,
                'resend_count'   => $ecr->resend_count + 1,
                'last_resend_at' => now(),
                // Renova janela de validade para os 15min cheios apos reenvio.
                'expires_at'     => now()->addMinutes(EmailChangeRequest::TTL_MINUTES),
            ]);

            Mail::to($ecr->new_email)->queue(
                EmailChangeVerificationCodeMail::for($user, $ecr->new_email, $code, $ecr->expires_at)
                    ->afterCommit()
            );

            AuditLog::log(
                AuditLog::EVENT_UPDATE,
                'email_change_requests',
                $ecr->id,
                null,
                ['event' => 'resent', 'resend_count' => $ecr->resend_count],
                $user->id,
            );

            return $ecr;
        });
    }

    /**
     * 6 digitos criptograficamente seguros (random_int).
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
