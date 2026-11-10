<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Services;

use App\Models\User;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaDTO;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaVersaoDTO;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Models\NotaTecnicaCategoria;
use App\Modules\NotasTecnicas\Support\GuiasDeUsoCatalogo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Carga dos guias de uso (GuiasDeUsoCatalogo) em Notas Tecnicas: cada guia vai
 * para a pasta do seu modulo, reaproveitando as pastas que ja existem e criando
 * as que faltam. A nota nasce publicada pelo mesmo fluxo da tela (codigo NT,
 * storage, extracao de texto), sem aviso no sino.
 *
 * Idempotente: guia e achado pelo titulo (inclusive excluido). Rodar de novo
 * so cria o que falta e devolve para a pasta do catalogo o guia que estiver em
 * outra.
 */
final class NotaTecnicaGuiasImportacaoService
{
    public const VERSAO_INICIAL = '1.0';

    public function __construct(
        private readonly NotaTecnicaService $notas,
        private readonly NotaTecnicaCategoriaService $categorias,
    ) {}

    /**
     * @param  string  $diretorio  pasta Doc/ com os PDFs
     * @return list<array{pasta: string, titulo: string, codigo: ?string, situacao: string}>
     */
    public function importar(string $diretorio, User $autor, bool $simular = false): array
    {
        $diretorio = rtrim($diretorio, '/\\') . DIRECTORY_SEPARATOR;
        $catalogo = GuiasDeUsoCatalogo::pastas();

        // Falha antes de gravar qualquer coisa se faltar um PDF.
        $this->validarArquivos($diretorio, $catalogo);

        $pastas = [];
        foreach ($catalogo as $definicao) {
            $pastas[$definicao['slug']] = $simular
                ? $this->acharPasta($definicao['slug'])
                : $this->garantirPasta($definicao);
        }

        // Na ordem do catalogo: o codigo NT acompanha a numeracao dos guias.
        $relatorio = [];
        foreach ($catalogo as $definicao) {
            foreach ($definicao['guias'] as $guia) {
                $relatorio[] = $this->importarGuia($diretorio, $guia, $definicao['nome'], $pastas[$definicao['slug']], $autor, $simular);
            }
        }

        return $relatorio;
    }

    /**
     * @param  array{arquivo: string, titulo: string, resumo: string, rota: ?string}  $guia
     * @return array{pasta: string, titulo: string, codigo: ?string, situacao: string}
     */
    private function importarGuia(string $diretorio, array $guia, string $nomePasta, ?NotaTecnicaCategoria $pasta, User $autor, bool $simular): array
    {
        $existente = NotaTecnica::withTrashed()->where('titulo', $guia['titulo'])->first();

        if ($existente !== null) {
            return $this->linha($nomePasta, $guia, $existente->codigo, $this->alinharPasta($existente, $pasta, $simular));
        }

        if ($simular) {
            return $this->linha($nomePasta, $guia, null, 'seria criada');
        }

        $nota = DB::transaction(function () use ($diretorio, $guia, $pasta, $autor): NotaTecnica {
            $nota = $this->notas->criar($this->dadosNota($guia, $pasta), $this->dadosVersao($diretorio, $guia), $autor);

            return $this->notas->publicar($nota, $autor, avisar: false);
        });

        return $this->linha($nomePasta, $guia, $nota->codigo, 'criada');
    }

    /** Guia ja importado: devolve para a pasta do catalogo se estiver em outra. */
    private function alinharPasta(NotaTecnica $nota, ?NotaTecnicaCategoria $pasta, bool $simular): string
    {
        if ($nota->trashed()) {
            return 'ja existe (excluida)';
        }

        if ($pasta !== null && $nota->categoria_id === $pasta->id) {
            return 'ja existe';
        }

        if ($simular) {
            return 'seria movida';
        }

        $nota->update(['categoria_id' => $pasta->id]);

        return 'movida';
    }

    private function acharPasta(string $slug): ?NotaTecnicaCategoria
    {
        return NotaTecnicaCategoria::query()->where('slug', $slug)->first();
    }

    /**
     * Acha a pasta pelo slug ou cria na raiz pelo servico de pastas (que gera o
     * mesmo slug e poe no fim da arvore), com a ordem do catalogo se houver.
     *
     * @param  array{slug: string, nome: string, ordem: ?int}  $definicao
     */
    private function garantirPasta(array $definicao): NotaTecnicaCategoria
    {
        $pasta = $this->acharPasta($definicao['slug']);

        if ($pasta !== null) {
            return $pasta;
        }

        $pasta = $this->categorias->criar($definicao['nome'], null);

        if ($definicao['ordem'] !== null) {
            $pasta->update(['ordem' => $definicao['ordem']]);
        }

        return $pasta;
    }

    /**
     * @param  array{arquivo: string, titulo: string, resumo: string, rota: ?string}  $guia
     */
    private function dadosNota(array $guia, NotaTecnicaCategoria $pasta): NotaTecnicaDTO
    {
        $telas = config('notas_tecnicas.telas_relacionadas', []);
        $rota = $guia['rota'];

        return new NotaTecnicaDTO(
            titulo: $guia['titulo'],
            categoriaId: $pasta->id,
            resumo: $guia['resumo'],
            vigenciaInicio: null,
            rotasRelacionadas: $rota !== null && isset($telas[$rota])
                ? [['nome' => $rota, 'rotulo' => (string) $telas[$rota]]]
                : [],
        );
    }

    /**
     * @param  array{arquivo: string, titulo: string, resumo: string, rota: ?string}  $guia
     */
    private function dadosVersao(string $diretorio, array $guia): NotaTecnicaVersaoDTO
    {
        $caminho = $diretorio . $guia['arquivo'];

        return new NotaTecnicaVersaoDTO(
            arquivo: new UploadedFile($caminho, basename($caminho), 'application/pdf', null, true),
            numero: self::VERSAO_INICIAL,
            changelog: GuiasDeUsoCatalogo::CHANGELOG,
        );
    }

    /**
     * @param  list<array{guias: list<array{arquivo: string}>}>  $catalogo
     */
    private function validarArquivos(string $diretorio, array $catalogo): void
    {
        $problemas = [];

        foreach ($catalogo as $pasta) {
            foreach ($pasta['guias'] as $guia) {
                $caminho = $diretorio . $guia['arquivo'];

                if (! is_file($caminho) || ! is_readable($caminho)) {
                    $problemas[] = "nao encontrado: {$guia['arquivo']}";
                } elseif (file_get_contents($caminho, false, null, 0, 5) !== '%PDF-') {
                    $problemas[] = "nao e PDF: {$guia['arquivo']}";
                }
            }
        }

        if ($problemas !== []) {
            throw new InvalidArgumentException("Arquivos dos guias em {$diretorio}:\n- " . implode("\n- ", $problemas));
        }
    }

    /**
     * @param  array{titulo: string}  $guia
     * @return array{pasta: string, titulo: string, codigo: ?string, situacao: string}
     */
    private function linha(string $pasta, array $guia, ?string $codigo, string $situacao): array
    {
        return ['pasta' => $pasta, 'titulo' => $guia['titulo'], 'codigo' => $codigo, 'situacao' => $situacao];
    }
}
