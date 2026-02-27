<?php

declare(strict_types=1);

namespace App\Modules\Inmet\DTOs;

use Carbon\Carbon;
use App\Modules\Inmet\Enums\NivelPrecipitacao;

readonly class LeituraMeteorologicaDTO
{
    public function __construct(
        public string $codigoEstacao,
        public string $nomeEstacao,
        public string $municipio,
        public Carbon $dataHoraMedicao,
        public ?float $temperatura,
        public ?float $umidade,
        public ?float $precipitacao,
        public ?float $velocidadeVento,
        public ?float $pressao,
        public NivelPrecipitacao $nivelPrecipitacao,
        public string $condicao,
        public float $latitude,
        public float $longitude,
    ) {
    }

    public static function fromInmetArray(array $data): self
    {
        $date = $data['DT_MEDICAO'] ?? date('Y-m-d');
        $time = str_pad((string) ($data['HR_MEDICAO'] ?? '0000'), 4, '0', STR_PAD_LEFT);

        $carbonDate = Carbon::createFromFormat('Y-m-d Hi', "$date $time", 'UTC')
            ->setTimezone('America/Sao_Paulo');

        $precipitacao = self::parseFloat($data['CHUVA'] ?? null);
        $nivel = NivelPrecipitacao::fromMilimetros($precipitacao ?? 0.0);

        return new self(
            codigoEstacao: $data['CD_ESTACAO'] ?? 'UNKNOWN',
            nomeEstacao: $data['DC_NOME'] ?? 'Estacao Desconhecida',
            municipio: $data['VL_MUNICIPIO'] ?? $data['DC_NOME'] ?? '',
            dataHoraMedicao: $carbonDate,
            temperatura: self::parseFloat($data['TEM_INS'] ?? null),
            umidade: self::parseFloat($data['UMD_INS'] ?? null),
            precipitacao: $precipitacao,
            velocidadeVento: self::parseFloat($data['VEN_VEL'] ?? null),
            pressao: self::parseFloat($data['PRE_INS'] ?? null),
            nivelPrecipitacao: $nivel,
            condicao: self::calculateCondition($data),
            latitude: (float) ($data['VL_LATITUDE'] ?? $data['latitude'] ?? 0),
            longitude: (float) ($data['VL_LONGITUDE'] ?? $data['longitude'] ?? 0),
        );
    }

    private static function calculateCondition(array $data): string
    {
        $rain = (float) ($data['CHUVA'] ?? 0);
        if ($rain > 0) {
            return 'rain';
        }

        $hourUTC = (int) substr(str_pad((string) ($data['HR_MEDICAO'] ?? '0000'), 4, '0', STR_PAD_LEFT), 0, 2);
        $isNight = $hourUTC >= 21 || $hourUTC < 9;

        return $isNight ? 'clear-night' : 'sunny';
    }

    private static function parseFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    public function toArray(): array
    {
        return [
            'codigo_estacao' => $this->codigoEstacao,
            'nome_estacao' => $this->nomeEstacao,
            'municipio' => $this->municipio,
            'data_hora' => $this->dataHoraMedicao->format('d/m/Y H:i'),
            'temperatura' => $this->temperatura,
            'umidade' => $this->umidade,
            'precipitacao' => $this->precipitacao,
            'velocidade_vento' => $this->velocidadeVento,
            'pressao' => $this->pressao,
            'nivel' => $this->nivelPrecipitacao->value,
            'nivel_label' => $this->nivelPrecipitacao->getLabel(),
            'nivel_color' => $this->nivelPrecipitacao->getColor(),
            'condicao' => $this->condicao,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    public static function collection(iterable $leituras): array
    {
        $items = is_array($leituras) ? $leituras : iterator_to_array($leituras);

        return array_map(
            fn($leitura) => is_array($leitura)
            ? self::fromInmetArray($leitura)->toArray()
            : $leitura->toArray(),
            $items
        );
    }
}
