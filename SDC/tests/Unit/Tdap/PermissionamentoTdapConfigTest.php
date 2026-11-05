<?php

declare(strict_types=1);

namespace Tests\Unit\Tdap;

use PHPUnit\Framework\TestCase;

/**
 * Permissionamento do TDAP, lido direto do config/permissions.php (sem banco).
 * Os cargos Fiscal e SGT da #85 foram descartados (2026-11-05) e saíram daqui.
 */
class PermissionamentoTdapConfigTest extends TestCase
{
    /** @return array<string, mixed> */
    private function config(): array
    {
        return require __DIR__.'/../../../config/permissions.php';
    }

    public function test_compdec_so_acessa_confirmar_recebimento_no_tdap(): void
    {
        $tdap = array_values(array_filter(
            $this->config()['role_permissions']['compdec'],
            static fn (string $slug): bool => str_starts_with($slug, 'tdap.'),
        ));

        sort($tdap);
        // tdap.cronogramas.view entrou junto com a confirmacao pelo COMPDEC
        // (36242bfd): o coordenador precisa ver o cronograma da viagem que atesta.
        $this->assertSame(
            ['tdap.cronogramas.view', 'tdap.viagens.confirmar', 'tdap.viagens.reprovar', 'tdap.viagens.view'],
            $tdap,
        );
    }

    public function test_cargos_novos_nao_colidem_de_nivel_com_outros(): void
    {
        $niveis = $this->config()['levels'];

        $this->assertSame(count($niveis), count(array_unique($niveis)));
    }
}
