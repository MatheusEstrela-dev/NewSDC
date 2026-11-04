<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Models;

use App\Modules\Medalhao\Support\TabelasGeo;
use App\Support\Database\ConexaoGeo;
use App\Support\Database\Geo\DominioGeo;
use App\Support\Database\UsaConexaoGeo;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Payload bruto de uma coleta. Cada dominio tem a propria tabela
 * (<dominio>_bronze.ingestao_bruta), entao o model nao vale sem dizer o
 * dominio: use doDominio() ou doGrupo().
 */
class IngestaoBruta extends Model
{
    use UsaConexaoGeo;

    // A tabela usa coletado_em/processado_em, nao created_at/updated_at: o
    // instante da coleta e dado da ingestao, nao metadado do ORM.
    public $timestamps = false;

    protected $fillable = [
        'fonte',
        'conteudo_bruto',
        'formato',
        'hash_conteudo',
        'meta',
        'coletado_em',
        'processado_em',
        'verificado_em',
    ];

    protected $casts = [
        'meta' => 'array',
        'coletado_em' => 'datetime',
        'processado_em' => 'datetime',
        'verificado_em' => 'datetime',
    ];

    /**
     * Sem dominio nao ha tabela certa: falhar alto e melhor que gravar o
     * bruto de um dominio no schema de outro.
     */
    public function getTable(): string
    {
        return $this->table
            ?? throw new LogicException('IngestaoBruta exige o dominio: use doDominio() ou doGrupo().');
    }

    public static function doDominio(DominioGeo $dominio): static
    {
        return (new static)->setTable(TabelasGeo::ingestaoBruta($dominio));
    }

    /** Tabela bruta do dominio que guarda o grupo (inmet, sismos...). */
    public static function doGrupo(string $grupo): static
    {
        return static::doDominio(TabelasGeo::dominioDoGrupo($grupo));
    }

    /**
     * Instante da verificacao mais recente entre as fontes informadas.
     *
     * Fica no kernel, e nao nos repositorios de dominio, porque a pergunta
     * "quando checamos por ultimo" e identica para sismos, INMET e CEMADEN --
     * so muda a lista de fontes, que o chamador obtem do IngestorRegistry.
     *
     * Le pela conexao de leitura: e consulta de tela, nao de ingestao.
     *
     * @param list<string> $fontes
     */
    public static function verificadoEm(DominioGeo $dominio, array $fontes): ?string
    {
        if ($fontes === []) {
            return null;
        }

        return ConexaoGeo::leitura()
            ->table(TabelasGeo::ingestaoBruta($dominio))
            ->whereIn('fonte', $fontes)
            ->max('verificado_em');
    }
}
