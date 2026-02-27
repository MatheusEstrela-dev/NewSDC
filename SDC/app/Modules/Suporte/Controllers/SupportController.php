<?php

namespace App\Modules\Suporte\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Suporte\DTOs\CreateTicketDTO;
use App\Modules\Suporte\Resources\TicketResource;
use App\Modules\Suporte\Services\SupportTicketService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class SupportController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $ticketService
    ) {}

    public function index(Request $request)
    {
        $tickets = $this->ticketService->listByUser($request->user()->id);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return TicketResource::collection($tickets);
        }

        return Inertia::render('Suporte/Index', [
            'tickets' => TicketResource::collection($tickets),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string',
            'priority' => 'required|string',
            'description' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $dto = new CreateTicketDTO(
            userId: $request->user()->id,
            subject: $validated['subject'],
            category: $validated['category'],
            priority: $validated['priority'],
            description: $validated['description'],
        );

        $this->ticketService->create($dto);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
             return response()->json(['message' => 'Ticket criado com sucesso!']);
        }

        return Redirect::back()->with('success', 'Ticket criado com sucesso!');
    }
}
