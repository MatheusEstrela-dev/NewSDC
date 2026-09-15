<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Services;

use App\Modules\Geoespacial\DTOs\FeicaoKmlDTO;
use DOMDocument;
use RuntimeException;
use ZipArchive;

/**
 * Le KML e KMZ e devolve os fragmentos de geometria.
 *
 * Nao interpreta coordenada, nao calcula area, nao valida topologia: isso e
 * trabalho do PostGIS, via ST_GeomFromKML. Aqui so se separa o XML em pedacos.
 */
final class KmlExtrator
{
    /** Tamanho maximo descomprimido, contra zip bomb. */
    private const LIMITE_BYTES = 32 * 1024 * 1024;

    /** Geometrias que o ST_GeomFromKML aceita como raiz do fragmento. */
    private const RAIZES = ['MultiGeometry', 'Polygon', 'LineString', 'Point'];

    public function conteudoDeArquivo(string $caminho): string
    {
        if (! is_file($caminho)) {
            throw new RuntimeException("Arquivo nao encontrado: {$caminho}");
        }

        // KMZ e ZIP. A assinatura decide, e nao a extensao: extensao e o que o
        // usuario escreveu, assinatura e o que o arquivo e.
        $assinatura = (string) file_get_contents($caminho, false, null, 0, 2);

        return $assinatura === 'PK' ? $this->doKmz($caminho) : $this->doArquivoTexto($caminho);
    }

    /** @return list<FeicaoKmlDTO> */
    public function feicoes(string $conteudo): array
    {
        $dom = $this->carregar($conteudo);
        $feicoes = [];

        foreach ($dom->getElementsByTagName('Placemark') as $placemark) {
            foreach (self::RAIZES as $raiz) {
                $geometrias = $placemark->getElementsByTagName($raiz);

                if ($geometrias->length === 0) {
                    continue;
                }

                // Para o primeiro tipo encontrado: MultiGeometry ja contem
                // Polygon dentro, e pegar os dois duplicaria a feicao.
                $nome = null;

                foreach ($placemark->getElementsByTagName('name') as $tag) {
                    $nome = trim((string) $tag->nodeValue);
                    break;
                }

                $xml = $dom->saveXML($geometrias->item(0));

                if ($xml === false) {
                    continue;
                }

                $feicoes[] = new FeicaoKmlDTO(
                    // Neste arquivo todo Placemark se chama "0": nome inutil
                    // vira null para a camada cair no nome do Document.
                    nome: ($nome === '' || $nome === '0') ? null : $nome,
                    kmlGeometria: $xml,
                );

                break;
            }
        }

        if ($feicoes === []) {
            throw new RuntimeException('Nenhuma geometria encontrada: o arquivo nao parece ser um KML valido.');
        }

        return $feicoes;
    }

    public function nomeDoDocumento(string $conteudo): ?string
    {
        $dom = $this->carregar($conteudo);

        foreach ($dom->getElementsByTagName('Document') as $documento) {
            foreach ($documento->getElementsByTagName('name') as $tag) {
                $nome = trim((string) $tag->nodeValue);

                return $nome === '' ? null : $nome;
            }
        }

        return null;
    }

    /**
     * O XML vem de fora, entao o parse e fechado por opcao explicita:
     * LIBXML_NONET corta acesso a rede, e a ausencia de LIBXML_NOENT e de
     * LIBXML_DTDLOAD e o que impede entidade externa ler arquivo do servidor.
     */
    private function carregar(string $conteudo): DOMDocument
    {
        $dom = new DOMDocument();
        $anterior = libxml_use_internal_errors(true);

        $ok = $dom->loadXML($conteudo, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        if ($ok === false) {
            throw new RuntimeException('Conteudo nao e XML valido.');
        }

        return $dom;
    }

    private function doKmz(string $caminho): string
    {
        $zip = new ZipArchive();

        if ($zip->open($caminho) !== true) {
            throw new RuntimeException('KMZ ilegivel: ZIP invalido.');
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if ($stat === false || ! str_ends_with(strtolower((string) $stat['name']), '.kml')) {
                    continue;
                }

                // Checa o tamanho DESCOMPRIMIDO antes de extrair: depois de
                // extrair, a memoria do worker ja foi.
                if ((int) $stat['size'] > self::LIMITE_BYTES) {
                    throw new RuntimeException('KMZ recusado: conteudo descomprimido acima do limite.');
                }

                $conteudo = $zip->getFromIndex($i);

                if ($conteudo === false) {
                    throw new RuntimeException('Falha ao ler o KML de dentro do KMZ.');
                }

                return $conteudo;
            }
        } finally {
            $zip->close();
        }

        throw new RuntimeException('KMZ nao contem nenhum arquivo .kml.');
    }

    private function doArquivoTexto(string $caminho): string
    {
        if ((int) filesize($caminho) > self::LIMITE_BYTES) {
            throw new RuntimeException('KML recusado: acima do limite de tamanho.');
        }

        return (string) file_get_contents($caminho);
    }
}
