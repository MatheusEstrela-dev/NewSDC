<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Anexo do pedido (RN-22): PDF de ate 2 MB.
 *
 * A mesma regra vive no AnexoPedidoService, porque precisa valer tambem fora
 * do ciclo de requisicao. Aqui ela existe para o usuario receber a mensagem
 * de validacao no formulario em vez de um flash generico.
 */
class StoreAnexoPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('humanitaria.pedidos.anexos') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) (config('ajuda-humanitaria.upload_limits.anexo_pedido', 2 * 1024 * 1024) / 1024);

        return [
            'arquivo' => ['required', 'file', 'mimes:pdf', "max:{$maxKb}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'arquivo.required' => 'Selecione o arquivo.',
            'arquivo.mimes'    => 'Apenas arquivos PDF são aceitos.',
            'arquivo.max'      => 'O arquivo deve ter no máximo 2 MB.',
        ];
    }
}
