<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Models\User;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

abstract class AbstractVistoriaRequest extends FormRequest
{
    public const PERMISSAO_APROVAR = 'tdap.vistorias.aprovar';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nome'        => ['required', 'string', 'max:150'],
            'edital'      => ['nullable', 'string', 'max:50'],
            'lote'        => ['nullable', 'string', 'max:30'],
            'placa_id'    => ['required', 'integer', Rule::exists('tdap_caminhoes', 'id')->whereNull('deleted_at')],
            'modelo'      => ['nullable', 'string', 'max:50'],
            'cor'         => ['nullable', 'string', 'max:30'],
            // A vistoria e um ato ja praticado: data futura nao existe e ainda
            // esticava a vigencia de 12 meses para frente (Vistoria::vigente).
            'data'        => ['required', 'date', 'before_or_equal:today'],
            'ano'         => ['nullable', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'capacidade'  => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'parecer'     => ['required', new Enum(ParecerVistoria::class)],
            'ficha'       => ['nullable', 'string', 'max:50'],
            // Numero do lacre aplicado no tanque ao fim da vistoria.
            'lacre'       => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (Vistoria::ITENS_ESTRUTURAIS as $campo) {
            $rules[$campo] = ['nullable', 'boolean'];
            $rules["{$campo}_obs"] = ['nullable', 'string', 'max:500'];
        }
        foreach (Vistoria::ITENS_TANQUE as $campo) {
            $rules[$campo] = ['nullable', 'boolean'];
            $rules["{$campo}_obs"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * Parecer "Aprovada" exige `tdap.vistorias.aprovar`.
     *
     * Na edicao, manter o parecer que ja estava gravado nao conta como aprovar:
     * so a MUDANCA para Aprovada cobra a permissao.
     */
    public function withValidator(Validator $validator): void
    {
        $vistoria = $this->route('vistoria');

        self::exigirPermissaoParaAprovar(
            $validator,
            $this->user(),
            $vistoria instanceof Vistoria ? $vistoria->parecer : null,
        );
    }

    /**
     * Regra compartilhada com StoreVistoriaComCaminhaoRequest, que nao herda
     * desta classe.
     */
    public static function exigirPermissaoParaAprovar(Validator $validator, ?User $user, ?ParecerVistoria $parecerAtual): void
    {
        $validator->after(function (Validator $validator) use ($user, $parecerAtual): void {
            if ($validator->errors()->has('parecer')) {
                return;
            }

            $valor = $validator->getData()['parecer'] ?? null;
            $novo = is_string($valor) ? ParecerVistoria::tryFrom($valor) : null;

            if ($novo !== ParecerVistoria::Aprovada || $parecerAtual === ParecerVistoria::Aprovada) {
                return;
            }

            if (! ($user?->can(self::PERMISSAO_APROVAR) ?? false)) {
                $validator->errors()->add('parecer', 'Você não tem permissão para aprovar a vistoria.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ano.regex' => 'Ano deve ter 4 dígitos numéricos.',
            'data.before_or_equal' => 'A data da vistoria não pode ser futura.',
        ];
    }
}
