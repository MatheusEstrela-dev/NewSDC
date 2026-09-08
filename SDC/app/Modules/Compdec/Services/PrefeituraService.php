<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Models\Municipio;
use App\Modules\Compdec\DTOs\PrefeituraDTO;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Models\Prefeitura;
use App\Modules\Compdec\Support\LegacyParser;
use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class PrefeituraService
{
    public function obterPorOrgao(int $orgaoId): ?Prefeitura
    {
        $orgao = Orgao::findOrFail($orgaoId);

        if (! $orgao->municipio_id) {
            return null;
        }

        return Prefeitura::query()
            ->where('municipio_id', $orgao->municipio_id)
            ->first();
    }

    public function upsertPorOrgao(int $orgaoId, PrefeituraDTO $dto): Prefeitura
    {
        return DB::transaction(function () use ($orgaoId, $dto): Prefeitura {
            $orgao = Orgao::findOrFail($orgaoId);

            if (! $orgao->municipio_id) {
                throw new InvalidArgumentException('Orgao nao possui municipio vinculado; nao e possivel criar prefeitura.');
            }

            // garante que o DTO traz o municipio_id correto do orgao
            $payload = $dto->toArray();
            $payload['municipio_id'] = $orgao->municipio_id;

            // legacy_id e a ponte com o registro de origem no legado e tem
            // indice proprio. O formulario nao envia esse campo, porque
            // UpsertPrefeituraRequest nao o valida: o DTO chega com null e o
            // toArray() emite a chave assim mesmo, entao o updateOrCreate
            // apagaria a rastreabilidade de quem ja veio do ETL. Quem escreve
            // nessa coluna e so quem passa o valor explicito.
            if ($dto->legacyId === null) {
                unset($payload['legacy_id']);
            }

            return Prefeitura::updateOrCreate(
                ['municipio_id' => $orgao->municipio_id],
                $payload,
            );
        });
    }

    public function uploadFoto(int $prefeituraId, UploadedFile $arquivo): Media
    {
        $prefeitura = Prefeitura::findOrFail($prefeituraId);

        return $prefeitura
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO, config('compdec.disk', 'compdec'));
    }

    public function removerFoto(int $prefeituraId): bool
    {
        $prefeitura = Prefeitura::findOrFail($prefeituraId);
        $prefeitura->clearMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO);

        return true;
    }

    public function obterPrefeituraPorOrgaoOuFalhar(int $orgaoId): Prefeitura
    {
        $prefeitura = $this->obterPorOrgao($orgaoId);

        if (! $prefeitura) {
            throw new ModelNotFoundException('Prefeitura nao cadastrada para este orgao.');
        }

        return $prefeitura;
    }

    /* ============================================================
     * ETL: Migracao do legado para compdec_prefeituras
     * ============================================================ */

    /**
     * Migra prefeituras do legado gestaocedec:
     *   - cedec_municipio (854 linhas) e a fonte de prefeito_nome/telefone/celular,
     *     contato institucional principal, endereco e INSS.
     *   - cedec_prefeitura (854 linhas, join por id_municipio) complementa com
     *     partido, e-mails/telefones secundarios (fallback) e a foto.
     *   - Ponte para o NewSDC: cedec_municipio.Codmundv = municipios.codigo_ibge
     *     (mesmo padrao de ImportCedecMunicipioCommand). Como 'legado_gestaocedec'
     *     (MySQL) e a conexao padrao (Postgres) sao conexoes diferentes, a resolucao
     *     e feita em duas etapas: le o chunk do legado, depois busca os municipio_id
     *     correspondentes em uma unica query por chunk.
     *   - id_municipio = 7221 (sentinela "MUNICIPIO TESTE", Codmundv = 0) e excluida.
     *   - Conexao lida de config('cedec.legacy_connection'), NAO de
     *     config('compdec.legacy_connection'): esta ultima aponta para 'legacy'
     *     (banco dbsdc, legado sdc/Laravel), usada por outros consumidores do
     *     Compdec, Pmda e AjudaHumanitaria -- schema incompativel com
     *     cedec_municipio/cedec_prefeitura, que vivem no legado gestaocedec.
     */
    public function migrarLegado(int $chunk = 100, bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('prefeituras');
        $report->dryRun = $dryRun;
        $connection = config('cedec.legacy_connection', 'legado_gestaocedec');

        DB::connection($connection)
            ->table('cedec_municipio as m')
            ->leftJoin('cedec_prefeitura as p', 'p.id_municipio', '=', 'm.id_municipio')
            ->where('m.id_municipio', '!=', 7221)
            ->select(
                'm.id_municipio as legacy_id',
                'm.Codmundv as codmundv',
                'm.prefeito as prefeito_nome',
                'm.tel_pref as prefeito_telefone',
                'm.cel_pref as prefeito_celular',
                'p.partido as prefeito_partido',
                'm.email as email_municipio',
                'p.email as email_prefeitura_legado',
                'p.email2 as email_prefeitura_2',
                'p.email3 as email_prefeitura_3',
                'm.tel as tel_municipio',
                'p.tel1 as tel_prefeitura_legado',
                'p.tel2 as tel_prefeitura_2',
                'm.fax as fax_municipio',
                'p.fax as fax_prefeitura_legado',
                'm.endereco',
                'm.bairro',
                'm.cep',
                'm.latitude_dec as latitude',
                'm.longitude_dec as longitude',
                'm.cobra_iss',
                'm.aliquota_iss',
                'm.num_lei_iss',
                'm.resp_cob_iss',
                'p.fotoPref as foto_prefeito',
            )
            ->orderBy('m.id_municipio')
            ->chunk($chunk, function ($linhas) use ($report, $dryRun): void {
                $codigos = $linhas->pluck('codmundv')->filter()->unique()->values()->all();

                $mapaMunicipios = Municipio::query()
                    ->whereIn('codigo_ibge', $codigos)
                    ->pluck('id', 'codigo_ibge');

                foreach ($linhas as $row) {
                    $this->migrarPrefeituraLegada($row, $mapaMunicipios, $report, $dryRun);
                }
            });

        return $report;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $mapaMunicipios  codigo_ibge => municipio_id
     */
    private function migrarPrefeituraLegada(object $row, $mapaMunicipios, MigracaoReport $report, bool $dryRun): void
    {
        $legacyId = LegacyParser::toIntOrNull($row->legacy_id ?? null);
        $codmundv = LegacyParser::toStringOrNull($row->codmundv ?? null);

        if ($codmundv === null || ! $mapaMunicipios->has($codmundv)) {
            $report->registrarSkip();
            $this->logEtl($legacyId, null, 'skipped', 'Codmundv sem municipio correspondente em municipios.codigo_ibge', $row, $dryRun);

            return;
        }

        $municipioId = (int) $mapaMunicipios->get($codmundv);

        try {
            $emailPrefeitura = $this->resolverEmailComPrecedencia(
                [
                    'cedec_municipio.email' => $row->email_municipio ?? null,
                    'cedec_prefeitura.email' => $row->email_prefeitura_legado ?? null,
                ],
                $legacyId,
                'email_prefeitura',
                $row,
                $dryRun,
            );

            $emailPrefeitura2 = $this->validarOuDescartarEmail(
                $this->sanitizarEmail($row->email_prefeitura_2 ?? null),
                $legacyId,
                'email_prefeitura_2',
                $row,
                $dryRun,
            );

            $emailPrefeitura3 = $this->validarOuDescartarEmail(
                $this->sanitizarEmail($row->email_prefeitura_3 ?? null),
                $legacyId,
                'email_prefeitura_3',
                $row,
                $dryRun,
            );

            $telPrefeitura = $this->sanitizarTelefone($row->tel_municipio ?? null)
                ?? $this->sanitizarTelefone($row->tel_prefeitura_legado ?? null);

            $faxPrefeitura = $this->sanitizarTelefone($row->fax_municipio ?? null)
                ?? $this->sanitizarTelefone($row->fax_prefeitura_legado ?? null);

            $payload = [
                'municipio_id' => $municipioId,
                'prefeito_nome' => LegacyParser::toStringOrNull($row->prefeito_nome ?? null),
                'prefeito_telefone' => $this->sanitizarTelefone($row->prefeito_telefone ?? null),
                'prefeito_celular' => $this->sanitizarTelefone($row->prefeito_celular ?? null),
                'prefeito_email' => null,
                'prefeito_partido' => LegacyParser::toStringOrNull($row->prefeito_partido ?? null),
                'email_prefeitura' => $emailPrefeitura,
                'email_prefeitura_2' => $emailPrefeitura2,
                'email_prefeitura_3' => $emailPrefeitura3,
                'tel_prefeitura' => $telPrefeitura,
                'tel_prefeitura_2' => $this->sanitizarTelefone($row->tel_prefeitura_2 ?? null),
                'fax_prefeitura' => $faxPrefeitura,
                'endereco' => LegacyParser::toStringOrNull($row->endereco ?? null),
                'bairro' => LegacyParser::toStringOrNull($row->bairro ?? null),
                'cep' => LegacyParser::toStringOrNull($row->cep ?? null),
                'latitude' => $this->sanitizarCoordenada($row->latitude ?? null),
                'longitude' => $this->sanitizarCoordenada($row->longitude ?? null),
                'inss_tem_cobranca' => LegacyParser::toBool($row->cobra_iss ?? null),
                'inss_aliquota' => isset($row->aliquota_iss) && $row->aliquota_iss !== null ? LegacyParser::toDecimalBR($row->aliquota_iss) : null,
                'inss_lei_cobranca' => LegacyParser::toStringOrNull($row->num_lei_iss ?? null),
                'inss_responsavel' => LegacyParser::toStringOrNull($row->resp_cob_iss ?? null),
                'legacy_id' => $legacyId,
            ];

            if ($dryRun) {
                $existente = Prefeitura::query()->where('municipio_id', $municipioId)->exists();
                $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();

                return;
            }

            $existente = Prefeitura::query()->where('municipio_id', $municipioId)->first();
            $prefeitura = Prefeitura::query()->updateOrCreate(['municipio_id' => $municipioId], $payload);

            $this->migrarFotoPrefeito($prefeitura, LegacyParser::toStringOrNull($row->foto_prefeito ?? null), $legacyId, $dryRun);

            if ($existente) {
                $report->registrarAtualizacao();
                $this->logEtl($legacyId, $prefeitura->id, 'updated', null, $row, false);
            } else {
                $report->registrarInsercao();
                $this->logEtl($legacyId, $prefeitura->id, 'inserted', null, $row, false);
            }
        } catch (Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl($legacyId, null, 'error', $e->getMessage(), $row, $dryRun);
        }
    }

    /**
     * Copia a foto do prefeito do disco legado (cedec_prefeitura.fotoPref guarda so o
     * nome do arquivo) para a Media Library. O nome no legado e sujo por natureza --
     * acento removido, as vezes extensao duplicada, como "120_Foto_Prefeito.jpg.jpg" --
     * e entra como esta, sem tentativa de limpeza. preservingOriginal() garante que o
     * arquivo fonte, montado read-only, nunca e apagado.
     */
    private function migrarFotoPrefeito(Prefeitura $prefeitura, ?string $nomeArquivoLegado, ?int $legacyId, bool $dryRun): void
    {
        if ($nomeArquivoLegado === null || $dryRun) {
            return;
        }

        $diretorio = rtrim((string) config('compdec.legacy_paths.foto_prefeito'), '/');
        $caminhoCompleto = $diretorio . '/' . $nomeArquivoLegado;

        if (! is_file($caminhoCompleto)) {
            $this->logEtl($legacyId, $prefeitura->id, 'skipped', "foto_prefeito nao encontrada em {$caminhoCompleto}", null, false);

            return;
        }

        try {
            $prefeitura
                ->addMedia($caminhoCompleto)
                ->preservingOriginal()
                ->usingFileName($nomeArquivoLegado)
                ->toMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO, config('compdec.disk', 'compdec'));
        } catch (Throwable $e) {
            $this->logEtl($legacyId, $prefeitura->id, 'skipped', "falha ao migrar foto_prefeito: {$e->getMessage()}", null, false);
        }
    }

    private function sanitizarEmail(?string $valor): ?string
    {
        $valor = $this->limparCampoSujo($valor);

        return $valor === null ? null : mb_strtolower($valor);
    }

    private function sanitizarTelefone(?string $valor): ?string
    {
        return $this->limparCampoSujo($valor);
    }

    /**
     * Higienizacao comum: trim, via LegacyParser::toStringOrNull, que ja trata "" como
     * null, mais o traco solto ("-") que o legado usa como marcador de "nao preenchido"
     * em cedec_prefeitura.tel2 e afins.
     */
    private function limparCampoSujo(?string $valor): ?string
    {
        $valor = LegacyParser::toStringOrNull($valor);

        return $valor === '-' ? null : $valor;
    }

    private function validarOuDescartarEmail(?string $email, ?int $legacyId, string $campo, object $row, bool $dryRun): ?string
    {
        if ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            return $email;
        }

        $this->logEtl($legacyId, null, 'skipped', "{$campo} invalido no legado: {$email}", $row, $dryRun);

        return null;
    }

    /**
     * Resolve um campo com fallback -- email_prefeitura vem de cedec_municipio.email e,
     * na falta dele, de cedec_prefeitura.email -- validando CADA candidato antes de
     * escolher, em vez de escolher o primeiro preenchido e so entao validar. Sem isso,
     * um e-mail malformado na fonte de maior precedencia anula o resultado e impede que
     * o fallback valido seja usado. Se nenhum candidato for valido, grava null e
     * registra em compdec_etl_log o que foi descartado; candidato vazio ou ausente nao
     * conta como descarte.
     *
     * @param  array<string, mixed>  $candidatos  fonte (para o log) => valor bruto, na ordem de precedencia
     */
    private function resolverEmailComPrecedencia(array $candidatos, ?int $legacyId, string $campo, object $row, bool $dryRun): ?string
    {
        $descartados = [];

        foreach ($candidatos as $fonte => $valorBruto) {
            $email = $this->sanitizarEmail($valorBruto);

            if ($email === null) {
                continue;
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                return $email;
            }

            $descartados[] = "{$fonte}={$email}";
        }

        if ($descartados !== []) {
            $this->logEtl(
                $legacyId,
                null,
                'skipped',
                "{$campo} invalido no legado, nenhum candidato valido: " . implode('; ', $descartados),
                $row,
                $dryRun,
            );
        }

        return null;
    }

    /**
     * LegacyParser::toDecimalBR nunca devolve null: vazio ou null viram 0.0, que e um
     * ponto real no Golfo da Guine e passaria a ser tratado como coordenada valida.
     * Filtra null/vazio/"-" ANTES de chamar toDecimalBR, na borda deste ETL, reusando
     * limparCampoSujo em vez de alterar o LegacyParser, compartilhado por outros ETLs.
     */
    private function sanitizarCoordenada(mixed $valor): ?float
    {
        $texto = $this->limparCampoSujo($valor === null ? null : (string) $valor);

        return $texto === null ? null : LegacyParser::toDecimalBR($texto);
    }

    private function logEtl(
        ?int $legacyId,
        ?int $newId,
        string $acao,
        ?string $motivo,
        mixed $payload,
        bool $dryRun,
    ): void {
        if ($dryRun) {
            return;
        }

        DB::table('compdec_etl_log')->insert([
            'recurso' => 'prefeituras',
            'legacy_table' => 'cedec_municipio+cedec_prefeitura',
            'legacy_id' => $legacyId ?? 0,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload !== null ? json_encode((array) $payload) : null,
            'created_at' => now(),
        ]);
    }
}
