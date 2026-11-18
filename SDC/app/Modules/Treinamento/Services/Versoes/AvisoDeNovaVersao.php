<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Versoes;

use App\Models\User;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Mail\NovaVersaoDisponivelMail;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Enfileira o aviso "versao nova disponivel" para quem se formou (inscricao
 * concluida) numa versao anterior do mesmo curso e tem e-mail cadastrado.
 */
class AvisoDeNovaVersao
{
    /** @return int quantidade de e-mails enfileirados */
    public function avisar(Treinamento $nova): int
    {
        if ($nova->curso_id === null || $nova->versao <= 1) {
            return 0;
        }

        $curso = (string) $nova->titulo;
        $rotulo = $nova->rotuloDeVersao();
        $enviados = [];

        foreach ($this->formadosEmVersaoAnterior($nova) as $inscricao) {
            $inscrito = $inscricao->inscrito;
            $email = $inscrito === null ? '' : trim((string) $inscrito->email);
            $chave = mb_strtolower($email);
            if ($email === '' || isset($enviados[$chave])) {
                continue;
            }

            $enviados[$chave] = true;
            Mail::to($email)->queue(new NovaVersaoDisponivelMail((string) $inscrito->name, $curso, $rotulo, $nova->versao_notas));
        }

        return count($enviados);
    }

    /**
     * Uma consulta para as inscricoes; o morphTo carrega um lote por tipo de
     * inscrito, nunca uma consulta por pessoa.
     *
     * @return Collection<int, Inscricao>
     */
    private function formadosEmVersaoAnterior(Treinamento $nova): Collection
    {
        $anteriores = Treinamento::query()
            ->where('curso_id', $nova->curso_id)
            ->where('tipo', TipoTreinamento::EAD->value)
            ->where('versao', '<', $nova->versao)
            ->select('id');

        return Inscricao::query()
            ->whereIn('treinamento_id', $anteriores)
            ->whereNotNull('concluido_em')
            ->whereIn('inscrito_type', [(new User)->getMorphClass(), (new Cidadao)->getMorphClass()])
            ->with('inscrito')
            ->get();
    }
}
