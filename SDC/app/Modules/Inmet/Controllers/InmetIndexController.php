<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cemaden\Repositories\CemadenRepository;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Inmet\Repositories\InmetRepository;
use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use App\Modules\Shared\Geo\CaixaEnvolvente;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InmetIndexController extends Controller
{
    public function __construct(
        private readonly InmetRepository $inmet,
        private readonly CemadenRepository $cemaden,
        private readonly IngestorRegistry $registry,
        private readonly GeoCamadaRepository $geoespacial,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Camada de risco escolhida no seletor. Vem pela query e nao por sessao
        // porque o link com a camada aberta precisa ser compartilhavel entre
        // operadores de plantao.
        $camadaGeoId = $request->integer('camada_geo') ?: null;

        // Toda a agregacao ja esta materializada na camada Gold: aqui so se le e
        // se normaliza o formato das duas redes num contrato unico.
        //
        // O parametro uf saiu de proposito: o recorte e do pipeline, nao da
        // requisicao. E a bbox aqui nao filtra nada — serve para o mapa se
        // enquadrar em MG.
        return Inertia::render('Inmet/MapaInmet', [
            'estacoes' => $this->estacoesUnificadas($camadaGeoId),
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
            'bbox' => CaixaEnvolvente::deConfig(config('medalhao.inmet.bbox'))->paraArray(),
            // Lista enxuta: so o que o seletor precisa. As feicoes da camada
            // escolhida vem por partial reload, e nao todas de uma vez -- com
            // varias camadas carregadas, mandar toda geometria seria payload
            // morto para o operador que olha uma.
            'camadasGeo' => $this->geoespacial->camadas()->all(),
            'feicoesGeo' => $camadaGeoId !== null ? $this->geoespacial->mapa($camadaGeoId)->all() : [],
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
    private function estacoesUnificadas(?int $camadaGeoId = null): array
    {
        // Só os campos que a tela realmente usa. As matviews carregam umidade,
        // pressao e vento, que nenhuma coluna nem popup le: com 890 estacoes,
        // manda-los seria payload morto.
        $estacoes = $this->inmet->mapa($camadaGeoId)->map(static fn (object $e): array => [
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
            // Cota vinda do inventario do INMET: 61 de 61 preenchidas.
            'altitude' => $e->altitude,
        ])->all();

        $estacoes = array_merge($estacoes, $this->cemaden->mapa($camadaGeoId)->map(static fn (object $e): array => [
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
            // Nem cota. As 830 ficam sem altimetria ate o MDE entrar por
            // raster -- ai a cota vem de ST_Value(rast, geom), igual para as
            // duas redes, e deixa de depender do que a fonte publica.
            'altitude' => null,
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
