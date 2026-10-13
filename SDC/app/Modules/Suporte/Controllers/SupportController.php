<?php

namespace App\Modules\Suporte\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Suporte\DTOs\CreateTicketDTO;
use App\Modules\Suporte\Enums\TicketCategory;
use App\Modules\Suporte\Enums\TicketPriority;
use App\Modules\Suporte\Models\SupportTicket;
use App\Modules\Suporte\Models\SupportTicketMessage;
use App\Modules\Suporte\Resources\TicketResource;
use App\Modules\Suporte\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            // Enums do model: texto livre passava aqui e estourava 500 no create().
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'description' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        // Disco privado: o anexo so sai pela rota autenticada suporte.anexo.
        $anexo = $request->file('attachment')?->store('suporte/anexos', 'local');

        $this->ticketService->create(
            CreateTicketDTO::fromValidated($validated, (int) $request->user()->id, $anexo ?: null)
        );

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
             return response()->json(['message' => 'Ticket criado com sucesso!']);
        }

        return Redirect::back()->with('success', 'Ticket criado com sucesso!');
    }

    /**
     * Anexo de uma mensagem do chamado. So o autor do chamado ou um
     * administrador baixam; a mensagem precisa pertencer ao chamado da rota.
     */
    public function anexo(Request $request, SupportTicket $ticket, SupportTicketMessage $mensagem): StreamedResponse
    {
        abort_unless((int) $mensagem->support_ticket_id === (int) $ticket->id, 404);
        abort_unless(
            (int) $ticket->user_id === (int) $request->user()->id || Gate::allows('is-admin'),
            403
        );
        abort_unless($mensagem->attachment_path && Storage::disk('local')->exists($mensagem->attachment_path), 404);

        return Storage::disk('local')->download($mensagem->attachment_path);
    }
}
