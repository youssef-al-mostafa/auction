<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $chat_thread_id
 * @property int $user_id
 * @property string $body
 * @property CarbonImmutable $created_at
 * @property-read ChatThread $chatThread
 * @property-read User $user
 */
#[Fillable(['chat_thread_id', 'user_id', 'body'])]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    /**
     * Messages are append-only, so there is nothing for an updated_at to record.
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInSendOrder(Builder $query): Builder
    {
        return $query->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<ChatThread, $this>
     */
    public function chatThread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
