<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Copia os arquivos do legado para as collections do MediaLibrary.
 *
 * No legado eram ~54 colunas de caminho de arquivo. As fotos do imovel
 * ficavam em cisterna/{cpf}/ — CPF no caminho, dado pessoal — e as de
 * vistoria em relatorios/cisterna/{form}/{id}/.
 *
 * As colunas img_*_lk guardavam link do Google Drive, nao arquivo local:
 * preservadas em custom_properties.origem_legado para conferencia manual.
 */
class RefinaMidia implements Refinador
{
    /**
     * Coluna do legado -> angulo da foto do imovel.
     *
     * @var array<string, string>
     */
    private const ANGULOS = [
        'img_frontal' => 'frontal',
        'img_lat_direito' => 'lateral_direita',
        'img_lat_esquerdo' => 'lateral_esquerda',
        'img_fundo' => 'fundo',
        'img_local_ins_p1' => 'local_instalacao_1',
        'img_local_ins_p2' => 'local_instalacao_2',
        'img_op1' => 'opcional_1',
        'img_op2' => 'opcional_2',
        'img_op3' => 'opcional_3',
        'img_op4' => 'opcional_4',
    ];

    public function recurso(): string
    {
        return 'midia';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $beneficiario = CisternaBeneficiario::where('legacy_id', $legacyId)->first();

        if ($beneficiario === null) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Beneficiario nao importado: midia ignorada.');

            return;
        }

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'dry-run: copiaria as fotos e comprovantes.');

            return;
        }

        $copiadas = 0;
        $ausentes = [];

        // Fotos do imovel. No legado o diretorio era o CPF sem mascara.
        $cpf = $beneficiario->cpf;

        foreach (self::ANGULOS as $coluna => $angulo) {
            $observacao = trim((string) ($doc[$coluna] ?? ''));
            $caminho = "cisterna/{$cpf}/{$cpf}{$coluna}.jpg";

            if (! Storage::disk('legado_cisterna')->exists($caminho)) {
                // Link do Google Drive: nao ha arquivo local para copiar.
                // A coluna _lk guarda link do Drive em 5.129 linhas, mas
                // tambem placeholder ('0', '-', '.') em 679: chamar placeholder
                // de link mandaria a area procurar arquivo que nao existe.
                $link = trim((string) ($doc[$coluna.'_lk'] ?? ''));

                $ausentes[] = str_starts_with($link, 'http')
                    ? "{$angulo} (Google Drive: {$link})"
                    : "{$angulo} (sem arquivo e sem link)";

                continue;
            }

            if ($beneficiario->getMedia('fotos_imovel')->contains(
                fn ($m): bool => $m->getCustomProperty('angulo') === $angulo
            )) {
                continue;
            }

            $beneficiario
                ->addMediaFromDisk($caminho, 'legado_cisterna')
                ->preservingOriginal()
                ->withCustomProperties([
                    'angulo' => $angulo,
                    'observacao' => $observacao === '' ? null : $observacao,
                    'origem_legado' => $caminho,
                ])
                ->toMediaCollection('fotos_imovel');

            $copiadas++;
        }

        $copiadas += $this->copiarComprovantes($beneficiario, $doc, $ausentes);

        // As vistorias precisam do doc DELAS, nao do beneficiario: os caminhos
        // das fotos estao nas colunas das tabelas de relatorio.
        foreach ($beneficiario->vistorias as $vistoria) {
            $docVistoria = $this->docDaVistoria($vistoria);

            if ($docVistoria === null) {
                continue;
            }

            $copiadas += $this->copiarMidiaDaVistoria($vistoria, $docVistoria, $ausentes);
        }

        if ($ausentes !== []) {
            // `ignorado`, e nao `erro`: arquivo do legado que nao esta nesta
            // maquina e foto que sempre foi link do Google Drive nao sao falha
            // da carga -- sao 5.264 casos conhecidos e documentados. Marcados
            // como erro, afogariam o log e esconderiam defeito de verdade.
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Arquivos nao localizados: '.implode('; ', $ausentes));

            return;
        }

        RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId,
            $beneficiario->id, ['copiadas' => $copiadas]);
    }

    /**
     * @param  array<string, mixed>  $doc
     * @param  array<int, string>  $ausentes
     */
    private function copiarComprovantes(CisternaBeneficiario $beneficiario, array $doc, array &$ausentes): int
    {
        $mapa = [
            'anexo_deficiencia' => 'deficiencia',
            'anexo_mulher' => 'chefia_mulher',
            'anexo_observacao' => 'observacao',
        ];

        $copiados = 0;

        foreach ($mapa as $coluna => $tipo) {
            $caminho = trim((string) ($doc[$coluna] ?? ''));

            if ($caminho === '') {
                continue;
            }

            if (! Storage::disk('legado_cisterna')->exists($caminho)) {
                $ausentes[] = "comprovante {$tipo} ({$caminho})";

                continue;
            }

            if ($beneficiario->getMedia('comprovantes')->contains(
                fn ($m): bool => $m->getCustomProperty('tipo') === $tipo
            )) {
                continue;
            }

            $beneficiario
                ->addMediaFromDisk($caminho, 'legado_cisterna')
                ->preservingOriginal()
                ->withCustomProperties(['tipo' => $tipo, 'origem_legado' => $caminho])
                ->toMediaCollection('comprovantes');

            $copiados++;
        }

        return $copiados;
    }

    /**
     * Le de volta o doc cru da vistoria, para pegar as colunas de caminho.
     *
     * @return array<string, mixed>|null
     */
    private function docDaVistoria(CisternaVistoria $vistoria): ?array
    {
        if ($vistoria->legacy_id === null) {
            return null;
        }

        // A area de pouso identifica a origem por (tabela, pk_legado), e
        // pk_legado e varchar -- nao existe coluna legacy_id aqui. Com o nome
        // errado, os 791 beneficiarios que tem vistoria morriam com
        // "Undefined column" e a midia da vistoria nao era nem tentada.
        $linha = DB::table('cisterna_legado_raw')
            ->where('tabela', $vistoria->etapa->tabelaLegado())
            ->where('pk_legado', (string) $vistoria->legacy_id)
            ->value('doc');

        $doc = $linha === null ? null : json_decode((string) $linha, true);

        return is_array($doc) ? $doc : null;
    }

    /**
     * Fotos e assinatura das vistorias.
     *
     * **Le as COLUNAS do doc, nao varre o diretorio.** Varrer parecia mais
     * simples, mas quebra na pratica, por tres motivos verificados nos dados
     * de producao:
     *
     *  1. A assinatura tem nome ALEATORIO, nao "assinatura*":
     *     `relatorios/cisterna/form_fornecedor_fiscalizacao/1694/Qpueq2...`
     *     Sao 736 assinaturas no fornecedor e 658 no COMPDEC que seriam
     *     classificadas como "foto nao reconhecida".
     *  2. Os caminhos gravados tem BARRA DUPLA: o legado concatenava um
     *     diretorio que ja terminava em "/" com o nome do arquivo.
     *  3. A extensao vem VAZIA: `cisterna_foto1.` termina em ponto, porque
     *     getClientOriginalExtension() devolveu string vazia no upload.
     *
     * Lendo a coluna, o vinculo item -> arquivo vem do dado e nao de
     * convencao de nome.
     *
     * @param  array<string, mixed>  $doc  Linha crua da tabela de relatorio.
     * @param  array<int, string>  $ausentes
     */
    private function copiarMidiaDaVistoria(
        CisternaVistoria $vistoria,
        array $doc,
        array &$ausentes,
    ): int {
        $copiadas = 0;

        // Assinatura: coluna unica em todas as tres etapas.
        $assinatura = $this->normalizarCaminho($doc['assinatura_eng_foto'] ?? null);

        if ($assinatura !== null && $vistoria->getMedia('assinatura_engenheiro')->isEmpty()) {
            $copiadas += $this->copiar($vistoria, $assinatura, 'assinatura_engenheiro', [], $ausentes);
        }

        // Fotos por item. O nome da coluna muda por etapa: o fornecedor tem
        // {item}_foto1 e {item}_foto2; o COMPDEC tem {item}_foto, e o item do
        // logo se chama cisterna_logo la e cisterna aqui.
        foreach (ItemInstalacao::cases() as $item) {
            foreach ($this->colunasDeFoto($vistoria->etapa, $item) as $sequencia => $coluna) {
                $caminho = $this->normalizarCaminho($doc[$coluna] ?? null);

                if ($caminho === null) {
                    continue;
                }

                $jaTem = $vistoria->getMedia('fotos_vistoria')->contains(
                    fn ($m): bool => $m->getCustomProperty('item') === $item->value
                        && (int) $m->getCustomProperty('sequencia') === $sequencia
                );

                if ($jaTem) {
                    continue;
                }

                $copiadas += $this->copiar($vistoria, $caminho, 'fotos_vistoria', [
                    'item' => $item->value,
                    'sequencia' => $sequencia,
                ], $ausentes);
            }
        }

        return $copiadas;
    }

    /**
     * Colunas de foto de um item, por etapa, na ordem da sequencia (1-based).
     *
     * @return array<int, string>
     */
    private function colunasDeFoto(EtapaVistoria $etapa, ItemInstalacao $item): array
    {
        // A etapa CEDEC nao guarda foto por item, apenas a assinatura.
        if ($etapa === EtapaVistoria::CEDEC) {
            return [];
        }

        if ($etapa === EtapaVistoria::COMPDEC) {
            // cisterna_logo_foto, sucao_foto, bomba_foto, ...
            return [1 => $item->value.'_foto'];
        }

        // Fornecedor: o primeiro item se chama `cisterna`, nao `cisterna_logo`.
        $base = $item === ItemInstalacao::CISTERNA_LOGO ? 'cisterna' : $item->value;

        return [1 => $base.'_foto1', 2 => $base.'_foto2'];
    }

    /**
     * Colapsa barra dupla e descarta vazio. Caminho terminando em ponto (por
     * extensao vazia no upload) e preservado como esta: quem sabe se o arquivo
     * existe e o disco, nao a gente.
     */
    private function normalizarCaminho(mixed $valor): ?string
    {
        $caminho = trim((string) ($valor ?? ''));

        if ($caminho === '' || $caminho === '0') {
            return null;
        }

        return preg_replace('#/{2,}#', '/', $caminho) ?? $caminho;
    }

    /**
     * @param  array<string, mixed>  $propriedades
     * @param  array<int, string>  $ausentes
     */
    private function copiar(
        CisternaVistoria|CisternaBeneficiario $dono,
        string $caminho,
        string $collection,
        array $propriedades,
        array &$ausentes,
    ): int {
        if (! Storage::disk('legado_cisterna')->exists($caminho)) {
            $ausentes[] = $caminho;

            return 0;
        }

        $dono->addMediaFromDisk($caminho, 'legado_cisterna')
            ->preservingOriginal()
            ->withCustomProperties(array_merge($propriedades, ['origem_legado' => $caminho]))
            ->toMediaCollection($collection);

        return 1;
    }
}
