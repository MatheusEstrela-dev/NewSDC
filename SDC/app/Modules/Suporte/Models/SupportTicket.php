<?php

declare(strict_types=1);

namespace App\Modules\Suporte\Models;

use App\Models\User;
use App\Modules\Suporte\Enums\TicketCategory;
use App\Modules\Suporte\Enums\TicketPriority;
use App\Modules\Suporte\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
