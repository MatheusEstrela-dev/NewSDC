<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\UseCases;

use App\Modules\Decretacoes\Application\DTOs\ProcessoFormDataDTO;

/**
 * UseCase: Obter dados para formulário de Processo
 * Busca todas as dependências necessárias para o form
 */
class GetProcessoFormDataUseCase
{
    public function execute(?int $processoId = null): ProcessoFormDataDTO
    {
        // TODO: Buscar dados reais do banco
        // Por ora, retorna dados mock

        $tiposDesastre = [
            ['id' => 1, 'nome' => 'Chuvas Intensas'],
            ['id' => 2, 'nome' => 'Inundação'],
            ['id' => 3, 'nome' => 'Deslizamento'],
            ['id' => 4, 'nome' => 'Seca'],
            ['id' => 5, 'nome' => 'Vendaval'],
        ];

        $cobrades = [
            ['id' => 1, 'codigo' => '1.3.1.1.1', 'descricao' => 'Inundações'],
            ['id' => 2, 'codigo' => '1.3.1.1.2', 'descricao' => 'Enxurradas'],
            ['id' => 3, 'codigo' => '1.3.2.1.1', 'descricao' => 'Deslizamentos'],
        ];

        $municipios = [
            ['id' => 3106200, 'nome' => 'Belo Horizonte', 'ibge' => '3106200'],
            ['id' => 3118601, 'nome' => 'Contagem', 'ibge' => '3118601'],
            ['id' => 3106705, 'nome' => 'Betim', 'ibge' => '3106705'],
        ];

        $redecs = [
            ['id' => 1, 'nome' => 'REDEC Central'],
            ['id' => 2, 'nome' => 'REDEC Norte'],
            ['id' => 3, 'nome' => 'REDEC Sul'],
        ];

        $analistas = [
            ['id' => 1, 'nome' => 'Analista A'],
            ['id' => 2, 'nome' => 'Analista B'],
            ['id' => 3, 'nome' => 'Analista C'],
        ];

        if ($processoId) {
            // TODO: Buscar processo existente para edição
            return ProcessoFormDataDTO::forEdit(
                processo: ['id' => $processoId],
                tiposDesastre: $tiposDesastre,
                cobrades: $cobrades,
                municipios: $municipios,
                redecs: $redecs,
                analistas: $analistas
            );
        }

        return ProcessoFormDataDTO::forCreate(
            tiposDesastre: $tiposDesastre,
            cobrades: $cobrades,
            municipios: $municipios,
            redecs: $redecs,
            analistas: $analistas
        );
    }
}
