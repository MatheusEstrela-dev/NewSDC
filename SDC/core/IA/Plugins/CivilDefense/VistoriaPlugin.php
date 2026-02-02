<?php

namespace App\Core\IA\Plugins\CivilDefense;

use App\Core\IA\Contracts\AIPluginInterface;
use App\Modules\Rat\Domain\Entities\Rat;

class VistoriaPlugin implements AIPluginInterface
{
    public function getName(): string
    {
        return "consultar_vistorias";
    }

    public function getDescription(): string
    {
        return "Busca as vistorias (RATs) mais recentes de uma cidade específica no banco SDC.";
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cidade' => [
                    'type' => 'string',
                    'description' => 'O nome da cidade ou município para buscar as vistorias. Ex: Pouso Alegre',
                ],
            ],
            'required' => ['cidade'],
        ];
    }

    public function execute(array $params)
    {
        $query = Rat::query();

        if (isset($params['cidade'])) {
            // Using JSON query for 'local' column
            $query->where('local->municipio', 'like', '%' . $params['cidade'] . '%');
        }

        return $query->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
    }
}
