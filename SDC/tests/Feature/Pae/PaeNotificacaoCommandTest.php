<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Mail\PaeNotificacaoMail;
use App\Models\User;
use App\Modules\Pae\Models\PaeEmpnto;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaeNotificacaoCommandTest extends TestCase
{
    use DatabaseTransactions;

    private function protocoloDelegadoComEmail(): PaeProtocolo
    {
        $empnto = PaeEmpnto::factory()->create([
            'email_coord'     => 'coord@example.com',
            'email_coord_sub' => 'sub@example.com',
        ]);

        return PaeProtocolo::factory()->create([
            'status'            => 'notificacao',
            'analista_atual_id' => User::factory()->create()->id,
            'pae_empnto_id'     => $empnto->id,
        ]);
    }

    public function test_emitir_envia_email_para_coordenador(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();

        app(PaeNotificacaoService::class)
            ->emitir($protocolo, User::factory()->create(), ['num_sei' => 'SEI-1']);

        Mail::assertQueued(PaeNotificacaoMail::class, function (PaeNotificacaoMail $mail) {
            return $mail->hasTo('coord@example.com') && $mail->hasCc('sub@example.com');
        });
    }

    public function test_ciclo_vencido_emite_proxima_automaticamente(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();
        $service = app(PaeNotificacaoService::class);

        $this->travelTo(now()->subDays(31), function () use ($service, $protocolo) {
            $service->emitir($protocolo, User::factory()->create(), ['num_sei' => 'SEI-1']);
        });

        $this->artisan('pae:verificar-notificacoes')->assertSuccessful();

        $this->assertSame(2, \App\Modules\Pae\Models\PaeNotificacao::whereHas(
            'analise',
            fn ($q) => $q->where('pae_protocolo_id', $protocolo->id)
        )->count());
    }

    public function test_terceiro_ciclo_vencido_suspende_protocolo(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();
        $service = app(PaeNotificacaoService::class);
        $user = User::factory()->create();

        $this->travelTo(now()->subDays(95), fn () => $service->emitir($protocolo, $user, ['num_sei' => 'SEI-1']));
        $this->artisan('pae:verificar-notificacoes'); // emite ciclo 2 (retroativo, dt = hoje-0... ver nota)

        // Forcar vencimento dos ciclos 2 e 3 ajustando as datas diretamente:
        \App\Modules\Pae\Models\PaeNotificacao::query()->update([
            'dt_notificacao' => now()->subDays(40)->toDateString(),
        ]);
        $this->artisan('pae:verificar-notificacoes'); // emite ciclo 3

        \App\Modules\Pae\Models\PaeNotificacao::query()->update([
            'dt_notificacao' => now()->subDays(40)->toDateString(),
        ]);
        $this->artisan('pae:verificar-notificacoes'); // suspende

        $this->assertSame('suspenso', $protocolo->fresh()->status->value);
    }

    public function test_comando_e_idempotente_no_mesmo_dia(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();
        $service = app(PaeNotificacaoService::class);

        $this->travelTo(now()->subDays(31), fn () => $service->emitir($protocolo, User::factory()->create(), ['num_sei' => 'SEI-1']));

        $this->artisan('pae:verificar-notificacoes');
        $this->artisan('pae:verificar-notificacoes');

        $this->assertSame(2, \App\Modules\Pae\Models\PaeNotificacao::whereHas(
            'analise',
            fn ($q) => $q->where('pae_protocolo_id', $protocolo->id)
        )->count());
    }
}
