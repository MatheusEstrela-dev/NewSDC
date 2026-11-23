<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Treinamento\Enums\CategoriaOferta;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Enums\SituacaoCurso;
use App\Modules\Treinamento\Enums\StatusExibicaoCurso;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Support\ClassificacaoDeOferta;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Treinamento extends Model implements Rastreavel
{
    use SoftDeletes, TrilhaDeAcoes, UsaConexaoDeTreinamento;

    /** Rotas que entregam a imagem de capa: uma por escopo (servidor e cidadao). */
    public const ROTA_CAPA_SDC = 'treinamentos.capa';

    public const ROTA_CAPA_PORTAL = 'portal.treinamento.capa';

    protected $table = 'treinamento.treinamentos';

    protected $fillable = [
        'titulo',
        'descricao',
        'carga_horaria',
        'categoria',
        'tipo',
        'publico_alvo',
        'inscricao_aprovacao_automatica',
        'status',
        'instrutor',
        'local',
        'municipio_id',
        'curso_id',
        'versao',
        'versao_origem_id',
        'versao_notas',
        'data_inicio',
        'data_fim',
        'hora_inicio',
        'numero_vagas',
        'percentual_frequencia_minimo',
        'created_by',
        'link_publico_slug',
        'publicado_em',
        'presenca_liberada',
        'presenca_liberada_em',
        'presenca_liberada_por',
        'presenca_autoconfirmavel',
        'finalizado_em',
        'com_tutoria',
    ];

    protected $casts = [
        'categoria' => CategoriaTreinamento::class,
        'tipo' => TipoTreinamento::class,
        'publico_alvo' => PublicoAlvo::class,
        'inscricao_aprovacao_automatica' => 'boolean',
        'status' => StatusTreinamento::class,
        'situacao' => SituacaoCurso::class,
        'situacao_alterada_em' => 'datetime',
        'municipio_id' => 'integer',
        'curso_id' => 'integer',
        'versao' => 'integer',
        'versao_origem_id' => 'integer',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'percentual_frequencia_minimo' => 'decimal:2',
        'presenca_liberada' => 'boolean',
        'presenca_autoconfirmavel' => 'boolean',
        'publicado_em' => 'datetime',
        'presenca_liberada_em' => 'datetime',
        'finalizado_em' => 'datetime',
        'com_tutoria' => 'boolean',
    ];

    // vagas_disponiveis e total_inscricoes sao accessors sob demanda (os Resources os
    // leem direto): ficam fora de $appends para nao custar um count por linha em
    // qualquer serializacao do model. Em listas, use withCount('inscricoes', 'inscricoesAprovadas').

    // Relationships

    /** Curso do catalogo ao qual esta turma pertence (NULL = ainda nao vinculada). */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    /** Versao da qual esta foi copiada (NULL na primeira versao do curso). */
    public function versaoOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'versao_origem_id');
    }

    /** Rotulo de exibicao da versao ("v2.0"). */
    public function rotuloDeVersao(): string
    {
        return "v{$this->versao}.0";
    }

    public function modulos(): HasMany
    {
        return $this->hasMany(Modulo::class)->orderBy('ordem');
    }

    public function professores(): HasMany
    {
        return $this->hasMany(TreinamentoProfessor::class);
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    public function inscricoesAprovadas(): HasMany
    {
        return $this->hasMany(Inscricao::class)->where('status', 'APROVADA');
    }

    public function certificados(): HasMany
    {
        return $this->hasMany(Certificado::class);
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(AvaliacaoCurso::class);
    }

    public function cronogramas(): HasMany
    {
        return $this->hasMany(CursoCronograma::class)->orderBy('inicio');
    }

    /** Historico do fluxo do curso, do mais recente para o mais antigo. */
    public function eventos(): HasMany
    {
        return $this->hasMany(CursoEvento::class)->orderByDesc('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function presencaLiberadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenca_liberada_por');
    }

    // Business Logic

    public function podeReceberInscricao(): bool
    {
        return $this->situacaoEfetiva()->recebeInscricao() && $this->aceitaInscricaoAlemDaSituacao();
    }

    /**
     * Status e vagas permitem inscricao, sem considerar pausa: e o que o
     * catalogo usa para listar um curso pausado como "Pausado" em vez de
     * esconde-lo.
     */
    public function aceitaInscricaoAlemDaSituacao(): bool
    {
        if (!$this->status->podeReceberInscricoes()) {
            return false;
        }

        if ($this->numero_vagas !== null && $this->totalDeInscricoesAprovadas() >= $this->numero_vagas) {
            return false;
        }

        return true;
    }

    public function isEad(): bool
    {
        return $this->tipo === TipoTreinamento::EAD;
    }

    /**
     * RN13 - ponto unico de decisao do controle de presenca. Vale para
     * presencial, semi-presencial, online (ao vivo) e qualquer evento; o EAD
     * nao tem (o progresso da trilha ja valida a participacao).
     */
    public function usaControleDePresenca(): bool
    {
        return ! $this->isEad() || $this->categoria === CategoriaTreinamento::EVENTO;
    }

    /** Categoria de oferta da turma (mosaico das vitrines); regra em ClassificacaoDeOferta. */
    public function categoriaOferta(): CategoriaOferta
    {
        return ClassificacaoDeOferta::classificar($this->categoria, $this->tipo, (bool) $this->com_tutoria);
    }

    /**
     * Status que a tela do gestor mostra, derivado de status + publicacao.
     * Nao ha coluna para ele; a precedencia esta documentada no enum.
     * A situacao (Pausado/Desativado) e ortogonal e vem de situacaoEfetiva().
     */
    public function statusDeExibicao(): StatusExibicaoCurso
    {
        // "Publicado" usa a mesma definicao do resto do modulo (estaPublicado:
        // slug + publicado_em), para nao haver duas nocoes de publicado.
        return StatusExibicaoCurso::derivar($this->status, $this->estaPublicado());
    }

    /**
     * Inscricao encerrada pelas datas do curso. So o limite final recusa:
     * inscricao antes do inicio e inscricao antecipada, sempre permitida pelo
     * modulo.
     */
    public function encerrouInscricoes(?\Carbon\CarbonInterface $agora = null): bool
    {
        if ($this->data_fim === null) {
            return false;
        }

        $hoje = \Carbon\CarbonImmutable::parse($agora ?? now())->startOfDay();

        return $hoje->greaterThan(\Carbon\CarbonImmutable::parse($this->data_fim)->startOfDay());
    }

    public function estaCheio(): bool
    {
        if ($this->numero_vagas === null) {
            return false;
        }

        return $this->inscricoesAprovadas()->count() >= $this->numero_vagas;
    }

    /** Linhas anteriores a coluna (ou lidas sem ela) contam como ATIVO. */
    public function situacaoEfetiva(): SituacaoCurso
    {
        return $this->situacao ?? SituacaoCurso::ATIVO;
    }

    /**
     * Ponto unico de "o aluno pode usar este curso agora" (sala, itens,
     * arquivos, progresso, entregas, prova e video, nos dois escopos).
     */
    public function alunoAcessaAgora(): bool
    {
        return $this->isEad() && $this->estaPublicado() && $this->situacaoEfetiva()->permiteAcessoDoAluno();
    }

    /**
     * Endereco da capa, ou null quando o curso nao tem imagem propria (so curso
     * EAD tem capa). O carimbo vem do proprio arquivo: muda a cada troca, mesmo
     * duas no mesmo segundo, e editar o curso nao invalida o cache da imagem.
     */
    public function capaUrl(string $rota = self::ROTA_CAPA_SDC): ?string
    {
        if ($this->capa_path === null || ! $this->isEad()) {
            return null;
        }

        return route($rota, ['treinamento' => $this->id, 'v' => substr(md5($this->capa_path), 0, 12)]);
    }

    /**
     * Ponto unico de quais cursos tem a avaliacao como etapa final obrigatoria:
     * sem ela a inscricao nao conclui (ConclusaoService::registrar) e, por
     * consequencia, nao recebe certificado. Vale para todos os tipos: no EAD a
     * avaliacao e a ultima etapa da trilha na sala de aula; nos eventos
     * (presencial/online/semi), que nao tem sala, ela aparece em "Minhas
     * inscricoes" (cidadao) e "Meus cursos" (servidor).
     */
    public function exigeAvaliacaoParaConcluir(): bool
    {
        return true;
    }

    public function estaPublicado(): bool
    {
        return $this->link_publico_slug !== null && $this->publicado_em !== null;
    }

    /**
     * Torna o treinamento visivel no catalogo do Portal do Cidadao. Gera o
     * slug publico se ainda nao existir (nao regenera se ja estiver publicado).
     */
    public function publicarNoPortal(): void
    {
        $this->update([
            'link_publico_slug' => $this->link_publico_slug ?? (Str::slug($this->titulo) . '-' . Str::lower(Str::random(6))),
            'publicado_em' => $this->publicado_em ?? now(),
        ]);
    }

    public function liberarPresenca(User $por): void
    {
        $this->update([
            'presenca_liberada' => true,
            'presenca_liberada_em' => now(),
            'presenca_liberada_por' => $por->id,
        ]);
    }

    public function bloquearPresenca(): void
    {
        $this->update(['presenca_liberada' => false]);
    }

    public function finalizar(): void
    {
        $this->update([
            'status' => StatusTreinamento::CONCLUIDO,
            'finalizado_em' => now(),
        ]);
    }

    // Accessors

    public function getVagasDisponiveisAttribute(): ?int
    {
        if ($this->numero_vagas === null) {
            return null;
        }

        return max(0, $this->numero_vagas - $this->totalDeInscricoesAprovadas());
    }

    public function getTotalInscricoesAttribute(): int
    {
        return $this->contagemPreCarregada('inscricoes_count') ?? $this->inscricoes()->count();
    }

    /** Aprovadas: usa o withCount('inscricoesAprovadas') quando veio na consulta; senao conta. */
    public function totalDeInscricoesAprovadas(): int
    {
        return $this->contagemPreCarregada('inscricoes_aprovadas_count') ?? $this->inscricoesAprovadas()->count();
    }

    private function contagemPreCarregada(string $atributo): ?int
    {
        return array_key_exists($atributo, $this->attributes) ? (int) $this->attributes[$atributo] : null;
    }

    // Scopes

    /**
     * Busca por texto sem diferenciar caixa (ILIKE do Postgres), escapando % e _ e
     * a barra do termo para valerem como letras comuns. Casa em QUALQUER coluna.
     *
     * @param  list<string>  $colunas
     */
    public function scopeBuscarTexto($query, string $termo, array $colunas)
    {
        $padrao = '%'.addcslashes($termo, '\\%_').'%';

        return $query->where(function ($q) use ($colunas, $padrao) {
            foreach ($colunas as $coluna) {
                $q->orWhere($coluna, 'ilike', $padrao);
            }
        });
    }

    public function scopePorStatus($query, StatusTreinamento $status)
    {
        return $query->where('status', $status->value);
    }

    public function scopeEmAndamento($query)
    {
        return $query->where('status', StatusTreinamento::EM_ANDAMENTO->value);
    }

    public function scopeConcluidos($query)
    {
        return $query->where('status', StatusTreinamento::CONCLUIDO->value);
    }

    public function scopeParaPublico($query, PublicoAlvo $publico)
    {
        return $query->where('publico_alvo', $publico->value);
    }

    /** Espelho em SQL de categoriaOferta(). */
    public function scopeDaCategoriaOferta($query, CategoriaOferta $oferta)
    {
        return ClassificacaoDeOferta::restringir($query, $oferta);
    }

    public function scopePublicado($query)
    {
        return $query->whereNotNull('link_publico_slug')->whereNotNull('publicado_em');
    }

    /**
     * Versao vigente do curso: nenhuma outra versao EAD publicada (e nao excluida) do
     * mesmo curso do catalogo tem numero maior. Sem curso do catalogo, a turma e a
     * unica versao e portanto vigente.
     */
    public function scopeVigente($query)
    {
        $tabela = $this->getTable();

        return $query->where(function ($q) use ($tabela) {
            // Versao e coisa de EAD: turma presencial do mesmo curso do catalogo nunca e substituida.
            $q->where("{$tabela}.tipo", '<>', TipoTreinamento::EAD->value)
                ->orWhereNull("{$tabela}.curso_id")
                ->orWhereNotExists(function ($sub) use ($tabela) {
                    $sub->selectRaw('1')
                        ->from("{$tabela} as posterior")
                        ->whereColumn('posterior.curso_id', "{$tabela}.curso_id")
                        ->whereColumn('posterior.versao', '>', "{$tabela}.versao")
                        ->where('posterior.tipo', TipoTreinamento::EAD->value)
                        ->whereNotNull('posterior.publicado_em')
                        ->whereNull('posterior.deleted_at');
                });
        });
    }

    public function eVigente(): bool
    {
        return self::query()->whereKey($this->getKey())->vigente()->exists();
    }

    /** Publicado e nao desativado: o que os catalogos (interno e portal) listam. */
    public function scopeNoCatalogo($query)
    {
        return $query->publicado()->vigente()->whereIn('situacao', SituacaoCurso::valoresNoCatalogo());
    }

    /** Cursos que ainda podem concluir alunos e emitir certificado. */
    public function scopeAtivo($query)
    {
        return $query->where('situacao', SituacaoCurso::ATIVO->value);
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'treinamento';
    }

    public function rotuloProtocolo(): string
    {
        $titulo = trim((string) $this->titulo);

        return $titulo === ''
            ? 'Treinamento #'.$this->getKey()
            : 'Treinamento "'.$titulo.'"';
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        $ids = $this->professores()->pluck('user_id')->map(fn ($id) => (int) $id);

        if ($this->created_by !== null) {
            $ids->prepend((int) $this->created_by);
        }

        return $ids->unique()->values()->all();
    }

    public function urlNotificacao(): ?string
    {
        return '/treinamentos/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->status instanceof StatusTreinamento ? $this->status->getLabel() : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return match ($this->status) {
            StatusTreinamento::CONCLUIDO => 'success',
            StatusTreinamento::CANCELADO => 'warning',
            default => 'info',
        };
    }
}
