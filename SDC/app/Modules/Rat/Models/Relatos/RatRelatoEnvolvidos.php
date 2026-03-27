<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

/**
 * Envolvidos de uma ocorrência RAT.
 * 
 * Campos conforme a aba "Envolvidos" do formulário:
 * - Tipo de Pessoa
 * - Nome Completo
 * - Matrícula/MASF
 * - Tipo de Identificação
 * - Cargo
 * - Órgão
 * - Unidade
 * - Função no Atendimento
 * - Contato
 */
class RatRelatoEnvolvidos extends RatRelato
{
    protected $table = 'rat_relato_envolvidos';

    protected $fillable = [
        'tipo_pessoa',
        'nome_completo',
        'matricula_masf',
        'tipo_identificacao',
        'numero_identificacao',
        'orgao_expedidor',
        'cargo',
        'orgao',
        'unidade',
        'funcao_atendimento',
        'telefone',
        'email',
        'cpf',
        'data_nascimento',
        'sexo',
        'nacionalidade',
        'estado_civil',
        'profissao',
        'contato_emergencia',
        'telefone_emergencia',
        'endereco_completo',
        'cep',
        'cidade',
        'estado',
        'pais',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
