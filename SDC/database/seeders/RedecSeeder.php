<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Decretacoes\Services\RedecService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popula `dec_redecs` com as 19 Regioes de Defesa Civil (REDEC) de Minas Gerais.
 *
 * ESTE ARQUIVO E A CARGA INICIAL, NAO A FONTE DE VERDADE EM TEMPO DE EXECUCAO.
 * Depois de aplicado, quem manda e a tabela: incluir, renomear ou desativar uma
 * regional e um UPDATE/INSERT em `dec_redecs`, sem deploy de codigo. Antes esta
 * lista vivia num enum PHP (`Enums\Redec`), o que obrigava a alterar codigo para
 * corrigir uma sede e deixou o sistema 14 REDECs atrasado por meses.
 *
 * FONTE dos dados: relacao de regionais publicada pela propria CEDEC em
 * sistema.defesacivil.mg.gov.br (acao usuarioRegionaisSite). Cada REDEC
 * corresponde a uma Regiao da Policia Militar (RPM) e leva o nome da cidade
 * sede - por isso a coluna e `sede`, e nao a antiga divisao por mesorregiao.
 *
 * Os ids sao os mesmos do legado (`cedec_municipio.redec_id` e `rat_redec.id`),
 * o que permite a correspondencia municipio -> REDEC direto pelo dump legado.
 * Por isso a chave primaria NAO e auto-incremento: o numero da REDEC e o id.
 *
 * Idempotente: `upsert` pelo id, pode ser reexecutado sem duplicar. Nao mexe em
 * `ativo`, para nao ressuscitar regional que a CEDEC tenha desativado no banco.
 */
class RedecSeeder extends Seeder
{
    /**
     * Carga inicial: id (numero da REDEC) => cidade sede.
     *
     * Usada tambem pela migration que cria a tabela, para que um `migrate` sem
     * `db:seed` nao deixe as listas suspensas vazias em producao.
     *
     * @var array<int, string>
     */
    public const SEDES = [
        1  => 'Belo Horizonte',
        2  => 'Contagem',
        3  => 'Santa Luzia',
        4  => 'Juiz de Fora',
        5  => 'Uberaba',
        6  => 'Lavras',
        7  => 'Divinópolis',
        8  => 'Governador Valadares',
        9  => 'Uberlândia',
        10 => 'Patos de Minas',
        11 => 'Montes Claros',
        12 => 'Ipatinga',
        13 => 'Barbacena',
        14 => 'Curvelo',
        15 => 'Teófilo Otoni',
        16 => 'Unaí',
        17 => 'Pouso Alegre',
        18 => 'Poços de Caldas',
        19 => 'Sete Lagoas',
    ];

    /**
     * Linhas prontas para insert/upsert em `dec_redecs`.
     *
     * As colunas derivadas (sigla, rpm, nome) sao montadas aqui uma unica vez:
     * a partir da carga inicial elas passam a ser dado, editavel no banco.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function linhas(): array
    {
        $agora = now();

        return array_map(
            fn (int $id, string $sede) => [
                'id'         => $id,
                'sigla'      => $id . 'ª REDEC',
                'sede'       => $sede,
                'rpm'        => $id . 'ª RPM',
                'nome'       => 'Região de Defesa Civil de ' . $sede . ' (' . $id . 'ª RPM)',
                'ativo'      => true,
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
            array_keys(self::SEDES),
            array_values(self::SEDES)
        );
    }

    public function run(): void
    {
        DB::table('dec_redecs')->upsert(
            self::linhas(),
            ['id'],
            ['sigla', 'sede', 'rpm', 'nome', 'updated_at']
        );

        // upsert pelo Query Builder nao dispara eventos de model, entao o hook
        // de invalidacao do model Redec nao roda: limpa aqui. Em deploy o store
        // de cache pode nem estar de pe, e isso nao e motivo para falhar o seed
        // - o TTL de 24h fecha a conta, e `cache:clear` resolve na hora.
        try {
            RedecService::clearCache();
        } catch (\Throwable $e) {
            $this->command?->warn('RedecSeeder: cache nao invalidado (' . $e->getMessage() . ').');
        }

        $this->command?->info('RedecSeeder: ' . count(self::SEDES) . ' REDECs de Minas Gerais sincronizadas.');
    }
}
