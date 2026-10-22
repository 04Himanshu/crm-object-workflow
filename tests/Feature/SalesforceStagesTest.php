<?php

namespace Tests\Feature;

use App\Jobs\RunWorkflowJob;
use App\Models\Lead;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Workflows\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesforceStagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_salesforce_lead_happy_path_traversal(): void
    {
        $wf = Workflow::first();

        // Qualified lead simulation (score 85)
        $response = $this->postJson("/workflows/{$wf->id}/simulate", [
            'sample' => [
                'name' => 'Sarah Jenkins',
                'email' => 'sarah@cloudscale.io',
                'company' => 'CloudScale Technologies',
                'score' => 85,
                'status' => 'new',
                'stage' => 'new',
                'source' => 'web',
            ],
        ]);

        $response->assertStatus(200);
        $steps = $response->json('steps');
        $this->assertNotEmpty($steps);

        $nodeTypes = array_column($steps, 'node_type');

        // Verify traversal order matches Salesforce lead lifecycle:
        // 1. Trigger (New) -> 2. Assign -> 3. Activity (Outreach) -> 4. Stage (Contacted)
        // -> 5. Activity (Discovery) -> 6. Stage (Working) -> 7. Decision (MQL Criteria)
        // -> 8. Stage (Qualified) -> 9. Convert -> 10. Account
        $this->assertContains('trigger', $nodeTypes);
        $this->assertContains('assign', $nodeTypes);
        $this->assertContains('activity', $nodeTypes);
        $this->assertContains('stage', $nodeTypes);
        $this->assertContains('decision', $nodeTypes);
        $this->assertContains('convert', $nodeTypes);
        $this->assertContains('account', $nodeTypes);

        $stagesVisited = collect($steps)
            ->where('node_type', 'stage')
            ->pluck('output.stage')
            ->values()
            ->all();

        // Contacted -> Working -> Qualified
        $this->assertEquals(['contacted', 'working', 'qualified'], $stagesVisited);
    }

    public function test_salesforce_lead_disqualification_path_traversal(): void
    {
        $wf = Workflow::first();

        // Unfit lead simulation (score 20)
        $response = $this->postJson("/workflows/{$wf->id}/simulate", [
            'sample' => [
                'name' => 'Dave Miller',
                'email' => 'dave@daveshardware.com',
                'company' => "Dave's Hardware",
                'score' => 20,
                'status' => 'new',
                'stage' => 'new',
                'source' => 'web',
            ],
        ]);

        $response->assertStatus(200);
        $steps = $response->json('steps');
        $nodeTypes = array_column($steps, 'node_type');

        $this->assertContains('trigger', $nodeTypes);
        $this->assertContains('assign', $nodeTypes);
        $this->assertContains('activity', $nodeTypes);
        $this->assertContains('stage', $nodeTypes);
        $this->assertContains('decision', $nodeTypes);
        $this->assertContains('disqualify', $nodeTypes);
        $this->assertContains('archive', $nodeTypes);

        $stagesVisited = collect($steps)
            ->where('node_type', 'stage')
            ->pluck('output.stage')
            ->values()
            ->all();

        // Contacted -> Working -> Disqualified
        $this->assertEquals(['contacted', 'working', 'disqualified'], $stagesVisited);
    }

    public function test_live_execution_transitions_lead_through_salesforce_stages(): void
    {
        $wf = Workflow::where('status', 'active')->first();

        $lead = Lead::create([
            'name' => 'Enterprise Prospect',
            'email' => 'enterprise@bigcorp.com',
            'company' => 'BigCorp Global',
            'score' => 95,
            'source' => 'web',
            'status' => 'new',
            'stage' => 'new',
        ]);

        $job = new RunWorkflowJob($wf->id, $lead->id);
        $job->handle(new WorkflowEngine());

        $run = WorkflowRun::where('workflow_id', $wf->id)->where('subject_id', $lead->id)->first();
        $this->assertNotNull($run);
        $this->assertEquals('completed', $run->status);

        $lead->refresh();

        // Final stage & status is converted
        $this->assertEquals('converted', $lead->stage);
        $this->assertEquals('converted', $lead->status);
        $this->assertNotNull($lead->converted_at);
        $this->assertNotNull($lead->owner_id);
        $this->assertNotNull($lead->account);
        $this->assertNotNull($lead->opportunity);
    }
}
