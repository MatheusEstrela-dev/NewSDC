<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use App\Modules\Pmda\Requests\Concerns\CamposDeRepresentante;
use Illuminate\Foundation\Http\FormRequest;

class StoreRepresentanteRequest extends FormRequest
{
    use CamposDeRepresentante;

    public function authorize(): bool
    {
        return $this->user()?->can('pmda.representantes.create') ?? false;
    }

    public function rules(): array
    {
        return $this->camposDaFicha();
    }
}
