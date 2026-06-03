# Email Change Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Quando o e-mail de um usuario muda (proprio ou via admin), nao trocar `users.email` direto: persistir o pedido em `email_change_requests`, enviar codigo de 6 digitos no novo e-mail + aviso no atual, e exigir validacao via popup persistente antes de promover a mudanca.

**Architecture:** Tabela dedicada `email_change_requests` + `EmailChangeService` (SRP, padrao `OnboardingService`) + Controller dedicado para verify/resend/cancel + middleware `RequireEmailVerified` aplicado em rotas POST/PUT/DELETE criticas. Frontend: modal Vue nao-dismissable montado em `AuthenticatedLayout`, alimentado por `pending_email_change` em `HandleInertiaRequests::share`.

**Tech Stack:** Laravel 11 (Eloquent, Mailables com `ShouldQueue` + `afterCommit`), Inertia.js, Vue 3 SFC + Tailwind, PostgreSQL, Redis (cache), MailHog (DEV). Testes em PHPUnit (`extends TestCase`, metodos `test_*`).

**Spec:** [docs/superpowers/specs/2026-05-29-email-change-verification-design.md](../specs/2026-05-29-email-change-verification-design.md)

**Notas operacionais (regras do projeto, ver memoria):**
- Sem emojis no codigo.
- Sem trailer `Co-Authored-By` em commits/PRs.
- Commits agrupados por fase; testes ficam locais (rodam pra validar, NAO entram no commit).
- Branch derivada de `dev` (nao empilhar em release).
- Migrations consolidadas no UM arquivo da feature.
- Padrao DRY / SOLID.

---

## Phase 0 — Setup da feature branch

### Task 0.1: Criar feature branch a partir de `dev`

**Files:** nenhum (apenas git).

- [ ] **Step 1:** Verificar que `dev` esta atualizado

Run:
```bash
git fetch origin
git log --oneline origin/dev -5
```

- [ ] **Step 2:** Criar a feature branch

Run:
```bash
git switch dev
git pull --ff-only origin dev
git switch -c feat/email-change-verification
```

- [ ] **Step 3:** Confirmar branch ativa

Run: `git branch --show-current`
Expected: `feat/email-change-verification`

---

## Phase 1 — Schema, model e excecoes

Phase delivers: tabela criada + model + relacao no User + 7 excecoes. **Commit unico ao final.**

### Task 1.1: Gerar migration vazia

**Files:**
- Create: `SDC/database/migrations/<gerado>_create_email_change_requests_table.php`

- [ ] **Step 1:** Gerar migration

Run (a partir de `SDC/`):
```bash
cd SDC
php artisan make:migration create_email_change_requests_table
```

Expected: arquivo `SDC/database/migrations/2026_05_29_<HHMMSS>_create_email_change_requests_table.php` criado.

### Task 1.2: Preencher migration

**Files:**
- Modify: `SDC/database/migrations/2026_05_29_<HHMMSS>_create_email_change_requests_table.php`

- [ ] **Step 1:** Substituir o conteudo da migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('current_email', 191);
            $table->string('new_email', 191);

            // Codigo nunca em claro. Hash bcrypt + Hash::check constant-time.
            $table->string('code_hash');
            $table->unsignedTinyInteger('code_attempts')->default(0);

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Forense
            $table->string('requested_ip', 45)->nullable();
            $table->string('requested_user_agent')->nullable();
            $table->foreignId('requested_by_admin_id')
                ->nullable()->constrained('users')->nullOnDelete();

            // Reenvio
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('last_resend_at')->nullable();

            $table->timestamps();

            $table->index(
                ['user_id', 'used_at', 'cancelled_at'],
                'idx_ecr_user_pending'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
    }
};
```

- [ ] **Step 2:** Rodar migration localmente

Run (em `SDC/`):
```bash
php artisan migrate
```

Expected: `Migrated: 2026_05_29_<HHMMSS>_create_email_change_requests_table` e sem erro.

- [ ] **Step 3:** Validar schema via tinker

Run:
```bash
php artisan tinker --execute="echo json_encode(Schema::getColumnListing('email_change_requests'));"
```

Expected (em ordem aproximada):
```
["id","user_id","current_email","new_email","code_hash","code_attempts","expires_at","used_at","cancelled_at","requested_ip","requested_user_agent","requested_by_admin_id","resend_count","last_resend_at","created_at","updated_at"]
```

### Task 1.3: Criar model `EmailChangeRequest`

**Files:**
- Create: `SDC/app/Models/EmailChangeRequest.php`

- [ ] **Step 1:** Escrever o model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeRequest extends Model
{
    public const MAX_ATTEMPTS = 5;
    public const TTL_MINUTES = 15;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_RESENDS_PER_REQUEST = 5;

    protected $fillable = [
        'user_id',
        'current_email',
        'new_email',
        'code_hash',
        'expires_at',
        'requested_ip',
        'requested_user_agent',
        'requested_by_admin_id',
    ];

    protected $casts = [
        'expires_at'      => 'datetime',
        'used_at'         => 'datetime',
        'cancelled_at'    => 'datetime',
        'last_resend_at'  => 'datetime',
        'code_attempts'   => 'integer',
        'resend_count'    => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_admin_id');
    }

    /**
     * Verdadeiro enquanto o pedido pode ser confirmado.
     */
    public function isPending(): bool
    {
        return $this->used_at === null
            && $this->cancelled_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture()
            && $this->code_attempts < self::MAX_ATTEMPTS;
    }

    /**
     * Escopo: pedidos ativos (nao usados, nao cancelados) de um usuario.
     */
    public function scopeActiveFor(Builder $q, int $userId): void
    {
        $q->where('user_id', $userId)
          ->whereNull('used_at')
          ->whereNull('cancelled_at');
    }
}
```

- [ ] **Step 2:** Validar carregamento via tinker

Run:
```bash
php artisan tinker --execute="echo \App\Models\EmailChangeRequest::MAX_ATTEMPTS;"
```

Expected: `5`

### Task 1.4: Adicionar relacao em `User.php`

**Files:**
- Modify: `SDC/app/Models/User.php`

- [ ] **Step 1:** Adicionar `use` no topo (apos os `use` existentes)

Localizar a linha 12 (`use Spatie\Permission\Traits\HasRoles;`) e logo apos ela adicionar:

```php
use Illuminate\Database\Eloquent\Relations\HasOne;
```

- [ ] **Step 2:** Adicionar metodo no fim da classe (antes do `}`final, depois de `statusHistories()`)

Localizar o metodo `statusHistories()` na linha 266 e adicionar logo apos ele:

```php
    /**
     * Pedido ativo de troca de e-mail (pending). Latest-of-many porque so
     * um pode estar ativo por vez (regra mantida pelo EmailChangeService).
     */
    public function activeEmailChangeRequest(): HasOne
    {
        return $this->hasOne(EmailChangeRequest::class)
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->latestOfMany();
    }
```

- [ ] **Step 3:** Validar relacao via tinker

Run:
```bash
php artisan tinker --execute="\$u = \App\Models\User::first(); echo get_class(\$u->activeEmailChangeRequest()->getRelated());"
```

Expected: `App\Models\EmailChangeRequest`

### Task 1.5: Criar as 7 excecoes

**Files:**
- Create: `SDC/app/Exceptions/Auth/EmailChange/EmailAlreadyInUseException.php`
- Create: `SDC/app/Exceptions/Auth/EmailChange/SameEmailException.php`
- Create: `SDC/app/Exceptions/Auth/EmailChange/InvalidCodeException.php`
- Create: `SDC/app/Exceptions/Auth/EmailChange/CodeExpiredException.php`
- Create: `SDC/app/Exceptions/Auth/EmailChange/TooManyAttemptsException.php`
- Create: `SDC/app/Exceptions/Auth/EmailChange/ResendCooldownException.php`
- Create: `SDC/app/Exceptions/Auth/EmailChange/MaxResendsReachedException.php`

- [ ] **Step 1:** `EmailAlreadyInUseException.php`

