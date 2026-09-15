<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Services;

use App\Modules\Geoespacial\DTOs\MetadadoCamadaDTO;
use App\Modules\Geoespacial\Enums\StatusCamada;
use App\Modules\Geoespacial\Jobs\AtualizarGoldGeoJob;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * O que acontece com uma camada DEPOIS de ela existir: editar metadado,
 * arquivar, reativar e reprocessar a geometria.
 *
 * Separado de RevisaoDeCamadas de proposito. Revisao decide se a camada ENTRA
 * no mapa (pendente -> aprovada/recusada), e e ato de moderacao. Aqui e o
 * inverso: a camada ja entrou, e o que muda e a vida dela dali para frente.
 *
 * A maquina de estado esta em Enums\StatusCamada, e nao aqui: quem responde
 * "pode ir de A para B" e o estado, e espalhar isso por comparacao de string
 * pelos Services faria os dois divergirem.
 *
 * Consequencia aceita: hash_arquivo e UNIQUE, entao o mesmo KML nao pode ser
 * reimportado enquanto a camada arquivada existir. Nao e limitacao, e o
 * caminho certo -- reativar a camada arquivada e mais barato e mais rastreavel
 * que reimportar o mesmo arquivo, e o upload avisa isso explicitamente.
 */
final class CicloDeVidaDaCamada
{
    public function __construct(
        private readonly GeoCamadaRepository $repository,
        private readonly KmlExtrator $extrator,
    ) {
    }

    /**
     * Edita METADADO. A geometria nao entra aqui, e nao por falta de tempo: ela
     * e a identidade da camada (hash_arquivo UNIQUE, calculado sobre o KML) e o
     * Bronze guarda o arquivo que a originou. Editar geometria romperia a
     * correspondencia entre o que esta no mapa e o que o municipio enviou --
     * que e a unica coisa auditavel aqui. Corrigir area e enviar outro KML.
     */
    public function editar(int $camadaId, MetadadoCamadaDTO $dto): void
    {
        $camada = $this->existeOuFalha($camadaId);
        $status = StatusCamada::deBanco($camada->status);

        if ($status === StatusCamada::ARQUIVADA) {
            throw new RuntimeException(
                "A camada \"{$camada->nome}\" esta arquivada. Reative antes de editar."
            );
        }

        DB::table('silver.geo_camadas')->where('id', $camadaId)->update([
            'nome' => $dto->nome,
            'dominio' => $dto->dominio,
            'nivel' => $dto->nivel,
            'emitido_em' => $dto->emitidoEm,
            'valido_ate' => $dto->validoAte,
            'updated_at' => now(),
        ]);

        /*
         * O refresh do Gold NAO e opcional aqui.
         *
         * gold.geo_feicao_mapa COPIA c.nome, c.dominio, c.nivel e c.emitido_em
         * para dentro da matview. Sem refresh, o Silver fica com o nome novo e
         * o mapa segue mostrando o antigo por tempo indefinido -- e a tela de
         * lista (que le do Silver) discordaria da tabela e do mapa (que leem do
         * Gold) na mesma pagina.
         *
         * So para camada aprovada porque as outras nao estao na matview: refresh
         * ali seria I/O para nada.
         */
        if ($status->noMapa()) {
            AtualizarGoldGeoJob::dispatch();
        }
    }

