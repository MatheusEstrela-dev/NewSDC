<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use App\Modules\Demandas\Models\Demanda;
use Illuminate\Foundation\Http\FormRequest;

class AnexoDemandaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demanda = Demanda::find($this->route('id'));

        return $demanda !== null && $this->user()->can('comment', $demanda);
    }

    public function rules(): array
    {
        return [
            'arquivo' => [
                'required', 'file',
                'max:'.config('demandas.anexos.max_kb'),
                'mimes:'.implode(',', config('demandas.anexos.mimes')),
            ],
        ];
    }
}
