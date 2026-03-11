<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed de permissões do módulo RAT.
 *
 * Segue o padrão de slug: (fm) slug.modulo.ação conforme o Padrão Ouro.
 *
 * Permissões criadas:
 *   Protocolo RAT: view, create, edit, finalize, delete, export
 *   Relatos RAT:   manage (dados_gerais, envolvidos, recursos, vistoria)
 *   BI/Analytics:  rat.bi.view
 *   Histórico:     rat.historico.view
 *   API Mobile:    rat.api.access
 */
class RatPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    /**
     * Permissões RAT completas agrupadas por domínio.
     */
    private const PERMISSIONS = [
        // ------------------------------------------------------------------ //
        // Protocolos / Ocorrências
        // ------------------------------------------------------------------ //
        'rat.protocolos.view' => [
            'description' => 'Listar e visualizar RATs e ocorrências',
        ],
        'rat.protocolos.create' => [
            'description' => 'Criar novos RATs e registrar ocorrências (BOS)',
        ],
        'rat.protocolos.edit' => [
            'description' => 'Editar dados de RATs e ocorrências em rascunho/andamento',
        ],
        'rat.protocolos.finalize' => [
            'description' => 'Finalizar RATs e ocorrências (bloqueia edição)',
        ],
        'rat.protocolos.delete' => [
            'description' => 'Remover RATs e ocorrências (soft delete)',
        ],
        'rat.protocolos.export' => [
            'description' => 'Exportar RATs em CSV ou PDF',
        ],

        // ------------------------------------------------------------------ //
        // Relatos (Dados Gerais, Envolvidos, Recursos, Vistoria)
        // ------------------------------------------------------------------ //
        'rat.relatos.manage' => [
            'description' => 'Criar, editar e remover relatos de uma ocorrência (envolvidos, recursos, vistoria, dados gerais)',
        ],

        // ------------------------------------------------------------------ //
        // Business Intelligence / Power BI
        // ------------------------------------------------------------------ //
        'rat.bi.view' => [
            'description' => 'Acessar dados agregados e dashboards BI do módulo RAT',
        ],

        // ------------------------------------------------------------------ //
        // Histórico
        // ------------------------------------------------------------------ //
        'rat.historico.view' => [
            'description' => 'Visualizar a timeline de eventos de uma ocorrência RAT',
        ],

        // ------------------------------------------------------------------ //
        // Acesso via API Mobile / Integrações externas
        // ------------------------------------------------------------------ //
        'rat.api.access' => [
            'description' => 'Acesso aos endpoints REST da API mobile do módulo RAT (Sanctum)',
        ],
    ];

    public function run(): void
    {
        // Garante que o cache de permissões seja limpo, evitando conflitos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name => $attributes) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
                $attributes
            );
        }

        // ------------------------------------------------------------------ //
        // Atribui permissões aos roles existentes
        // ------------------------------------------------------------------ //

        // Super Admin — acesso total (já gerenciado por Spatie via role super-admin)
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(array_keys(self::PERMISSIONS));
        }

        // Analista RAT — acesso operacional completo (sem delete)
        $analista = Role::firstOrCreate(
            ['name' => 'analista-rat', 'guard_name' => self::GUARD],
            ['description' => 'Analista do módulo RAT com acesso operacional completo']
        );
        $analista->syncPermissions([
            'rat.protocolos.view',
            'rat.protocolos.create',
            'rat.protocolos.edit',
            'rat.protocolos.finalize',
            'rat.protocolos.export',
            'rat.relatos.manage',
            'rat.historico.view',
            'rat.api.access',
        ]);

        // Coordenador RAT — tudo incluindo delete e BI
        $coordenador = Role::firstOrCreate(
            ['name' => 'coordenador-rat', 'guard_name' => self::GUARD],
            ['description' => 'Coordenador do módulo RAT com acesso gerencial']
        );
        $coordenador->syncPermissions(array_keys(self::PERMISSIONS));

        // Leitor RAT — somente visualização
        $leitor = Role::firstOrCreate(
            ['name' => 'leitor-rat', 'guard_name' => self::GUARD],
            ['description' => 'Acesso somente leitura ao módulo RAT']
        );
        $leitor->syncPermissions([
            'rat.protocolos.view',
            'rat.historico.view',
        ]);

        $this->command->info('[RatPermissionsSeeder] ' . count(self::PERMISSIONS) . ' permissões RAT sincronizadas com sucesso.');
    }
}
