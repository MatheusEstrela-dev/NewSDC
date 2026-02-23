<?php

namespace App\Core\IA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIMessage extends Model
{
    use HasUuids;

    protected $table = 'ai_messages';

    protected $fillable = ['id', 'conversation_id', 'role', 'content', 'tool_calls', 'metadata'];

    protected $casts = ['tool_calls' => 'array', 'metadata' => 'array'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AIConversation::class, 'conversation_id');
    }
}
