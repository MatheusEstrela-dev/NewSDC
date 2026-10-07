<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Pae\Models\PaeFichaAnexoB;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PaeFichaAnexoBService
{
    private const CAMPOS = [
        'nome_barragem', 'nome_mina', 'metodo_construtivo', 'volume_reservatorio',
        'municipio_sede_id', 'municipio_sede_nome', 'latitude', 'longitude',
        'tipo_rejeito', 'toxicidade', 'extensao_zas_km', 'populacao_zas',
        'populacao_zas_mobilidade_reduzida', 'populacao_zss', 'cursos_agua',
        'edificacoes_hospitalares', 'edificacoes_escolares',
        'edificacoes_prisionais', 'edificacoes_outras',
        'estruturas_associadas', 'municipios_snapshot',
    ];

    private const TEXTOS = [
        'nome_barragem', 'nome_mina', 'metodo_construtivo',
        'tipo_rejeito', 'toxicidade',
    ];

    private const INTEIROS = [
        'populacao_zas', 'populacao_zas_mobilidade_reduzida', 'populacao_zss',
        'edificacoes_hospitalares', 'edificacoes_escolares',
        'edificacoes_prisionais', 'edificacoes_outras',
    ];

    private const DECIMAIS = [
        'volume_reservatorio' => 2,
        'latitude' => 7,
        'longitude' => 7,
        'extensao_zas_km' => 3,
    ];

    private const OBRIGATORIOS = [
        'nome_barragem', 'nome_mina', 'metodo_construtivo', 'volume_reservatorio',
        'municipio_sede_id', 'latitude', 'longitude', 'tipo_rejeito',
        'toxicidade', 'extensao_zas_km', 'populacao_zas',
        'populacao_zas_mobilidade_reduzida', 'populacao_zss', 'cursos_agua',
        'edificacoes_hospitalares', 'edificacoes_escolares',
        'edificacoes_prisionais', 'edificacoes_outras', 'estruturas_associadas',
    ];

    public function visualizar(PaeProtocolo $protocolo, ?int $versao = null): array
    {
        $municipiosAtuais = $this->snapshotMunicipios(
            $protocolo->municipiosImpactados()->with('municipio:id,nome')->get(),
        );
        $atual = $protocolo->fichasAnexoB()->reorder('versao', 'desc')->first();
        $selecionada = $versao === null
            ? $atual
            : $protocolo->fichasAnexoB()->where('versao', $versao)->firstOrFail();
        $selecionada?->load('autor:id,name');

        $ficha = $selecionada === null
            ? $this->preencherDoEmpreendimento($protocolo, $municipiosAtuais)
            : [
                ...$selecionada->only(self::CAMPOS),
                'versao' => $selecionada->versao,
                'criado_em' => $selecionada->created_at?->toIso8601String(),
                'autor_nome' => $selecionada->autor?->name,
            ];
        $municipiosDaFicha = $selecionada?->municipios_snapshot ?? $municipiosAtuais;

        return [
            'ficha' => $ficha,
            'versoes' => $protocolo->fichasAnexoB()
                ->with('autor:id,name')
                ->reorder('versao', 'desc')
                ->get()
                ->map(fn (PaeFichaAnexoB $item): array => [
                    'versao' => $item->versao,
                    'criado_em' => $item->created_at?->toIso8601String(),
                    'autor_nome' => $item->autor?->name,
                ])->all(),
            'municipios_atuais' => $municipiosAtuais,
            'pendencias' => $this->pendencias($ficha, $municipiosDaFicha),
            'municipios_alterados' => $selecionada !== null
                && ! $this->listasIguais($municipiosDaFicha, $municipiosAtuais),
            'rascunho' => $selecionada === null,
            'historica' => $selecionada !== null && $atual?->versao !== $selecionada->versao,
            'versao_atual' => $atual?->versao ?? 0,
        ];
    }

    public function salvar(PaeProtocolo $protocolo, array $dados, User $usuario): PaeFichaAnexoB
    {
        return DB::transaction(function () use ($protocolo, $dados, $usuario): PaeFichaAnexoB {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            if ($locked->arquivado) {
                throw ValidationException::withMessages([
                    'protocolo' => 'Protocolo arquivado: a ficha está somente para consulta.',
                ]);
            }

            $atual = $locked->fichasAnexoB()->reorder('versao', 'desc')->first();
            if ((int) ($dados['base_versao'] ?? -1) !== ($atual?->versao ?? 0)) {
                throw ValidationException::withMessages([
                    'base_versao' => 'A ficha foi alterada por outra pessoa. Recarregue a página.',
                ]);
            }

            $municipios = $locked->municipiosImpactados()->with('municipio:id,nome')->get();
            $base = $atual?->only(self::CAMPOS) ?? array_fill_keys(self::CAMPOS, null);
            $conteudo = $this->normalizar(array_replace($base, $dados), $municipios);
            if ($atual !== null && $this->igual($atual, $conteudo)) {
                return $atual;
            }

            $numero = ($atual?->versao ?? 0) + 1;
            $ficha = $locked->fichasAnexoB()->create([
                ...$conteudo,
                'versao' => $numero,
                'criado_por' => $usuario->id,
                'created_at' => now(),
            ]);
            TimelinePae::registrar(
                $locked, 'ficha_anexo_b',
                'Ficha cadastral Anexo B salva na versão '.$numero.'.', $usuario,
            );

            return $ficha;
        });
    }

    public function pendencias(array $ficha, array $municipios): array
    {
        $pendencias = [];
        foreach (self::OBRIGATORIOS as $campo) {
            $valor = $ficha[$campo] ?? null;
            if ($valor === null || (is_string($valor) && trim($valor) === '')) {
                $pendencias[] = $campo;
            }
        }

        if (! collect($municipios)->contains(fn (array $m): bool => (bool) $m['na_zas'])) {
            $pendencias[] = 'municipios_zas';
        }
        if (! collect($municipios)->contains(fn (array $m): bool => (bool) $m['na_zss'])) {
            $pendencias[] = 'municipios_zss';
        }

        return $pendencias;
    }

    private function preencherDoEmpreendimento(PaeProtocolo $protocolo, array $municipios): array
    {
        $empreendimento = $protocolo->empreendimento;
        $ficha = array_fill_keys(self::CAMPOS, null);
        $ficha['nome_barragem'] = $empreendimento?->nome;
        $ficha['nome_mina'] = $empreendimento?->mina;
        $ficha['metodo_construtivo'] = $empreendimento?->m_construcao;
        $ficha['volume_reservatorio'] = $empreendimento?->volume;
        $ficha['municipio_sede_id'] = $empreendimento?->municipio_id;
        $ficha['municipio_sede_nome'] = $empreendimento?->municipio?->nome;
        $ficha['populacao_zas'] = $empreendimento?->pop_zas;
        $ficha['municipios_snapshot'] = $municipios;
        $ficha['versao'] = 0;
        $ficha['criado_em'] = null;
        $ficha['autor_nome'] = null;

        return $ficha;
    }

    private function normalizar(array $dados, Collection $municipios): array
    {
        $conteudo = [];
        foreach (self::TEXTOS as $campo) {
            $texto = trim((string) ($dados[$campo] ?? ''));
            $conteudo[$campo] = $texto === '' ? null : $texto;
        }
        foreach (self::INTEIROS as $campo) {
            $valor = $dados[$campo] ?? null;
            $conteudo[$campo] = $valor === null || $valor === '' ? null : (int) $valor;
        }
        foreach (self::DECIMAIS as $campo => $escala) {
            $conteudo[$campo] = $this->decimal($dados[$campo] ?? null, $escala);
        }

        $sedeId = $dados['municipio_sede_id'] ?? null;
        $conteudo['municipio_sede_id'] = $sedeId === null || $sedeId === '' ? null : (int) $sedeId;
        $conteudo['municipio_sede_nome'] = $conteudo['municipio_sede_id'] === null
            ? null
            : Municipio::query()->whereKey($conteudo['municipio_sede_id'])->value('nome');
        $conteudo['cursos_agua'] = $this->normalizarLista($dados['cursos_agua'] ?? null);
        $conteudo['estruturas_associadas'] = $this->normalizarLista($dados['estruturas_associadas'] ?? null);
        $conteudo['municipios_snapshot'] = $this->snapshotMunicipios($municipios);

        return $conteudo;
    }

    private function igual(PaeFichaAnexoB $atual, array $conteudo): bool
    {
        return Arr::sortRecursive($atual->only(self::CAMPOS)) === Arr::sortRecursive($conteudo);
    }

    private function listasIguais(array $primeira, array $segunda): bool
    {
        return Arr::sortRecursive($primeira) === Arr::sortRecursive($segunda);
    }

    private function snapshotMunicipios(Collection $municipios): array
    {
        return $municipios->map(fn ($item): array => [
            'municipio_id' => (int) $item->municipio_id,
            'nome' => $item->municipio?->nome,
            'na_zas' => (bool) $item->na_zas,
            'na_zss' => (bool) $item->na_zss,
        ])->sortBy('municipio_id')->values()->all();
    }

    private function normalizarLista(?array $itens): ?array
    {
        if ($itens === null) {
            return null;
        }

        return collect($itens)
            ->map(fn ($item): string => trim((string) $item))
            ->filter(fn (string $item): bool => $item !== '')
            ->unique(fn (string $item): string => mb_strtolower($item))
            ->sortBy(fn (string $item): string => mb_strtolower($item))
            ->values()->all();
    }

    private function decimal(mixed $valor, int $escala): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $partes = explode('.', (string) $valor, 2);
        $inteiro = $partes[0];
        $fracao = str_pad($partes[1] ?? '', $escala, '0');

        return $inteiro.'.'.substr($fracao, 0, $escala);
    }
}