```php
<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class EmailAlreadyInUseException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este e-mail ja esta em uso por outro usuario.');
    }
}
```

- [ ] **Step 2:** `SameEmailException.php`

```php
<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class SameEmailException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('O novo e-mail e igual ao atual.');
    }
}
```

- [ ] **Step 3:** `InvalidCodeException.php`

```php
<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class InvalidCodeException extends RuntimeException
{
    public function __construct(public readonly int $remaining)
    {
        parent::__construct("Codigo invalido. Restam {$remaining} tentativa(s).");
    }
}
```

- [ ] **Step 4:** `CodeExpiredException.php`

```php
<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class CodeExpiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Codigo expirado. Solicite um novo.');
    }
}
```

- [ ] **Step 5:** `TooManyAttemptsException.php`

```php
<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class TooManyAttemptsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Limite de tentativas atingido. Solicite um novo codigo.');
    }
}
```

- [ ] **Step 6:** `ResendCooldownException.php`

```php
<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class ResendCooldownException extends RuntimeException
{
    public function __construct(public readonly int $secondsRemaining)
    {
        parent::__construct("Aguarde {$secondsRemaining}s antes de reenviar.");
    }
}
```

- [ ] **Step 7:** `MaxResendsReachedException.php`

```php
<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class MaxResendsReachedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Limite de reenvios atingido. Cancele e tente novamente.');
    }
}
```

- [ ] **Step 8:** Validar autoload das excecoes

Run:
```bash
composer dump-autoload
php artisan tinker --execute="echo class_exists('App\\Exceptions\\Auth\\EmailChange\\InvalidCodeException') ? 'OK' : 'FALHA';"
```

Expected: `OK`

### Task 1.6: Smoke test local da Phase 1

**Files (locais, NAO commitar):**
- Create: `SDC/tests/Feature/Auth/EmailChange/ModelSmokeTest.php`

- [ ] **Step 1:** Criar o teste

```php
<?php

namespace Tests\Feature\Auth\EmailChange;

use App\Models\EmailChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_request_is_marked_pending_until_used_or_expired(): void
    {
        $user = User::factory()->create();
        $ecr = EmailChangeRequest::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => 'novo@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->assertTrue($ecr->isPending());
        $this->assertSame(1, EmailChangeRequest::activeFor($user->id)->count());

        $ecr->update(['used_at' => now()]);
        $this->assertFalse($ecr->fresh()->isPending());
        $this->assertSame(0, EmailChangeRequest::activeFor($user->id)->count());
    }
}
```

- [ ] **Step 2:** Rodar o teste

Run (em `SDC/`):
```bash
php artisan test --filter=ModelSmokeTest
```

Expected: `OK (1 test)` em verde.

- [ ] **Step 3:** Se passar, prosseguir. Caso falhe, corrigir model/migration e reexecutar.

### Task 1.7: Commit da Phase 1 (somente arquivos de producao)

- [ ] **Step 1:** Confirmar staging exclui o teste smoke

Run:
```bash
git status
git add SDC/database/migrations/2026_05_29_*_create_email_change_requests_table.php \
        SDC/app/Models/EmailChangeRequest.php \
        SDC/app/Models/User.php \
        SDC/app/Exceptions/Auth/EmailChange/
```

- [ ] **Step 2:** Verificar que o teste local NAO esta staged

Run:
```bash
git status --short
```

Expected: linha `?? SDC/tests/Feature/Auth/EmailChange/ModelSmokeTest.php` (untracked, nao staged).

- [ ] **Step 3:** Commit

Run:
```bash
git commit -m "feat(auth): add email_change_requests schema, model and exceptions"
```

---

## Phase 2 — Service layer (`EmailChangeService`)

Phase delivers: servico com 3 metodos (`requestChange`, `confirmChange`, `resendCode`) + auditoria via `AuditLog::log`. **Commit unico ao final.**

### Task 2.1: Criar o service

**Files:**
- Create: `SDC/app/Services/Auth/EmailChangeService.php`

- [ ] **Step 1:** Criar o arquivo com o esqueleto + assinaturas

```php
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
```

### Task 2.2: Smoke test local do service

**Files (locais, NAO commitar):**
- Create: `SDC/tests/Feature/Auth/EmailChange/ServiceSmokeTest.php`

- [ ] **Step 1:** Criar o teste

```php
<?php

namespace Tests\Feature\Auth\EmailChange;

use App\Exceptions\Auth\EmailChange\EmailAlreadyInUseException;
use App\Exceptions\Auth\EmailChange\InvalidCodeException;
use App\Exceptions\Auth\EmailChange\ResendCooldownException;
use App\Exceptions\Auth\EmailChange\SameEmailException;
use App\Mail\EmailChangeNoticeMail;
use App\Mail\EmailChangeVerificationCodeMail;
use App\Models\EmailChangeRequest;
use App\Models\User;
use App\Services\Auth\EmailChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ServiceSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_change_persists_pending_and_queues_two_mails(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com']);

        $ecr = app(EmailChangeService::class)->requestChange(
            $user,
            'new@example.com',
            Request::create('/profile', 'PATCH'),
        );

        $this->assertSame('old@example.com', $user->fresh()->email);
        $this->assertSame('new@example.com', $ecr->new_email);
        $this->assertTrue($ecr->isPending());

        Mail::assertQueued(EmailChangeVerificationCodeMail::class);
        Mail::assertQueued(EmailChangeNoticeMail::class);
    }

    public function test_same_email_throws(): void
    {
        $user = User::factory()->create(['email' => 'same@example.com']);
        $this->expectException(SameEmailException::class);

        app(EmailChangeService::class)->requestChange(
            $user, 'same@example.com', Request::create('/x')
        );
    }

    public function test_taken_email_throws(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->expectException(EmailAlreadyInUseException::class);
        app(EmailChangeService::class)->requestChange(
            $user, 'taken@example.com', Request::create('/x')
        );
    }

    public function test_confirm_with_wrong_code_increments_attempts(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $svc = app(EmailChangeService::class);

        $svc->requestChange($user, 'new@example.com', Request::create('/x'));

        try {
            $svc->confirmChange($user, '000000');
            $this->fail('Esperava InvalidCodeException');
        } catch (InvalidCodeException $e) {
            $this->assertSame(4, $e->remaining);
        }

        $this->assertSame(1, EmailChangeRequest::activeFor($user->id)->first()->code_attempts);
    }

    public function test_resend_respects_cooldown(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $svc = app(EmailChangeService::class);

        $svc->requestChange($user, 'new@example.com', Request::create('/x'));
        $svc->resendCode($user);

        $this->expectException(ResendCooldownException::class);
        $svc->resendCode($user); // imediatamente em seguida -> cooldown
    }
}
```

- [ ] **Step 2:** Rodar

Run (em `SDC/`):
```bash
php artisan test --filter=ServiceSmokeTest
```

Expected: 5 testes em verde.

### Task 2.3: Commit da Phase 2

- [ ] **Step 1:** Stage e commit (excluindo testes)

Run:
```bash
git add SDC/app/Services/Auth/EmailChangeService.php
git status --short
git commit -m "feat(auth): implement EmailChangeService with magic code lifecycle"
```

---

## Phase 3 — Mailables e views

Phase delivers: 2 mailables (`EmailChangeVerificationCodeMail`, `EmailChangeNoticeMail`) + 2 Blade views. **Commit unico ao final.**

### Task 3.1: `EmailChangeVerificationCodeMail`

**Files:**
- Create: `SDC/app/Mail/EmailChangeVerificationCodeMail.php`

- [ ] **Step 1:** Criar o mailable

