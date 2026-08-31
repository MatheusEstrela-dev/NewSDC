<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Vincula os usuarios municipais ao seu orgao COMPDEC.
 *
 * O legado nunca teve esse vinculo em tabela: `com_usuario_comdec` esta vazia.
 * O que existe e uma convencao no nome do usuario -- nome do municipio em
 * caixa alta, sem acento, seguido do codigo do municipio no legado
 * (`cedec_municipio.id`): ABADIADOSD10, ABAETE20, ACAIACA40. O mesmo codigo
 * que a tela antiga mostra na coluna "Cod.Municipio".
 *
 * Sem esse vinculo o sistema novo nao sabe de qual municipio o usuario logado
 * e, e o envio de plano de contingencia nao tem onde gravar.
 *
 * Grava nos dois lugares que o app consulta: `users.orgao_principal_id` e o
 * pivot `compdec_orgao_user` com is_principal.
 */
class VinculoUsuarioService
{
    /**
     * Nome do usuario municipal: prefixo do nome do municipio (truncado em
     * ~10 caracteres) seguido do codigo legado. Ex.: ABADIADOSD + 10.
     */
    private const PADRAO_CODIGO = '/^(.*?)([0-9]+)$/';

    /**
     * Terminar em digito NAO basta para o usuario ser municipal: "ADMIN1",
     * "TESTE10" ou "CEDEC2024" casariam o padrao e seriam vinculados a uma
     * COMPDEC aleatoria, sobrescrevendo o orgao_principal_id de quem e
     * estadual. O prefixo tem que parecer com o nome do municipio daquele
     * codigo.
     *
     * O limiar veio da base real: dos 849 usuarios que casam o padrao, o pior
     * legitimo pontua 0,80 (COUTOMAGAL/COUTODEMAGALHAESDEMINAS) e 95% pontuam
     * 1,00; nomes inventados ficam entre 0,00 e 0,29. 65% fica no meio do vao.
     */
    private const SIMILARIDADE_MINIMA = 65.0;

    public function vincularPorConvencaoDeNome(bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('usuarios');
        $report->dryRun = $dryRun;

        $mapa = $this->mapaCodigoParaOrgao();

        User::query()
            ->select(['id', 'name', 'orgao_principal_id'])
            ->orderBy('id')
            ->chunkById(200, function ($usuarios) use ($report, $dryRun, $mapa): void {
                foreach ($usuarios as $usuario) {
                    $this->vincularUsuario($usuario, $mapa, $report, $dryRun);
                }
            });

        return $report;
    }

