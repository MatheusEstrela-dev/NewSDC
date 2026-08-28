<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlantaoRequest extends FormRequest
{
    /**
     * Autorizacao fina (dono + turno ATIVO, com excecao de supervisao) fica no
     * controller, nao aqui: quem falha essa checagem recebe 403 (falta de
     * autorizacao), nao 422 (erro de validacao de formulario).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Somente o que pertence ao turno em si, e nao ao historico ja declarado
     * no encerramento/aceite. Ver decisao "O que e editavel" do plano:
     * plantonista, datas/periodo, status e snapshots mudam so pela maquina de
     * estados (PassagemServicoService), nunca por esta tela.
     */
    public function rules(): array
    {
        return [
            'localizacao' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'ocorrencias_destaque' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
