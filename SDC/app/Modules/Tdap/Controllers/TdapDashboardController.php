<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Services\TdapService;
use Inertia\Inertia;
use Inertia\Response;

class TdapDashboardController extends Controller
{
    public function __construct(
        private readonly TdapService $tdapService
    ) {
    }

    public function index(): Response
    {
        $statistics = $this->tdapService->getDashboardStatistics();

        return Inertia::render('Tdap/Dashboard', [
            'statistics' => $statistics,
        ]);
    }
}
