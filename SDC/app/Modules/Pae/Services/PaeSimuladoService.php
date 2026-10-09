<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeSimuladoAvaliacao;
use App\Modules\Pae\Models\PaeSimuladoRelatorio;
use App\Modules\Pae\Requests\AvaliarSimuladoRequest;
use App\Modules\Pae\Requests\PreviaIndiciosSimuladoRequest;
use App\Modules\Pae\Requests\RegistrarSimuladoRequest;
use App\Modules\Pae\Support\Evacuacao\TempoAnexoE;
use App\Modules\Pae\Support\PaeArquivoPdf;
use App\Modules\Pae\Support\PaeIdempotencia;
use App\Modules\Pae\Support\PaeListagem;
use App\Modules\Pae\Support\Simulado\PaeSimuladoJanela;
use App\Modules\Pae\Support\Simulado\SimuladoAnexoC;
use App\Modules\Pae\Support\TimelinePae;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PaeSimuladoService
{
    /** Art. 94: aviso a CEDEC com no minimo uma semana de antecedencia. */
    public const ANTECEDENCIA_MINIMA_DIAS = 7;

    private const CAMPOS_IDEMPOTENCIA = [
        'dt_realizacao', 'nivel_emergencia', 'dt_apresentacao', 'num_sei', 'observacao', 'integrado',
        'barragens_integradas', 'aviso_cedec_em', 'criterios', 'tempos', 'alarme', 'informativos',
    ];

    private const ROTULO_MOTIVO = [
        'licenca_instalacao' => 'Licença de Instalação, Art. 17',
        'metodo_alternativo' => 'método alternativo aprovado, Art. 21',
    ];

    public function avaliar(PaeProtocolo $protocolo, array $dados, User $user): PaeSimuladoAvaliacao
    {
        $dados = Validator::make($dados, AvaliarSimuladoRequest::regras(), AvaliarSimuladoRequest::mensagens())->validate();
        $dados['fundamentacao'] = trim($dados['fundamentacao']);
        $dados['num_sei'] = trim($dados['num_sei']);
        $dados['motivo_dispensa'] = $dados['resultado'] === 'dispensado' ? ($dados['motivo_dispensa'] ?? null) : null;
        if ($dados['fundamentacao'] === '' || $dados['num_sei'] === '') {
            throw ValidationException::withMessages(['avaliacao' => 'Informe fundamentação e número SEI.']);
        }

        return DB::transaction(function () use ($protocolo, $dados, $user): PaeSimuladoAvaliacao {
            $locked = $this->protocoloAberto($protocolo);
            $existente = $locked->avaliacoesSimulado()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
            if ($existente !== null) {
                PaeIdempotencia::exigirMesmosDados($existente, $dados, ['resultado', 'motivo_dispensa', 'fundamentacao', 'num_sei']);

                return $existente;
            }

            $avaliacao = $locked->avaliacoesSimulado()->create([
                'resultado' => $dados['resultado'],
                'motivo_dispensa' => $dados['motivo_dispensa'],
                'fundamentacao' => $dados['fundamentacao'],
                'num_sei' => $dados['num_sei'],
                'chave_idempotencia' => $dados['chave_idempotencia'],
                'decidido_por' => $user->id,
                'decidido_em' => now(),
            ]);
            $rotulo = $avaliacao->resultado === 'exigivel'
                ? 'exigível'
                : 'dispensado ('.self::ROTULO_MOTIVO[$avaliacao->motivo_dispensa].')';
            TimelinePae::registrar($locked, 'simulado_avaliacao',
                "Exigibilidade do simulado: {$rotulo}. SEI {$avaliacao->num_sei}.", $user);

            return $avaliacao;
        });
    }

    public function registrarRelatorio(PaeProtocolo $protocolo, array $dados, UploadedFile $arquivo, User $user): PaeSimuladoRelatorio
    {
        $validado = Validator::make(
            $dados + ['arquivo' => $arquivo],
            RegistrarSimuladoRequest::regras($dados),
            RegistrarSimuladoRequest::mensagens(),
        )->validate();
        $relatorio = $this->normalizar($validado);
        if ($relatorio['num_sei'] === '') {
            throw ValidationException::withMessages(['num_sei' => 'Informe o número SEI.']);
        }
        $erros = [];
        foreach (SimuladoAnexoC::justificativasFaltantes($relatorio['criterios']) as $numero) {
            $erros["criterios.{$numero}.justificativa"] = 'Justifique o critério que não atende.';
        }
        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }
        $chave = $validado['chave_idempotencia'];
        $calculados = [
            'validado' => SimuladoAnexoC::validado($relatorio['criterios']),
            'indicios' => SimuladoAnexoC::indicios($relatorio['tempos'], $relatorio['alarme']),
        ];

        return PaeArquivoPdf::executar(fn (PaeArquivoPdf $pdf): PaeSimuladoRelatorio => DB::transaction(
            function () use ($protocolo, $relatorio, $calculados, $chave, $arquivo, $user, $pdf): PaeSimuladoRelatorio {
                $locked = $this->protocoloAberto($protocolo);
                $existente = $locked->relatoriosSimulado()->where('chave_idempotencia', $chave)->first();
                if ($existente !== null) {
                    PaeIdempotencia::exigirMesmosDados($existente, $relatorio, self::CAMPOS_IDEMPOTENCIA);

                    return $existente;
                }
                if ($locked->avaliacoesSimulado()->first()?->resultado !== 'exigivel') {
                    throw ValidationException::withMessages(['avaliacao' => 'Registre antes uma avaliação de simulado exigível.']);
                }

                $versao = (int) $locked->relatoriosSimulado()->where('dt_realizacao', $relatorio['dt_realizacao'])->max('versao') + 1;
                $metadados = $pdf->guardar("simulados/{$locked->id}/{$relatorio['dt_realizacao']}", $arquivo, 'Não foi possível guardar o relatório do simulado.');

                $registro = $locked->relatoriosSimulado()->create([
                    ...$relatorio,
                    ...$metadados,
                    ...$calculados,
                    'versao' => $versao,
                    'chave_idempotencia' => $chave,
                    'registrado_por' => $user->id,
                    'registrado_em' => now(),
                ]);
                $situacao = $registro->validado ? 'validado' : 'não validado';
                TimelinePae::registrar($locked, 'simulado_relatorio', sprintf(
                    'Simulado de %s, versão %d: %s. SEI %s.',
                    $registro->dt_realizacao->format('d/m/Y'), $versao, $situacao, $registro->num_sei,
                ), $user);

                return $registro;
            },
        ));
    }

    /** Indicios de uma tela ainda nao registrada: valida so tempos e alarme e nao grava. */
    public function previaIndicios(array $dados): array
    {
        $validado = Validator::make($dados, PreviaIndiciosSimuladoRequest::regras())->validate();

        return SimuladoAnexoC::indicios($this->tempos($validado['tempos'] ?? []), $this->alarme($validado['alarme']));
    }

    public function resumo(PaeProtocolo $protocolo, CarbonImmutable $hoje): array
    {
        $avaliacoes = $protocolo->avaliacoesSimulado()->with('decisor:id,name')->get();
        $relatorios = $protocolo->relatoriosSimulado()->with('registrador:id,name')
            ->orderByDesc('dt_realizacao')->orderByDesc('versao')->get();
        $vigente = $avaliacoes->first();
        $ccpae = $protocolo->ccpaeVigente()->first();
        $dia = $hoje->toDateString();
        $janela = $relatorios->filter(fn (PaeSimuladoRelatorio $r): bool => PaeSimuladoJanela::contem($r->dt_realizacao, $hoje)
            && $r->dt_apresentacao->toDateString() <= $dia);
        $validado = $this->ultimasVersoes($janela)->first(fn (PaeSimuladoRelatorio $r): bool => $r->validado);
        $idsVigentes = $this->ultimasVersoes($relatorios)->pluck('id')->flip();

        return [
            'situacao' => $this->situacaoAnual($vigente, $janela, $ccpae !== null),
            'janela_inicio' => PaeSimuladoJanela::inicio($hoje)->toDateString(),
            'proximo_vencimento' => $validado === null ? null : PaeSimuladoJanela::vencimento($validado->dt_realizacao)->toDateString(),
            'alerta_legado' => $vigente === null && $ccpae !== null,
            'avaliacao' => $vigente,
            'avaliacoes' => $avaliacoes,
            'relatorios' => $relatorios->map(fn (PaeSimuladoRelatorio $r): array => $this->apresentar($r, $idsVigentes->has($r->id)))->all(),
            'catalogo' => SimuladoAnexoC::catalogo(),
            'ccpae' => $ccpae,
        ];
    }

    /**
     * @return array{avaliacao: PaeSimuladoAvaliacao, relatorio: ?PaeSimuladoRelatorio}
     */
    public function evidenciaParaEmissao(PaeProtocolo $protocoloBloqueado, CarbonImmutable $dataEmissao): array
    {
        $avaliacao = $protocoloBloqueado->avaliacoesSimulado()->first();
        if ($avaliacao === null) {
            throw ValidationException::withMessages(['simulado' => 'Avalie a exigibilidade do simulado antes de emitir o CCPAE.']);
        }
        if ($avaliacao->resultado === 'dispensado') {
            return ['avaliacao' => $avaliacao, 'relatorio' => null];
        }

        $dia = $dataEmissao->toDateString();
        $candidatos = $protocoloBloqueado->relatoriosSimulado()
            ->whereBetween('dt_realizacao', [PaeSimuladoJanela::inicio($dataEmissao)->toDateString(), $dia])
            ->where('dt_apresentacao', '<=', $dia)
            ->orderByDesc('dt_realizacao')->orderByDesc('versao')->get();
        $relatorio = $this->ultimasVersoes($candidatos)->first(fn (PaeSimuladoRelatorio $r): bool => $r->validado);

        if ($relatorio === null || ! PaeArquivoPdf::existe($relatorio->arquivo_path)) {
            throw ValidationException::withMessages([
                'simulado' => 'A emissão exige relatório de simulado validado, realizado nos 12 meses anteriores e disponível.',
            ]);
        }

        return ['avaliacao' => $avaliacao, 'relatorio' => $relatorio];
    }

    public function anotarListagem(LengthAwarePaginator $pagina, CarbonImmutable $hoje): LengthAwarePaginator
    {
        return PaeListagem::anotar(
            $pagina,
            fn (array $ids): array => [
                'avaliacoes' => PaeSimuladoAvaliacao::query()->whereIn('protocolo_id', $ids)
                    ->orderByDesc('id')->get()->unique('protocolo_id')->keyBy('protocolo_id'),
                'relatorios' => PaeSimuladoRelatorio::query()->whereIn('protocolo_id', $ids)
                    ->whereBetween('dt_realizacao', [PaeSimuladoJanela::inicio($hoje)->toDateString(), $hoje->toDateString()])
                    ->where('dt_apresentacao', '<=', $hoje->toDateString())
                    ->orderByDesc('dt_realizacao')->orderByDesc('versao')
                    ->get(['id', 'protocolo_id', 'dt_realizacao', 'versao', 'validado'])->groupBy('protocolo_id'),
                'certificados' => PaeCcpae::query()->whereIn('protocolo_id', $ids)
                    ->distinct()->pluck('protocolo_id')->flip(),
            ],
            fn (int $id, array $contexto): array => [
                'simulado_situacao' => $this->situacaoAnual(
                    $contexto['avaliacoes']->get($id),
                    $contexto['relatorios']->get($id, collect()),
                    $contexto['certificados']->has($id),
                ),
            ],
        );
    }

    private function protocoloAberto(PaeProtocolo $protocolo): PaeProtocolo
    {
        $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
        if ($locked->arquivado) {
            throw ValidationException::withMessages(['protocolo' => 'Protocolo arquivado: os simulados estão somente para consulta.']);
        }

        return $locked;
    }

    /**
     * @param  Collection<int, PaeSimuladoRelatorio>  $relatoriosDaJanela  ordenados por realizacao e versao decrescentes
     */
    private function situacaoAnual(?PaeSimuladoAvaliacao $avaliacao, Collection $relatoriosDaJanela, bool $temCcpae): string
    {
        if ($avaliacao === null) {
            return 'nao_avaliada';
        }
        if ($avaliacao->resultado === 'dispensado') {
            return 'dispensado';
        }

        $vigentes = $this->ultimasVersoes($relatoriosDaJanela);
        if ($vigentes->contains(fn (PaeSimuladoRelatorio $r): bool => $r->validado)) {
            return 'em_dia';
        }
        if ($vigentes->isNotEmpty()) {
            return 'nao_validado';
        }

        // Sem certificado nao ha ciclo anual em atraso: a falta do relatorio so impede a emissao.
        return $temCcpae ? 'vencido' : 'pendente_emissao';
    }

    /**
     * A maior versao de cada simulado (dt_realizacao); as revisoes anteriores nao contam.
     *
     * @param  Collection<int, PaeSimuladoRelatorio>  $relatorios  ordenados por realizacao e versao decrescentes
     * @return Collection<int, PaeSimuladoRelatorio>
     */
    private function ultimasVersoes(Collection $relatorios): Collection
    {
        return $relatorios->unique(fn (PaeSimuladoRelatorio $r): string => $r->dt_realizacao->toDateString())->values();
    }

    /** Valida o conteudo: o servidor decide `validado` e `indicios`, o cliente nunca os informa. */
    private function normalizar(array $v): array
    {
        $integrado = $this->booleano($v['integrado']);

        return [
            'dt_realizacao' => $v['dt_realizacao'],
            'nivel_emergencia' => (int) $v['nivel_emergencia'],
            'dt_apresentacao' => $v['dt_apresentacao'],
            'num_sei' => trim($v['num_sei']),
            'observacao' => $this->texto($v['observacao'] ?? null),
            'integrado' => $integrado,
            'barragens_integradas' => $integrado ? $this->texto($v['barragens_integradas'] ?? null) : null,
            'aviso_cedec_em' => $this->texto($v['aviso_cedec_em'] ?? null),
            'criterios' => $this->criterios($v['criterios']),
            'tempos' => $this->tempos($v['tempos'] ?? []),
            'alarme' => $this->alarme($v['alarme']),
            'informativos' => $this->informativos($v['informativos'] ?? []),
        ];
    }

    private function criterios(array $criterios): array
    {
        $normalizados = [];
        foreach (SimuladoAnexoC::numeros() as $numero) {
            $normalizados[$numero] = [
                'atende' => $this->booleano($criterios[$numero]['atende']),
                'justificativa' => $this->texto($criterios[$numero]['justificativa'] ?? null),
            ];
        }

        return $normalizados;
    }

    private function tempos(array $tempos): array
    {
        $normalizados = [];
        foreach (SimuladoAnexoC::CATEGORIAS as $categoria) {
            $normalizados[$categoria] = array_values(array_map(
                fn (array $linha): array => $this->linhaTempo($categoria, $linha),
                $tempos[$categoria] ?? [],
            ));
        }

        return $normalizados;
    }

    private function linhaTempo(string $categoria, array $linha): array
    {
        $normalizada = [
            'nome' => trim($linha['nome']),
            'populacao' => $this->inteiro($linha['populacao'] ?? null),
            'chegada_onda_segundos' => TempoAnexoE::paraSegundos($linha['chegada_onda']),
            'saida_segundos' => TempoAnexoE::paraSegundos($linha['saida']),
            'houve_problemas' => $this->booleano($linha['houve_problemas']),
            'ponto_valido' => $this->booleano($linha['ponto_valido']),
            'estimativa' => $this->booleano($linha['estimativa']),
        ];
        if ($categoria === 'hospitalares_prisionais') {
            $normalizada['nivel_emergencia'] = (int) $linha['nivel_emergencia'];
        }

        return $normalizada;
    }

    private function alarme(array $alarme): array
    {
        $audivel = $this->booleano($alarme['audivel_todos']);

        return [
            'audivel_todos' => $audivel,
            'morador_nome' => $audivel ? null : $this->texto($alarme['morador_nome'] ?? null),
            'morador_localizacao' => $audivel ? null : $this->texto($alarme['morador_localizacao'] ?? null),
        ];
    }

    private function informativos(array $informativos): array
    {
        $participacao = $informativos['participacao'] ?? [];

        return [
            'participacao' => [
                'populacao_zas' => $this->inteiro($participacao['populacao_zas'] ?? null),
                'participantes' => $this->inteiro($participacao['participantes'] ?? null),
                'cadastrados_pae' => $this->inteiro($participacao['cadastrados_pae'] ?? null),
                'anos_anteriores' => array_values(array_map(
                    fn (array $ano): array => ['ano' => (int) $ano['ano'], 'participantes' => (int) $ano['participantes']],
                    $participacao['anos_anteriores'] ?? [],
                )),
            ],
            'ensino_observacoes' => $this->texto($informativos['ensino_observacoes'] ?? null),
            'recursos_observacoes' => $this->texto($informativos['recursos_observacoes'] ?? null),
            'conclusao_compdec' => $informativos['conclusao_compdec'] ?? null,
        ];
    }

    private function apresentar(PaeSimuladoRelatorio $r, bool $vigente): array
    {
        $antecedencia = $r->aviso_cedec_em === null
            ? null
            : (int) round($r->aviso_cedec_em->diffInDays($r->dt_realizacao, false));

        return [
            'id' => $r->id,
            'dt_realizacao' => $r->dt_realizacao->toDateString(),
            'versao' => $r->versao,
            'vigente' => $vigente,
            'nivel_emergencia' => $r->nivel_emergencia,
            'dt_apresentacao' => $r->dt_apresentacao->toDateString(),
            'num_sei' => $r->num_sei,
            'observacao' => $r->observacao,
            'arquivo_nome_original' => $r->arquivo_nome_original,
            'integrado' => $r->integrado,
            'barragens_integradas' => $r->barragens_integradas,
            'aviso_cedec_em' => $r->aviso_cedec_em?->toDateString(),
            'aviso_antecedencia_dias' => $antecedencia,
            'alerta_aviso' => $antecedencia !== null && $antecedencia < self::ANTECEDENCIA_MINIMA_DIAS,
            'criterios' => $r->criterios,
            'tempos' => TempoAnexoE::formatarResultado($r->tempos),
            'alarme' => $r->alarme,
            'informativos' => $r->informativos,
            'validado' => $r->validado,
            'indicios' => $r->indicios,
            'registrador' => $r->registrador?->name,
            'registrado_em' => $r->registrado_em?->toIso8601String(),
        ];
    }

    private function booleano(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    private function texto(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }

    private function inteiro(mixed $valor): ?int
    {
        return $valor === null || $valor === '' ? null : (int) $valor;
    }
}
