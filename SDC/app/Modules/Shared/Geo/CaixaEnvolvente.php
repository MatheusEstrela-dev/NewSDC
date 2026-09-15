<?php

declare(strict_types=1);

namespace App\Modules\Shared\Geo;

use InvalidArgumentException;

/**
 * Retangulo geografico, usado para enquadrar o mapa.
 *
 * Antes disto a bbox trafegava como array solto de config direto para a tela,
 * sem ninguem conferir nada. Um sinal trocado num dos quatro numeros -- e sao
 * todos negativos em Minas -- nao levantava erro: produzia um mapa enquadrado
 * no oceano, e o operador via um mapa vazio sem saber por que.
 *
 * Nao filtra dado nenhum. O recorte do que entra no sistema e do pipeline, por
 * UF no INMET e no CEMADEN, e por bbox no FDSN dos sismos. Esta classe serve o
 * enquadramento da entrega.
 */
final readonly class CaixaEnvolvente
{
    public function __construct(
        public Coordenada $sudoeste,
        public Coordenada $nordeste,
    ) {
        if ($sudoeste->latitude >= $nordeste->latitude) {
            throw new InvalidArgumentException(
                "Latitude minima ({$sudoeste->latitude}) deve ser menor que a maxima ({$nordeste->latitude})."
            );
        }

        if ($sudoeste->longitude >= $nordeste->longitude) {
            throw new InvalidArgumentException(
                "Longitude minima ({$sudoeste->longitude}) deve ser menor que a maxima ({$nordeste->longitude})."
            );
        }
    }

    /**
     * Constroi a partir do formato que o config usa.
     *
     * @param array{min_lat: float|int|string, max_lat: float|int|string, min_lon: float|int|string, max_lon: float|int|string} $config
     */
    public static function deConfig(array $config): self
    {
        foreach (['min_lat', 'max_lat', 'min_lon', 'max_lon'] as $chave) {
            if (! isset($config[$chave])) {
                throw new InvalidArgumentException("bbox sem a chave obrigatoria: {$chave}");
            }
        }

        return new self(
            sudoeste: new Coordenada((float) $config['min_lat'], (float) $config['min_lon']),
            nordeste: new Coordenada((float) $config['max_lat'], (float) $config['max_lon']),
        );
    }

    /**
     * Formato que o MapaLeaflet.vue ja consome. Mantido igual ao do config para
     * que introduzir este objeto nao mexa em nenhuma tela.
     *
     * @return array{min_lat: float, max_lat: float, min_lon: float, max_lon: float}
     */
    public function paraArray(): array
    {
        return [
            'min_lat' => $this->sudoeste->latitude,
            'max_lat' => $this->nordeste->latitude,
            'min_lon' => $this->sudoeste->longitude,
            'max_lon' => $this->nordeste->longitude,
        ];
    }

    /** Envelope do PostGIS, na ordem (xmin, ymin, xmax, ymax). */
    public function paraEnvelopeSql(): string
    {
        return sprintf(
            'ST_MakeEnvelope(%F, %F, %F, %F, 4326)',
            $this->sudoeste->longitude,
            $this->sudoeste->latitude,
            $this->nordeste->longitude,
            $this->nordeste->latitude,
        );
    }

    public function contem(Coordenada $ponto): bool
    {
        return $ponto->latitude >= $this->sudoeste->latitude
            && $ponto->latitude <= $this->nordeste->latitude
            && $ponto->longitude >= $this->sudoeste->longitude
            && $ponto->longitude <= $this->nordeste->longitude;
    }
}
