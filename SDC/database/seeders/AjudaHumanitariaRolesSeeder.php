<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Cargos CEDEC do modulo Ajuda Humanitaria.
 *
 * Os cargos nao sao invencao: saem do proprio fluxo de aprovacao do pedido. O
 * enum StatusPedidoAh define AnaliseDlog e AnaliseDiretorDlog, EtapaParecer
 * define analise_dlog e analise_coord, e pedidos_ah carrega analista_id e
 * diretor_id. Sao esses os dois papeis da CEDEC na cadeia; o terceiro ator,
 * EdicaoCompdec, e o municipio, que nao e cargo da CEDEC.
 *
 * O legado dizia o mesmo por outro caminho: aju_h_permissao tinha as colunas
 * analista_drd, analista_dlog e analista_coord.
 *
 * Nenhuma permissao nova e criada aqui. As 22 humanitaria.* ja existem no
 * banco; este seeder apenas agrupa quem pode o que.
 *
 * Toda atribuicao de cargo a usuario passa por PermissionEventSubscriber, que
 * grava role_assigned e role_removed em permission_audit_log com autor, IP e
 * estado anterior. Nao e preciso auditar aqui.
 */
class AjudaHumanitariaRolesSeeder extends Seeder
{
    private const GUARD = 'web';

    /**
     * Leitura pura, comum aos tres cargos.
     */
    private const LEITURA = [
        'humanitaria.pedidos.view',
        'humanitaria.prestacao.view',
        'humanitaria.saldo.view',
        'humanitaria.beneficiarios.view',
    ];

    /**
     * O que o analista faz alem de ler: instrui o processo, mas nao decide.
     * Liberar item, homologar prestacao e mexer em parametro ficam de fora de
     * proposito, porque sao atos de decisao.
     */
    private const ANALISTA = [
        'humanitaria.pedidos.edit',
        'humanitaria.pedidos.parecer',
        'humanitaria.pedidos.tramitar',
        'humanitaria.pedidos.anexos',
        'humanitaria.pedidos.export',
        'humanitaria.pedidos.print',
        'humanitaria.prestacao.lancar',
        'humanitaria.beneficiarios.create',
        'humanitaria.beneficiarios.edit',
        'humanitaria.beneficiarios.export',
        'humanitaria.beneficiarios.print',
        // Registrar entrada de material e operacao de deposito, nao decisao
        // sobre o pedido: fica com quem toca o dia a dia. O Diretor herda.
        'humanitaria.estoque.movimentar',
    ];

    /**
     * O que so o diretor faz: decide.
     */
    private const DIRETOR = [
        'humanitaria.pedidos.create',
        'humanitaria.pedidos.delete',
        'humanitaria.pedidos.liberar_itens',
        'humanitaria.prestacao.homologar',
        'humanitaria.materiais.manage',
        'humanitaria.parametros.manage',
        'humanitaria.beneficiarios.delete',
    ];

    public function run(): void
    {
        $analista = $this->cargo(
            'analista-dlog',
            'Analista DLOG',
            3,
            'Analista da Diretoria de Logistica: instrui o pedido de ajuda humanitaria, emite parecer e tramita'
        );
        $analista->syncPermissions([...self::LEITURA, ...self::ANALISTA]);

        $diretor = $this->cargo(
            'diretor-dlog',
            'Diretor DLOG',
            2,
            'Diretor da Diretoria de Logistica: aprova o pedido, libera itens e homologa a prestacao de contas'
        );
        $diretor->syncPermissions([...self::LEITURA, ...self::ANALISTA, ...self::DIRETOR]);

        $leitor = $this->cargo(
            'leitor-ajuda-humanitaria',
            'Leitor Ajuda Humanitaria',
            5,
            'Acesso somente leitura ao modulo Ajuda Humanitaria'
        );
        $leitor->syncPermissions(self::LEITURA);

        $this->command?->info(sprintf(
            'Cargos CEDEC do modulo: %s (%d permissoes), %s (%d), %s (%d).',
            $analista->name,
            $analista->permissions()->count(),
            $diretor->name,
            $diretor->permissions()->count(),
            $leitor->name,
            $leitor->permissions()->count(),
        ));
    }

    private function cargo(string $slug, string $nome, int $nivel, string $descricao): Role
    {
        return Role::updateOrCreate(
            ['slug' => $slug, 'guard_name' => self::GUARD],
            [
                'name' => $nome,
                'slug' => $slug,
                'guard_name' => self::GUARD,
                'hierarchy_level' => $nivel,
                'description' => $descricao,
                'is_active' => true,
            ]
        );
    }
}
