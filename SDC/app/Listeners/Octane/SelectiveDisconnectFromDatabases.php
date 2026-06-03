<?php

declare(strict_types=1);

namespace App\Listeners\Octane;

use Illuminate\Database\DatabaseManager;

class SelectiveDisconnectFromDatabases
{
    /**
     * Conexoes que mudam por request (tenancy dinamica, ETL ad-hoc).
     * Devem ser desconectadas ao fim de cada operacao para evitar
     * vazamento de estado entre requests no mesmo worker Octane.
     */
    private const CONEXOES_VOLATEIS = ['tenancy', 'legacy', 'carga'];

    public function __construct(private DatabaseManager $db) {}

    public function handle(object $event): void
    {
        foreach (self::CONEXOES_VOLATEIS as $nome) {
            $this->db->purge($nome);
        }
    }
}
