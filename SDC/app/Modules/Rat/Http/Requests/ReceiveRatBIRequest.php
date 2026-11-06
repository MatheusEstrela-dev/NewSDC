<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use App\Modules\Rat\DTOs\RatVistoriaDTO;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveRatBIRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de vistoria.<grupo>.<chave> geradas do mapa canonico do
     * RatVistoriaDTO (tudo nullable: nenhum campo e obrigatorio).
     *
     * @return array<string, array<int, string>>
     */
    private static function regrasVistoriaAninhada(): array
    {
        $regras = [];

        foreach (RatVistoriaDTO::camposAninhados() as $grupo => $chaves) {
            $regras["vistoria.{$grupo}"] = ['nullable', 'array'];

            foreach ($chaves as $chave => $tipo) {
                $regras["vistoria.{$grupo}.{$chave}"] = self::regraDoTipo($tipo);
            }
        }

        foreach (RatVistoriaDTO::CAMPOS_RAIZ as $chave => $tipo) {
            $regras["vistoria.{$chave}"] = self::regraDoTipo($tipo);
        }

        return $regras;
    }

    /** @return array<int, string> */
    private static function regraDoTipo(string $tipo): array
    {
        [$base, $limite] = array_pad(explode(':', $tipo, 2), 2, null);

        return match ($base) {
            'bool'      => ['nullable', 'boolean'],
            'int'       => ['nullable', 'integer', 'min:0', 'max:32767'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'misto'     => ['nullable'],
            'text'      => ['nullable', 'string', 'max:65535'],
            default     => ['nullable', 'string', 'max:' . ($limite ?? '255')],
        };
    }

    public function rules(): array
    {
        // Explicitas vencem: o contrato atual nao muda; o gerado so acrescenta o que faltava.
        return array_merge(self::regrasVistoriaAninhada(), $this->regrasExplicitas());
    }

    /** @return array<string, array<int, string>> */
    private function regrasExplicitas(): array
    {
        return [
            // Dados Gerais
            'dados_gerais'                               => ['nullable', 'array'],
            'dados_gerais.data_fato'                     => ['nullable', 'string'],
            'dados_gerais.data_inicio_atividade'         => ['nullable', 'string'],
            'dados_gerais.data_termino_atividade'        => ['nullable', 'string'],
            'dados_gerais.nat_cobrade_id'                => ['nullable'],
            'dados_gerais.nat_nome_operacao'             => ['nullable', 'string', 'max:255'],
            'dados_gerais.tem_vistoria'                  => ['nullable', 'boolean'],

            // Comunicacao
            'comunicacao'                                => ['nullable', 'array'],
            'comunicacao.tipo_solicitacao'               => ['nullable', 'string', 'in:telefone,radio,pessoal,sistema,email,outro'],
            'comunicacao.data_comunicacao'               => ['nullable', 'string'],
            'comunicacao.telefone_contato'               => ['nullable', 'string', 'max:20'],
            'comunicacao.nome_solicitante'               => ['nullable', 'string', 'max:255'],

            // Local
            'local'                                      => ['nullable', 'array'],
            'local.pais_id'                              => ['nullable', 'integer'],
            'local.uf'                                   => ['nullable', 'string', 'size:2'],
            'local.municipio_id'                         => ['nullable'],

            // Endereco
            'endereco'                                   => ['nullable', 'array'],
            'endereco.cep'                               => ['nullable', 'string', 'max:10'],
            'endereco.logradouro'                        => ['nullable', 'string', 'max:255'],
            'endereco.numero'                            => ['nullable', 'string', 'max:20'],
            'endereco.complemento'                       => ['nullable', 'string', 'max:255'],
            'endereco.bairro'                            => ['nullable', 'string', 'max:150'],
            'endereco.km'                                => ['nullable', 'string', 'max:20'],
            'endereco.cruzamento'                        => ['nullable', 'string'],
            'endereco.ponto_referencia'                  => ['nullable', 'string'],
            'endereco.tipo_localizacao'                  => ['nullable', 'string', 'in:urbana,rural,rodovia,estrada,mata,montanha,rio,lago,outros'],
            'endereco.latitude'                          => ['nullable', 'numeric', 'between:-90,90'],
            'endereco.longitude'                         => ['nullable', 'numeric', 'between:-180,180'],

            // Recursos (array de objetos)
            'recursos'                                   => ['nullable', 'array'],
            'recursos.*.tipo_recurso'                    => ['nullable', 'string'],
            'recursos.*.categoria'                       => ['nullable', 'string'],
            'recursos.*.orgao_responsavel'               => ['nullable', 'string'],
            'recursos.*.identificacao'                   => ['nullable', 'string'],
            'recursos.*.condutor'                        => ['nullable', 'string'],
            'recursos.*.descricao'                       => ['nullable', 'string'],
            'recursos.*.data_saida'                      => ['nullable', 'string'],
            'recursos.*.data_chegada'                    => ['nullable', 'string'],
            'recursos.*.km_percorrido'                   => ['nullable', 'numeric'],
            'recursos.*.local_origem'                    => ['nullable', 'string'],
            'recursos.*.local_destino'                   => ['nullable', 'string'],

            // Envolvidos (array de objetos)
            'envolvidos'                                 => ['nullable', 'array'],
            'envolvidos.*.tipo_pessoa'                   => ['nullable', 'string'],
            'envolvidos.*.cpf'                           => ['nullable', 'string'],
            'envolvidos.*.nome'                          => ['nullable', 'string'],
            'envolvidos.*.nome_social'                   => ['nullable', 'string'],
            'envolvidos.*.data_nascimento'               => ['nullable', 'date'],
            'envolvidos.*.idade_aparente'                => ['nullable', 'integer'],
            'envolvidos.*.sexo'                          => ['nullable', 'string'],
            'envolvidos.*.nome_mae'                      => ['nullable', 'string'],
            'envolvidos.*.nome_pai'                      => ['nullable', 'string'],
            'envolvidos.*.ocupacao'                      => ['nullable', 'string'],
            'envolvidos.*.escolaridade'                  => ['nullable', 'string'],
            'envolvidos.*.cep'                           => ['nullable', 'string'],
            'envolvidos.*.uf'                            => ['nullable', 'string'],
            'envolvidos.*.municipio'                     => ['nullable', 'string'],
            'envolvidos.*.logradouro'                    => ['nullable', 'string'],
            'envolvidos.*.bairro'                        => ['nullable', 'string'],
            'envolvidos.*.numero'                        => ['nullable', 'string'],
            'envolvidos.*.complemento'                   => ['nullable', 'string'],

            // Vistoria (objeto)
            'vistoria'                                   => ['nullable', 'array'],
            'vistoria.solicitante'                       => ['nullable', 'array'],
            'vistoria.solicitante.nome'                  => ['nullable', 'string'],
            'vistoria.solicitante.cpf'                   => ['nullable', 'string'],
            'vistoria.solicitante.telefone'              => ['nullable', 'string'],
            'vistoria.solicitante.cep'                   => ['nullable', 'string'],
            'vistoria.solicitante.bairro'                => ['nullable', 'string'],
            'vistoria.solicitante.endereco'              => ['nullable', 'string'],
            'vistoria.imovel'                            => ['nullable', 'array'],
            'vistoria.imovel.endereco'                   => ['nullable', 'string'],
            'vistoria.imovel.bairro'                     => ['nullable', 'string'],
            'vistoria.imovel.municipio'                  => ['nullable', 'string'],
            'vistoria.imovel.cep'                        => ['nullable', 'string'],
            'vistoria.estrutura'                         => ['nullable', 'array'],
            'vistoria.estrutura.tipo_imovel'             => ['nullable', 'string'],
            'vistoria.estrutura.tipo_construcao'         => ['nullable', 'string'],
            'vistoria.estrutura.tipo_destinacao'         => ['nullable', 'string'],
            'vistoria.estrutura.tipo_edificacao'         => ['nullable', 'string'],
            'vistoria.estrutura.sistema_estrutural'      => ['nullable', 'string'],
            'vistoria.estrutura.estado_conservacao'      => ['nullable', 'string'],
            'vistoria.estrutura.regime_ocupacao'         => ['nullable', 'string'],
            'vistoria.estrutura.num_pavimentos'          => ['nullable', 'integer'],
            'vistoria.moradores'                         => ['nullable', 'array'],
            'vistoria.moradores.proprietario'            => ['nullable', 'string'],
            'vistoria.moradores.telefone'                => ['nullable', 'string'],

            // Raiz
            'finalize'                                   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'comunicacao.tipo_solicitacao.in' => 'tipo_solicitacao deve ser: telefone, radio, pessoal, sistema, email ou outro.',
            'endereco.tipo_localizacao.in'    => 'tipo_localizacao deve ser: urbana, rural, rodovia, estrada, mata, montanha, rio, lago ou outros.',
            'endereco.latitude.between'       => 'latitude deve estar entre -90 e 90.',
            'endereco.longitude.between'      => 'longitude deve estar entre -180 e 180.',
        ];
    }
}
