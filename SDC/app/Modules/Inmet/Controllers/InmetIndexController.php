<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cemaden\Repositories\CemadenRepository;
use App\Modules\Inmet\Repositories\InmetRepository;
use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InmetIndexController extends Controller
{
    public function __construct(
        private readonly InmetRepository $inmet,
        private readonly CemadenRepository $cemaden,
        private readonly IngestorRegistry $registry,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Toda a agregacao ja esta materializada na camada Gold: aqui so se le e
        // se normaliza o formato das duas redes num contrato unico.
        //
        // O parametro uf saiu de proposito: o recorte e do pipeline, nao da
        // requisicao. E a bbox aqui nao filtra nada — serve para o mapa se
        // enquadrar em MG.
        return Inertia::render('Inmet/MapaInmet', [
            'estacoes' => $this->estacoesUnificadas(),
            'estatisticas' => [
                'inmet' => $this->inmet->estatisticas(),
                'cemaden' => $this->cemaden->estatisticas(),
            ],
            // Quando cada rede foi consultada pela ultima vez, independente de
            // ter trazido novidade. O 'ultima_atualizacao' das matviews diz a
            // idade do DADO; este campo diz se o coletor esta vivo. Sao
            // perguntas diferentes, e antes nenhuma das duas era respondida.
            'verificado_em' => [
                'inmet' => IngestaoBruta::verificadoEm($this->registry->chavesDoGrupo('inmet')),
                'cemaden' => IngestaoBruta::verificadoEm($this->registry->chavesDoGrupo('cemaden')),
            ],
            'bbox' => config('medalhao.inmet.bbox'),
        ]);
    }

    /**
     * Junta INMET e CEMADEN numa lista so.
     *
     * As duas redes medem a mesma grandeza e usam as mesmas faixas do
     * LHASA_RIO, entao separa-las em duas telas obrigava o operador a olhar em
     * dois lugares para responder "esta chovendo onde?". O que muda entre elas
     * e a cadencia (o INMET publica de hora em hora, o CEMADEN a cada ~10 min) e
     * a densidade (60 estacoes contra 830) — e isso vira a coluna 'rede', nao
     * uma pagina separada.
     *
     * Normaliza-se o nome do campo de chuva: 'precipitacao' no INMET,
     * 'acumulado_24h' no CEMADEN. Ambos sao acumulado de 24h, entao a tela usa
     * um nome so.
     *
     * @return list<array<string, mixed>>
     */
    private function estacoesUnificadas(): array
    {
        // Só os campos que a tela realmente usa. As matviews carregam umidade,
        // pressao e vento, que nenhuma coluna nem popup le: com 890 estacoes,
        // manda-los seria payload morto.
        $estacoes = $this->inmet->mapa()->map(static fn (object $e): array => [
            // Prefixo obrigatorio: os ids vem de bigserial independentes em cada
            // matview e colidem entre as redes, o que quebraria o :key do v-for
            // e a identidade dos marcadores no mapa.
            'id' => "inmet-{$e->id}",
            'rede' => 'INMET',
            'codigo_estacao' => $e->codigo_estacao,
            'nome_estacao' => $e->nome_estacao,
            'municipio' => $e->municipio,
            'tipo' => 'Automática',
            'latitude' => $e->latitude,
            'longitude' => $e->longitude,
            'medido_em' => $e->medido_em,
            'precipitacao' => $e->precipitacao,
            'classe_precipitacao' => $e->classe_precipitacao,
            'temperatura' => $e->temperatura,
        ])->all();

        $estacoes = array_merge($estacoes, $this->cemaden->mapa()->map(static fn (object $e): array => [
            'id' => "cemaden-{$e->id}",
            'rede' => 'CEMADEN',
            'codigo_estacao' => $e->codigo_estacao,
            'nome_estacao' => $e->nome_estacao,
            'municipio' => $e->municipio,
            'tipo' => $e->tipo,
            'latitude' => $e->latitude,
            'longitude' => $e->longitude,
            'medido_em' => $e->medido_em,
            'precipitacao' => $e->acumulado_24h,
            'classe_precipitacao' => $e->classe_precipitacao,
            // O feed do CEMADEN nao traz temperatura: e rede pluviometrica.
            'temperatura' => null,
        ])->all());

        // Ordena o conjunto ja unificado, e nao cada rede: senao as 60 do INMET
        // ficariam todas antes das 830 do CEMADEN e a primeira pagina da tabela
        // nunca mostraria a maior chuva do estado.
        usort($estacoes, static function (array $a, array $b): int {
            return ($b['precipitacao'] ?? -1) <=> ($a['precipitacao'] ?? -1);
        });

        return $estacoes;
    }
}