```php
<?php

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * E-mail com codigo de 6 digitos para confirmar troca de e-mail.
 * Enviado SEMPRE para o NOVO endereco (posse).
 *
 * PRIMITIVOS no construtor (padrao UserOnboardingMail) — evita
 * SerializesModels + ModelNotFoundException no worker.
 */
class EmailChangeVerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $name,
        public string $newEmail,
        public string $code,
        public string $expiresAtIso,
    ) {
    }

    public static function for(User $user, string $newEmail, string $code, CarbonInterface $expiresAt): self
    {
        return new self(
            userId: $user->id,
            name: $user->name,
            newEmail: $newEmail,
            code: $code,
            expiresAtIso: $expiresAt->toIso8601String(),
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Codigo de verificacao do novo e-mail - SDC',
            using: [
                function (Email $message): void {
                    $logoPath = public_path('imgs/logo_dc.png');
                    if (is_file($logoPath)) {
                        $message->embedFromPath($logoPath, 'logo-cedec', 'image/png');
                    }
                },
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email_change_verification',
            with: [
                'name'      => $this->name,
                'newEmail'  => $this->newEmail,
                'code'      => $this->code,
                'expiresAt' => \Carbon\Carbon::parse($this->expiresAtIso),
                'ttlMin'    => 15,
            ],
        );
    }
}
```

### Task 3.2: View `email_change_verification.blade.php`

**Files:**
- Create: `SDC/resources/views/emails/email_change_verification.blade.php`

- [ ] **Step 1:** Criar a view (clone visual do `user_onboarding`)

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Codigo de verificacao</title>
</head>
<body style="margin:0;padding:0;background:#0B1F3A;font-family:Arial,Helvetica,sans-serif;color:#0B1F3A;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0B1F3A;padding:40px 0;">
        <tr><td align="center">
            <table width="560" cellpadding="0" cellspacing="0" border="0" style="background:#FFFFFF;border-radius:8px;overflow:hidden;">
                <tr><td align="center" style="background:#0B1F3A;padding:24px;">
                    <img src="cid:logo-cedec" alt="CEDEC" width="64" style="display:block;border:0;">
                </td></tr>
                <tr><td style="padding:32px;">
                    <h1 style="margin:0 0 16px;font-size:20px;color:#0B1F3A;">Confirme seu novo e-mail</h1>
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                        Ola <strong>{{ $name }}</strong>,
                    </p>
                    <p style="margin:0 0 24px;font-size:15px;line-height:1.5;">
                        Recebemos um pedido para usar este endereco (<strong>{{ $newEmail }}</strong>) como
                        e-mail principal da sua conta no Sistema Integrado de Defesa Civil.
                        Para confirmar a posse deste endereco, digite o codigo abaixo no SDC.
                    </p>
                    <div style="background:#F5F7FA;border:1px solid #E5E9F0;border-radius:8px;padding:24px;text-align:center;margin:0 0 24px;">
                        <p style="margin:0 0 8px;font-size:13px;color:#64748B;letter-spacing:1px;">SEU CODIGO</p>
                        <p style="margin:0;font-family:'Courier New',monospace;font-size:32px;letter-spacing:8px;color:#0B1F3A;font-weight:bold;">
                            {{ $code }}
                        </p>
                        <p style="margin:8px 0 0;font-size:12px;color:#64748B;">
                            Expira em {{ $ttlMin }} minutos ({{ $expiresAt->format('d/m/Y H:i') }})
                        </p>
                    </div>
                    <p style="margin:0 0 8px;font-size:13px;color:#64748B;line-height:1.5;">
                        Se voce nao solicitou esta troca, ignore este e-mail e troque sua senha imediatamente.
                        Seu e-mail atual continua valido ate a confirmacao.
                    </p>
                </td></tr>
                <tr><td style="background:#F5F7FA;padding:16px 32px;text-align:center;font-size:12px;color:#64748B;">
                    CEDEC - Defesa Civil de Minas Gerais
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
```

### Task 3.3: `EmailChangeNoticeMail`

**Files:**
- Create: `SDC/app/Mail/EmailChangeNoticeMail.php`

- [ ] **Step 1:** Criar o mailable

```php
<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Aviso enviado para o e-mail ATUAL quando alguem pede troca.
 * Diferencia copy quando o pedido foi feito por um admin.
 */
class EmailChangeNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $name,
        public string $currentEmail,
        public string $newEmailMasked,
        public ?string $byAdminName,
    ) {
    }

    public static function for(User $user, string $newEmail, ?User $byAdmin = null): self
    {
        return new self(
            userId: $user->id,
            name: $user->name,
            currentEmail: $user->email,
            newEmailMasked: self::maskEmail($newEmail),
            byAdminName: $byAdmin?->name,
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido de troca do seu e-mail - SDC',
            using: [
                function (Email $message): void {
                    $logoPath = public_path('imgs/logo_dc.png');
                    if (is_file($logoPath)) {
                        $message->embedFromPath($logoPath, 'logo-cedec', 'image/png');
                    }
                },
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email_change_notice',
            with: [
                'name'           => $this->name,
                'newEmailMasked' => $this->newEmailMasked,
                'byAdminName'    => $this->byAdminName,
                'passwordResetUrl' => url(route('password.request', absolute: false)),
            ],
        );
    }

    /**
     * "matheus.estrela@gmail.com" -> "ma***@gmail.com"
     */
    public static function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + ['', ''];
        if ($local === '' || $domain === '') {
            return $email;
        }
        $visible = substr($local, 0, 2);
        return "{$visible}***@{$domain}";
    }
}
```

### Task 3.4: View `email_change_notice.blade.php`

**Files:**
- Create: `SDC/resources/views/emails/email_change_notice.blade.php`

- [ ] **Step 1:** Criar a view

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedido de troca de e-mail</title>
</head>
<body style="margin:0;padding:0;background:#0B1F3A;font-family:Arial,Helvetica,sans-serif;color:#0B1F3A;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0B1F3A;padding:40px 0;">
        <tr><td align="center">
            <table width="560" cellpadding="0" cellspacing="0" border="0" style="background:#FFFFFF;border-radius:8px;overflow:hidden;">
                <tr><td align="center" style="background:#0B1F3A;padding:24px;">
                    <img src="cid:logo-cedec" alt="CEDEC" width="64" style="display:block;border:0;">
                </td></tr>
                <tr><td style="padding:32px;">
                    <h1 style="margin:0 0 16px;font-size:20px;color:#0B1F3A;">Pedido de troca do seu e-mail</h1>
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                        Ola <strong>{{ $name }}</strong>,
                    </p>

                    @if ($byAdminName)
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                            O administrador <strong>{{ $byAdminName }}</strong> solicitou a troca do
                            e-mail da sua conta no SDC para <strong>{{ $newEmailMasked }}</strong>.
                        </p>
                    @else
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                            Recebemos um pedido para trocar o e-mail da sua conta para
                            <strong>{{ $newEmailMasked }}</strong>.
                        </p>
                    @endif

                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                        <strong>Se foi voce:</strong> use o codigo de 6 digitos que enviamos para o novo endereco
                        para confirmar a troca.
                    </p>

                    <div style="background:#FEF3C7;border-left:4px solid #F59E0B;padding:16px 20px;border-radius:4px;margin:0 0 24px;">
                        <p style="margin:0 0 8px;font-size:14px;font-weight:bold;color:#92400E;">
                            Nao foi voce?
                        </p>
                        <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#92400E;">
                            Seu e-mail atual continua valido e voce continua recebendo as notificacoes
                            normalmente. Recomendamos trocar sua senha imediatamente.
                        </p>
                        <a href="{{ $passwordResetUrl }}"
                           style="display:inline-block;background:#DC2626;color:#FFFFFF;padding:8px 16px;border-radius:4px;text-decoration:none;font-size:13px;font-weight:bold;">
                            Trocar senha agora
                        </a>
                    </div>

                    <p style="margin:0;font-size:12px;color:#64748B;line-height:1.5;">
                        Voce esta recebendo este e-mail porque ele e o endereco atual cadastrado na sua conta.
                    </p>
                </td></tr>
                <tr><td style="background:#F5F7FA;padding:16px 32px;text-align:center;font-size:12px;color:#64748B;">
                    CEDEC - Defesa Civil de Minas Gerais
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
```

### Task 3.5: Smoke render local (MailHog opcional)

