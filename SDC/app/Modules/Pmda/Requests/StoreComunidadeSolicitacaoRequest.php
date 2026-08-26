<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use App\Modules\Pmda\Support\CoordenadaMG;
use Illuminate\Foundation\Http\FormRequest;

class StoreComunidadeSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.comunidades.solicitar') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome'      => ['required', 'string', 'max:150'],
            'latitude'  => CoordenadaMG::regrasLatitude(),
            'longitude' => CoordenadaMG::regrasLongitude(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return CoordenadaMG::mensagens();
    }
}
