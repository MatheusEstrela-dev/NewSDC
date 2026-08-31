<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use App\Modules\Pmda\Requests\Concerns\CamposDeRepresentante;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRepresentanteRequest extends FormRequest
{
    use CamposDeRepresentante;

    public function authorize(): bool
    {
        return $this->user()?->can('pmda.representantes.edit') ?? false;
    }

    public function rules(): array
    {
        return $this->camposDaFicha();
    }
}
