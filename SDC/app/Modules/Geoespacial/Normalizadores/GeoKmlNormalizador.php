<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Normalizadores;

use App\Modules\Geoespacial\DTOs\CamadaGeoDTO;
use App\Modules\Geoespacial\Services\KmlExtrator;
use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use RuntimeException;

final class GeoKmlNormalizador implements NormalizadorSilver
{
    public function __construct(
        private readonly KmlExtrator $extrator,
    ) {
    }

    public function normalizar(PayloadBruto $bruto): iterable
    {
        $envelope = json_decode($bruto->conteudo, true);

        if (! is_array($envelope) || ! isset($envelope['kml'])) {
            throw new RuntimeException('Envelope do upload sem a chave kml.');
        }

        $kml = (string) $envelope['kml'];

        yield new CamadaGeoDTO(
            dominio: (string) ($envelope['dominio'] ?? 'geologico'),
            nome: (string) ($envelope['nome'] ?? $this->extrator->nomeDoDocumento($kml) ?? 'Camada sem nome'),
            arquivoNome: (string) ($envelope['arquivo_nome'] ?? 'desconhecido.kml'),
            emitidoEm: $envelope['emitido_em'] ?? null,
            validoAte: $envelope['valido_ate'] ?? null,
            nivel: $envelope['nivel'] ?? null,
            // Hash do KML, nao do envelope: mudar o nivel na tela nao deve criar
            // camada nova com a mesma geometria, senao o mapa mostra a area
            // duplicada.
            hashArquivo: hash('sha256', $kml),
            feicoes: $this->extrator->feicoes($kml),
            // Procedencia vem do envelope, montado pelo controller a partir do
            // usuario autenticado -- nunca de campo de formulario. Ausente,
            // cai no default estadual/aprovada, que e o caminho da CEDEC e da
            // ingestao automatica.
            origem: (string) ($envelope['origem'] ?? 'estadual'),
            municipioId: isset($envelope['municipio_id']) ? (int) $envelope['municipio_id'] : null,
            orgaoId: isset($envelope['orgao_id']) ? (int) $envelope['orgao_id'] : null,
            enviadoPor: isset($envelope['enviado_por']) ? (int) $envelope['enviado_por'] : null,
            status: (string) ($envelope['status'] ?? 'aprovada'),
            arquivoCaminho: $envelope['arquivo_caminho'] ?? null,
        );
    }
}
