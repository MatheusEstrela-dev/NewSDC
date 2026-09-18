<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Models\Caminhao;

class StoreVistoriaRequest extends AbstractVistoriaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.vistorias.create') ?? false;
    }

    /**
     * O caminhao vem da ROTA, nao do corpo.
     *
     * A rota e aninhada (`tdap/frota/{caminhao}/vistorias`), entao `placa_id` e
     * decidido pela URL. Sobrescrever aqui, em vez de confiar no que o front
     * mandar, impede que um POST forjado grave a vistoria no caminhao errado --
     * e e o que faz o pre-preenchimento funcionar de verdade.
     *
     * `placa_id` e o nome legado da coluna: guarda o ID do caminhao, nao a placa.
     */
    protected function prepareForValidation(): void
    {
        $caminhao = $this->route('caminhao');

        if ($caminhao !== null) {
            $this->merge([
                'placa_id' => $caminhao instanceof Caminhao ? $caminhao->id : (int) $caminhao,
            ]);
        }
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
