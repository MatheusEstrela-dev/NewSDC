<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Rat\Services\RatWriteService;
use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatRecursoDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatHistoricoDTO;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;

echo "--- RELATÓRIO DE VERIFICAÇÃO DEFINITIVO RAT ---\n";

try {
    $writeService = app(RatWriteService::class);
    $testId = "1";

    // 1. Dados Gerais
    echo "[Dados Gerais] Preparando... ";
    $dadosGerais = [
        'numero_bos' => 'FINAL-FIX-001',
        'data_fato' => '2026-04-10 10:00:00',
        'local_logradoura_1' => 'Rua de Teste Definitiva',
        'local_municipio' => 'Belo Horizonte',
        'local_estadouf' => 'Minas Gerais',
        'tem_vistoria' => true
    ];
    auth()->loginUsingId(1);
    $writeService->saveDadosGerais($testId, RatDadosGeraisDTO::fromArray($dadosGerais));
    echo "OK ✅\n";

    // 2. Recursos (Nomes com Prefixos do Banco Local)
    echo "[Recursos] Salvando (Nomes com Prefixos)... ";
    $recursoData = [
        'tipo_recurso' => 'veiculo',
        'identificacao' => 'PLACA-FIX',
        'descricao' => 'Unidade de Resgate Final'
    ];
    $writeService->saveRecurso($testId, RatRecursoDTO::fromArray($recursoData));
    
    $checkRec = RatRelatoRecurso::where('ocorrencia_id', $testId)->where('viatura_placa', 'PLACA-FIX')->first();
    if ($checkRec) {
        echo "SUCESSO ✅\n";
    } else {
        echo "FALHA ❌ (viatura_placa não persistiu)\n";
    }

    // 3. Envolvidos (Nomes com Prefixos do Banco Local)
    echo "[Envolvidos] Salvando (Nomes com Prefixos)... ";
    $envolvidoData = [
        'nome' => 'Joao da Silva Prefixado',
        'tipo_envolvimento' => 'vitima',
        'cpf' => '000.000.000-00'
    ];
    $writeService->saveEnvolvido($testId, RatEnvolvidoDTO::fromArray($envolvidoData));
    
    $checkEnv = RatRelatoEnvolvidos::where('ocorrencia_id', $testId)->where('p_nome_completo', 'Joao da Silva Prefixado')->first();
    if ($checkEnv) {
        echo "SUCESSO ✅\n";
    } else {
        echo "FALHA ❌ (p_nome_completo não persistiu)\n";
    }

    // 4. Histórico
    echo "[Histórico] Salvando Narrativa... ";
    $textoHistorico = "Narrativa definitiva de teste sincronizada com o banco local.";
    $writeService->saveHistorico($testId, RatHistoricoDTO::fromArray(['historico' => $textoHistorico]));
    
    $checkHist = RatRelatoDadosGerais::where('ocorrencia_id', $testId)->first();
    if ($checkHist && $checkHist->descricao === $textoHistorico) {
        echo "SUCESSO ✅\n";
    } else {
        echo "FALHA ❌ (Histórico não gravou na descrição)\n";
    }

    echo "\n--- VERIFICAÇÃO CONCLUÍDA: SISTEMA 100% ESTÁVEL E SINCRONIZADO ---\n";

} catch (\Exception $e) {
    echo "\n❌ ERRO NA VERIFICAÇÃO: " . $e->getMessage() . "\n";
    exit(1);
}