**Files (locais, NAO commitar):**
- Create: `SDC/tests/Feature/Auth/EmailChange/MailRenderSmokeTest.php`

- [ ] **Step 1:** Teste de render

```php
<?php

namespace Tests\Feature\Auth\EmailChange;

use App\Mail\EmailChangeNoticeMail;
use App\Mail\EmailChangeVerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_mail_renders_code_and_new_email(): void
    {
        $user = User::factory()->create(['name' => 'Joao']);
        $mail = EmailChangeVerificationCodeMail::for(
            $user, 'novo@example.com', '458102', now()->addMinutes(15)
        );
        $html = $mail->render();

        $this->assertStringContainsString('458102', $html);
        $this->assertStringContainsString('novo@example.com', $html);
        $this->assertStringContainsString('Joao', $html);
    }

    public function test_notice_mail_masks_new_email_and_differs_by_admin(): void
    {
        $user = User::factory()->create(['name' => 'Joao']);
        $admin = User::factory()->create(['name' => 'Maria Admin']);

        $self = EmailChangeNoticeMail::for($user, 'omatheus@gmail.com');
        $byAdmin = EmailChangeNoticeMail::for($user, 'omatheus@gmail.com', $admin);

        $selfHtml = $self->render();
        $adminHtml = $byAdmin->render();

        $this->assertStringContainsString('om***@gmail.com', $selfHtml);
        $this->assertStringContainsString('Trocar senha agora', $selfHtml);
        $this->assertStringContainsString('Maria Admin', $adminHtml);
    }
}
```

- [ ] **Step 2:** Rodar

Run:
```bash
php artisan test --filter=MailRenderSmokeTest
```

Expected: 2 testes em verde.

- [ ] **Step 3 (opcional):** Smoke visual via MailHog

```bash
php artisan tinker --execute="
\$u = \App\Models\User::factory()->create(['email' => 'old@example.com']);
\Illuminate\Support\Facades\Mail::to('new@example.com')->send(
    \App\Mail\EmailChangeVerificationCodeMail::for(\$u, 'new@example.com', '123456', now()->addMinutes(15))
);
\Illuminate\Support\Facades\Mail::to(\$u->email)->send(
    \App\Mail\EmailChangeNoticeMail::for(\$u, 'new@example.com')
);
echo 'enviado';
"
```

Abrir MailHog em `http://localhost:8025` e validar visual.

### Task 3.6: Commit da Phase 3

- [ ] **Step 1:** Stage e commit

Run:
```bash
git add SDC/app/Mail/EmailChangeVerificationCodeMail.php \
        SDC/app/Mail/EmailChangeNoticeMail.php \
        SDC/resources/views/emails/email_change_verification.blade.php \
        SDC/resources/views/emails/email_change_notice.blade.php
git status --short
git commit -m "feat(auth): add email change verification mailables and templates"
```

---

## Phase 4 — HTTP layer (controllers, rotas, middleware, Inertia)

Phase delivers: form request + controller novo + middleware novo + patches em `ProfileController` / `UserManagementController` / rotas / `HandleInertiaRequests`. **Commit unico ao final.**

### Task 4.1: `VerifyEmailChangeRequest` (form request)

**Files:**
- Create: `SDC/app/Http/Requests/Auth/VerifyEmailChangeRequest.php`

- [ ] **Step 1:** Criar

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'O codigo deve ter exatamente 6 digitos numericos.',
            'code.size'  => 'O codigo deve ter exatamente 6 digitos.',
        ];
    }
}
```

### Task 4.2: `EmailChangeVerificationController`

**Files:**
- Create: `SDC/app/Http/Controllers/Auth/EmailChangeVerificationController.php`

- [ ] **Step 1:** Criar o controller

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Auth\EmailChange\CodeExpiredException;
use App\Exceptions\Auth\EmailChange\InvalidCodeException;
use App\Exceptions\Auth\EmailChange\MaxResendsReachedException;
use App\Exceptions\Auth\EmailChange\ResendCooldownException;
use App\Exceptions\Auth\EmailChange\TooManyAttemptsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailChangeRequest;
use App\Models\EmailChangeRequest;
use App\Services\Auth\EmailChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmailChangeVerificationController extends Controller
{
    public function __construct(private readonly EmailChangeService $service)
    {
    }

    public function verify(VerifyEmailChangeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $code = $request->string('code')->value();

        try {
            $this->service->confirmChange($user, $code);
        } catch (InvalidCodeException $e) {
            return back()->withErrors([
                'code' => "Codigo invalido. Restam {$e->remaining} tentativa(s).",
            ]);
        } catch (CodeExpiredException) {
            return back()->withErrors([
                'code' => 'Codigo expirado. Solicite um novo.',
            ]);
        } catch (TooManyAttemptsException) {
            return back()->withErrors([
                'code' => 'Limite de tentativas atingido. Solicite um novo codigo.',
            ]);
        }

        // Email + email_verified_at mudaram -> cache do payload Inertia
        // precisa ser purgado (mesmo padrao do FirstAccessController).
        Cache::forget("inertia_user_data_{$user->id}");

        return back()->with('success', 'E-mail atualizado e verificado com sucesso.');
    }

    public function resend(Request $request): RedirectResponse
    {
        try {
            $this->service->resendCode($request->user());
        } catch (ResendCooldownException $e) {
            return back()->withErrors([
                'resend' => "Aguarde {$e->secondsRemaining}s antes de reenviar.",
            ]);
        } catch (MaxResendsReachedException) {
            return back()->withErrors([
                'resend' => 'Limite de reenvios atingido. Cancele e tente novamente.',
            ]);
        } catch (CodeExpiredException) {
            return back()->withErrors([
                'resend' => 'O pedido expirou. Cancele e abra um novo.',
            ]);
        }

        return back()->with('success', 'Novo codigo enviado.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        EmailChangeRequest::activeFor($request->user()->id)
            ->update(['cancelled_at' => now()]);

        Cache::forget("inertia_user_data_{$request->user()->id}");

        return back()->with('success', 'Pedido de troca de e-mail cancelado.');
    }
}
```

### Task 4.3: Middleware `RequireEmailVerified`

**Files:**
- Create: `SDC/app/Http/Middleware/RequireEmailVerified.php`

- [ ] **Step 1:** Criar o middleware

```php
<?php

namespace App\Http\Middleware;

use App\Models\EmailChangeRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia operacoes de escrita sensiveis enquanto o usuario tem
 * um pedido de troca de e-mail ativo (pending).
 *
 * Leitura (GETs) e a propria UI de validacao continuam liberadas
 * para nao impedir produtividade — o popup persistente do frontend
 * ja garante pressao visual.
 */
class RequireEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $hasPending = EmailChangeRequest::activeFor($user->id)
            ->where('expires_at', '>', now())
            ->exists();

        if (!$hasPending) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'  => 'Voce precisa validar o novo e-mail antes de continuar.',
                'redirect' => null,
            ], 423);
        }

        return back()->with(
            'error',
            'Valide o novo e-mail (verifique sua caixa de entrada) antes de continuar.'
        );
    }
}
```

- [ ] **Step 2:** Registrar alias no Kernel

Modify: `SDC/app/Http/Kernel.php`

Localizar o array `$middlewareAliases` (ou `$routeMiddleware` em versoes antigas) e adicionar:

```php
'email.verified' => \App\Http\Middleware\RequireEmailVerified::class,
```

### Task 4.4: Patch em `ProfileController::update`

**Files:**
- Modify: `SDC/app/Http/Controllers/ProfileController.php`

- [ ] **Step 1:** Substituir o `update()` inteiro

Localizar o metodo `update()` (linhas ~27-38) e trocar pelo seguinte:

