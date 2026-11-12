<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaAlternativa;
use App\Modules\Treinamento\Models\ProvaQuestao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\Ordenacao;
use Illuminate\Validation\ValidationException;

/**
 * Montagem da prova pelo professor: configuracao, questoes e alternativas.
 *
 * Exclusao sempre logica (spec 8.7): tentativas ja enviadas continuam
 * apontando para o que existia. Mudar gabarito ou nota minima nao reavalia
 * nada: a correcao e gravada no envio.
 */
class ProvaConteudoService
{
    /**
     * Linha em `provas` de um item PROVA recem-criado.
     *
     * @param  array<string, mixed>  $dados  nota_minima?, max_tentativas?
     */
    public function criarParaItem(ModuloItem $item, array $dados = []): Prova
    {
        return Prova::query()->create(['modulo_item_id' => $item->id] + $this->configuracao($dados));
    }

    /**
     * @param  array<string, mixed>  $dados  nota_minima?, max_tentativas?
     */
    public function configurar(Prova $prova, array $dados): Prova
    {
        $prova->update($this->configuracao($dados));

        return $prova;
    }

    /**
     * 404 se a prova nao e de um item ativo do curso da URL.
     */
    public function garantirDoCurso(Treinamento $treinamento, Prova $prova): void
    {
        $doCurso = ModuloItem::query()
            ->whereKey($prova->modulo_item_id)
            ->whereIn('modulo_id', Modulo::query()->porTreinamento($treinamento->id)->select('id'))
            ->exists();

        abort_unless($doCurso, 404);
    }

    /**
     * 404 se a questao nao e da prova da URL.
     */
    public function garantirQuestaoDaProva(Prova $prova, ProvaQuestao $questao): void
    {
        abort_unless((int) $questao->prova_id === (int) $prova->id, 404);
    }

    /**
     * @param  array{enunciado: string, alternativas: list<array{texto: string, correta?: bool}>}  $dados
     */
    public function criarQuestao(Prova $prova, array $dados): ProvaQuestao
    {
        $this->exigirUmaCorreta($dados['alternativas']);

        return ConexaoTreinamento::transacao(function () use ($prova, $dados) {
            $ultima = ProvaQuestao::query()->where('prova_id', $prova->id)->max('ordem');

            $questao = ProvaQuestao::query()->create([
                'prova_id' => $prova->id,
                'enunciado' => $dados['enunciado'],
                'ordem' => ((int) $ultima) + 1,
            ]);

            foreach (array_values($dados['alternativas']) as $posicao => $alternativa) {
                $this->gravarAlternativa($questao, $alternativa, $posicao + 1);
            }

            return $questao;
        });
    }

    /**
     * Alternativa com `id` e atualizada; sem `id` e criada; a ativa que nao
     * veio na lista e excluida logicamente. O gabarito troca em duas escritas
     * (desmarca todas, marca a nova) por causa do indice unico parcial.
     *
     * @param  array{enunciado: string, alternativas: list<array{id?: int, texto: string, correta?: bool}>}  $dados
     */
    public function atualizarQuestao(ProvaQuestao $questao, array $dados): ProvaQuestao
    {
        $this->exigirUmaCorreta($dados['alternativas']);

        return ConexaoTreinamento::transacao(function () use ($questao, $dados) {
            ProvaQuestao::query()->whereKey($questao->id)->lockForUpdate()->first();

            $ativas = ProvaAlternativa::query()->where('prova_questao_id', $questao->id)->pluck('id')->all();
            $mantidas = array_filter(array_column($dados['alternativas'], 'id'));

            if (array_diff($mantidas, $ativas) !== []) {
                throw ValidationException::withMessages(['alternativas' => 'Alternativa nao pertence a esta questao.']);
            }

            ProvaAlternativa::query()->whereKey(array_diff($ativas, $mantidas))->delete();
            ProvaAlternativa::query()->where('prova_questao_id', $questao->id)->update(['correta' => false]);

            foreach (array_values($dados['alternativas']) as $posicao => $alternativa) {
                $this->gravarAlternativa($questao, $alternativa, $posicao + 1);
            }

            $questao->update(['enunciado' => $dados['enunciado']]);

            return $questao;
        });
    }

    public function excluirQuestao(ProvaQuestao $questao): void
    {
        $questao->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function reordenarQuestoes(Prova $prova, array $ids): void
    {
        $ativas = ProvaQuestao::query()->where('prova_id', $prova->id)->pluck('id')->all();

        try {
            Ordenacao::aplicar(ProvaQuestao::class, $ativas, $ids);
        } catch (\DomainException $e) {
            throw ValidationException::withMessages(['ids' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function configuracao(array $dados): array
    {
        return array_intersect_key($dados, array_flip(['nota_minima', 'max_tentativas']));
    }

    /**
     * @param  array{id?: int, texto: string, correta?: bool}  $alternativa
     */
    private function gravarAlternativa(ProvaQuestao $questao, array $alternativa, int $ordem): void
    {
        $valores = [
            'texto' => $alternativa['texto'],
            'correta' => (bool) ($alternativa['correta'] ?? false),
            'ordem' => $ordem,
        ];

        if (! empty($alternativa['id'])) {
            ProvaAlternativa::query()->whereKey($alternativa['id'])->update($valores);

            return;
        }

        ProvaAlternativa::query()->create($valores + ['prova_questao_id' => $questao->id]);
    }

    /**
     * @param  list<array<string, mixed>>  $alternativas
     */
    private function exigirUmaCorreta(array $alternativas): void
    {
        $corretas = count(array_filter($alternativas, fn (array $a) => ! empty($a['correta'])));

        if ($corretas !== 1) {
            throw ValidationException::withMessages([
                'alternativas' => 'Marque exatamente uma alternativa correta.',
            ]);
        }
    }
}
