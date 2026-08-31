<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `outbox_events.aggregate_id` era UUID, mas o outbox e generico e serve
 * agregados de chave INTEIRA tambem.
 *
 * SINTOMA: qualquer `persist()` de evento cujo agregado tem id auto-incremento
 * estourava
 *
 *     SQLSTATE[22P02]: invalid input syntax for type uuid: "4406"
 *
 * Isso derrubava, com 500, dois fluxos do TDAP:
 *   - CronogramaService::ativar()  -> CronogramaAtivadoV1 (aggregate_id = id do
 *     cronograma). O e-mail ao prestador dependia deste evento, logo NUNCA foi
 *     enviado pela via do outbox.
 *   - CronoViagemService::validar() -> ViagemValidadaV1 (aggregate_id = id da
 *     viagem), que alimenta a projecao do processo e a EncerramentoSaga.
 *
 * A tabela estava VAZIA quando esta migration foi escrita -- nenhum evento
 * jamais foi persistido com sucesso --, o que confirma o diagnostico e torna a
 * conversao trivial.
 *
 * varchar(64) e nao text: cabe UUID (36), ULID (26) e id numerico com folga, e
 * mantem o indice compacto. Os indices sobrevivem ao ALTER TYPE; o Postgres os
 * reconstroi sozinho.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->tipoAtual() === 'uuid') {
            DB::statement('ALTER TABLE outbox_events ALTER COLUMN aggregate_id TYPE VARCHAR(64) USING aggregate_id::text');
        }
    }

    public function down(): void
    {
        // Volta a uuid apenas se TODO valor presente for um UUID valido —
        // caso contrario a conversao falharia no meio e deixaria a tabela
        // inutilizavel. Linha com id numerico e justamente o que motivou a
        // mudanca, entao o rollback e recusado em silencio.
        if ($this->tipoAtual() !== 'character varying') {
            return;
        }

        $temValorNaoUuid = DB::table('outbox_events')
            ->whereRaw("aggregate_id !~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'")
            ->exists();

        if ($temValorNaoUuid) {
            return;
        }

        DB::statement('ALTER TABLE outbox_events ALTER COLUMN aggregate_id TYPE UUID USING aggregate_id::uuid');
    }

    private function tipoAtual(): ?string
    {
        $tipo = DB::table('information_schema.columns')
            ->where('table_name', 'outbox_events')
            ->where('column_name', 'aggregate_id')
            ->value('data_type');

        return $tipo === null ? null : (string) $tipo;
    }
};
