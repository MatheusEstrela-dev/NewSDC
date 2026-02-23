<?php

namespace App\Core\IA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AIConversation extends Model
{
    use HasUuids;

    protected $table = 'ai_conversations';

    protected $fillable = ['id', 'user_id', 'title', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AIMessage::class, 'conversation_id');
    }
}