```php
public function update(
    ProfileUpdateRequest $request,
    \App\Services\Auth\EmailChangeService $emailChangeService,
): RedirectResponse {
    $user = $request->user();
    $validated = $request->validated();

    // Troca de e-mail NUNCA vai direto pra users.email — passa
    // pelo fluxo de magic code (EmailChangeService).
    if (isset($validated['email']) && $validated['email'] !== $user->email) {
        $newEmail = $validated['email'];
        unset($validated['email']);

        try {
            $emailChangeService->requestChange($user, $newEmail, $request);
        } catch (\App\Exceptions\Auth\EmailChange\EmailAlreadyInUseException) {
            return Redirect::back()->withErrors(['email' => 'E-mail ja cadastrado.']);
        } catch (\App\Exceptions\Auth\EmailChange\SameEmailException) {
            // no-op silencioso (mesmo email digitado em duplicidade)
        }
    }

    $user->fill($validated)->save();

    \Illuminate\Support\Facades\Cache::forget("inertia_user_data_{$user->id}");

    return Redirect::back()->with('success', 'Perfil atualizado.');
}
```

- [ ] **Step 2:** Adicionar `use` no topo se faltar

Logo apos `use Illuminate\Support\Facades\Redirect;` adicionar (se nao existirem):

```php
use App\Exceptions\Auth\EmailChange\EmailAlreadyInUseException;
use App\Exceptions\Auth\EmailChange\SameEmailException;
use App\Services\Auth\EmailChangeService;
use Illuminate\Support\Facades\Cache;
```

(Se ja tiver feito o injection com `\App\...\EmailChangeService` inline, os `use` sao opcionais — mas idiomaticamente melhor adicionar.)

### Task 4.5: Patch em `UserManagementController::update`

**Files:**
- Modify: `SDC/app/Http/Controllers/Admin/UserManagementController.php`

- [ ] **Step 1:** Adicionar `use`

No topo, junto aos demais:

```php
use App\Services\Auth\EmailChangeService;
```

- [ ] **Step 2:** Inserir bloco antes de `$user->update($payload)`

Localizar (linhas ~465-466):

```php
$oldStatus = $user->status ?? ($user->active ? 'active' : 'inactive');
$user->update($payload);
```

Substituir por:

```php
$oldStatus = $user->status ?? ($user->active ? 'active' : 'inactive');

// Troca de e-mail pelo admin segue o MESMO fluxo de magic code
// (sem bypass) — admin nao pode "forjar" posse de e-mail.
if (isset($payload['email']) && $payload['email'] !== $user->email) {
    $newEmail = $payload['email'];
    unset($payload['email']);

    try {
        app(EmailChangeService::class)->requestChange(
            $user, $newEmail, $request, byAdmin: auth()->user()
        );
        // Invalida cache para o user-alvo enxergar pending_email_change
        // assim que carregar a proxima pagina.
        \Illuminate\Support\Facades\Cache::forget("inertia_user_data_{$user->id}");
    } catch (\App\Exceptions\Auth\EmailChange\EmailAlreadyInUseException) {
        return back()->with('error', 'E-mail ja cadastrado em outro usuario.');
    } catch (\App\Exceptions\Auth\EmailChange\SameEmailException) {
        // silencioso
    }
}

$user->update($payload);
```

### Task 4.6: Rotas em `routes/web.php`

**Files:**
- Modify: `SDC/routes/web.php`

- [ ] **Step 1:** Localizar o grupo `Route::middleware('auth')` existente

- [ ] **Step 2:** Adicionar dentro dele as 3 rotas novas

```php
use App\Http\Controllers\Auth\EmailChangeVerificationController;

// ... dentro do grupo auth:
Route::post('/profile/email/verify', [EmailChangeVerificationController::class, 'verify'])
    ->name('profile.email.verify')
    ->middleware('throttle:10,1');

Route::post('/profile/email/resend', [EmailChangeVerificationController::class, 'resend'])
    ->name('profile.email.resend')
    ->middleware('throttle:6,1');

Route::post('/profile/email/cancel', [EmailChangeVerificationController::class, 'cancel'])
    ->name('profile.email.cancel');
```

- [ ] **Step 3:** Aplicar middleware `email.verified` em rotas de escrita criticas

Localizar (uma de cada vez) os blocos abaixo no `routes/web.php` e aninhar o `middleware('email.verified')`. Aplicar APENAS em verbos de escrita (POST/PUT/PATCH/DELETE):

- Grupo de RAT (`Route::prefix('rat')` ou similar): `->store`, `->update`, `->destroy`
- Grupo de Demandas: idem
- Grupo de Decretacoes: `->store`, `->update`
- Grupo de Permissoes/Users em `/admin/permissions/users`: todas EXCETO `->show` e `->index`
- Grupo de PAE Empreendimentos: `->store`, `->update`, `->destroy`

Padrao:

```php
Route::middleware('email.verified')->group(function () {
    Route::post('/rat', [RatController::class, 'store'])->name('rat.store');
    Route::put('/rat/{rat}', [RatController::class, 'update'])->name('rat.update');
    Route::delete('/rat/{rat}', [RatController::class, 'destroy'])->name('rat.destroy');
    // ...
});
```

Quando o agrupamento de uma feature ja for um `Route::resource`, prefira excluir GETs:

```php
Route::resource('rat', RatController::class)
    ->only(['store', 'update', 'destroy'])
    ->middleware('email.verified');
```

> **Validacao da lista exata:** ao chegar nesta task, rode `php artisan route:list --columns=method,uri,name --middleware` antes e depois para comparar. Se nao tiver certeza se uma rota e "critica", PARE e pergunte. Em duvida, **NAO aplique** o middleware — e melhor faltar do que sobrar.

### Task 4.7: Patch em `HandleInertiaRequests`

**Files:**
- Modify: `SDC/app/Http/Middleware/HandleInertiaRequests.php`

- [ ] **Step 1:** Localizar `getCachedUserData($user)` e o callback do `Cache::remember`

- [ ] **Step 2:** Acrescentar a chave `pending_email_change` no array retornado dentro do callback

No mesmo array que ja contem `id`, `name`, `email`, etc., adicionar:

```php
'pending_email_change' => $this->buildPendingEmailChange($user),
```

- [ ] **Step 3:** Implementar os helpers no fim da classe

Adicionar (antes do `}` final):

```php
/**
 * Snapshot do pedido de troca de e-mail ativo, para o frontend
 * decidir se monta o popup de verificacao.
 *
 * Retorna null quando nao ha pedido pending — assim o front pode
 * tratar com `v-if="user.pending_email_change"`.
 */
protected function buildPendingEmailChange($user): ?array
{
    $ecr = $user->activeEmailChangeRequest;

    if (!$ecr || !$ecr->isPending()) {
        return null;
    }

    $resendCooldown = \App\Models\EmailChangeRequest::RESEND_COOLDOWN_SECONDS;
    $resendAvailableAt = $ecr->last_resend_at
        ? $ecr->last_resend_at->copy()->addSeconds($resendCooldown)
        : null;

    return [
        'id'                   => $ecr->id,
        'new_email_masked'     => $this->maskEmail($ecr->new_email),
        'current_email_masked' => $this->maskEmail($ecr->current_email),
        'expires_at'           => $ecr->expires_at->toIso8601String(),
        'attempts_remaining'   => \App\Models\EmailChangeRequest::MAX_ATTEMPTS - $ecr->code_attempts,
        'resend_available_at'  => $resendAvailableAt?->toIso8601String(),
        'resends_remaining'    => \App\Models\EmailChangeRequest::MAX_RESENDS_PER_REQUEST - $ecr->resend_count,
        'requested_by_admin'   => $ecr->requested_by_admin_id !== null,
    ];
}

/**
 * "matheus.estrela@gmail.com" -> "ma***@gmail.com"
 */
protected function maskEmail(string $email): string
{
    [$local, $domain] = explode('@', $email, 2) + ['', ''];
    if ($local === '' || $domain === '') {
        return $email;
    }
    return substr($local, 0, 2) . '***@' . $domain;
}
```

- [ ] **Step 4:** Garantir que `activeEmailChangeRequest` esta sendo carregada

Onde o `$user` e usado no `getCachedUserData`, garantir o eager load:

```php
$user->loadMissing('activeEmailChangeRequest');
```

(coloque essa linha como primeira instrucao dentro da closure do `Cache::remember`)

