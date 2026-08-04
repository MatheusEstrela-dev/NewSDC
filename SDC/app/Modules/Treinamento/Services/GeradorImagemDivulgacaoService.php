<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Treinamento;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\Font;

/**
 * RF02 - gera a imagem de divulgacao sobrepondo os dados reais do
 * treinamento no template fornecido pela Diretoria de Educacao
 * (public/imgs/divulgacao.png, 1275x664).
 *
 * O template ja vem com textos de exemplo desenhados ("DEFESA CIVIL",
 * "NOME DO EVENTO" etc.) - cada zona e "apagada" com um retangulo solido na
 * cor de fundo antes do texto real ser escrito por cima.
 *
 * O retangulo de apagar usa imagefilledrectangle() do GD diretamente (via
 * core()->first()->native()) em vez de Image::drawRectangle(): a API de alto
 * nivel do Intervention v4 provoca um artefato de "fantasma" (duplicacao com
 * leve deslocamento) em texto anti-aliased vizinho quando a borda do
 * retangulo encosta nele - GD puro nao tem esse problema. Importante manter
 * uma margem de alguns pixels entre cada retangulo e o texto estatico
 * vizinho (labels como "TIPO DO EVENTO") para nao acionar o mesmo artefato.
 *
 * Sem um .ttf configurado em TTF_BOLD_PATH/TTF_REGULAR_PATH, o GD usa a
 * fonte bitmap embutida (pequena, tamanhos fixos) - para o resultado ficar
 * legivel e fiel ao template, e necessario uma fonte livre (ex: Poppins,
 * SIL Open Font License) em public/fonts/.
 */
class GeradorImagemDivulgacaoService
{
    private const TEMPLATE_PATH = 'imgs/divulgacao.png';
    private const TTF_BOLD_PATH = 'fonts/Poppins-Bold.ttf';
    private const TTF_REGULAR_PATH = 'fonts/Poppins-Regular.ttf';

    private const COR_FUNDO = [0x17, 0x1e, 0x39];
    private const COR_LARANJA = '#f67a2b';
    private const COR_BRANCA = '#ffffff';

    public function gerar(Treinamento $treinamento): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->decodePath(public_path(self::TEMPLATE_PATH));

        // Categoria (substitui o "DEFESA CIVIL" de exemplo). Comeca em
        // y=100, nao 78: precisa de folga da label "TIPO DO EVENTO" acima.
        $this->apagarZona($image, 20, 100, 700, 220);
        $image->text(
            $treinamento->categoria->getLabel(),
            32,
            230,
            $this->fonte(bold: true, tamanho: 62, cor: self::COR_LARANJA)
        );

        // Nome do evento/curso
        $this->apagarZona($image, 20, 328, 750, 55);
        $image->text(
            $this->truncar($treinamento->titulo, 55),
            32,
            372,
            $this->fonte(bold: false, tamanho: 27, cor: self::COR_BRANCA)
        );

        // Descricao (curta, uma linha)
        $this->apagarZona($image, 20, 410, 750, 50);
        if ($treinamento->descricao) {
            $image->text(
                $this->truncar($treinamento->descricao, 85),
                32,
                448,
                $this->fonte(bold: false, tamanho: 18, cor: self::COR_BRANCA)
            );
        }

        // Local (ou modalidade, se online)
        $textoLocal = $treinamento->tipo === TipoTreinamento::ONLINE
            ? 'Online'
            : ($treinamento->local ?? 'A definir');

        $this->apagarZona($image, 95, 555, 240, 45);
        $image->text(
            $this->truncar($textoLocal, 20),
            98,
            590,
            $this->fonte(bold: false, tamanho: 23, cor: self::COR_BRANCA)
        );

        // Data e hora
        $dataTexto = optional($treinamento->data_inicio)->format('d/m/Y') ?? 'A definir';
        if ($treinamento->hora_inicio) {
            $dataTexto .= ' - ' . substr($treinamento->hora_inicio, 0, 5);
        }

        $this->apagarZona($image, 405, 555, 280, 45);
        $image->text(
            $dataTexto,
            410,
            590,
            $this->fonte(bold: false, tamanho: 23, cor: self::COR_BRANCA)
        );

        return $image->encode()->toString();
    }

    /**
     * imagefilledrectangle() puro (ver nota da classe sobre o artefato do
     * Image::drawRectangle() do Intervention).
     */
    private function apagarZona(ImageInterface $image, int $x, int $y, int $largura, int $altura): void
    {
        $gd = $image->core()->first()->native();
        $cor = imagecolorallocate($gd, ...self::COR_FUNDO);
        imagefilledrectangle($gd, $x, $y, $x + $largura, $y + $altura, $cor);
    }

    private function fonte(bool $bold, int $tamanho, string $cor): Font
    {
        $caminho = public_path($bold ? self::TTF_BOLD_PATH : self::TTF_REGULAR_PATH);

        $font = new Font(size: $tamanho, color: $cor);

        if (file_exists($caminho)) {
            $font->setFilepath($caminho);
        }

        return $font;
    }

    private function truncar(string $texto, int $tamanho): string
    {
        return mb_strlen($texto) > $tamanho ? mb_substr($texto, 0, $tamanho - 1) . '…' : $texto;
    }
}
