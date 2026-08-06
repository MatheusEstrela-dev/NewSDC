<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Abertura de pedido (RN-04).
 *
 * O legado exige COBRADE, populacao atendida, esforcos realizados com no
 * maximo 1000 caracteres e municipio.
 */
class StorePedidoAhRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('humanitaria.pedidos.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'municipio_id'        => ['required', 'integer', Rule::exists('municipios', 'id')],
            'cobrade_id'          => ['required', 'integer', Rule::exists('dec_cobrade', 'id')],
            'pop_atendida'        => ['required', 'integer', 'min:1'],
            'esforcos_realizados' => ['required', 'string', 'max:1000'],

            'decreto_se_ecp_vig' => ['boolean'],
            'tipo_decreto'       => ['nullable', Rule::enum(TipoDecreto::class)],
            'numero_decreto'     => ['nullable', 'string', 'max:50'],
            'vigencia_decreto'   => ['nullable', 'date'],

            'nome_coordenador'  => ['nullable', 'string', 'max:255'],
            'tel_coordenador'   => ['nullable', 'string', 'max:20'],
            'cel_coordenador'   => ['nullable', 'string', 'max:20'],
            'email_coordenador' => ['nullable', 'email', 'max:255'],

            'nome_prefeito'  => ['nullable', 'string', 'max:255'],
            'tel_prefeito'   => ['nullable', 'string', 'max:20'],
            'cel_prefeito'   => ['nullable', 'string', 'max:20'],
            'email_prefeito' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cobrade_id.required'          => 'Informe o COBRADE do desastre.',
            'pop_atendida.required'        => 'Informe a população atendida.',
            'pop_atendida.min'             => 'A população atendida deve ser maior que zero.',
            'esforcos_realizados.required' => 'Descreva os esforços já realizados pelo município.',
            'esforcos_realizados.max'      => 'Os esforços realizados devem ter no máximo 1000 caracteres.',
        ];
    }
}
