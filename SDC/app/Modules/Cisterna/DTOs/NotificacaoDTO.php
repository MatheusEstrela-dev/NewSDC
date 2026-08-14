<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Validation\ValidationException;

final readonly class NotificacaoDTO
{
    /**
     * Alias curto -> classe. O formulario nao envia FQCN, para nao expor a
     * estrutura interna nem aceitar classe arbitraria no morph.
     *
     * @var array<string, class-string>
     */
    public const TIPOS_PERMITIDOS = [
        'beneficiario' => CisternaBeneficiario::class,
        'vistoria' => CisternaVistoria::class,
    ];

    /**
     * @param  class-string  $notificavelType
     */
    public function __construct(
        public string $notificavelType,
        public int $notificavelId,
        public string $observacao,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     *
     * @throws ValidationException quando o alias nao e reconhecido
     */
    public static function deValidados(array $d): self
    {
        $alias = (string) ($d['notificavel_type'] ?? '');
        $classe = self::TIPOS_PERMITIDOS[$alias] ?? null;

        if ($classe === null) {
            throw ValidationException::withMessages([
                'notificavel_type' => 'Tipo de registro invalido para notificacao.',
            ]);
        }

        return new self(
            notificavelType: $classe,
            notificavelId: (int) $d['notificavel_id'],
            observacao: trim((string) $d['observacao']),
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'notificavel_type' => $this->notificavelType,
            'notificavel_id' => $this->notificavelId,
            'observacao' => $this->observacao,
            'legacy_id' => $this->legacyId,
        ];
    }

    /**
     * Alias curto correspondente a classe, para devolver ao frontend.
     */
    public function alias(): string
    {
        return (string) array_search($this->notificavelType, self::TIPOS_PERMITIDOS, true);
    }
}
