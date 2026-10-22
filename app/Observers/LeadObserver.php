<?php

namespace App\Observers;

use App\Jobs\RunWorkflowJob;
use App\Models\Lead;
use App\Models\Workflow;

class LeadObserver
{
    /** Fire only after the surrounding DB transaction commits, so the job always finds the lead. */
    public bool $afterCommit = true;

    public function created(Lead $lead): void
    {
        Workflow::query()
            ->where('target_object', 'lead')
            ->where('status', 'active')
            ->whereNotNull('published_graph')
            ->pluck('id')
            ->each(fn ($id) => RunWorkflowJob::dispatch($id, $lead->id));
    }
}
