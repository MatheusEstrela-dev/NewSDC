<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use App\Modules\Pae\Services\PaeAdmissibilidadeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class SalvarTriagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return PaeAdmissibilidadeService::regrasTriagem();
    }

    public function withValidator(Validator $validator): void
    {
        $municipios = $this->input('municipios', []);
        $itens = $this->input('itens', []);
        PaeAdmissibilidadeService::validarComplementos(
            $validator,
            is_array($municipios) ? $municipios : [],
            is_array($itens) ? $itens : [],
        );
    }
}
