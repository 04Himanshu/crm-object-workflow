<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRunStep extends Model
{
    public $timestamps = false;

    protected $fillable = ['workflow_run_id', 'node_id', 'node_type', 'branch', 'status', 'output', 'error', 'executed_at'];

    protected $casts = ['output' => 'array', 'executed_at' => 'datetime'];
}