### Task 4.8: Smoke local da Phase 4

**Files (locais, NAO commitar):**
- Create: `SDC/tests/Feature/Auth/EmailChange/HttpFlowSmokeTest.php`

- [ ] **Step 1:** Criar o teste

```php
<?php

namespace Tests\Feature\Auth\EmailChange;

use App\Models\EmailChangeRequest;
use App\Models\User;
use App\Services\Auth\EmailChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HttpFlowSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_email_does_not_change_users_email_directly(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com', 'name' => 'X']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name'  => 'X',
                'email' => 'new@example.com',
            ])
            ->assertSessionHas('success');

        $this->assertSame('old@example.com', $user->fresh()->email);
        $this->assertDatabaseHas('email_change_requests', [
            'user_id'   => $user->id,
            'new_email' => 'new@example.com',
            'used_at'   => null,
        ]);
    }

    public function test_verify_with_correct_code_promotes_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com']);
        $svc = app(EmailChangeService::class);
        $svc->requestChange($user, 'new@example.com', Request::create('/x'));

        // Obter o codigo em claro reescrevendo o hash conhecido
        $known = '123456';
        EmailChangeRequest::activeFor($user->id)->update(['code_hash' => Hash::make($known)]);

        $this->actingAs($user)
            ->post(route('profile.email.verify'), ['code' => $known])
            ->assertSessionHas('success');

        $this->assertSame('new@example.com', $user->fresh()->email);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_gate_returns_423_for_json_on_pending(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        app(EmailChangeService::class)->requestChange($user, 'new@example.com', Request::create('/x'));

        // Substituir abaixo por uma rota POST real sob middleware email.verified
        // descoberta no proprio projeto (ex.: route('demandas.store')). Se a feature
        // ainda nao existir no ambiente do teste, validar em browser conforme Task 5.6.
        $response = $this->actingAs($user)
            ->postJson('/demandas', []);

        $this->assertSame(423, $response->status());
    }
}
```

- [ ] **Step 2:** Rodar

Run:
```bash
php artisan test --filter=HttpFlowSmokeTest
```

Expected: 3 testes em verde (ou pular o gate test se a rota nao existir e validar no browser).

### Task 4.9: Commit da Phase 4

- [ ] **Step 1:** Stage e commit

Run:
```bash
git add SDC/app/Http/Requests/Auth/VerifyEmailChangeRequest.php \
        SDC/app/Http/Controllers/Auth/EmailChangeVerificationController.php \
        SDC/app/Http/Middleware/RequireEmailVerified.php \
        SDC/app/Http/Kernel.php \
        SDC/app/Http/Controllers/ProfileController.php \
        SDC/app/Http/Controllers/Admin/UserManagementController.php \
        SDC/app/Http/Middleware/HandleInertiaRequests.php \
        SDC/routes/web.php
git status --short
git commit -m "feat(auth): wire HTTP layer for email change verification flow"
```

---

## Phase 5 — Frontend (Vue + Inertia)

Phase delivers: `OtpInput.vue` + `EmailChangeVerifyModal.vue` montado em `AuthenticatedLayout`, toasts em `UserProfileModal` e `SettingsModal`. **Commit unico ao final.**

### Task 5.1: `OtpInput.vue` (componente generico)

**Files:**
- Create: `SDC/resources/js/Components/OtpInput.vue`

- [ ] **Step 1:** Criar o componente

```vue
<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    length: { type: Number, default: 6 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'complete']);

const digits = ref(Array.from({ length: props.length }, (_, i) => props.modelValue[i] || ''));
const refs = ref([]);

const value = computed(() => digits.value.join(''));

watch(value, (v) => {
    emit('update:modelValue', v);
    if (v.length === props.length && !v.includes('')) {
        emit('complete', v);
    }
});

watch(() => props.modelValue, (mv) => {
    const arr = Array.from({ length: props.length }, (_, i) => mv[i] || '');
    digits.value = arr;
});

function onInput(event, index) {
    const raw = event.target.value.replace(/\D/g, '');
    if (!raw) {
        digits.value[index] = '';
        return;
    }
    digits.value[index] = raw[0];
    if (index < props.length - 1) {
        nextTick(() => refs.value[index + 1]?.focus());
    }
}

function onKeyDown(event, index) {
    if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
        nextTick(() => refs.value[index - 1]?.focus());
    }
    if (event.key === 'ArrowLeft' && index > 0) refs.value[index - 1]?.focus();
    if (event.key === 'ArrowRight' && index < props.length - 1) refs.value[index + 1]?.focus();
}

function onPaste(event) {
    const text = (event.clipboardData || window.clipboardData).getData('text');
    const clean = text.replace(/\D/g, '').slice(0, props.length);
    if (!clean) return;
    event.preventDefault();
    for (let i = 0; i < props.length; i++) {
        digits.value[i] = clean[i] || '';
    }
    const focusIdx = Math.min(clean.length, props.length - 1);
    nextTick(() => refs.value[focusIdx]?.focus());
}

defineExpose({
    focus: () => refs.value[0]?.focus(),
    clear: () => {
        digits.value = Array.from({ length: props.length }, () => '');
        refs.value[0]?.focus();
    },
});
</script>

<template>
    <div class="flex gap-2 justify-center">
        <input
            v-for="(_, index) in length"
            :key="index"
            :ref="(el) => (refs[index] = el)"
            :value="digits[index]"
            :disabled="disabled"
            type="text"
            inputmode="numeric"
            maxlength="1"
            class="w-12 h-14 text-center text-2xl font-mono border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100"
            @input="onInput($event, index)"
            @keydown="onKeyDown($event, index)"
            @paste="onPaste"
        />
    </div>
</template>
```

### Task 5.2: `EmailChangeVerifyModal.vue`

**Files:**
- Create: `SDC/resources/js/Components/Organisms/EmailChangeVerifyModal.vue`

- [ ] **Step 1:** Criar o modal

```vue
<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import OtpInput from '@/Components/OtpInput.vue';

const props = defineProps({
    pendingChange: { type: Object, required: true },
});

const page = usePage();

const form = useForm({ code: '' });
const resendForm = useForm({});
const cancelForm = useForm({});

const now = ref(Date.now());
setInterval(() => { now.value = Date.now(); }, 1000);

const resendAvailable = computed(() => {
    if (!props.pendingChange.resend_available_at) return true;
    return now.value >= Date.parse(props.pendingChange.resend_available_at);
});

const resendCountdown = computed(() => {
    if (resendAvailable.value) return '';
    const ms = Date.parse(props.pendingChange.resend_available_at) - now.value;
    const s = Math.max(0, Math.ceil(ms / 1000));
    return `(${s}s)`;
});

const expiryHuman = computed(() => {
    const ms = Date.parse(props.pendingChange.expires_at) - now.value;
    if (ms <= 0) return 'expirado';
    const min = Math.floor(ms / 60000);
    const sec = Math.floor((ms % 60000) / 1000);
    return `${min}min ${sec.toString().padStart(2, '0')}s`;
});

function submit() {
    if (form.code.length !== 6) return;
    form.post(route('profile.email.verify'), {
        preserveScroll: true,
        onSuccess: () => form.reset('code'),
    });
}

function resend() {
    resendForm.post(route('profile.email.resend'), { preserveScroll: true });
}

function cancel() {
    if (!confirm('Cancelar o pedido de troca de e-mail?')) return;
    cancelForm.post(route('profile.email.cancel'), { preserveScroll: true });
}
</script>

<template>
    <Modal :show="true" :closeable="false" max-width="md">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">
                Confirme seu novo e-mail
            </h2>

            <p v-if="pendingChange.requested_by_admin" class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                Um administrador alterou seu e-mail para
                <strong>{{ pendingChange.new_email_masked }}</strong>.
                Enviamos um codigo de 6 digitos para confirmar.
            </p>
            <p v-else class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                Voce pediu para trocar seu e-mail para
                <strong>{{ pendingChange.new_email_masked }}</strong>.
                Enviamos um codigo de 6 digitos para esse endereco.
            </p>

            <OtpInput v-model="form.code" :length="6" @complete="submit" class="mb-4" />

            <InputError class="mt-2 text-center" :message="form.errors.code" />
            <InputError class="mt-2 text-center" :message="resendForm.errors.resend" />

            <p class="text-xs text-slate-500 dark:text-slate-400 text-center mt-4">
                Tentativas restantes: {{ pendingChange.attempts_remaining }} ·
                Codigo expira em {{ expiryHuman }}
            </p>

            <div class="flex justify-between items-center mt-6">
                <SecondaryButton type="button" @click="cancel">Cancelar troca</SecondaryButton>

                <div class="flex gap-2">
                    <button
                        type="button"
                        :disabled="!resendAvailable || resendForm.processing"
                        @click="resend"
                        class="px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:underline disabled:opacity-50 disabled:no-underline"
                    >
                        Reenviar {{ resendCountdown }}
                    </button>
                    <PrimaryButton
                        type="button"
                        :disabled="form.code.length !== 6 || form.processing"
                        @click="submit"
                    >
                        Confirmar
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
```

