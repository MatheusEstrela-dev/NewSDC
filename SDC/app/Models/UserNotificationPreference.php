<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'canal_sistema',
        'canal_email',
        'canal_push',
        'canal_telegram',
        'canal_whatsapp',
    ];

    protected $casts = [
        'canal_sistema'  => 'boolean',
        'canal_email'    => 'boolean',
        'canal_push'     => 'boolean',
        'canal_telegram' => 'boolean',
        'canal_whatsapp' => 'boolean',
    ];

    /**
     * Canais externos comecam desligados: opt-in explicito do usuario.
     */
    private const DEFAULTS = [
        'canal_sistema'  => true,
        'canal_email'    => false,
        'canal_push'     => false,
        'canal_telegram' => false,
        'canal_whatsapp' => false,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Slugs dos modulos notificaveis. A lista vive em config/notificacoes.php,
     * fonte unica compartilhada com a validacao do controller e com o frontend.
     *
     * @return list<string>
     */
    public static function modules(): array
    {
        return array_keys(config('notificacoes.modulos', []));
    }

    /**
     * Preferencias do usuario, criando os defaults que faltarem.
     *
     * Custo fixo de 3 queries independente da quantidade de modulos: SELECT dos
     * existentes, INSERT em lote dos que faltam e SELECT final. insertOrIgnore
     * cobre a corrida entre duas requisicoes do mesmo usuario, apoiado no unique
     * (user_id, module).
     */
    public static function forUser(int $userId): EloquentCollection
    {
        $existentes = static::query()
            ->where('user_id', $userId)
            ->pluck('module')
            ->all();

        $faltantes = array_values(array_diff(static::modules(), $existentes));

        if ($faltantes !== []) {
            $agora = now();

            static::query()->insertOrIgnore(array_map(
                fn (string $modulo): array => self::DEFAULTS + [
                    'user_id'    => $userId,
                    'module'     => $modulo,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ],
                $faltantes
            ));
        }

        return static::query()
            ->where('user_id', $userId)
            ->orderBy('module')
            ->get();
    }

    /**
     * Preferencias de um modulo para varios usuarios, indexadas por user_id.
     *
     * Usado no fan-out para decidir os canais de N destinatarios com UMA query,
     * em vez de uma consulta por destinatario dentro do via() da notificacao.
     * Usuario sem linha nao aparece no retorno: quem consome trata a ausencia
     * chamando padrao().
     *
     * @param  list<int>  $userIds
     * @return Collection<int, static>
     */
    public static function paraUsuarios(array $userIds, string $module): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return static::query()
            ->whereIn('user_id', $userIds)
            ->where('module', $module)
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Preferencia efetiva de quem ainda nao tem linha gravada para o modulo.
     */
    public static function padrao(int $userId, string $module): static
    {
        return new static(self::DEFAULTS + [
            'user_id' => $userId,
            'module'  => $module,
        ]);
    }
}
