<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'graph'           => 'array',
        'published_graph' => 'array',
        'published_at'    => 'datetime',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }
}
