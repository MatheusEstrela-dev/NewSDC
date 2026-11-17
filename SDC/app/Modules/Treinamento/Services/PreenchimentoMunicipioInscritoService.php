<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Inscricao;

/**
 * Preenche inscricoes.municipio_inscrito_id das inscricoes anteriores a regra:
 * servidor pelo orgao principal atual, cidadao pelo cadastro. Quem nao tem
 * municipio resolvivel fica NULL e e contado no relatorio.
 */
class PreenchimentoMunicipioInscritoService
{
    private const LOTE = 500;

    /** @return array{analisadas: int, preenchidas: int, sem_municipio: int} */
    public function preencher(bool $simular): array
    {
        $relatorio = ['analisadas' => 0, 'preenchidas' => 0, 'sem_municipio' => 0];

        Inscricao::query()
            ->whereNull('municipio_inscrito_id')
            ->orderBy('id')
            ->chunkById(self::LOTE, function ($lote) use (&$relatorio, $simular) {
                $municipios = $this->municipiosDoLote($lote);

                foreach ($lote as $inscricao) {
                    $relatorio['analisadas']++;
                    $municipioId = $municipios[$inscricao->inscrito_type][$inscricao->inscrito_id] ?? null;

                    if ($municipioId === null) {
                        $relatorio['sem_municipio']++;

                        continue;
                    }

                    $relatorio['preenchidas']++;

                    if (!$simular) {
                        Inscricao::query()->whereKey($inscricao->id)->update(['municipio_inscrito_id' => $municipioId]);
                    }
                }
            });

        return $relatorio;
    }

    /**
     * @return array<string, array<int, int|null>> tipo => [inscrito_id => municipio_id]
     */
    private function municipiosDoLote($lote): array
    {
        $idsDe = fn (string $tipo) => $lote->where('inscrito_type', $tipo)->pluck('inscrito_id')->unique()->values()->all();

        $orgaosPorUsuario = User::query()
            ->whereIn('id', $idsDe(User::class))
            ->whereNotNull('orgao_principal_id')
            ->pluck('orgao_principal_id', 'id');

        $municipioPorOrgao = Orgao::query()
            ->whereIn('id', $orgaosPorUsuario->unique()->values()->all())
            ->whereNotNull('municipio_id')
            ->pluck('municipio_id', 'id');

        $servidores = [];
        foreach ($orgaosPorUsuario as $userId => $orgaoId) {
            $servidores[(int) $userId] = isset($municipioPorOrgao[$orgaoId]) ? (int) $municipioPorOrgao[$orgaoId] : null;
        }

        $cidadaos = Cidadao::withTrashed()
            ->whereIn('id', $idsDe(Cidadao::class))
            ->whereNotNull('municipio_id')
            ->pluck('municipio_id', 'id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [User::class => $servidores, Cidadao::class => $cidadaos];
    }
}
