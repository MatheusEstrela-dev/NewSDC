<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Application\UseCases;

use App\Models\User;
use App\Modules\Compdec\Domain\Entities\Orgao;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use App\Modules\Compdec\Domain\ValueObjects\TipoOrgao;

class CreateOrgaoUseCase
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository
    ) {
    }

    /**
     * Executar criação de órgão
     */
    public function execute(array $data, User $user): Orgao
    {
        // Validar dados básicos
        if (empty($data['codigo'])) {
            $data['codigo'] = $this->gerarCodigo($data);
        }

        // Validar hierarquia se tiver órgão superior
        if (!empty($data['orgao_superior_id'])) {
            $this->validarHierarquia($data);
        }

        // Criar órgão
        $orgao = $this->repository->create($data);

        return $orgao;
    }

    /**
     * Gerar código automático para o órgão
     */
    private function gerarCodigo(array $data): string
    {
        $tipo = TipoOrgao::from($data['tipo']);
        $sigla = $tipo->getSigla();

        // Se tiver município
        if (!empty($data['municipio_id'])) {
            $municipio = \App\Models\Municipio::find($data['municipio_id']);
            if ($municipio) {
                $uf = $municipio->uf;
                $nomeLimpo = preg_replace('/[^A-Z]/', '', strtoupper($municipio->nome));
                $codigo = substr($nomeLimpo, 0, 3);
                return "{$sigla}-{$uf}-{$codigo}";
            }
        }

        // Fallback: usar timestamp
        return "{$sigla}-" . strtoupper(uniqid());
    }

    /**
     * Validar hierarquia de órgãos
     */
    private function validarHierarquia(array $data): void
    {
        $superior = $this->repository->find($data['orgao_superior_id']);

        if (!$superior) {
            throw new \DomainException('Órgão superior não encontrado');
        }

        $tipo = TipoOrgao::from($data['tipo']);

        // Validar se pode ser subordinado
        if (!$tipo->podeSerSubordinadoDe($superior->tipo)) {
            throw new \DomainException(
                "Um {$tipo->getLabel()} não pode ser subordinado a um {$superior->tipo->getLabel()}"
            );
        }

        // Se for COMPDEC, validar abrangência
        if ($tipo === TipoOrgao::COMPDEC && !empty($data['municipio_id'])) {
            if (!$superior->municipioEstaNaAbrangencia($data['municipio_id'])) {
                throw new \DomainException(
                    'Município fora da abrangência do órgão superior'
                );
            }
        }
    }
}
