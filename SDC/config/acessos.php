<?php

declare(strict_types=1);

// Segredos (DIRETORIO_SENHA, DIRETORIO_CHAVE_ENTREGA e seus _FILE) NAO ficam
// aqui: sao lidos no uso por App\Modules\Acessos\Infrastructure\SegredoDiretorio,
// para que `config:cache` nunca os grave em bootstrap/cache/config.php.
$lista = static fn (?string $valor): array => array_values(array_filter(
    array_map('trim', explode(',', (string) $valor)),
    static fn (string $item): bool => $item !== '',
));
$texto = static function (string $env, ?string $padrao = null): ?string {
    $valor = env($env);

    return is_string($valor) && trim($valor) !== '' ? trim($valor) : $padrao;
};
// Inteiro positivo dentro da faixa; vazio = padrao; invalido = null (o consumidor
// recusa com config_ausente), nunca um 0 silencioso.
$inteiro = static function (string $env, int $padrao, int $maximo = PHP_INT_MAX) use ($texto): ?int {
    $valor = $texto($env);
    if ($valor === null) {
        return $padrao;
    }
    if (preg_match('/^\d+$/', $valor) !== 1) {
        return null;
    }
    $numero = (int) $valor;

    return $numero >= 1 && $numero <= $maximo ? $numero : null;
};
// Lista de segundos (0 vale: espera nenhuma); lista vazia, item nao numerico ou
// acima do teto = null.
$segundos = static function (string $env, string $padrao, int $maximo) use ($texto, $lista): ?array {
    $itens = $lista($texto($env, $padrao));
    foreach ($itens as $item) {
        if (preg_match('/^\d+$/', $item) !== 1 || (int) $item > $maximo) {
            return null;
        }
    }

    return $itens === [] ? null : array_map('intval', $itens);
};

$seguranca = $texto('DIRETORIO_SEGURANCA', 'ldaps') === 'starttls' ? 'starttls' : 'ldaps';
$usuario = $texto('DIRETORIO_USUARIO');

// sAMAccountName da conta de servico a partir do UPN (svc@dominio) ou DOMINIO\svc.
$loginServico = $usuario === null ? null : mb_strtolower((string) preg_replace(['/@.*$/', '/^.*\\\\/'], '', $usuario));

return [

    /*
    |--------------------------------------------------------------------------
    | Diretorio corporativo (Active Directory)
    |--------------------------------------------------------------------------
    | driver: desligado (padrao; web sempre) | ldap (worker on-prem) | fake.
    | Hosts por FQDN, nunca IP. Nenhum valor real neste arquivo.
    */
    'diretorio' => [
        'driver' => $texto('DIRETORIO_DRIVER', 'desligado'),
        'hosts' => $lista(env('DIRETORIO_HOSTS')),
        'dominio' => $texto('DIRETORIO_DOMINIO'),
        'host_preferencial' => $texto('DIRETORIO_HOST_PREFERENCIAL'),
        'seguranca' => $seguranca,
        'porta' => $inteiro('DIRETORIO_PORTA', $seguranca === 'starttls' ? 389 : 636, 65535),
        'base_dn' => $texto('DIRETORIO_BASE_DN'),
        'search_base' => $texto('DIRETORIO_SEARCH_BASE'),
        'usuario' => $usuario,
        'ca_cert' => $texto('DIRETORIO_CA_CERT', '/etc/ssl/diretorio/ca.pem'),
        'timeout_conexao' => $inteiro('DIRETORIO_TIMEOUT_CONEXAO', 5),
        'timeout_operacao' => $inteiro('DIRETORIO_TIMEOUT_OPERACAO', 10),

        // A propria conta de servico entra sempre; comparacao em minusculas.
        'contas_protegidas' => array_values(array_unique(array_filter([
            ...array_map('mb_strtolower', $lista(env('DIRETORIO_CONTAS_PROTEGIDAS'))),
            $loginServico,
        ]))),

        'fila' => [
            'conexao' => $texto('DIRETORIO_FILA_CONEXAO', 'diretorio'),
            'nome' => 'diretorio',
        ],
        // Retry e tamanho de senha tem padrao seguro: invalido vira o padrao, nunca 0.
        'tentativas' => $inteiro('DIRETORIO_TENTATIVAS', 3, 10) ?? 3,
        'backoff' => $segundos('DIRETORIO_BACKOFF', '10,30', 600) ?? [10, 30],
        // Operacao em voo sem progresso por mais que isto vira falhou (indisponivel).
        // Pior caso de um job vivo com o padrao: 3 tentativas x retry_after 120 s
        // + backoff 10 + 30 s = 400 s (~7 min); 15 min da mais que o dobro de folga.
        'reconciliar_minutos' => $inteiro('DIRETORIO_RECONCILIAR_MINUTOS', 15, 1440) ?? 15,

        'senha_tamanho' => max(14, $inteiro('DIRETORIO_SENHA_TAMANHO', 16, 128) ?? 16),
        'entrega_ttl_minutos' => $inteiro('DIRETORIO_ENTREGA_TTL_MINUTOS', 10),

        'sincronizar' => [
            'agendar' => filter_var(env('DIRETORIO_SINCRONIZAR_AGENDA', false), FILTER_VALIDATE_BOOL),
            'tamanho_pagina' => $inteiro('DIRETORIO_SYNC_TAMANHO_PAGINA', 500),
            'limiar_ausencia' => (float) ($texto('DIRETORIO_SYNC_LIMIAR_AUSENCIA') ?? 0.2),
            'max_sem_cadastro' => (int) ($texto('DIRETORIO_SYNC_MAX_SEM_CADASTRO') ?? 200),
            'retencao_dias' => (int) ($texto('DIRETORIO_RETENCAO_SINCRONIZACOES_DIAS') ?? 30),
        ],

        'fake' => [
            'arquivo' => $texto('DIRETORIO_FAKE_ARQUIVO'),
        ],
    ],

];
