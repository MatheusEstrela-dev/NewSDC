<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

/**
 * Na edicao a etapa e o beneficiario nao mudam: sao lidos da vistoria em rota,
 * nao do corpo do request. Sem isso um PUT poderia mover a vistoria para outra
 * etapa ou outro beneficiario, furando o UNIQUE (beneficiario_id, etapa) e a
 * ordem da cadeia que o VistoriaService garante.
 */
class UpdateVistoriaRequest extends StoreVistoriaRequest
{
    public function authorize(): bool
    {
        $vistoria = $this->route('vistoria');

        return $vistoria !== null && ($this->user()?->can('update', $vistoria) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $vistoria = $this->route('vistoria');

        if ($vistoria === null) {
            return;
        }

        // Injeta etapa e beneficiario a partir da rota, para o rules() do pai
        // escolher o conjunto certo de regras -- e para o que vier no corpo ser
        // sobrescrito, nao respeitado.
        $this->merge([
            'etapa' => $vistoria->etapa->value,
            'beneficiario_id' => $vistoria->beneficiario_id,
        ]);
    }
}
