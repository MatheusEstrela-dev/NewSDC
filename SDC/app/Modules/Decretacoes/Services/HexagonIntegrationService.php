<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Http\Resources\Decretacoes\HexagonDecretoResource;
use App\Models\Decreto\EntradaProcesso;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HexagonIntegrationService
{
    public function updateHexagonService(EntradaProcesso $processo): void
    {       
        $processo->load('municipios');

        if ($this->hasRequiredDataForHexagon($processo)) {
            $this->sendToHexagonAPI($processo);
        }
    }

    private function sendToHexagonAPI(EntradaProcesso $processo): void
    {
        try {
            $resource = new HexagonDecretoResource($processo);
            $data = [$resource->toArray(null)];

            $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post('http://ComOnCallQA/HxGN.DecretoAPI/CEDECDecretos/api/Decretos', $data);

            if ($response->successful()) {
                Log::info('Hexagon API: Decreto enviado com sucesso', [
                    'processo_id' => $processo->id,
                    'response_status' => $response->status(),
                ]);
            } else {
                Log::error('Hexagon API: Erro ao enviar decreto', [
                    'processo_id' => $processo->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Hexagon API: Exceção ao enviar decreto', [
                'processo_id' => $processo->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function hasRequiredDataForHexagon(EntradaProcesso $processo): bool
    {
        if ($processo->municipios->isEmpty()) {
            return false;
        }
        
        $municipio = $processo->municipios->first();
        
        $processoFields = ['n_protocolo_fide', 'data_publicacao_mg', 'prazo_vigencia'];
        foreach ($processoFields as $field) {
            if (empty($processo->{$field})) {
                return false;
            }
        }
        
        $municipioFields = ['Codmundv', 'nome', 'macroregiao', 'rpm'];
        foreach ($municipioFields as $field) {
            if (empty($municipio->{$field})) {
                return false;
            }
        }
        
        return true;
    }
}
