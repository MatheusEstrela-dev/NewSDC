<?php

namespace App\Modules\Rat\Application\UseCases;

use App\Modules\Rat\Domain\Entities\Rat;

class DeleteRatUseCase
{
    public function execute(string $id): void
    {
        $rat = Rat::findOrFail($id);
        $rat->delete();
    }
}
