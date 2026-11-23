<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Shared\BaseService;
use App\Modules\Treinamento\Enums\Sexo;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Mail\InscricaoConfirmadaMail;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\ControleDeVagas;
use App\Modules\Treinamento\Support\MunicipioDoInscrito;
use App\Modules\Treinamento\Support\PeriodoDeInscricao;
use App\Modules\Treinamento\Support\VersoesDoCurso;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InscricaoService extends BaseService
{
    public function listPorTreinamento(int $treinamentoId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Inscricao::query()
            ->with(['inscrito', 'certificado'])
            ->porTreinamento($treinamentoId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('data_inscricao', 'desc')->paginate($perPage);
    }

    /**
     * $inscrito e polimorfico: App\Models\User (servidor, area interna) ou
     * App\Modules\Treinamento\Models\Cidadao (portal do cidadao).
     */
    public function inscrever(Treinamento $treinamento, Model $inscrito, ?Sexo $sexo = null, ?string $dataNascimento = null): Inscricao
    {
        return $this->registrarInscricao($treinamento, $inscrito, $sexo, $dataNascimento, null);
    }

    /**
     * Inscricao de atualizacao: pessoa formada em versao anterior entra na
     * vigente. Passa por publicacao, vigencia, vaga, periodo e publico como
     * qualquer inscricao, mas nunca pela regra das 3 inscricoes, e nasce
     * aprovada (a pessoa ja foi aprovada nesta formacao).
     */
    public function inscreverParaAtualizacao(Treinamento $vigente, Model $inscrito, Inscricao $origem, ?Sexo $sexo = null, ?string $dataNascimento = null): Inscricao
    {
        return $this->registrarInscricao($vigente, $inscrito, $sexo, $dataNascimento, $origem);
    }

    private function registrarInscricao(Treinamento $treinamento, Model $inscrito, ?Sexo $sexo, ?string $dataNascimento, ?Inscricao $origemDaAtualizacao): Inscricao
    {
        if ($treinamento->isEad() && !$treinamento->estaPublicado()) {
            throw new \DomainException('Este curso ainda nao foi publicado.');
        }

        $this->exigirVersaoVigente($treinamento);

        if (!$treinamento->situacaoEfetiva()->recebeInscricao()) {
            throw new \DomainException('Este curso esta '.mb_strtolower($treinamento->situacaoEfetiva()->getLabel()).' e nao recebe inscricoes.');
        }

        // So o status aqui. A vaga fica com ControleDeVagas, sob trava, mais
        // abaixo: contar aprovadas sem trava (como podeReceberInscricao() faz)
        // deixaria duas inscricoes simultaneas ocuparem a ultima vaga e
        // trocaria a mensagem "nao tem mais vagas" por esta.
        if (!$treinamento->status->podeReceberInscricoes()) {
            throw new \DomainException('Este treinamento nao esta recebendo inscricoes no momento.');
        }

        if (!$treinamento->publico_alvo->aceita($inscrito)) {
            throw new \DomainException('Este treinamento nao e destinado ao seu perfil.');
        }

        if ($inscrito instanceof User && app(ProfessorService::class)->leciona((int) $inscrito->getKey(), $treinamento)) {
            throw new \DomainException('Professor do curso nao pode se inscrever como aluno.');
        }

        $jaInscrito = Inscricao::query()
            ->porTreinamento($treinamento->id)
            ->where('inscrito_type', $inscrito::class)
            ->where('inscrito_id', $inscrito->getKey())
            ->exists();

        if ($jaInscrito) {
            throw new \DomainException('Voce ja possui uma inscricao neste treinamento.');
        }

        if ($origemDaAtualizacao === null) {
            $this->garantirDentroDoLimiteDoCurso($treinamento, $inscrito);
        }

        // Periodo de atuacao: so o limite final recusa (ver PeriodoDeInscricao).
        PeriodoDeInscricao::exigirAberto($treinamento);

        $aprovacaoAutomatica = $origemDaAtualizacao !== null || (bool) $treinamento->inscricao_aprovacao_automatica;

        // A vaga so e segura com a linha do limite travada, e a trava so vale
        // dentro da transacao - por isso a checagem e a insercao andam juntas.
        $inscricao = ConexaoTreinamento::transacao(function () use ($treinamento, $inscrito, $sexo, $dataNascimento, $aprovacaoAutomatica, $origemDaAtualizacao) {
            ControleDeVagas::exigirVagaComTrava($treinamento);

            return Inscricao::create([
                'treinamento_id' => $treinamento->id,
                'inscrito_type' => $inscrito::class,
                'inscrito_id' => $inscrito->getKey(),
                'status' => ($aprovacaoAutomatica ? StatusInscricao::APROVADA : StatusInscricao::PENDENTE)->value,
                'data_aprovacao' => $aprovacaoAutomatica ? now() : null,
                'aprovado_por_id' => null,
                'qr_code_token' => (string) Str::uuid(),
                'data_inscricao' => now(),
                'sexo' => $sexo?->value,
                'data_nascimento' => $dataNascimento,
                'municipio_inscrito_id' => MunicipioDoInscrito::de($inscrito),
                'atualizacao_de_inscricao_id' => $origemDaAtualizacao?->id,
            ]);
        });

        if ($aprovacaoAutomatica) {
            $this->notificarAprovacao($inscricao);
        }

        // O QR Code (o "ingresso") so sai por e-mail quando a inscricao e
        // aprovada (ver aprovar() abaixo) - antes disso a inscricao ainda pode
        // ser reprovada, e mandar o ingresso de uma inscricao pendente confunde
        // o cidadao (ingresso na mao antes de ter vaga confirmada).

        // Avisa o dono do treinamento pela trilha do protocolo. Relacionado e a
        // acao certa: um terceiro acrescentou conteudo, nada mudou de situacao.
        // O agrupamento do modulo cuida do volume -- um curso com 200 inscritos
        // vira um card com contador, nao 200 cards.
        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $treinamento->id,
            AcaoTrilha::Relacionado,
            'nova inscricao',
        );

        return $inscricao;
    }

    /** Versao substituida por outra publicada mais nova nao recebe inscricao nova. */
    private function exigirVersaoVigente(Treinamento $treinamento): void
    {
        if (!$treinamento->isEad() || $treinamento->eVigente()) {
            return;
        }

        $vigente = VersoesDoCurso::vigenteDe((int) $treinamento->curso_id);

        throw new \DomainException(
            'Esta versao do curso foi substituida pela '.($vigente?->rotuloDeVersao() ?? 'versao atual').'. Inscreva-se na versao atual.'
        );
    }

    private function garantirDentroDoLimiteDoCurso(Treinamento $treinamento, Model $inscrito): void
    {
        if ($treinamento->curso_id === null) {
            return;
        }

        $situacao = app(RegraDeReinscricaoService::class)->situacao((int) $treinamento->curso_id, $inscrito);

        if ($situacao->bloqueado) {
            throw new \DomainException(sprintf(
                'O limite e de %d inscricoes por curso e este CPF ja o atingiu neste curso. Nova inscricao liberada a partir de %s.',
                $situacao->limite,
                $situacao->bloqueadoAte->format('d/m/Y'),
            ));
        }
    }

    /**
     * Sexo informado na inscricao mais recente do mesmo inscrito, para
     * pre-preencher a pergunta e nao trata-lo como se fosse a primeira vez.
     */
    public function sexoDaUltimaInscricao(?Model $inscrito): ?Sexo
    {
        return $this->ultimaInscricaoCom($inscrito, 'sexo')?->sexo;
    }

    /**
     * Data de nascimento informada na inscricao mais recente do mesmo inscrito
     * (Y-m-d), para pre-preencher o campo.
     */
    public function dataNascimentoDaUltimaInscricao(?Model $inscrito): ?string
    {
        return $this->ultimaInscricaoCom($inscrito, 'data_nascimento')?->data_nascimento?->toDateString();
    }

    private function ultimaInscricaoCom(?Model $inscrito, string $coluna): ?Inscricao
    {
        if ($inscrito === null) {
            return null;
        }

        return Inscricao::query()
            ->where('inscrito_type', $inscrito::class)
            ->where('inscrito_id', $inscrito->getKey())
            ->whereNotNull($coluna)
            ->orderByDesc('data_inscricao')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Texto do aviso apos inscrever: a inscricao pode ja nascer aprovada.
     */
    public function mensagemDeSucesso(Inscricao $inscricao): string
    {
        return $inscricao->status === StatusInscricao::APROVADA
            ? 'Inscricao realizada e aprovada!'
            : 'Inscricao realizada! Aguarde a aprovacao.';
    }

    /**
     * A vaga e reconferida aqui, nao so no inscrever(): entre a inscricao
     * pendente e a aprovacao outras inscricoes podem ter ocupado a vaga, e
     * duas aprovacoes simultaneas disputariam a mesma ultima vaga.
     */
    public function aprovar(Inscricao $inscricao, User $aprovador, ?string $observacoes = null): void
    {
        if ($inscricao->status !== StatusInscricao::PENDENTE) {
            throw new \DomainException('Somente inscricoes pendentes podem ser aprovadas.');
        }

        $inscricao->loadMissing(['treinamento']);

        if ($inscricao->treinamento === null) {
            throw new \DomainException('Treinamento da inscricao nao encontrado.');
        }

        ConexaoTreinamento::transacao(function () use ($inscricao, $aprovador, $observacoes) {
            ControleDeVagas::exigirVagaComTrava($inscricao->treinamento);

            $inscricao->update([
                'status' => StatusInscricao::APROVADA->value,
                'data_aprovacao' => now(),
                'aprovado_por_id' => $aprovador->id,
                'observacoes' => $observacoes,
            ]);
        });

        // E-mail fora da transacao: enfileirar dentro faria o job sair antes do
        // commit em qualquer fila nao transacional.
        $this->notificarAprovacao($inscricao);
    }

    private function notificarAprovacao(Inscricao $inscricao): void
    {
        // RF03: QR Code por e-mail so faz sentido para presencial - online
        // confirma presenca direto na tela (ver PresencaService::autoconfirmar()).
        $inscricao->loadMissing(['treinamento', 'inscrito']);
        if ($inscricao->treinamento->tipo === TipoTreinamento::PRESENCIAL && $inscricao->inscrito?->email) {
            Mail::to($inscricao->inscrito->email)->queue(new InscricaoConfirmadaMail($inscricao));
        }
    }

    public function reprovar(Inscricao $inscricao, User $aprovador, string $observacoes): void
    {
        if ($inscricao->status !== StatusInscricao::PENDENTE) {
            throw new \DomainException('Somente inscricoes pendentes podem ser reprovadas.');
        }

        $inscricao->update([
            'status' => StatusInscricao::REPROVADA->value,
            'data_aprovacao' => now(),
            'aprovado_por_id' => $aprovador->id,
            'observacoes' => $observacoes,
        ]);
    }

    public function cancelar(Inscricao $inscricao): void
    {
        if ($inscricao->status->isFinal() && $inscricao->status !== StatusInscricao::APROVADA) {
            throw new \DomainException('Esta inscricao nao pode mais ser cancelada.');
        }

        $inscricao->update(['status' => StatusInscricao::CANCELADA->value]);
    }
}
