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
    /**
     * Colunas que a aba de prefeitura do Compdec de fato gerencia -- as mesmas que o
     * UpsertPrefeituraRequest valida.
     *
     * Fora desta lista, nada e tocado por aquela aba: contato institucional (as sete
     * colunas do modulo Cedec) e legacy_id ficam de fora de proposito.
     */
    private const CAMPOS_DA_ABA_COMPDEC = [
        'prefeito_nome',
        'prefeito_telefone',
        'prefeito_celular',
        'prefeito_email',
        'endereco',
        'bairro',
        'cep',
        'latitude',
        'longitude',
        'inss_tem_cobranca',
        'inss_aliquota',
        'inss_lei_cobranca',
        'inss_responsavel',
    ];

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

            // Escreve SO o que a aba do Compdec gerencia.
            //
            // PrefeituraDTO::toArray() emite todas as colunas, inclusive as sete de
            // contato institucional e o legacy_id. O UpsertPrefeituraRequest desta aba
            // nao valida nenhuma delas, entao chegam nulas -- e gravar esse null
            // apagaria o que o ETL carregou e a rastreabilidade da origem.
            //
            // A guarda e por LISTA do que a aba controla, nao por excecao campo a
            // campo: a versao anterior protegia apenas legacy_id e deixou as sete
            // colunas novas desprotegidas quando elas nasceram. Campo novo que a aba
            // nao tenha passa a ser preservado por omissao, nao por lembranca.
            $payload = array_intersect_key($dto->toArray(), array_flip(self::CAMPOS_DA_ABA_COMPDEC));
            $payload['municipio_id'] = $orgao->municipio_id;

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

                $payloads = [];
                $fotos = [];
                $origens = [];

                foreach ($linhas as $row) {
                    $preparado = $this->prepararPrefeituraLegada($row, $mapaMunicipios, $report, $dryRun);

                    if ($preparado === null) {
                        continue;
                    }

                    [$municipioId, $payload, $foto] = $preparado;

                    // Indexado por municipio_id: se o legado trouxer duas linhas para o
                    // mesmo municipio, a ultima vence -- e o upsert nao pode receber
                    // duas linhas com a mesma chave unica no mesmo lote.
                    $payloads[$municipioId] = $payload;
                    $origens[$municipioId] = $row;

                    if ($foto !== null) {
                        $fotos[$municipioId] = $foto;
                    }
                }

                $this->gravarLote($payloads, $fotos, $origens, $report, $dryRun);
                $this->descarregarLogs();
            });

        $this->descarregarLogs();

        return $report;
    }

    /**
     * Grava o chunk inteiro em tres consultas, em vez de tres POR MUNICIPIO.
     *
     * A carga real de 853 municipios disparava o guarda de query budget do projeto
     * ("possivel N+1"): cada linha fazia um SELECT para saber se ja existia, um
     * updateOrCreate e um INSERT no log de ETL.
     *
     * @param  array<int, array<string, mixed>>  $payloads  municipio_id => payload
     * @param  array<int, string>  $fotos  municipio_id => nome do arquivo no legado
     * @param  array<int, object>  $origens  municipio_id => linha crua, para o log
     */
    private function gravarLote(array $payloads, array $fotos, array $origens, MigracaoReport $report, bool $dryRun): void
    {
        if ($payloads === []) {
            return;
        }

        $municipioIds = array_keys($payloads);

        // 1 de 3: quem ja existe. Serve para separar inseridos de atualizados no
        // relatorio -- o upsert sozinho nao conta isso.
        //
        // withTrashed() e obrigatorio: o unique de municipio_id e do BANCO e ignora
        // deleted_at. Sem isso, uma prefeitura soft-deleted ficaria fora desta lista,
        // o upsert casaria com ela pelo indice e o recarregamento abaixo -- que
        // tambem precisa de withTrashed -- nao a encontraria, produzindo um erro
        // falso de "linha nao encontrada".
        $jaExistiam = Prefeitura::withTrashed()
            ->whereIn('municipio_id', $municipioIds)
            ->pluck('municipio_id')
            ->all();

        if ($dryRun) {
            foreach ($municipioIds as $municipioId) {
                in_array($municipioId, $jaExistiam, true)
                    ? $report->registrarAtualizacao()
                    : $report->registrarInsercao();
            }

            return;
        }

        try {
            // 2 de 3: um upsert para o lote. A chave e municipio_id, que ja e unique
            // na tabela.
            //
            // Fora dos atualizaveis: a propria chave, e prefeito_email, que o ETL
            // grava como null fixo por nao haver coluna de origem no legado. Deixa-lo
            // na lista fazia cada reexecucao apagar o e-mail que alguem digitou pela
            // tela -- ele entra no INSERT da primeira carga e nunca mais e tocado.
            $colunas = array_keys(reset($payloads));
            $atualizaveis = array_values(array_diff($colunas, ['municipio_id', 'prefeito_email']));

            Prefeitura::query()->upsert(array_values($payloads), ['municipio_id'], $atualizaveis);

            // 3 de 3: recarrega o lote para ter os ids e os models das fotos.
            // withTrashed pelo mesmo motivo da consulta 1 de 3.
            $prefeituras = Prefeitura::withTrashed()
                ->whereIn('municipio_id', $municipioIds)
                ->get()
                ->keyBy('municipio_id');
        } catch (Throwable $e) {
            // Falha de lote nao tem uma linha culpada: registra uma vez, com os
            // municipios envolvidos, em vez de fingir que foi de um municipio so.
            $report->registrarErro(null, $e->getMessage());
            $this->logEtl(null, null, 'error', 'falha ao gravar lote: ' . $e->getMessage(), ['municipio_ids' => $municipioIds], $dryRun);

            return;
        }

        foreach ($municipioIds as $municipioId) {
            $prefeitura = $prefeituras->get($municipioId);
            $legacyId = $payloads[$municipioId]['legacy_id'] ?? null;

            if ($prefeitura === null) {
                $report->registrarErro($legacyId, 'linha nao encontrada apos o upsert do lote');
                $this->logEtl($legacyId, null, 'error', 'linha nao encontrada apos o upsert do lote', $origens[$municipioId] ?? null, false);

                continue;
            }

            if (isset($fotos[$municipioId])) {
                $this->migrarFotoPrefeito($prefeitura, $fotos[$municipioId], $legacyId, false);
            }

            if (in_array($municipioId, $jaExistiam, true)) {
                $report->registrarAtualizacao();
                $this->logEtl($legacyId, $prefeitura->id, 'updated', null, $origens[$municipioId] ?? null, false);
            } else {
                $report->registrarInsercao();
                $this->logEtl($legacyId, $prefeitura->id, 'inserted', null, $origens[$municipioId] ?? null, false);
            }
        }
    }

    /**
     * Traduz UMA linha do legado no payload de compdec_prefeituras. Nao grava nada:
     * quem grava e gravarLote(), em lote.
     *
     * @param  \Illuminate\Support\Collection<string, int>  $mapaMunicipios  codigo_ibge => municipio_id
     * @return array{0: int, 1: array<string, mixed>, 2: ?string}|null  [municipio_id, payload, nome da foto] ou null quando a linha e descartada
     */
    private function prepararPrefeituraLegada(object $row, $mapaMunicipios, MigracaoReport $report, bool $dryRun): ?array
    {
        $legacyId = LegacyParser::toIntOrNull($row->legacy_id ?? null);
        $codmundv = LegacyParser::toStringOrNull($row->codmundv ?? null);

        if ($codmundv === null || ! $mapaMunicipios->has($codmundv)) {
            $report->registrarSkip();
            $this->logEtl($legacyId, null, 'skipped', 'Codmundv sem municipio correspondente em municipios.codigo_ibge', $row, $dryRun);

            return null;
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

            return [
                $municipioId,
                $payload,
                LegacyParser::toStringOrNull($row->foto_prefeito ?? null),
            ];
        } catch (Throwable $e) {
            // Erro de TRADUCAO da linha (parse, sanitizacao). Erro de GRAVACAO e do
            // lote e fica em gravarLote().
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl($legacyId, null, 'error', $e->getMessage(), $row, $dryRun);

            return null;
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

        if ($texto === null) {
            return null;
        }

        $numero = LegacyParser::toDecimalBR($texto);

        // Zero tambem e ausencia, nao coordenada. O legado grava 0 em vez de null em
        // parte das linhas, e 0,0 e um ponto real no Golfo da Guine: passaria por
        // localizacao valida em qualquer mapa ou calculo de distancia. Nenhuma cidade
        // de Minas fica no equador nem no meridiano de Greenwich, entao aqui zero
        // nunca e dado bom.
        return $numero === 0.0 ? null : $numero;
    }

    /**
     * Linhas de log aguardando gravacao. Acumular e descarregar por lote e o que tira
     * o terceiro INSERT por municipio -- eram 853 inserts numa carga completa.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $logsPendentes = [];

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

        $this->logsPendentes[] = [
            'recurso' => 'prefeituras',
            'legacy_table' => 'cedec_municipio+cedec_prefeitura',
            'legacy_id' => $legacyId ?? 0,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload !== null ? json_encode((array) $payload) : null,
            'created_at' => now(),
        ];
    }

    /**
     * Grava o log acumulado numa consulta so. Chamado ao fim de cada chunk e ao fim da
     * migracao -- nunca deixa linha pendente, mesmo quando o ultimo chunk e parcial.
     */
    private function descarregarLogs(): void
    {
        if ($this->logsPendentes === []) {
            return;
        }

        DB::table('compdec_etl_log')->insert($this->logsPendentes);

        $this->logsPendentes = [];
    }
}