    /**
     * Retira do mapa sem apagar. So camada APROVADA se arquiva.
     *
     * Pendente nao se arquiva porque ela nem chegou ao mapa, e ja existe a acao
     * certa para ela: recusar, que exige motivo e avisa o remetente. Recusada
     * tambem nao, porque ja esta fora. Arquivar as duas criaria dois caminhos
     * para o mesmo resultado, com auditoria diferente.
     *
     * @param int|null $usuarioId null significa arquivamento AUTOMATICO por
     *                            validade vencida -- ver arquivarVencidas().
     */
    public function arquivar(int $camadaId, ?int $usuarioId, ?string $motivo = null): void
    {
        $camada = $this->existeOuFalha($camadaId);

        // O predicado de status vai no UPDATE, e nao so na leitura acima:
        // ler-depois-escrever sem trava deixa duas acoes concorrentes passarem
        // as duas. E o banco que decide quem chegou primeiro.
        $aplicou = DB::table('silver.geo_camadas')
            ->where('id', $camadaId)
            ->where('status', StatusCamada::APROVADA->value)
            ->update([
                'status' => StatusCamada::ARQUIVADA->value,
                'arquivado_por' => $usuarioId,
                'arquivado_em' => now(),
                'motivo_arquivamento' => $motivo,
                'updated_at' => now(),
            ]);

        if ($aplicou === 0) {
            throw new RuntimeException(
                sprintf(
                    'So camada aprovada pode ser arquivada, e "%s" esta como "%s".',
                    $camada->nome,
                    StatusCamada::deBanco($camada->status)->label()
                )
            );
        }

        // Sem isto a camada sai do Silver e CONTINUA no mapa: o filtro
        // `status = 'aprovada'` do Gold so e reavaliado no refresh.
        AtualizarGoldGeoJob::dispatch();
    }

    /** Devolve ao mapa. O caminho de volta e sempre para 'aprovada'. */
    public function reativar(int $camadaId, int $usuarioId): void
    {
        $camada = $this->existeOuFalha($camadaId);

        /*
         * Reativar camada VENCIDA e recusado, e nao permitido em silencio.
         *
         * A varredura das 05:00 arquivaria de novo na manha seguinte, e a
         * pessoa veria a camada voltar ao mapa e sumir sozinha sem entender por
         * que -- e no meio disso o plantao teria visto uma area de risco fora
         * do prazo apresentada como vigente. A saida e estender a validade
         * antes, e a mensagem diz isso.
         */
        if ($camada->valido_ate !== null && $camada->valido_ate < now()->toDateString()) {
            throw new RuntimeException(sprintf(
                'A camada "%s" venceu em %s. Atualize a validade antes de reativar,'
                . ' senao ela sera arquivada de novo na proxima varredura.',
                $camada->nome,
                date('d/m/Y', strtotime((string) $camada->valido_ate))
            ));
        }

        $aplicou = DB::table('silver.geo_camadas')
            ->where('id', $camadaId)
            ->where('status', StatusCamada::ARQUIVADA->value)
            ->update([
                'status' => StatusCamada::APROVADA->value,
                // A autoria da reativacao entra em revisado_por/revisado_em, e
                // nao em campo novo: quem devolve area de risco ao mapa de
                // plantao esta tomando a mesma decisao de quem aprovou, e a
                // auditoria deve mostrar isso no mesmo lugar.
                'revisado_por' => $usuarioId,
                'revisado_em' => now(),
                'arquivado_por' => null,
                'arquivado_em' => null,
                'motivo_arquivamento' => null,
                'updated_at' => now(),
            ]);

        if ($aplicou === 0) {
            throw new RuntimeException(
                "A camada \"{$camada->nome}\" nao esta arquivada."
            );
        }

        AtualizarGoldGeoJob::dispatch();
    }