### Task 5.3: Mount global em `AuthenticatedLayout.vue`

**Files:**
- Modify: `SDC/resources/js/Layouts/AuthenticatedLayout.vue`

- [ ] **Step 1:** Adicionar import no `<script setup>` (junto aos demais)

```js
import EmailChangeVerifyModal from '@/Components/Organisms/EmailChangeVerifyModal.vue';
```

- [ ] **Step 2:** No `<template>`, fora do `<slot />` (preferencialmente no fim, antes do fechamento da raiz)

```vue
<EmailChangeVerifyModal
    v-if="$page.props.auth?.user?.pending_email_change"
    :pending-change="$page.props.auth.user.pending_email_change"
/>
```

> Se o projeto tiver outros layouts autenticados (ex.: `GuestLayout` excluido), adicione apenas em layouts que sao usados por usuarios autenticados. Layouts de login/cadastro NAO devem incluir.

### Task 5.4: Toast no `UserProfileModal.vue`

**Files:**
- Modify: `SDC/resources/js/Components/Organisms/UserProfileModal.vue`

- [ ] **Step 1:** Alterar a callback `onSuccess` do `submit()`

Localizar (linhas ~38-45):

```js
const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};
```

Substituir por:

```js
const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Se a troca de e-mail entrou em pending, o popup global de
            // verificacao ja vai aparecer via shared props — aqui so fechamos
            // o modal de perfil. O flash 'success' do controller cobre o toast.
            closeModal();
        },
    });
};
```

(Sem mudanca funcional necessaria — o `flash.success` ja vai aparecer no sistema de toast global. **Esta task confirma que NADA adicional precisa ser feito aqui** — apenas validar visualmente.)

### Task 5.5: Toast no `SettingsModal.vue` (aba Seguranca)

**Files:**
- Modify: `SDC/resources/js/Components/Organisms/Settings/SettingsModal.vue`

- [ ] **Step 1:** Localizar `updateEmail` (linha ~564)

```js
const updateEmail = () => {
    emailForm.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
```

Como o controller agora retorna `flash.success` com texto generico ("Perfil atualizado") quando a troca foi enfileirada, manter o callback simples. O popup global cuida do resto. **Nenhuma mudanca necessaria** alem de confirmar o comportamento.

> Se o time quiser uma mensagem mais explicita ao usuario logo apos clicar "Salvar E-mail", alterar o controller (`ProfileController::update`) para variar o flash quando `requestChange` for chamado:

```php
// dentro do bloco "if (isset($validated['email'])..." apos requestChange:
return Redirect::back()->with(
    'info',
    "Enviamos um codigo de 6 digitos para o novo e-mail. Confirme para concluir a troca."
);
```

Decidir antes de implementar: usar `info` em vez de `success` quando ha pedido pending.

### Task 5.6: Smoke manual no browser

- [ ] **Step 1:** Subir o stack DEV

Run (em `SDC/`):
```bash
php artisan serve &
npm run dev
```

- [ ] **Step 2:** Validar fluxo end-to-end

Walkthrough:
1. Login como usuario qualquer.
2. Abrir "Meu Perfil" (TopBar -> avatar) ou "Configuracoes > Seguranca > Alterar E-mail".
3. Trocar e-mail para um endereco diferente. Submit.
4. Observar:
   - URL nao redireciona; pagina recarrega via Inertia.
   - Popup nao-dismissable aparece (`EmailChangeVerifyModal`).
   - Toast `success` apareceu confirmando salvamento.
5. Abrir MailHog (`http://localhost:8025`) e verificar:
   - 1 e-mail no NOVO endereco com codigo de 6 digitos.
   - 1 e-mail no email ATUAL com aviso e link "Trocar senha agora".
6. Copiar o codigo do MailHog, colar no popup (testar paste no OtpInput).
7. Validar: popup fecha, toast `success`, e-mail no DB foi promovido.
8. Validar gate: antes de confirmar, tentar uma acao sob `email.verified` (ex.: criar RAT) e observar `back()->with('error', ...)`.
9. Validar cancel: criar novo pedido, clicar "Cancelar troca", confirmar prompt -> popup some.
10. Validar resend: criar pedido, clicar "Reenviar" antes de 60s -> erro de cooldown; aguardar 60s, clicar -> novo email no MailHog.

- [ ] **Step 3:** Se tudo OK, parar os servidores

```bash
pkill -f "artisan serve"
pkill -f "vite"
```

### Task 5.7: Commit da Phase 5

- [ ] **Step 1:** Stage e commit (apenas arquivos modificados/novos)

Run:
```bash
git add SDC/resources/js/Components/OtpInput.vue \
        SDC/resources/js/Components/Organisms/EmailChangeVerifyModal.vue \
        SDC/resources/js/Layouts/AuthenticatedLayout.vue
# UserProfileModal.vue e SettingsModal.vue so entram se foram tocados na Task 5.4/5.5
git status --short
git commit -m "feat(auth): add OTP modal and global mount for email change verification"
```

---

## Phase 6 — Scheduler de cleanup

Phase delivers: comando Artisan + agendamento. **Commit unico ao final.**

### Task 6.1: Comando Artisan

**Files:**
- Create: `SDC/app/Console/Commands/CleanupEmailChangeRequests.php`

- [ ] **Step 1:** Criar o comando

```php
<?php

namespace App\Console\Commands;

use App\Models\EmailChangeRequest;
use Illuminate\Console\Command;

class CleanupEmailChangeRequests extends Command
{
    protected $signature = 'email-change:cleanup-expired
        {--days=30 : Idade minima (em dias) para purgar registros}';

    protected $description = 'Remove pedidos de troca de e-mail concluidos, cancelados ou expirados ha mais de N dias.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = EmailChangeRequest::query()
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($q) use ($cutoff) {
                    $q->whereNotNull('used_at')->where('used_at', '<', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNotNull('cancelled_at')->where('cancelled_at', '<', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('used_at')
                      ->whereNull('cancelled_at')
                      ->where('expires_at', '<', $cutoff);
                });
            })
            ->delete();

        $this->info("Removidos {$deleted} pedidos com mais de {$days} dias.");

        return self::SUCCESS;
    }
}
```

### Task 6.2: Agendar no `Console/Kernel.php`

**Files:**
- Modify: `SDC/app/Console/Kernel.php`

- [ ] **Step 1:** Localizar `schedule(Schedule $schedule)`

- [ ] **Step 2:** Adicionar agendamento

```php
$schedule->command('email-change:cleanup-expired')
    ->daily()
    ->onOneServer()                  // safety pra cluster
    ->name('email-change-cleanup');
```

### Task 6.3: Smoke local da Phase 6

**Files (locais, NAO commitar):**
- Create: `SDC/tests/Feature/Auth/EmailChange/CleanupSmokeTest.php`

- [ ] **Step 1:** Teste

