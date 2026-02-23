<?php

namespace App\Modules\Suporte\Domain\Entities;

use App\Modules\Suporte\Domain\ValueObjects\TicketCategory;
use App\Modules\Suporte\Domain\ValueObjects\TicketPriority;
use App\Modules\Suporte\Domain\ValueObjects\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'status',
        'priority',
        'description',
    ];

    protected $casts = [
        'category' => TicketCategory::class,
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }
}
