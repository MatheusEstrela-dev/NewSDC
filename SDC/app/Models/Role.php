<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Extende o model do Spatie e adiciona metadados usados no NewSDC.
     *
     * Importante: o Spatie usa `name` como identificador lógico (role name).
     */
    protected $fillable = [
        'name',
        'guard_name',
        'slug',
        'hierarchy_level',
        'description',
        'is_active',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
        'is_active' => 'boolean',
    ];
}
