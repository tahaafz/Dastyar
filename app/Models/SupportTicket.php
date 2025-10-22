<?php

namespace App\Models;

use App\Enums\SupportTicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reply_to_id',
        'type',
        'message',
        'is_answered',
        'answered_at',
    ];

    protected $casts = [
        'type' => SupportTicketType::class,
        'is_answered' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    public function answer(): HasOne
    {
        return $this->hasOne(self::class, 'reply_to_id')->where('type', SupportTicketType::Answer);
    }

    public function scopeQuestions($query)
    {
        return $query->where('type', SupportTicketType::Question);
    }

    public function scopeAnswers($query)
    {
        return $query->where('type', SupportTicketType::Answer);
    }

    public function markAsAnswered(string $answerMessage, User $admin): self
    {
        return DB::transaction(function () use ($answerMessage, $admin) {
            $now = Carbon::now();

            $answer = self::create([
                'user_id' => $admin->id,
                'reply_to_id' => $this->id,
                'type' => SupportTicketType::Answer,
                'message' => $answerMessage,
                'is_answered' => true,
                'answered_at' => $now,
            ]);

            $this->forceFill([
                'is_answered' => true,
                'answered_at' => $now,
            ])->save();

            return $answer;
        });
    }
}
