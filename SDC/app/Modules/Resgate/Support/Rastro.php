<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

use Illuminate\Http\Request;

/**
 * Rastro de um ato no resgate (decisao D3): de onde veio, com que cliente,
 * em que sessao e requisicao. Mesmo conjunto de campos do permission_audit_log,
 * para auditoria e dossie lerem um padrao so.
 *
 * O autor NAO fica aqui: ele vem sempre da sessao autenticada e e passado a
 * parte, para nunca ser aceito do corpo da requisicao.
 */
final readonly class Rastro
{
    public function __construct(
        public ?string $ip,
        public ?string $userAgent,
        public ?string $sessao,
        public ?string $requisicao,
    ) {}

    public static function daRequisicao(Request $request): self
    {
        return new self(
            ip: $request->ip(),
            userAgent: $request->userAgent() !== null ? mb_substr($request->userAgent(), 0, 500) : null,
            sessao: $request->hasSession() ? $request->session()->getId() : null,
            requisicao: $request->headers->get('X-Request-Id'),
        );
    }

    /** Ato de sistema (seeder, comando): marcado como tal, nunca anonimo. */
    public static function doSistema(string $origem): self
    {
        return new self(ip: null, userAgent: "sistema:{$origem}", sessao: null, requisicao: null);
    }
}
