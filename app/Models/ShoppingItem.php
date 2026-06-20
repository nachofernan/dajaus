<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingItem extends Model
{
    protected $fillable = ['user_id', 'name', 'purchased_at', 'notes', 'is_favorite'];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'is_favorite' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->purchased_at === null;
    }
}
