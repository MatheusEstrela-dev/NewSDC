<?php

namespace App\Modules\Suporte\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'category' => $this->category,
            'category_label' => $this->category->label(),
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'priority' => $this->priority,
            'priority_label' => $this->priority->label(),
            'description' => $this->description,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