```php
<?php

namespace Tests\Feature\Auth\EmailChange;

use App\Models\EmailChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CleanupSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_deletes_old_used_cancelled_and_expired(): void
    {
        $user = User::factory()->create();
        $base = [
            'user_id' => $user->id,
            'current_email' => 'a@x.com',
            'new_email' => 'b@x.com',
            'code_hash' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(15),
        ];

        // Antigos -> devem sumir
        EmailChangeRequest::create($base + ['used_at' => now()->subDays(40)]);
        EmailChangeRequest::create($base + ['cancelled_at' => now()->subDays(35)]);
        EmailChangeRequest::create(array_merge($base, ['expires_at' => now()->subDays(31)]));

        // Recentes -> ficam
        EmailChangeRequest::create($base + ['used_at' => now()->subDays(5)]);
        EmailChangeRequest::create($base);

        $this->artisan('email-change:cleanup-expired')->assertExitCode(0);

        $this->assertSame(2, EmailChangeRequest::count());
    }
}
```

- [ ] **Step 2:** Rodar

Run:
```bash
php artisan test --filter=CleanupSmokeTest
```

Expected: 1 teste em verde.

### Task 6.4: Commit da Phase 6

- [ ] **Step 1:** Stage e commit

Run:
```bash
git add SDC/app/Console/Commands/CleanupEmailChangeRequests.php \
        SDC/app/Console/Kernel.php
git status --short
git commit -m "feat(auth): add scheduled cleanup of email change requests"
```

---

## Phase 7 — Validacao final e finalizacao

### Task 7.1: Suite completa de testes locais

- [ ] **Step 1:** Rodar TODOS os testes da feature

Run (em `SDC/`):
```bash
php artisan test --filter="Auth\\EmailChange"
```

Expected: todos verdes (ModelSmoke + ServiceSmoke + MailRenderSmoke + HttpFlowSmoke + CleanupSmoke).

- [ ] **Step 2:** Rodar a suite completa do projeto para garantir que nao regredimos

Run:
```bash
php artisan test
```

Expected: zero falhas novas. Se houver falhas, investigar (testes pre-existentes podem ja estar quebrados — comparar com `main`/`dev`).

### Task 7.2: Browser smoke walkthrough completo (re-rodar Task 5.6)

- [ ] **Step 1:** Re-validar o end-to-end conforme Task 5.6 step 2, agora com TUDO em vigor (inclusive cleanup desabilitado para o teste manual).

### Task 7.3: Audit visual do diff

- [ ] **Step 1:** Conferir nada vazou que nao deveria

Run:
```bash
git diff --stat dev...HEAD
git log --oneline dev..HEAD
```

Expected log:
```
feat(auth): add scheduled cleanup of email change requests
feat(auth): add OTP modal and global mount for email change verification
feat(auth): wire HTTP layer for email change verification flow
feat(auth): add email change verification mailables and templates
feat(auth): implement EmailChangeService with magic code lifecycle
feat(auth): add email_change_requests schema, model and exceptions
```

(6 commits, um por phase, sem `Co-Authored-By`.)

- [ ] **Step 2:** Conferir que NENHUM arquivo de teste da feature entrou no diff

Run:
```bash
git diff --name-only dev...HEAD | grep -i "tests/Feature/Auth/EmailChange" || echo "OK: nenhum teste committed"
```

Expected: `OK: nenhum teste committed`.

### Task 7.4: Subir branch e abrir PR

- [ ] **Step 1:** Push

Run:
```bash
git push -u origin feat/email-change-verification
```

- [ ] **Step 2:** Abrir PR via `gh`

Run:
```bash
gh pr create --base dev --title "feat(auth): verificacao de troca de e-mail via magic code (6 digitos)" --body "$(cat <<'EOF'
## Summary
- Email so e trocado apos validacao com codigo de 6 digitos enviado no novo endereco
- Email atual continua valido ate confirmacao; recebe aviso de seguranca paralelo
- Popup persistente nao-dismissable aparece em qualquer pagina autenticada enquanto ha pedido pending
- Middleware `email.verified` bloqueia operacoes de escrita criticas (RAT, demandas, decretacoes, gestao de usuarios, PAE empreendimentos)
- Admin que troca e-mail de outro usuario segue o MESMO fluxo (sem bypass)

## Test plan
- [ ] Login -> Meu Perfil -> trocar e-mail -> popup aparece -> codigo do MailHog valida -> email promovido
- [ ] Login -> Settings > Seguranca > Alterar E-mail -> idem
- [ ] Admin -> /admin/permissions/users/X/edit -> troca e-mail do user -> proximo login do user mostra popup com copy "administrador X.Y solicitou..."
- [ ] Codigo errado 5x -> request cancelled, popup pede "solicitar novo codigo"
- [ ] Codigo expirado (15min) -> erro + reenvio funcional
- [ ] Reenviar antes de 60s -> erro de cooldown
- [ ] Cancelar troca -> popup some
- [ ] Tentar POST em rota sob `email.verified` com pending ativo -> 423 (JSON) ou flash de erro (web)
- [ ] `php artisan email-change:cleanup-expired` remove > 30d
EOF
)"
```

- [ ] **Step 3:** Anotar URL do PR.

---

## Spec coverage check

| Spec section | Tasks que entregam |
|---|---|
| 2. Solucao | Phases 1-6 (todas) |
| 3. Decisoes #1 (popup persistente) | 5.2 + 5.3 |
| 3. Decisoes #2 (TTL/tentativas) | 1.3 (constantes) + 2.1 (service) |
| 3. Decisoes #3 (pending change) | 2.1 (`requestChange` nao toca `users.email`) |
| 3. Decisoes #4 (admin segue fluxo) | 4.5 (`UserManagementController::update`) |
| 3. Decisoes #5 (tabela dedicada) | 1.1 + 1.2 |
| 4.1 Migration | 1.1 + 1.2 |
| 4.3 Model | 1.3 |
| 4.4 User patch | 1.4 |
| 5. Service | 2.1 |
| 6.1 Controller | 4.2 |
| 6.2 ProfileController patch | 4.4 |
| 6.3 UserManagementController patch | 4.5 |
| 6.4 Rotas | 4.6 |
| 6.5 Middleware | 4.3 + 4.6 step 3 |
| 7.1 Mailable verification | 3.1 + 3.2 |
| 7.2 Mailable notice | 3.3 + 3.4 |
| 8.1 Shared props | 4.7 |
| 8.2 OtpInput | 5.1 |
| 8.2 EmailChangeVerifyModal | 5.2 |
| 8.3 Mount global | 5.3 |
| 8.4 Toasts modais existentes | 5.4 + 5.5 |
| 9. Seguranca (constants, throttle, lockForUpdate, mask, hash) | 1.3 + 2.1 + 4.6 |
| 10. Auditoria | 2.1 (chamadas a `AuditLog::log`) |
| 11. Scheduler | 6.1 + 6.2 |
| 12. Testes | Phase 7.1 (executa locais) — nao committed por politica |
| 13. Out of scope | n/a |
| 14. Migracao operacional | sem backfill (n/a) |
| 15. Estrutura de arquivos | espelhada nas tasks |

---

## No-regression / known caveats

- O cache `inertia_user_data_{user_id}` precisa ser invalidado quando:
  - `requestChange` cria um pending (caching de 5min sumiria com isso na pratica — porem o helper `buildPendingEmailChange` le `activeEmailChangeRequest` que e relacao Eloquent dentro do cache **fechado em closure**). **Acao:** apos `$emailChangeService->requestChange(...)` em `ProfileController::update` e `UserManagementController::update`, adicionar `Cache::forget("inertia_user_data_{$user->id}");`.
- Se uma rota POST que recebe o middleware `email.verified` nao foi marcada nas tasks de 4.6 step 3, o usuario consegue contornar o gate. **Acao:** revisar `route:list` e expandir se necessario antes de mergear.
- `AuditLog::log` usa `Auth::id()` por padrao quando `userId` e null. Para fluxo "admin troca e-mail do user X", passar explicitamente `$byAdmin?->id ?? $user->id` (ja feito em 2.1).
