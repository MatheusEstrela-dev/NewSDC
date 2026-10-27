<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Unique;

/**
 * Caminhao novo e primeira vistoria, no mesmo POST.
 *
 * As regras sao as dos dois cadastros. O que muda e a borda: capacidade e
 * digitada uma vez, observacoes ficam na vistoria, e `placa_id` nao vem do
 * cliente -- o caminhao ainda nao existe. Quem persiste continua sendo
 * CaminhaoDTO e VistoriaDTO, cada um com o recorte que lhe cabe.
 */
class StoreVistoriaComCaminhaoRequest extends AbstractCaminhaoRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('tdap.caminhoes.create') ?? false)
            && ($this->user()?->can('tdap.vistorias.create') ?? false);
    }

    protected function placaUniqueRule(): Unique
    {
        return $this->placaUnicaNoPrestador();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $veiculo = parent::rules();

        // Capacidade vai no nome da vistoria. Observacoes da ficha nao sao do
        // caminhao, e `ativo` fica de fora para o default da coluna (true)
        // assumir -- o DTO omite a chave quando ela nao veio.
        unset($veiculo['capacidade_m3'], $veiculo['observacoes'], $veiculo['ativo']);

        $ficha = $this->regrasDaFicha();
        unset($ficha['placa_id']);

        // O minimo do caminhao (0,5) e mais restrito que o da vistoria (0,01).
        // Validar pelo menor deixaria o insert do veiculo falhar depois que a
        // ficha ja tinha passado.
        $ficha['capacidade'] = ['required', 'numeric', 'min:0.5', 'max:999.99'];

        return array_merge($veiculo, $ficha, [
            'fotos'     => ['sometimes', 'array', 'max:'.StoreVistoriaFotoRequest::MAX_POR_LOTE],
            'fotos.*'   => StoreVistoriaFotoRequest::REGRAS_DO_ARQUIVO,
            'descricao' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Recorte que entra em CaminhaoDTO::fromRequest.
     *
     * Sem observacoes e sem ativo: a observacao e da vistoria, e o veiculo
     * novo nasce ativo pelo default da coluna.
     *
     * @return array<string, mixed>
     */
    public function dadosDoCaminhao(): array
    {
        $validados = $this->validated();

        return [
            'prestador_id'  => $validados['prestador_id'],
            'placa'         => $validados['placa'],
            'marca'         => $validados['marca'] ?? null,
            'modelo'        => $validados['modelo'] ?? null,
            'cor'           => $validados['cor'] ?? null,
            'ano'           => $validados['ano'] ?? null,
            'capacidade_m3' => $validados['capacidade'],
        ];
    }

    /**
     * Recorte que entra em VistoriaDTO::fromRequest.
     *
     * `placa_id` e o id recem-gerado, nao o que o cliente mandou. Marca, placa
     * e prestador sao colunas do caminhao -- deixa-los aqui faria o insert da
     * vistoria receber coluna que a tabela nao tem.
     *
     * @return array<string, mixed>
     */
    public function dadosDaVistoria(int $placaId): array
    {
        $dados = Arr::except($this->validated(), [
            'fotos',
            'descricao',
            'prestador_id',
            'placa',
            'marca',
        ]);

        $dados['placa_id'] = $placaId;

        return $dados;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['fotos.*' => 'foto'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'data.before_or_equal' => 'A data da vistoria não pode ser futura.',
            'capacidade.min'       => 'A capacidade mínima do tanque é 0,5 m³.',
        ], StoreVistoriaFotoRequest::MENSAGENS_DO_ARQUIVO);
    }

    /**
     * As regras da ficha, sem duplicar a lista de itens do checklist.
     *
     * AbstractVistoriaRequest nao e instanciavel pelo container aqui: so
     * precisamos do array de regras, que nao depende do request resolvido.
     *
     * @return array<string, mixed>
     */
    private function regrasDaFicha(): array
    {
        return (new class extends AbstractVistoriaRequest {})->rules();
    }
}
