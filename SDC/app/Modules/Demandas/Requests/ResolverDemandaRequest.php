<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use App\Modules\Demandas\Models\Demanda;
use Illuminate\Foundation\Http\FormRequest;

class ResolverDemandaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demanda = Demanda::find($this->route('id'));

        return $demanda !== null && $this->user()->can('resolver', $demanda);
    }

    public function rules(): array
    {
        return [
            'aberta_em' => ['required', 'date', 'before_or_equal:resolvida_em', 'before_or_equal:now'],
            'resolvida_em' => ['required', 'date', 'after_or_equal:aberta_em', 'before_or_equal:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'aberta_em.before_or_equal' => 'A data não pode ser posterior ao fechamento nem ao momento atual.',
            'resolvida_em.after_or_equal' => 'A data não pode ser anterior à abertura.',
            'resolvida_em.before_or_equal' => 'A data de fechamento não pode estar no futuro.',
        ];
    }
}