    /**
     * Reextrai a geometria a partir do arquivo guardado no Bronze.
     *
     * Serve para quando o extrator melhora -- ST_Force2D e ST_MakeValid ja
     * entraram depois de camadas terem sido importadas -- e a geometria no
     * Silver ficou pior do que o arquivo permite. O cabecalho, a procedencia e
     * a moderacao ficam intactos: o que se refaz e so o desenho.
     *
     * @return int quantidade de feicoes regravadas
     */
    public function reprocessar(int $camadaId): int
    {
        $camada = $this->existeOuFalha($camadaId);

        if ($camada->ingestao_id === null) {
            throw new RuntimeException(
                "A camada \"{$camada->nome}\" nao tem vinculo com o Bronze e nao pode ser reprocessada."
            );
        }

        $envelope = DB::table('bronze.ingestao_bruta')
            ->where('id', $camada->ingestao_id)
            ->value('conteudo_bruto');

        if ($envelope === null) {
            /*
             * O Bronze tem retencao de 30 dias: medalhao:rollup arquiva em
             * Parquet e poda o Postgres. Passado esse prazo o envelope nao esta
             * mais aqui, e reprocessar exigiria ler o Parquet -- que nao e
             * capacidade deste modulo.
             *
             * Falhar com esta frase e melhor que responder "reprocessado" sem
             * ter reprocessado nada.
             */
            throw new RuntimeException(
                "O registro Bronze da camada \"{$camada->nome}\" nao esta mais no banco"
                . ' (retencao de 30 dias). Para refazer a geometria, envie o arquivo novamente.'
            );
        }

        $dados = json_decode((string) $envelope, true);
        $kml = is_array($dados) ? ($dados['kml'] ?? null) : null;

        if (! is_string($kml) || $kml === '') {
            throw new RuntimeException(
                "O envelope Bronze da camada \"{$camada->nome}\" nao contem o KML original."
            );
        }

        $feicoes = $this->extrator->feicoes($kml);

        if ($feicoes === []) {
            throw new RuntimeException(
                "O KML da camada \"{$camada->nome}\" nao produziu nenhuma geometria."
            );
        }

        $total = $this->repository->substituirFeicoes($camadaId, $feicoes);

        if (StatusCamada::deBanco($camada->status)->noMapa()) {
            AtualizarGoldGeoJob::dispatch();
        }

        return $total;
    }

    /**
     * Arquiva as camadas cuja validade venceu.
     *
     * Existe porque valido_ate era gravado, exibido na tela e IGNORADO por todo
     * o resto: nem gold.geo_feicao_mapa nem a consulta do mapa filtravam por
     * ele, entao camada valida ate 28/02 seguia desenhada no mapa de plantao
     * indefinidamente. Area de risco vencida apresentada como vigente e pior
     * que area faltando, porque ninguem desconfia dela.
     *
     * Arquivamento e nao filtro na consulta: assim o estado fica explicito na
     * tela ("arquivada por validade vencida"), reversivel por reativar, e
     * auditavel -- arquivado_por NULL e a marca do automatico.
     *
     * @return int quantidade arquivada
     */
    public function arquivarVencidas(): int
    {
        $vencidas = DB::table('silver.geo_camadas')
            ->where('status', StatusCamada::APROVADA->value)
            ->whereNotNull('valido_ate')
            // A data de hoje vem do PHP, e nao do current_date do Postgres: o
            // banco roda em UTC e a aplicacao em America/Sao_Paulo, entao das
            // 21:00 a meia-noite o current_date ja e o dia seguinte -- e uma
            // execucao nessa janela arquivaria camada ainda vigente, tirando
            // area de risco do mapa de plantao tres horas antes da hora.
            ->whereRaw('valido_ate < ?::date', [now()->toDateString()])
            ->pluck('id');

        if ($vencidas->isEmpty()) {
            return 0;
        }

        $total = DB::table('silver.geo_camadas')
            ->whereIn('id', $vencidas)
            ->update([
                'status' => StatusCamada::ARQUIVADA->value,
                // NULL de proposito: e a marca de que ninguem decidiu isto, a
                // validade venceu sozinha.
                'arquivado_por' => null,
                'arquivado_em' => now(),
                'motivo_arquivamento' => 'Validade vencida: arquivada automaticamente.',
                'updated_at' => now(),
            ]);

        // Um refresh para o lote inteiro, e nao um por camada: a matview e
        // reconstruida por completo de qualquer forma.
        AtualizarGoldGeoJob::dispatch();

        return $total;
    }

    private function existeOuFalha(int $camadaId): object
    {
        $camada = DB::table('silver.geo_camadas')
            ->select(['id', 'nome', 'status', 'origem', 'ingestao_id', 'valido_ate'])
            ->where('id', $camadaId)
            ->first();

        if ($camada === null) {
            throw new RuntimeException('Camada nao encontrada.');
        }

        return $camada;
    }
}
