<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Workflows\Graph;
use App\Workflows\WorkflowEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60];

    public function __construct(public int $workflowId, public int $leadId)
    {
        $this->onQueue(config('workflows.queue', 'workflows'));
    }

    public function handle(WorkflowEngine $engine): void
    {
        $wf = Workflow::find($this->workflowId);
        $lead = Lead::find($this->leadId);
        if (!$wf || !$lead || $wf->status !== 'active' || !$wf->published_graph) return;

        // One run per (workflow, lead, published version): retries reuse it, re-queued duplicates do nothing.
        $run = WorkflowRun::firstOrCreate(
            ['workflow_id' => $wf->id, 'subject_type' => 'lead', 'subject_id' => $lead->id, 'version' => $wf->published_version],
            ['status' => 'running', 'started_at' => now()]
        );
        if ($run->status === 'completed') return;

        $run->steps()->delete();
        $run->update(['status' => 'running', 'error' => null]);

        try {
            $engine->walk(new Graph($wf->published_graph), $lead, false, function (array $step) use ($run) {
                $run->steps()->create($step + ['executed_at' => now()]);
            });
            $run->update(['status' => 'completed', 'finished_at' => now()]);
        } catch (Throwable $e) {
            $run->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);
            throw $e; // let the queue retry / mark failed
        }
    }
}
