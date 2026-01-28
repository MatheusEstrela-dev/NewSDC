<?php

namespace App\Modules\Suporte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Suporte\Application\DTOs\CreateTicketDTO;
use App\Modules\Suporte\Application\UseCases\CreateTicketUseCase;
use App\Modules\Suporte\Application\UseCases\ListUserTicketsUseCase;
use App\Modules\Suporte\Presentation\Http\Resources\TicketResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class SupportController extends Controller
{
    public function __construct(
        private readonly CreateTicketUseCase $createTicketUseCase,
        private readonly ListUserTicketsUseCase $listUserTicketsUseCase
    ) {}

    public function index(Request $request)
    {
        $tickets = $this->listUserTicketsUseCase->execute($request->user()->id);

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
            'attachment' => 'nullable|file|max:10240', // 10MB
        ]);

        $dto = new CreateTicketDTO(
            userId: $request->user()->id,
            subject: $validated['subject'],
            category: $validated['category'],
            priority: $validated['priority'],
            description: $validated['description'],
            // Attachment handling would need file upload logic here or in UseCase
        );

        $this->createTicketUseCase->execute($dto);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
             return response()->json(['message' => 'Ticket criado com sucesso!']);
        }

        return Redirect::back()->with('success', 'Ticket criado com sucesso!');
    }
}
