<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\PaeAnexoJ;
use App\Modules\Pae\Support\TimelinePae;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as LaravelValidator;

final class PaeAdmissibilidadeService
{
    public static function regrasTriagem(): array
    {
        return [
            'municipios' => ['present', 'array'],
            'municipios.*.municipio_id' => ['required', 'integer', 'distinct', 'exists:municipios,id'],
            'municipios.*.na_zas' => ['required', 'boolean'],
            'municipios.*.na_zss' => ['required', 'boolean'],
            'itens' => ['present', 'array', 'max:9'],
            'itens.*.chave' => ['required', 'string', 'distinct', Rule::in(PaeAnexoJ::CHAVES)],
            'itens.*.resultado' => ['required', Rule::in(['sim', 'nao', 'nao_aplicavel'])],
            'itens.*.justificativa' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public static function validarComplementos(LaravelValidator $validator, array $municipios, array $itens): void
    {
        $validator->after(static function (LaravelValidator $validator) use ($municipios, $itens): void {
            foreach ($municipios as $indice => $municipio) {
                if (! ($municipio['na_zas'] ?? false) && ! ($municipio['na_zss'] ?? false)) {
                    $validator->errors()->add("municipios.{$indice}.na_zas", 'Informe ZAS, ZSS ou ambas.');
                }
            }

            foreach ($itens as $indice => $item) {
                if (in_array($item['resultado'] ?? null, ['nao', 'nao_aplicavel'], true)
                    && trim((string) ($item['justificativa'] ?? '')) === '') {
                    $validator->errors()->add("itens.{$indice}.justificativa", 'Justifique a ausência ou não aplicabilidade.');
                }
            }
        });
    }

    public function salvarTriagem(PaeProtocolo $protocolo, array $municipios, array $itens, User $user): void
    {
        $validator = Validator::make(['municipios' => $municipios, 'itens' => $itens], self::regrasTriagem());
        self::validarComplementos($validator, $municipios, $itens);
        $validator->validate();

        DB::transaction(function () use ($protocolo, $municipios, $itens, $user): void {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [PaeProtocoloStatus::NOVO, PaeProtocoloStatus::ENTRADA_PROCESSO], true)) {
                throw ValidationException::withMessages(['protocolo' => 'A triagem só pode ser alterada na entrada do processo.']);
            }

            $municipioIds = [];
            foreach ($municipios as $municipio) {
                $municipioIds[] = (int) $municipio['municipio_id'];
                $locked->municipiosImpactados()->updateOrCreate(
                    ['municipio_id' => $municipio['municipio_id']],
                    [
                        'na_zas' => (bool) $municipio['na_zas'],
                        'na_zss' => (bool) $municipio['na_zss'],
                        'confirmado_por' => $user->id,
                        'confirmado_em' => now(),
                    ],
                );
            }
            $this->removerAusentes($locked->municipiosImpactados(), 'municipio_id', $municipioIds);

            $chaves = [];
            foreach ($itens as $item) {
                $chaves[] = $item['chave'];
                $locked->itensAdmissibilidade()->updateOrCreate(
                    ['chave' => $item['chave']],
                    [
                        'resultado' => $item['resultado'],
                        'justificativa' => trim((string) ($item['justificativa'] ?? '')) ?: null,
                        'updated_by' => $user->id,
                    ],
                );
            }
            $this->removerAusentes($locked->itensAdmissibilidade(), 'chave', $chaves);

            TimelinePae::registrar($locked, 'admissibilidade_triagem', 'Municípios ZAS/ZSS e checklist do Anexo J atualizados.', $user);
        });
    }

    private function removerAusentes($relacao, string $coluna, array $chaves): void
    {
        if ($chaves === []) {
            $relacao->delete();

            return;
        }

        $relacao->whereNotIn($coluna, $chaves)->delete();
    }

    public function resumo(PaeProtocolo $protocolo): array
    {
        $protocolo->load(['municipiosImpactados.municipio', 'itensAdmissibilidade', 'decisoesAdmissibilidade']);
        $registrados = $protocolo->itensAdmissibilidade->keyBy('chave');

        $itens = [];
        foreach (PaeAnexoJ::CHAVES as $chave) {
            $item = $registrados->get($chave);
            $itens[] = [
                'chave' => $chave,
                'rotulo' => PaeAnexoJ::ROTULOS[$chave],
                'resultado' => $item?->resultado,
                'justificativa' => $item?->justificativa,
            ];
        }

        $usuario = auth()->user();

        return [
            'municipios' => $protocolo->municipiosImpactados->map(fn ($municipio) => [
                'municipio_id' => $municipio->municipio_id,
                'nome' => $municipio->municipio?->nome,
                'uf' => $municipio->municipio?->uf,
                'na_zas' => $municipio->na_zas,
                'na_zss' => $municipio->na_zss,
            ])->values()->all(),
            'itens' => $itens,
            'decisao_vigente' => $protocolo->decisoesAdmissibilidade->first(),
            'legado_sem_triagem' => $protocolo->admissibilidade_legada_sem_triagem,
            'pode_editar' => $usuario?->can('pae.protocolos.edit')
                && in_array($protocolo->status, [PaeProtocoloStatus::NOVO, PaeProtocoloStatus::ENTRADA_PROCESSO], true),
            'pode_decidir' => $usuario?->can('pae.protocolos.validar')
                && $protocolo->status === PaeProtocoloStatus::ENTRADA_PROCESSO,
        ];
    }
}
