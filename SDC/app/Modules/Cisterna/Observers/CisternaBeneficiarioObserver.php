<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Observers;

use App\Models\AuditLog;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Registra em `audit_logs` os fatos que a fiscalizacao precisa reconstruir
 * depois: entrada do cadastro, movimentacao entre ordens de servico e mudanca
 * dos dois eixos de situacao. E o que alimenta a serie historica do
 * beneficiario (BeneficiarioHistoricoService).
 *
 * So grava quando uma dessas colunas de fato mudou: corrigir telefone ou
 * endereco nao e evento de acompanhamento e nao deve poluir o historico -- num
 * cadastro que passa por tres etapas de vistoria, ruido aqui torna a linha do
 * tempo inutil.
 *
 * ATENCAO: observer nao dispara em `->update()` de query builder. As acoes em
 * massa do BeneficiarioService gravam o log por conta propria, justamente
 * porque alocar em lote e o caminho principal de uso e passaria batido aqui.
 */
class CisternaBeneficiarioObserver
{
    /**
     * Colunas cuja mudanca vira evento na serie historica.
     *
     * @var array<int, string>
     */
    private const COLUNAS_OBSERVADAS = [
        'ordem_servico_id',
        'situacao_analise',
        'situacao_obra',
    ];

    /**
     * Sem isto a serie historica de um cadastro novo comecava no ar: a primeira
     * linha seria uma mudanca de situacao, sem nunca dizer quando o cadastro
     * entrou nem por quem.
     */
    public function created(CisternaBeneficiario $beneficiario): void
    {
        $this->registrar(
            $beneficiario,
            // 'insert', e nao 'created': audit_logs tem CHECK que aceita apenas
            // insert|update|delete|login|logout.
            'insert',
            [],
            [
                'situacao_analise' => $beneficiario->situacao_analise?->value,
                'situacao_obra' => $beneficiario->situacao_obra?->value,
            ],
        );
    }

    public function updated(CisternaBeneficiario $beneficiario): void
    {
        $mudadas = array_values(array_filter(
            self::COLUNAS_OBSERVADAS,
            static fn (string $coluna): bool => $beneficiario->wasChanged($coluna),
        ));

        if ($mudadas === []) {
            return;
        }

        // Uma linha por requisicao, com todas as colunas que mudaram juntas: o
        // formulario de edicao salva tudo de uma vez, e tres linhas simultaneas
        // com o mesmo carimbo de hora sugeririam tres acoes separadas.
        $this->registrar(
            $beneficiario,
            'update',
            $this->valores($beneficiario, $mudadas, original: true),
            $this->valores($beneficiario, $mudadas, original: false),
        );
    }

    /**
     * @param  array<int, string>  $colunas
     * @return array<string, mixed>
     */
    private function valores(CisternaBeneficiario $beneficiario, array $colunas, bool $original): array
    {
        $valores = [];

        foreach ($colunas as $coluna) {
            $valor = $original ? $beneficiario->getOriginal($coluna) : $beneficiario->getAttribute($coluna);

            // getAttribute devolve o enum, getOriginal devolve o valor cru do
            // banco. Normalizar aqui evita que o mesmo campo apareca ora como
            // objeto ora como string no jsonb.
            $valores[$coluna] = $valor instanceof \BackedEnum ? $valor->value : $valor;
        }

        return $valores;
    }

    /**
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $depois
     */
    private function registrar(CisternaBeneficiario $beneficiario, string $evento, array $antes, array $depois): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $evento,
            'table_name' => $beneficiario->getTable(),
            'row_id' => $beneficiario->getKey(),
            'old_values' => $antes,
            'new_values' => $depois,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
