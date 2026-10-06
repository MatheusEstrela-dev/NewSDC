<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use App\Modules\Pae\Services\PaeAdmissibilidadeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class DecidirAdmissibilidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return PaeAdmissibilidadeService::regrasDecisao();
    }

    public function withValidator(Validator $validator): void
    {
        PaeAdmissibilidadeService::validarDecisaoComplementar($validator, $this->all());
    }
}
