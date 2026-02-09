<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SoftDeletes;

    /**
     * Extende o model do Spatie e adiciona metadados usados no NewSDC.
     *
     * Importante: o Spatie usa `name` como identificador logico (role name).
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
