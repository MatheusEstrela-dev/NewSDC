<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Extende o model do Spatie e adiciona metadados usados no NewSDC.
     *
     * Importante: o Spatie usa `name` como identificador lógico (permission name).
     */
    protected $fillable = [
        'name',
        'guard_name',
        'slug',
        'description',
        'group',
        'module',
        'is_active',
        'is_immutable',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_immutable' => 'boolean',
    ];

    /**
     * Get all permissions grouped by category
     */
    public static function getGrouped(): array
    {
        return static::where('is_active', true)
            ->get()
            ->groupBy('group')
            ->toArray();
    }
}
