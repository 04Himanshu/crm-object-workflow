<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'score' => 'integer',
        'converted_at' => 'datetime',
        'archived_at' => 'datetime',
        'purge_after' => 'datetime',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    public function opportunity(): HasOne
    {
        return $this->hasOne(Opportunity::class);
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class);
    }
}
