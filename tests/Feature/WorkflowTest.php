<?php

namespace Tests\Feature;

use App\Jobs\RunWorkflowJob;
use App\Models\Lead;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Workflows\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_access_workflows_dashboard_without_auth(): void
    {
        $response = $this->get('/workflows');
        $response->assertStatus(200);
        $response->assertSee('Lead lifecycle');
    }

    public function test_user_can_view_workflow_builder_without_auth(): void
    {
        $wf = Workflow::first();
        $response = $this->get("/workflows/{$wf->id}");
        $response->assertStatus(200);
        $response->assertSee('wf-app');
    }

    public function test_user_can_create_workflow_without_auth(): void
    {
        $response = $this->post('/workflows', ['name' => 'New Inbound Flow']);
        $response->assertRedirect();
        $this->assertDatabaseHas('workflows', ['name' => 'New Inbound Flow']);
    }

    public function test_workflow_simulation(): void
    {
        $wf = Workflow::first();

        // Qualified lead simulation (score 85 >= 60 -> yes branch)
        $response = $this->postJson("/workflows/{$wf->id}/simulate", [
            'sample' => [
                'score' => 85,
                'status' => 'new',
                'source' => 'web',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['steps']);
        $steps = $response->json('steps');
        $this->assertNotEmpty($steps);
        $decisionStep = collect($steps)->firstWhere('node_type', 'decision');
        $this->assertNotNull($decisionStep);
        $this->assertEquals('yes', $decisionStep['branch']);

        // Disqualified lead simulation (score 20 < 60 -> no branch)
        $responseDisqualified = $this->postJson("/workflows/{$wf->id}/simulate", [
            'sample' => [
                'score' => 20,
                'status' => 'new',
                'source' => 'web',
            ],
        ]);

        $responseDisqualified->assertStatus(200);
        $disqualifiedSteps = $responseDisqualified->json('steps');
        $disqualifiedDecision = collect($disqualifiedSteps)->firstWhere('node_type', 'decision');
        $this->assertNotNull($disqualifiedDecision);
        $this->assertEquals('no', $disqualifiedDecision['branch']);
    }

    public function test_workflow_save_preserves_edge_properties_and_branches(): void
    {
        $wf = Workflow::first();
        $graph = $wf->graph;

        $saveResponse = $this->putJson("/workflows/{$wf->id}", [
            'revision' => $wf->revision,
            'graph' => $graph,
        ]);

        $saveResponse->assertStatus(200);
        $wf->refresh();

        // Verify simulate still works after saving
        $simResponse = $this->postJson("/workflows/{$wf->id}/simulate", [
            'sample' => ['score' => 85, 'status' => 'new', 'source' => 'web'],
        ]);
        $simResponse->assertStatus(200);
    }

    public function test_workflow_with_native_shapes_circle_rect_diamond_and_polyline(): void
    {
        $wf = Workflow::first();

        $nativeShapeGraph = [
            'nodes' => [
                ['id' => 'n1', 'type' => 'circle', 'x' => 100, 'y' => 100, 'properties' => ['title' => 'Start Trigger']],
                ['id' => 'n2', 'type' => 'rect', 'x' => 300, 'y' => 100, 'properties' => ['title' => 'Assign Lead', 'action' => 'assign', 'config' => ['strategy' => 'round_robin']]],
                ['id' => 'n3', 'type' => 'diamond', 'x' => 500, 'y' => 100, 'properties' => ['title' => 'Qualify?', 'config' => ['field' => 'score', 'op' => '>=', 'value' => '60']]],
                ['id' => 'n4', 'type' => 'rect', 'x' => 700, 'y' => 50, 'properties' => ['title' => 'Convert', 'action' => 'convert', 'config' => []]],
                ['id' => 'n5', 'type' => 'rect', 'x' => 700, 'y' => 150, 'properties' => ['title' => 'Disqualify', 'action' => 'disqualify', 'config' => ['reason' => 'not_a_fit']]],
            ],
            'edges' => [
                ['id' => 'e1', 'type' => 'polyline', 'sourceNodeId' => 'n1', 'targetNodeId' => 'n2', 'properties' => ['branch' => '']],
                ['id' => 'e2', 'type' => 'polyline', 'sourceNodeId' => 'n2', 'targetNodeId' => 'n3', 'properties' => ['branch' => '']],
                ['id' => 'e3', 'type' => 'polyline', 'sourceNodeId' => 'n3', 'targetNodeId' => 'n4', 'properties' => ['branch' => 'yes'], 'text' => ['value' => 'Yes (qualified)']],
                ['id' => 'e4', 'type' => 'polyline', 'sourceNodeId' => 'n3', 'targetNodeId' => 'n5', 'properties' => ['branch' => 'no'], 'text' => ['value' => 'No (disqualified)']],
            ],
        ];

        $saveResponse = $this->putJson("/workflows/{$wf->id}", [
            'revision' => $wf->revision,
            'graph' => $nativeShapeGraph,
        ]);

        $saveResponse->assertStatus(200);

        // Simulate qualified
        $simResponse = $this->postJson("/workflows/{$wf->id}/simulate", [
            'sample' => ['score' => 80, 'status' => 'new', 'source' => 'web'],
        ]);
        $simResponse->assertStatus(200);
        $steps = $simResponse->json('steps');
        $this->assertCount(4, $steps);
        $this->assertEquals('trigger', $steps[0]['node_type']);
        $this->assertEquals('assign', $steps[1]['node_type']);
        $this->assertEquals('decision', $steps[2]['node_type']);
        $this->assertEquals('convert', $steps[3]['node_type']);
    }

    public function test_workflow_engine_executes_job_for_lead(): void
    {
        $wf = Workflow::where('status', 'active')->first();

        // Create a new lead which triggers the observer
        $lead = Lead::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'company' => 'Acme Test',
            'score' => 85,
            'source' => 'web',
        ]);

        // Execute the job
        $job = new RunWorkflowJob($wf->id, $lead->id);
        $job->handle(new WorkflowEngine());

        // Check workflow run record was created
        $run = WorkflowRun::where('workflow_id', $wf->id)
            ->where('subject_id', $lead->id)
            ->first();

        $this->assertNotNull($run);
        $this->assertEquals('completed', $run->status);

        // Check that lead was converted and assigned
        $lead->refresh();
        $this->assertNotNull($lead->owner_id);
        $this->assertEquals('converted', $lead->status);
        $this->assertNotNull($lead->converted_at);

        // Check account and opportunity were created
        $this->assertNotNull($lead->account);
        $this->assertNotNull($lead->opportunity);
    }

    public function test_workflow_interpolates_dynamic_lead_tokens_in_actions(): void
    {
        $lead = Lead::create([
            'name' => 'Alice Wonderland',
            'company' => 'WonderTech',
            'score' => 90,
            'source' => 'partner',
        ]);

        $graph = [
            'nodes' => [
                ['id' => 'n1', 'type' => 'trigger', 'x' => 100, 'y' => 100],
                [
                    'id' => 'n2',
                    'type' => 'activity',
                    'x' => 300,
                    'y' => 100,
                    'properties' => [
                        'config' => [
                            'activity_type' => 'call',
                            'subject' => 'Follow up with {{lead.name}} at {{lead.company}}',
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'sourceNodeId' => 'n1', 'targetNodeId' => 'n2'],
            ],
        ];

        $engine = new WorkflowEngine();
        $steps = $engine->walk(new \App\Workflows\Graph($graph), $lead, false);

        $this->assertCount(2, $steps);
        $this->assertEquals('completed', $steps[1]['status']);
        $this->assertEquals('Follow up with Alice Wonderland at WonderTech', $steps[1]['output']['subject']);

        $activity = $lead->activities()->latest('id')->first();
        $this->assertNotNull($activity);
        $this->assertEquals('Follow up with Alice Wonderland at WonderTech', $activity->subject);
    }
}

