<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\VistoriaFoto
 *
 * A foto como a tela precisa dela.
 *
 * Antes o controller devolvia o MODEL CRU (`$vistoria->fotos()->get()`), unico
 * ponto do submodulo fora do padrao de Resource. Ia junto no payload o que a
 * tela nao usa e ninguem deveria ver: `path` e `disk` descrevem onde o binario
 * mora no storage privado, e `uploaded_by` e id de usuario -- tudo isso
 * aterrissava no HTML da pagina, legivel por qualquer um com o inspetor aberto.
 *
 * O binario continua saindo so pela rota autenticada
 * `tdap.frota.vistorias.fotos.show`, que e o que a tela monta a partir do `id`.
 */
class VistoriaFotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'vistoria_id'   => $this->vistoria_id,
            // O nome que a pessoa reconhece: e ele que aparece na legenda e na
            // confirmacao de remocao (VistoriaFotos.vue).
            'nome_original' => $this->nome_original,
            'descricao'     => $this->descricao,
            'mime_type'     => $this->mime_type,
            'tamanho_bytes' => (int) $this->tamanho_bytes,
            // Accessor do model, ja em pt-BR ("1,2 MB").
            'tamanho'       => $this->tamanho_formatado,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
