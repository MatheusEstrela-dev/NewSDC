<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests\Concerns;

/**
 * Campos do representante da comunidade, compartilhados entre criar e atualizar.
 *
 * Os dois requests validam exatamente a mesma ficha -- o que muda entre eles e a
 * permissao, nao o formato. A lista vem do legado (gestaocedec, mod_pipa:
 * representante.php): nome, CPF, endereco, bairro, telefone, WhatsApp e e-mail.
 */
trait CamposDeRepresentante
{
    /** @return array<string, array<int, string>> */
    protected function camposDaFicha(): array
    {
        return [
            'nome'     => ['required', 'string', 'max:100'],
            'tel'      => ['nullable', 'string', 'max:20'],
            'email'    => ['nullable', 'email', 'max:110'],
            'cpf'      => ['nullable', 'string', 'max:14'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:150'],
            'bairro'   => ['nullable', 'string', 'max:100'],
        ];
    }
}
