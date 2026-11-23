<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use App\Models\User;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use Illuminate\Http\Request;

/**
 * Certificado na visao do proprio aluno ("Meus certificados", "Meus cursos",
 * "Minhas inscricoes"). So sai disponivel, com codigo e link de validacao,
 * quando Certificado::liberadoAoAluno(); enquanto falta avaliar o curso
 * (legado) sai indisponivel, com aguarda_avaliacao e o caminho para avaliar.
 * A gestao segue com o CertificadoResource, sem esse corte.
 *
 * Espera `treinamento` e `inscricao.avaliacao` carregados (evita N+1 nas listas).
 */
class CertificadoDoAlunoResource extends CertificadoResource
{
    public function toArray(Request $request): array
    {
        $dados = parent::toArray($request);

        if ($this->liberadoAoAluno()) {
            return $dados + ['aguarda_avaliacao' => false, 'url_avaliar' => null];
        }

        $aguarda = $this->aguardaAvaliacao();

        return array_merge($dados, [
            'disponivel' => false,
            'codigo_verificacao' => null,
            'url_validacao' => null,
            'aguarda_avaliacao' => $aguarda,
            'url_avaliar' => $aguarda ? $this->urlAvaliar() : null,
        ]);
    }

    /**
     * Onde o aluno avalia: na sala, quando o curso EAD a tem aberta; senao no
     * card de avaliacao pendente de "Meus cursos" (servidor) ou "Minhas
     * inscricoes" (cidadao).
     */
    private function urlAvaliar(): string
    {
        $curso = $this->treinamento;
        $servidor = $this->inscricao->inscrito_type === User::class;

        if (InscricaoDoAluno::salaAberta($curso)) {
            return $servidor
                ? route('treinamentos.aluno.sala', $curso->id)
                : route('portal.treinamento.cursos.sala', $curso->link_publico_slug);
        }

        return $servidor ? route('treinamentos.aluno.index') : route('portal.treinamento.inscricoes.index');
    }
}
