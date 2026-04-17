<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'canal_sistema',
        'canal_email',
        'canal_push',
    ];

    protected $casts = [
        'canal_sistema' => 'boolean',
        'canal_email'   => 'boolean',
        'canal_push'    => 'boolean',
    ];

    public const MODULES = ['rat', 'pae', 'meteorologia', 'demandas', 'decretacoes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna as preferencias do usuario, criando defaults se nao existirem.
     */
    public static function forUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $existing = static::where('user_id', $userId)->get()->keyBy('module');

        foreach (self::MODULES as $module) {
            if (!$existing->has($module)) {
                $existing->put($module, static::create([
                    'user_id'       => $userId,
                    'module'        => $module,
                    'canal_sistema' => true,
                    'canal_email'   => false,
                    'canal_push'    => false,
                ]));
            }
        }

        return static::where('user_id', $userId)->get();
    }
}
