<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\DataPorExtenso;
use App\Modules\Treinamento\Support\VersoesDoCurso;
use Carbon\CarbonInterface;

/**
 * Dados da pagina de impressao do certificado (aluno ou instrutor), no
 * formato do modelo da DEPDC: curso com a versao, periodo e data por extenso,
 * e o QR que leva a validacao publica.
 */
class ImpressaoDeCertificado
{
    public const CONCLUINTE = 'CONCLUINTE';

    public const INSTRUTOR = 'INSTRUTOR';

    public function __construct(private readonly GeradorQrCodeService $qrCode) {}

    /** @return array<string, mixed> */
    public function dados(
        string $papel,
        Treinamento $treinamento,
        string $nome,
        string $codigo,
        ?CarbonInterface $emitidoEm,
        ?float $aproveitamento = null,
    ): array {
        $versao = VersoesDoCurso::rotuloDe($treinamento);
        $urlValidacao = route('certificados.validar', $codigo);
        $evento = $treinamento->categoria === CategoriaTreinamento::EVENTO;

        return [
            'papel' => $papel,
            'nome' => $nome,
            'curso' => $treinamento->titulo.($versao ? ' - '.$versao : ''),
            'artigoCurso' => $evento ? 'o evento' : 'a capacitação',
            'realizado' => $evento ? 'realizado' : 'realizada',
            // Concorda com o artigo acima: "o evento ... promovido", "a capacitacao ... promovida".
            'promovido' => $evento ? 'promovido' : 'promovida',
            'periodo' => DataPorExtenso::periodo($treinamento->data_inicio, $treinamento->data_fim),
            'cargaHoraria' => $treinamento->carga_horaria,
            'aproveitamento' => $papel === self::CONCLUINTE ? $aproveitamento : null,
            'codigoVerificacao' => $codigo,
            'enderecoValidacao' => preg_replace('#^https?://#', '', route('certificados.validar')),
            'qrValidacao' => 'data:image/png;base64,'.base64_encode($this->qrCode->gerarPng($urlValidacao)),
            'dataEmissao' => DataPorExtenso::data($emitidoEm ?? now()),
        ];
    }
}
