<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Services;

use App\Modules\NotasTecnicas\Models\NotaTecnicaCategoria;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Organizacao das pastas da arvore do workspace. */
final class NotaTecnicaCategoriaService
{
    public function criar(string $nome, ?int $paiId): NotaTecnicaCategoria
    {
        $ordem = (int) NotaTecnicaCategoria::query()->where('parent_id', $paiId)->max('ordem');

        return NotaTecnicaCategoria::create([
            'nome' => trim($nome),
            'slug' => $this->slugUnico($nome),
            'parent_id' => $paiId,
            'ordem' => $ordem + 10,
        ]);
    }

    /** O slug fica: e chave estavel, nao rotulo. */
    public function renomear(NotaTecnicaCategoria $categoria, string $nome): NotaTecnicaCategoria
    {
        $categoria->update(['nome' => trim($nome)]);

        return $categoria;
    }

    /**
     * So pasta vazia: sem subpastas e sem nota, nem excluida -- a nota excluida
     * ainda aponta para a pasta, e a FK restringe.
     */
    public function excluir(NotaTecnicaCategoria $categoria): void
    {
        if ($categoria->filhas()->exists()) {
            throw ValidationException::withMessages(['categoria' => 'Mova ou exclua as subpastas antes.']);
        }

        if ($categoria->notas()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['categoria' => 'A pasta tem notas. Mova as notas para outra pasta antes.']);
        }

        $categoria->delete();
    }

    /**
     * Opcoes do select de pasta: a arvore achatada, com o caminho no rotulo
     * ("RAT / Vistoria").
     *
     * @return list<array{value: int, label: string}>
     */
    public function opcoes(): array
    {
        $categorias = NotaTecnicaCategoria::query()->orderBy('ordem')->orderBy('nome')->get(['id', 'parent_id', 'nome']);
        $porPai = $categorias->groupBy(fn (NotaTecnicaCategoria $c): string => (string) $c->parent_id);
        $opcoes = [];

        $visitar = function (string $paiId, string $caminho) use (&$visitar, $porPai, &$opcoes): void {
            foreach ($porPai->get($paiId, collect()) as $categoria) {
                $rotulo = $caminho === '' ? $categoria->nome : "{$caminho} / {$categoria->nome}";
                $opcoes[] = ['value' => $categoria->id, 'label' => $rotulo];
                $visitar((string) $categoria->id, $rotulo);
            }
        };
        $visitar('', '');

        return $opcoes;
    }

    private function slugUnico(string $nome): string
    {
        $base = Str::slug($nome) ?: 'pasta';
        $slug = $base;

        for ($i = 2; NotaTecnicaCategoria::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
