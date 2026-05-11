<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveRatBIRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dados_gerais'   => 'nullable|array',
            'comunicacao'    => 'nullable|array',
            'local'          => 'nullable|array',
            'endereco'       => 'nullable|array',
            'recursos'       => 'nullable|array',
            'recursos.*'     => 'nullable|array',
            'envolvidos'     => 'nullable|array',
            'envolvidos.*'   => 'nullable|array',
            'vistoria'       => 'nullable|array',
            'finalize'       => 'nullable|boolean',
        ];
    }
}
