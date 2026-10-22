<?php

namespace App\Workflows\Actions;

use App\Models\Lead;

interface NodeAction
{
    /**
     * Must be idempotent: a queued job can retry and walk the graph again.
     * When $dry is true, change nothing and just return what would happen.
     */
    public function handle(Lead $lead, array $config, bool $dry): array;
}
