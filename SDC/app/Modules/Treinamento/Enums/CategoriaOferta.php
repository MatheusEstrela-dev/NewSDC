<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

use App\Modules\Treinamento\Services\CapaDeCategoriaService;

/**
 * Categoria de oferta: como a capacitacao chega ao aluno. Organiza as vitrines
 * (area do gestor, area do aluno e portal do cidadao) em pastas.
 *
 * LIVRE              capacitacoes livres: EAD sem tutoria, o aluno faz direto.
 * TUTORIA            capacitacoes com tutoria: turmas com periodo e tutor
 *                    definidos; inclui todas as online e semipresenciais.
 * PRESENCIAL         capacitacao inteiramente presencial.
 * EVENTO             seminarios, workshops, lives e webinarios (categoria Evento).
 * INTEGRACAO_SABERES biblioteca de materiais (antiga area dos materiais), ainda
 *                    nao existe: aparece como "Em breve" e nao classifica turma.
 *
 * A regra que decide a categoria de uma turma fica em ClassificacaoDeOferta.
 */
enum CategoriaOferta: string
{
    case LIVRE = 'LIVRE';
    case TUTORIA = 'TUTORIA';
    case PRESENCIAL = 'PRESENCIAL';
    case EVENTO = 'EVENTO';
    case INTEGRACAO_SABERES = 'INTEGRACAO_SABERES';

    public function getLabel(): string
    {
        return match ($this) {
            self::LIVRE => 'Capacitações livres',
            self::TUTORIA => 'Capacitações com tutoria',
            self::PRESENCIAL => 'Presencial',
            self::EVENTO => 'Eventos',
            self::INTEGRACAO_SABERES => 'Integração de Saberes / Biblioteca',
        };
    }

    public function getDescricao(): string
    {
        return match ($this) {
            self::LIVRE => 'Disponíveis direto, sem tutoria: você faz no seu ritmo',
            self::TUTORIA => 'Turmas com período e tutor definidos, incluindo as online e semipresenciais',
            self::PRESENCIAL => 'Capacitação inteiramente presencial',
            self::EVENTO => 'Seminários, workshops, lives e webinários',
            self::INTEGRACAO_SABERES => 'Biblioteca de materiais e conteúdos de apoio',
        };
    }

    /** Chave do icone do tile (o front mapeia para o componente). */
    public function getIcone(): string
    {
        return match ($this) {
            self::LIVRE => 'computer-desktop',
            self::TUTORIA => 'academic-cap',
            self::PRESENCIAL => 'users',
            self::EVENTO => 'calendar',
            self::INTEGRACAO_SABERES => 'book-open',
        };
    }

    /** Chave da cor do tile (o front mapeia para o gradiente). */
    public function getCor(): string
    {
        return match ($this) {
            self::LIVRE => 'sky',
            self::TUTORIA => 'indigo',
            self::PRESENCIAL => 'emerald',
            self::EVENTO => 'amber',
            self::INTEGRACAO_SABERES => 'slate',
        };
    }

    /** "1 capacitação", "26 capacitações", "3 eventos". */
    public function rotuloDoTotal(int $total): string
    {
        [$singular, $plural] = $this === self::EVENTO ? ['evento', 'eventos'] : ['capacitação', 'capacitações'];

        return $total.' '.($total === 1 ? $singular : $plural);
    }

    /** O modulo ainda nao existe: tile sem contagem e sem clique. */
    public function emBreve(): bool
    {
        return $this === self::INTEGRACAO_SABERES;
    }

    /**
     * Categorias em que uma turma pode cair.
     *
     * @return list<self>
     */
    public static function classificaveis(): array
    {
        return array_values(array_filter(self::cases(), fn (self $c) => ! $c->emBreve()));
    }

    /** Categoria vinda da URL (?categoria=); invalida ou "em breve" vira null. */
    public static function daRequisicao(mixed $valor): ?self
    {
        $categoria = is_string($valor) ? self::tryFrom($valor) : null;

        return $categoria !== null && ! $categoria->emBreve() ? $categoria : null;
    }

    /**
     * Itens do mosaico de categorias (PastasDeCategoria.vue).
     *
     * @param  array<string, int>  $totais  total por valor da categoria
     * @return list<array<string, mixed>>
     */
    public static function mosaico(array $totais, ?self $ativa): array
    {
        // Foto de fundo de cada pasta (enviada pela tela ou estatica), resolvida de uma vez.
        $capas = app(CapaDeCategoriaService::class)->todas();

        return array_map(fn (self $c) => [
            'valor' => $c->value,
            'rotulo' => $c->getLabel(),
            'descricao' => $c->getDescricao(),
            'icone' => $c->getIcone(),
            'cor' => $c->getCor(),
            'total' => $c->emBreve() ? null : (int) ($totais[$c->value] ?? 0),
            'total_rotulo' => $c->emBreve() ? null : $c->rotuloDoTotal((int) ($totais[$c->value] ?? 0)),
            'ativa' => $c === $ativa,
            'em_breve' => $c->emBreve(),
            'imagem' => $capas[$c->value]['imagem'],
            'capa_propria' => $capas[$c->value]['propria'],
        ], self::cases());
    }
}
