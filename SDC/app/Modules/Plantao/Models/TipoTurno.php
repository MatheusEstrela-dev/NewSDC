<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Horario de turno praticado. Fonte unica que substitui o enum PeriodoPlantao.
 *
 * `codigo` e o que viaja para plantoes.periodo e para os filtros do frontend --
 * nunca o `id`. Assim os turnos ja gravados continuam legiveis e a tabela pode
 * ser recriada sem orfanar historico.
 */
class TipoTurno extends Model
{
    protected $table = 'plantao_tipos_turno';

    protected $fillable = [
        'codigo',
        'nome',
        'hora_inicio',
        'hora_fim',
        'vira_dia',
        'escalavel',
        'cor',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'vira_dia' => 'boolean',
        'escalavel' => 'boolean',
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(EscalaItem::class, 'tipo_turno_id');
    }

    /**
     * Turnos que o montador pode escalar, na ordem em que devem aparecer.
     */
    public function scopeEscalaveis(Builder $query): Builder
    {
        return $query->where('ativo', true)
            ->where('escalavel', true)
            ->orderBy('ordem');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true)->orderBy('ordem');
    }

    /**
     * "06:00hs as 16:00hs". Substitui PeriodoPlantao::label(), consumido pelo
     * cabecalho do relatorio de passagem.
     */
    public function label(): string
    {
        if ($this->hora_inicio === null || $this->hora_fim === null) {
            return $this->nome;
        }

        return $this->horaFormatada('hora_inicio', 'H:i').'hs as '
            .$this->horaFormatada('hora_fim', 'H:i').'hs';
    }

    /**
     * "06h as 16h". Forma curta do cabecalho do relatorio.
     */
    public function labelCurto(): string
    {
        if ($this->hora_inicio === null || $this->hora_fim === null) {
            return $this->nome;
        }

        return $this->horaFormatada('hora_inicio', 'H').'h às '
            .$this->horaFormatada('hora_fim', 'H').'h';
    }

    /**
     * Minuto do dia em que o turno comeca. Base da deteccao de sobreposicao.
     */
    public function inicioEmMinutos(): ?int
    {
        return $this->minutosDe('hora_inicio');
    }

    /**
     * Minuto do fim, contado a partir do inicio do dia da DATA do turno. Turno
     * que vira o dia devolve valor acima de 1440 (20h-08h => 480 + 1440 = 1920),
     * que e o que permite comparar dois turnos na mesma reta.
     */
    public function fimEmMinutos(): ?int
    {
        $fim = $this->minutosDe('hora_fim');

        if ($fim === null) {
            return null;
        }

        return $this->vira_dia ? $fim + 1440 : $fim;
    }

    /**
     * Duracao real, ja considerando a virada de dia.
     */
    public function duracaoEmMinutos(): ?int
    {
        $inicio = $this->inicioEmMinutos();
        $fim = $this->fimEmMinutos();

        if ($inicio === null || $fim === null) {
            return null;
        }

        return $fim - $inicio;
    }

    /**
     * A coluna `time` do Postgres chega como string ("06:00:00") e nao como
     * Carbon, porque nao ha cast de time no Eloquent. Normalizado aqui em vez de
     * espalhar substr pelos consumidores.
     */
    private function horaFormatada(string $campo, string $formato): string
    {
        $valor = (string) $this->{$campo};

        return \Illuminate\Support\Carbon::createFromFormat('H:i:s', $this->normalizarHora($valor))
            ->format($formato);
    }

    private function minutosDe(string $campo): ?int
    {
        if ($this->{$campo} === null) {
            return null;
        }

        [$h, $m] = explode(':', $this->normalizarHora((string) $this->{$campo}));

        return ((int) $h * 60) + (int) $m;
    }

    /**
     * "06:00" (5 chars) e "06:00:00" (8) chegam conforme o driver; normaliza
     * para H:i:s antes de qualquer parse.
     */
    private function normalizarHora(string $valor): string
    {
        return strlen($valor) === 5 ? $valor.':00' : substr($valor, 0, 8);
    }
}
