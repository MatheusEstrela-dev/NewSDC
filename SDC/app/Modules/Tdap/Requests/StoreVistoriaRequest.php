<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

class StoreVistoriaRequest extends AbstractVistoriaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.vistorias.create') ?? false;
    }

    /**
     * As fotos sobem JUNTO com a ficha, no mesmo POST.
     *
     * A alternativa -- salvar a vistoria e so entao enviar as fotos -- exigiria
     * do front descobrir o id recem-criado pelo redirect, e deixaria a janela
     * em que a vistoria ja existe e as fotos ainda nao subiram. Aqui as duas
     * coisas acontecem na mesma requisicao.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'fotos'   => ['sometimes', 'array', 'max:'.StoreVistoriaFotoRequest::MAX_POR_LOTE],
            'fotos.*' => StoreVistoriaFotoRequest::REGRAS_DO_ARQUIVO,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['fotos.*' => 'foto'];
    }
}
