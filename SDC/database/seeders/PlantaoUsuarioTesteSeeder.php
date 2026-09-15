<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use App\Modules\Plantao\Models\Plantonista;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Conta de teste para EXERCITAR AS NOTIFICACOES do plantao.
 *
 * Existe por uma razao especifica do desenho do modulo de notificacoes: o
 * dispatcher DESCARTA o autor da acao. Quem publica a escala nao recebe o aviso
 * de escala publicada; quem encerra o turno nao recebe a pendencia de aceite.
 * Isso e correto -- ninguem precisa ser avisado do que acabou de fazer -- mas
 * torna impossivel ver qualquer notificacao operando com uma unica conta.
 *
 * Com esta conta o ciclo fecha: o admin escala e publica, esta recebe; esta
 * assume e encerra, o admin recebe a pendencia de aceite.
 *
 * As permissoes dadas sao as MINIMAS para o papel de plantonista comum: ver e
 * abrir turno, aceitar passagem e ver a escala. Deliberadamente SEM montar
 * escala, sem gerir plantonistas e sem excluir turno -- assim a conta tambem
 * serve para conferir que a tela se comporta para quem nao e montador.
 *
 * Idempotente: identifica por e-mail e nao duplica.
 */
class PlantaoUsuarioTesteSeeder extends Seeder
{
    private const EMAIL = 'plantonista.teste@defesa.mg.gov.br';
    // Conferido livre no banco antes de fixar: 99900011122 e 90000000001, os
    // primeiros candidatos obvios, ja pertenciam a contas existentes -- e `cpf`
    // e unique.
    private const CPF = '99988877766';
    private const SENHA = 'Plantao@2026';

    /**
     * Papel de plantonista comum, nao de montador.
     */
    private const PERMISSOES = [
        'plantao.turnos.view',
        'plantao.turnos.create',
        'plantao.turnos.edit',
        'plantao.viaturas.view',
        'plantao.viaturas.movimentar',
        'plantao.passagem.encerrar',
        'plantao.passagem.aceitar',
        'plantao.passagem.relatorio',
        'plantao.escala.view',
    ];

    public function run(): void
    {
        $usuario = User::withTrashed()->firstWhere('email', self::EMAIL);

        if ($usuario === null) {
            $usuario = User::create([
                'name' => 'Sgt Plantonista Teste',
                'email' => self::EMAIL,
                'cpf' => self::CPF,
                'password' => Hash::make(self::SENHA),
                'active' => true,
                'status' => 'active',
                'email_verified_at' => now(),
                // Sem tela de troca de senha no primeiro acesso: a conta existe
                // para testar notificacao, nao o fluxo de onboarding.
                'must_change_password' => false,
            ]);

            $this->command?->info('Conta criada.');
        } else {
            $usuario->restore();
            $usuario->update(['active' => true, 'status' => 'active']);
            $this->command?->info('Conta ja existia; reativada.');
        }

        // Permissoes DIRETAS, nao por cargo: nao mexe em cargo compartilhado
        // com usuarios reais.
        foreach (self::PERMISSOES as $slug) {
            Permission::findOrCreate($slug, 'web');
        }

        $usuario->syncPermissions(self::PERMISSOES);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Escalavel, para poder receber "voce foi escalado".
        Plantonista::updateOrCreate(
            ['user_id' => $usuario->id],
            ['posto' => 'Sgt', 'ativo' => true],
        );

        $this->command?->newLine();
        $this->command?->info('Usuario de teste do plantao');
        $this->command?->line('  nome:  Sgt Plantonista Teste');
        $this->command?->line('  CPF:   '.self::CPF);
        $this->command?->line('  senha: '.self::SENHA);
        $this->command?->line('  id:    '.$usuario->id);
        $this->command?->newLine();
        $this->command?->line('Para ver notificacao: como admin, escale esta conta');
        $this->command?->line('e publique a escala. O aviso chega no sino dela.');
    }
}
