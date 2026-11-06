<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Pae;

use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Exceptions\EntradaJaAplicadaException;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Requests\RegistrarDevolutivaRequest;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Http\Request;

/** Espelha POST v1/pae/notificacoes/{paeNotificacao}/devolutiva (NotificacaoController@devolutiva). */
final class RegistrarDevolutivaAplicador extends AplicadorBase
{
    private const PARAMETRO = 'paeNotificacao';

    public function __construct(private readonly PaeNotificacaoService $service)
    {
    }

    public function regras(Request $request): array
    {
        return (new RegistrarDevolutivaRequest())->rules();
    }

    /** Mesma fila da emissao: a devolutiva mexe nos prazos do protocolo. */
    public function recursoChave(array $payload, array $parametrosRota): string
    {
        $id = $this->parametroDeRota($parametrosRota, self::PARAMETRO);

        $analiseId = ctype_digit($id)
            ? PaeNotificacao::query()->whereKey((int) $id)->value('pae_analise_id')
            : null;
        $protocoloId = $analiseId !== null
            ? PaeAnalise::query()->whereKey($analiseId)->value('pae_protocolo_id')
            : null;

        return $protocoloId !== null ? "pae:protocolo:{$protocoloId}" : "pae:notificacao:{$id}";
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        // Trava a linha na transacao do job: a conferencia de repeticao e a
        // gravacao do service enxergam o mesmo estado.
        $notificacao = PaeNotificacao::query()->lockForUpdate()->findOrFail(
            $this->parametroDeRota($mensagem->parametrosRota, self::PARAMETRO),
        );

        if ($notificacao->dt_devolutiva !== null) {
            throw new EntradaJaAplicadaException(
                'Este ciclo de notificacao ja possui devolutiva registrada.',
                self::resultado($notificacao),
            );
        }

        return self::resultado($this->service->registrarDevolutiva(
            $notificacao,
            $this->usuario(),
            (string) $mensagem->payload['dt_devolutiva'],
        ));
    }

    /** @return array{id: int, dt_devolutiva: ?string} */
    private static function resultado(PaeNotificacao $notificacao): array
    {
        return [
            'id'            => (int) $notificacao->id,
            'dt_devolutiva' => $notificacao->dt_devolutiva?->toDateString(),
        ];
    }
}