    /**
     * @param  array<int, array{orgao_id: int, nomes: array<int, string>}>  $mapa
     */
    private function vincularUsuario(User $usuario, array $mapa, MigracaoReport $report, bool $dryRun): void
    {
        if (! preg_match(self::PADRAO_CODIGO, (string) $usuario->name, $m)) {
            $report->registrarSkip();

            return;
        }

        $entrada = $mapa[(int) $m[2]] ?? null;

        if ($entrada === null) {
            $report->registrarSkip();

            return;
        }

        if (! $this->prefixoConfere($m[1], $entrada['nomes'])) {
            $report->registrarSkip();

            return;
        }

        $orgaoId = $entrada['orgao_id'];

        // Ja vinculado ao mesmo orgao: nao conta como alteracao, para o
        // relatorio de uma segunda execucao nao mentir.
        if ((int) $usuario->orgao_principal_id === $orgaoId) {
            $report->registrarAtualizacao();

            return;
        }

        if ($dryRun) {
            $usuario->orgao_principal_id === null
                ? $report->registrarInsercao()
                : $report->registrarAtualizacao();

            return;
        }

        try {
            DB::transaction(function () use ($usuario, $orgaoId): void {
                $usuario->forceFill(['orgao_principal_id' => $orgaoId])->save();

                // O unique do pivot e (orgao_id, user_id, funcao). Casar tambem
                // por funcao='agente' criaria uma SEGUNDA linha para quem ja
                // esta no orgao como coordenador/tecnico, e ai
                // OrgaoDeLotacao::resolver() perde o fallback de "orgao unico".
                // Aqui a identidade e (orgao, usuario); a funcao existente e
                // preservada e so entra como 'agente' quando o vinculo e novo.
                $pivot = DB::table('compdec_orgao_user')
                    ->where('orgao_id', $orgaoId)
                    ->where('user_id', $usuario->id)
                    ->first();

                if ($pivot !== null) {
                    DB::table('compdec_orgao_user')
                        ->where('id', $pivot->id)
                        ->update(['is_principal' => true, 'updated_at' => now()]);
                } else {
                    DB::table('compdec_orgao_user')->insert([
                        'orgao_id' => $orgaoId,
                        'user_id' => $usuario->id,
                        'funcao' => 'agente',
                        'is_principal' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Principal e um so. Sem isto, mudar o usuario de municipio
                // deixa dois pivots marcados e o wherePivot('is_principal')
                // de OrgaoDeLotacao passa a devolver um dos dois por acaso.
                DB::table('compdec_orgao_user')
                    ->where('user_id', $usuario->id)
                    ->where('orgao_id', '!=', $orgaoId)
                    ->where('is_principal', true)
                    ->update(['is_principal' => false, 'updated_at' => now()]);
            });

            $report->registrarInsercao();
        } catch (Throwable $e) {
            $report->registrarErro($usuario->id, $e->getMessage());
        }
    }

    /**
     * O prefixo do nome do usuario precisa parecer com o nome do municipio.
     *
     * Compara contra os DOIS nomes -- o do legado e o oficial de `municipios`
     * -- porque eles divergem em varios casos e as vezes e o usuario que esta
     * certo: BRASOPOLIS890 e DONAEUZEBI2290 batem com o oficial (Brazopolis,
     * Dona Euzebia) e nao com a grafia antiga de cedec_municipio.
     *
     * @param  array<int, string>  $nomes  ja normalizados
     */
    private function prefixoConfere(string $prefixo, array $nomes): bool
    {
        $prefixo = $this->normalizar($prefixo);

        if (mb_strlen($prefixo) < 3) {
            return false;
        }

        foreach ($nomes as $nome) {
            if ($nome === '') {
                continue;
            }

            similar_text($prefixo, mb_substr($nome, 0, mb_strlen($prefixo)), $percentual);

            if ($percentual >= self::SIMILARIDADE_MINIMA) {
                return true;
            }
        }

        return false;
    }

    private function normalizar(string $valor): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT', $valor) ?: $valor;

        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($semAcento)) ?? '';
    }

    /**
     * Codigo do municipio no legado (cedec_municipio.id) => orgao COMPDEC novo
     * e os nomes usados para validar o prefixo. Uma consulta, ~853 linhas.
     *
     * @return array<int, array{orgao_id: int, nomes: array<int, string>}>
     */
    private function mapaCodigoParaOrgao(): array
    {
        $linhas = DB::table('cedec_municipio as cm')
            ->join('municipios as m', 'm.codigo_ibge', '=', 'cm.Codmundv')
            ->join('compdec_orgaos as o', function ($join): void {
                $join->on('o.municipio_id', '=', 'm.id')
                    ->where('o.tipo', '=', 'compdec')
                    ->whereNull('o.deleted_at');
            })
            ->select([
                'cm.id as codigo',
                'o.id as orgao_id',
                'cm.nome as nome_legado',
                'm.nome as nome_oficial',
            ])
            ->get();

        $mapa = [];

        foreach ($linhas as $linha) {
            // Se houver mais de um orgao para o municipio, fica o primeiro:
            // duplicidade de COMPDEC e problema de cadastro, nao do vinculo.
            $mapa[(int) $linha->codigo] ??= [
                'orgao_id' => (int) $linha->orgao_id,
                'nomes' => [
                    $this->normalizar((string) $linha->nome_legado),
                    $this->normalizar((string) $linha->nome_oficial),
                ],
            ];
        }

        return $mapa;
    }
}
