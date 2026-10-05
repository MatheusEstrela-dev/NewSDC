<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Infrastructure;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;

/**
 * Sondas de rede do diagnostico (DNS e handshake TLS). Nao e final: o teste
 * substitui a rede. O texto de erro do stream nunca sai daqui, so o codigo.
 */
class SondaRedeDiretorio
{
    public function resolve(string $host): bool
    {
        $registros = @dns_get_record($host, DNS_A | DNS_AAAA);

        return (is_array($registros) && $registros !== []) || @gethostbyname($host) !== $host;
    }

    /**
     * Handshake TLS com verificacao de cadeia e de nome contra a CA informada.
     *
     * @return int dias ate o vencimento do certificado do servidor
     *
     * @throws DiretorioIndisponivel
     */
    public function diasAteVencerCertificado(string $host, int $porta, string $ca, int $timeout): int
    {
        $contexto = stream_context_create(['ssl' => [
            'cafile' => $ca,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'capture_peer_cert' => true,
        ]]);

        $erro = 0;
        $texto = '';
        $socket = @stream_socket_client('ssl://'.$host.':'.$porta, $erro, $texto, $timeout, STREAM_CLIENT_CONNECT, $contexto);
        if ($socket === false) {
            throw new DiretorioIndisponivel($this->falhaDeCertificado($texto) ? CodigoErroDiretorio::CERTIFICADO : CodigoErroDiretorio::INDISPONIVEL);
        }

        $parametros = stream_context_get_params($socket);
        fclose($socket);
        $certificado = $parametros['options']['ssl']['peer_certificate'] ?? null;
        $dados = $certificado === null ? false : openssl_x509_parse($certificado);
        if (! is_array($dados) || ! isset($dados['validTo_time_t'])) {
            throw new DiretorioIndisponivel(CodigoErroDiretorio::CERTIFICADO);
        }

        return (int) floor(((int) $dados['validTo_time_t'] - time()) / 86400);
    }

    private function falhaDeCertificado(string $texto): bool
    {
        return stripos($texto, 'certificate') !== false || stripos($texto, 'SSL') !== false;
    }
}
