<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoticiasIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Plantao/NoticiasIndex', [
            'noticias' => [],
            'fontes' => [],
        ]);
    }
}
