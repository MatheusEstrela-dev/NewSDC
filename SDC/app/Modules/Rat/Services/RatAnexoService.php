<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatAnexo;
use Illuminate\Support\Collection;

class RatAnexoService
{
    public function listByRat(string $ratId): Collection
    {
        return RatAnexo::where('rat_id', $ratId)->get();
    }

    public function delete(int $id): void
    {
        $anexo = RatAnexo::findOrFail($id);
        $anexo->delete();
    }
}
