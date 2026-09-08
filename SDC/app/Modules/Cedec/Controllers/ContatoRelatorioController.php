<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cedec\Services\ContatoRelatorioService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatorios de contato da CEDEC, substituindo relbusca.php, rel_email.php e
 * rel_email_ca.php do legado gestaocedec.
 *
 * Duas abas: e-mails e telefones. A de telefones era href="#" no legado e nunca
 * existiu.
 */
final class ContatoRelatorioController extends Controller
{
    private const ABAS = ['emails', 'telefones'];

    private const TAMANHO_MAXIMO = 200;

    public function __construct(private readonly ContatoRelatorioService $service) {}

    public function index(Request $request): Response
    {
        $aba = $this->aba($request);
        $tamanho = $this->tamanhoDeBloco($request);

        // Duas varreduras, nao quatro: cada uma le as 853 linhas com LEFT JOIN. A
        // versao anterior chamava emails(), telefones() e mais blocosDeEmail() e
        // blocosDeTelefone(), que reconsultavam por dentro. Aqui os dados sao lidos
        // uma vez e os blocos saem deles.
        $emails = $this->service->emails();
        $telefones = $this->service->telefones();

        $blocosDeEmail = $this->service->blocosDeEmailDe($emails, $tamanho);
        $blocosDeTelefone = $this->service->blocosDeTelefoneDe($telefones, $tamanho);

        $ehTelefones = $aba === 'telefones';

        return Inertia::render('Cedec/Contatos/Index', [
            'aba' => $aba,
            // So a aba ativa vai completa. Mandar as duas dobrava o payload por um
            // conjunto que a tela nem renderiza -- a outra chega vazia e e preenchida
            // na visita da troca de aba.
            'emails' => $ehTelefones ? [] : $emails->values()->all(),
            'telefones' => $ehTelefones ? $telefones->values()->all() : [],
            'blocos' => $ehTelefones ? $blocosDeTelefone : $blocosDeEmail,
            'tamanho_bloco' => $tamanho,
            'totais' => [
                // Somar o total dos blocos, em vez de recontar, e o que garante que o
                // numero mostrado no card seja o mesmo que foi para os blocos.
                'emails_preenchidos' => array_sum(array_column($blocosDeEmail, 'total')),
                'telefones_preenchidos' => array_sum(array_column($blocosDeTelefone, 'total')),
                'municipios' => $emails->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $aba = $this->aba($request);

        $dados = $aba === 'telefones'
            ? $this->service->csvTelefones()
            : $this->service->csvEmails();

        $arquivo = 'cedec_contatos_' . $aba . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($dados): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8, para o Excel

            foreach ($dados as $linha) {
                fputcsv($handle, $linha, ';');
            }

            fclose($handle);
        }, $arquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function aba(Request $request): string
    {
        $aba = (string) $request->query('aba', 'emails');

        return in_array($aba, self::ABAS, true) ? $aba : 'emails';
    }

    /**
     * Fora da faixa util cai no padrao. A InvalidArgumentException do service e guarda
     * de programador: por HTTP ela nunca e alcancavel.
     */
    private function tamanhoDeBloco(Request $request): int
    {
        $tamanho = (int) $request->query('tamanho', (string) ContatoRelatorioService::TAMANHO_BLOCO_PADRAO);

        if ($tamanho < 1 || $tamanho > self::TAMANHO_MAXIMO) {
            return ContatoRelatorioService::TAMANHO_BLOCO_PADRAO;
        }

        return $tamanho;
    }
}
