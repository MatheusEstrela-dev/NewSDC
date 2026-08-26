<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use App\Modules\Pmda\Support\CoordenadaMG;
use Illuminate\Foundation\Http\FormRequest;

class StoreComunidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.comunidades.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'comunidade_id' => ['nullable', 'integer'],
            'municipio_id'  => ['nullable', 'integer', 'exists:municipios,id'],
            'ponto_id'      => ['nullable', 'integer'],
            'nome'          => ['required', 'string', 'max:150'],
            'latitude'      => CoordenadaMG::regrasLatitude(),
            'longitude'     => CoordenadaMG::regrasLongitude(),
            'trecho_pav'    => ['nullable', 'numeric', 'min:0'],
            'trecho_n_pav'  => ['nullable', 'numeric', 'min:0'],
            'distancia_km'  => ['nullable', 'numeric', 'min:0'],
            'pop_atendida'  => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return CoordenadaMG::mensagens();
    }
}
