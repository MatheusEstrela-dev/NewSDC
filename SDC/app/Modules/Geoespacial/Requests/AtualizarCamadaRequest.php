<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Requests;

use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Geoespacial\Services\ProcedenciaDoEnvio;
use App\Modules\Geoespacial\Support\AcessoACamada;
use Illuminate\Foundation\Http\FormRequest;

class AtualizarCamadaRequest extends FormRequest
{
    /**
     * A rota exige `can:geoespacial.camadas.edit`, que responde "esta pessoa
     * pode editar camada". Aqui vem a outra metade: "pode editar ESTA camada,
     * neste estado" -- ownership e status, que middleware nenhum sabe.
     *
     * Sem isto, qualquer usuario com a permissao editaria a camada de qualquer
     * municipio, e o municipio editaria camada ja aprovada e publicada no mapa
     * do estado.
     */
    public function authorize(): bool
    {
        $usuario = $this->user();

        if ($usuario === null) {
            return false;
        }

        $camada = app(GeoCamadaRepository::class)->camada((int) $this->route('camada'));

        if ($camada === null) {
            return false;
        }

        $procedencia = app(ProcedenciaDoEnvio::class)->para($usuario);

        return app(AcessoACamada::class)
            ->acoes($usuario, $camada, $procedencia->municipioId)['editar'];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // O arquivo NAO entra: a geometria e a identidade da camada
        // (hash_arquivo UNIQUE) e vem do Bronze. Ver CicloDeVidaDaCamada::editar.
        return RegrasDeMetadado::regras($this->string('dominio')->toString() ?: null);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return RegrasDeMetadado::mensagens();
    }
}
