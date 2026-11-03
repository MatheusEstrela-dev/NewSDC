<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Services;

use App\Models\User;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;

/**
 * Avisa o publico-alvo (NotaTecnicaLeituraService::publicoAlvo) quando uma nota
 * entra em vigor ou ganha versao nova, com link para abrir e dar ciencia.
 *
 * Os canais seguem a preferencia de cada usuario para o modulo `notas-tecnicas`
 * (config/notificacoes.php): o sino vem ligado, e-mail e push sao opt-in -- por
 * isso publicar nao dispara e-mail em massa para todo o quadro.
 *
 * Despacho afterCommit: quem chama esta dentro da transacao de publicacao, e um
 * rollback nao pode deixar aviso de nota que nao foi publicada.
 */
final class NotaTecnicaNotificacaoService
{
    private const MODULO = 'notas-tecnicas';

    public function __construct(private readonly NotaTecnicaLeituraService $leituras) {}

    public function avisarPublicacao(NotaTecnica $nota, NotaTecnicaVersao $versao, User $autor): void
    {
        $this->avisar(
            $nota,
            $versao,
            $autor,
            "Nova nota técnica: {$nota->codigo}",
            "{$nota->titulo} (versão {$versao->numero}) está em vigor. Leia e dê ciência.",
        );
    }

    public function avisarNovaVersao(NotaTecnica $nota, NotaTecnicaVersao $versao, User $autor): void
    {
        $this->avisar(
            $nota,
            $versao,
            $autor,
            "Nota técnica atualizada: {$nota->codigo}",
            "{$nota->titulo} ganhou a versão {$versao->numero}. A ciência anterior não vale para ela: leia de novo.",
        );
    }

    private function avisar(NotaTecnica $nota, NotaTecnicaVersao $versao, User $autor, string $titulo, string $mensagem): void
    {
        // Quem publicou ja sabe: nao se avisa a si mesmo.
        $destinatarios = $this->leituras->publicoAlvo()
            ->whereKeyNot($autor->id)
            ->pluck('users.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($destinatarios === []) {
            return;
        }

        EntregarNotificacaoJob::dispatch(new NotificacaoSpec(
            modulo: self::MODULO,
            titulo: $titulo,
            mensagem: $mensagem,
            tipo: 'info',
            // Um grupo por versao: republicar a mesma versao nao empilha avisos.
            groupKey: self::MODULO . ":{$nota->id}:v{$versao->id}",
            acaoUrl: route('notas-tecnicas.index', ['nota' => $nota->id], false),
            acaoTexto: 'Abrir nota',
        ), $destinatarios)->afterCommit();
    }
}
