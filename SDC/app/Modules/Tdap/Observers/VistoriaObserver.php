<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Observers;

use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Services\HistoricoService;
use App\Modules\Tdap\Services\VistoriaFotoService;
use App\Modules\Tdap\Support\VigenciaVistoria;

class VistoriaObserver
{
    public function __construct(
        private readonly HistoricoService $historicoService,
        private readonly VistoriaFotoService $fotoService,
    ) {}

    public function created(Vistoria $vistoria): void
    {
        $tipo = $vistoria->parecer === ParecerVistoria::Aprovada
            ? 'vistoria.aprovada'
            : 'vistoria.reprovada';

        // A vigencia sai da constante, nao de um "12" digitado aqui: o texto
        // fica gravado no acervo para sempre, e um numero solto passaria a
        // mentir no dia em que a regra mudasse.
        $obs = $vistoria->parecer === ParecerVistoria::Aprovada
            ? "Vistoria #{$vistoria->id} aprovada (vigencia ".VigenciaVistoria::VIGENCIA_MESES.' meses).'
            : "Vistoria #{$vistoria->id} reprovada.";

        $this->historicoService->registrar(
            tipoEvento: $tipo,
            entity: $vistoria,
            obs: $obs,
            payload: [
                'placa_id' => $vistoria->placa_id,
                'data'     => $vistoria->data?->toDateString(),
                'parecer'  => $vistoria->parecer->value,
                'ficha'    => $vistoria->ficha,
            ],
        );
    }

    public function updated(Vistoria $vistoria): void
    {
        if (! $vistoria->wasChanged('parecer')) {
            return;
        }

        $tipo = $vistoria->parecer === ParecerVistoria::Aprovada
            ? 'vistoria.aprovada'
            : 'vistoria.reprovada';

        $this->historicoService->registrar(
            tipoEvento: $tipo,
            entity: $vistoria,
            obs: "Parecer da vistoria #{$vistoria->id} alterado para {$vistoria->parecer->label()}.",
            payload: [
                // `getOriginal` devolve o ENUM (a coluna tem cast), e o valor
                // novo era string: o mesmo JSON saia com os dois lados em
                // formatos diferentes, e quem lesse o payload teria de saber
                // disso. Os dois viram `->value`.
                'parecer_anterior' => $this->valorDoParecer($vistoria->getOriginal('parecer')),
                'parecer_novo'     => $vistoria->parecer->value,
            ],
        );
    }

    /**
     * Exclusao entra na trilha.
     *
     * Faltava: criar e mudar parecer ficavam registrados, sumir com a ficha
     * nao. E a exclusao e o evento que mais importa nesta entidade -- e ela
     * que pode derrubar a aptidao de um caminhao em operacao.
     */
    public function deleted(Vistoria $vistoria): void
    {
        // `deleted` dispara duas vezes num force delete (uma pelo soft delete
        // que ja passou, outra pela remocao fisica). O registro ja saiu na
        // primeira; repetir criaria duas linhas para o mesmo fato.
        if ($vistoria->isForceDeleting()) {
            return;
        }

        $placa = $vistoria->caminhao?->placa ?? "id={$vistoria->placa_id}";

        $this->historicoService->registrar(
            tipoEvento: 'vistoria.excluida',
            entity: $vistoria,
            obs: "Vistoria #{$vistoria->id} do caminhao {$placa} excluida.",
            payload: [
                'placa_id' => $vistoria->placa_id,
                'data'     => $vistoria->data?->toDateString(),
                'parecer'  => $this->valorDoParecer($vistoria->parecer),
                'ficha'    => $vistoria->ficha,
            ],
        );
    }

    /**
     * Binarios das fotos morrem junto com a linha -- e so aqui.
     *
     * A FK de `tdap_vistoria_fotos` e `cascadeOnDelete`, mas cascade de banco
     * so dispara em DELETE fisico, e a vistoria faz soft delete: sem este
     * hook, as linhas ficavam vivas e os arquivos no disco 'tdap' para sempre,
     * inalcancaveis pela tela.
     *
     * No soft delete os arquivos FICAM de proposito: a ficha pode voltar por
     * `restore()`, e uma vistoria restaurada sem as fotos que a sustentavam
     * seria pior que nao restaurar.
     *
     * Roda em `forceDeleting`, e nao em `forceDeleted`: depois da remocao
     * fisica o cascade do banco ja levou as linhas, e nao haveria mais como
     * saber quais arquivos apagar.
     */
    public function forceDeleting(Vistoria $vistoria): void
    {
        foreach ($vistoria->fotos()->get() as $foto) {
            $this->fotoService->destroy($foto);
        }
    }

    /** O parecer chega como enum (cast) ou string crua (getOriginal de base legada). */
    private function valorDoParecer(mixed $parecer): ?string
    {
        return $parecer instanceof ParecerVistoria ? $parecer->value : $parecer;
    }
}
