<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowRunStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'is_sales_rep' => false,
            ]
        );

        $rep1 = User::firstOrCreate(
            ['email' => 'sarah@example.com'],
            [
                'name' => 'Sarah Connor',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'is_sales_rep' => true,
            ]
        );

        $rep2 = User::firstOrCreate(
            ['email' => 'alex@example.com'],
            [
                'name' => 'Alex Murphy',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'is_sales_rep' => true,
            ]
        );

        // Helper to generate full Salesforce 5-stage lifecycle BPMN graph with clean orthogonal polyline coordinates
        $makeGraph = function (array $cfg) {
            $nodes = [
                // Stage 1: New
                [
                    'id' => 'n1',
                    'type' => 'trigger',
                    'x' => 180,
                    'y' => 160,
                    'properties' => [
                        'title' => $cfg['n1_title'] ?? '1. Lead Inbound Created (New)',
                        'desc' => $cfg['n1_desc'] ?? 'Captures incoming lead from Web-to-Lead, API or Ads.',
                        'meta' => 'Stage: 1. New',
                        'action' => '',
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'n2',
                    'type' => 'assign',
                    'x' => 480,
                    'y' => 160,
                    'properties' => [
                        'title' => $cfg['n2_title'] ?? 'Route SDR Assignment',
                        'desc' => $cfg['n2_desc'] ?? 'Routes lead to SDR queue via territory round-robin rules.',
                        'meta' => 'Owner: round-robin (SDR)',
                        'action' => 'assign',
                        'config' => ['strategy' => 'round_robin', 'owner_id' => ''],
                    ],
                ],
                [
                    'id' => 'n3',
                    'type' => 'activity',
                    'x' => 780,
                    'y' => 160,
                    'properties' => [
                        'title' => $cfg['n3_title'] ?? 'Initial Outreach Call',
                        'desc' => $cfg['n3_desc'] ?? 'Salesperson initiates priority discovery outreach call or email.',
                        'meta' => $cfg['n3_meta'] ?? 'Type: call · 15m SLA',
                        'action' => 'activity',
                        'config' => ['activity_type' => $cfg['n3_type'] ?? 'call', 'subject' => $cfg['n3_subject'] ?? 'Initial Discovery Call (SLA 15m)'],
                    ],
                ],
                // Stage 2: Contacted
                [
                    'id' => 'n4',
                    'type' => 'stage',
                    'x' => 1080,
                    'y' => 160,
                    'properties' => [
                        'title' => $cfg['n4_title'] ?? '2. Set Stage: Contacted',
                        'desc' => $cfg['n4_desc'] ?? 'Salesperson calls or emails the lead.',
                        'meta' => 'Stage: 2. Contacted',
                        'action' => 'stage',
                        'config' => ['stage' => $cfg['n4_stage'] ?? 'contacted'],
                    ],
                ],

                // Stage 3: Working (Requirements Discovery)
                [
                    'id' => 'n5',
                    'type' => 'activity',
                    'x' => 1080,
                    'y' => 380,
                    'properties' => [
                        'title' => $cfg['n5_act_title'] ?? 'Requirements Discovery',
                        'desc' => $cfg['n5_act_desc'] ?? 'Sales team follows up and gathers requirements.',
                        'meta' => 'Type: meeting · Discovery',
                        'action' => 'activity',
                        'config' => ['activity_type' => 'meeting', 'subject' => 'Requirements Gathering & BANT Discovery'],
                    ],
                ],
                [
                    'id' => 'n6',
                    'type' => 'stage',
                    'x' => 780,
                    'y' => 380,
                    'properties' => [
                        'title' => $cfg['n6_stage_title'] ?? '3. Set Stage: Working',
                        'desc' => $cfg['n6_stage_desc'] ?? 'Sales team actively works the lead and qualifies criteria.',
                        'meta' => 'Stage: 3. Working',
                        'action' => 'stage',
                        'config' => ['stage' => $cfg['n6_stage'] ?? 'working'],
                    ],
                ],

                // Decision Gateway: Evaluate MQL Criteria
                [
                    'id' => 'n7',
                    'type' => 'decision',
                    'x' => 480,
                    'y' => 380,
                    'properties' => [
                        'title' => $cfg['n7_title'] ?? $cfg['n5_title'] ?? 'Evaluate MQL Criteria',
                        'desc' => $cfg['n7_desc'] ?? $cfg['n5_desc'] ?? 'Validates BANT criteria and buying potential (Score ≥ 60).',
                        'meta' => 'Rule: score ≥ ' . ($cfg['n7_score'] ?? $cfg['n5_score'] ?? '60'),
                        'action' => '',
                        'config' => ['field' => 'score', 'op' => '>=', 'value' => (string) ($cfg['n7_score'] ?? $cfg['n5_score'] ?? '60')],
                    ],
                ],

                // Stage 4: Qualified & Stage 5: Converted (Branch YES)
                [
                    'id' => 'n8',
                    'type' => 'stage',
                    'x' => 180,
                    'y' => 380,
                    'properties' => [
                        'title' => $cfg['n8_stage_title'] ?? '4. Set Stage: Qualified',
                        'desc' => $cfg['n8_stage_desc'] ?? 'Lead meets criteria and has genuine buying potential.',
                        'meta' => 'Stage: 4. Qualified',
                        'action' => 'stage',
                        'config' => ['stage' => $cfg['n8_stage'] ?? 'qualified'],
                    ],
                ],
                [
                    'id' => 'n9',
                    'type' => 'convert',
                    'x' => 180,
                    'y' => 580,
                    'properties' => [
                        'title' => $cfg['n9_title'] ?? $cfg['n6_title'] ?? '5. Convert Qualified Lead',
                        'desc' => $cfg['n9_desc'] ?? $cfg['n6_desc'] ?? 'Lead becomes an Account and Contact, with an optional Opportunity.',
                        'meta' => 'Status: converted (MQL)',
                        'action' => 'convert',
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'n10',
                    'type' => 'account',
                    'x' => 180,
                    'y' => 760,
                    'properties' => [
                        'title' => $cfg['n10_title'] ?? $cfg['n7_title'] ?? 'Create Deal & Account',
                        'desc' => $cfg['n10_desc'] ?? $cfg['n7_desc'] ?? 'Generates Account, B2B Contact and Opportunity.',
                        'meta' => 'Account, Contact, Deal',
                        'action' => 'account',
                        'config' => ['create_contact' => '1'],
                    ],
                ],

                // Disqualification & Nurturing (Branch NO)
                [
                    'id' => 'n11',
                    'type' => 'stage',
                    'x' => 780,
                    'y' => 580,
                    'properties' => [
                        'title' => $cfg['n11_title'] ?? 'Set Stage: Disqualified',
                        'desc' => $cfg['n11_desc'] ?? 'Rejected based on business criteria or unfit.',
                        'meta' => 'Stage: Disqualified',
                        'action' => 'stage',
                        'config' => ['stage' => $cfg['n11_stage'] ?? 'disqualified'],
                    ],
                ],
                [
                    'id' => 'n12',
                    'type' => 'disqualify',
                    'x' => 1080,
                    'y' => 580,
                    'properties' => [
                        'title' => $cfg['n12_title'] ?? $cfg['n8_title'] ?? 'Disqualify Unfit Lead',
                        'desc' => $cfg['n12_desc'] ?? $cfg['n8_desc'] ?? 'Closed - Disqualified with audited reason.',
                        'meta' => 'Reason: ' . ($cfg['n12_reason_label'] ?? $cfg['n8_reason_label'] ?? 'Not a Fit'),
                        'action' => 'disqualify',
                        'config' => ['reason' => $cfg['n12_reason'] ?? $cfg['n8_reason'] ?? 'not_a_fit'],
                    ],
                ],
                [
                    'id' => 'n13',
                    'type' => 'archive',
                    'x' => 1080,
                    'y' => 760,
                    'properties' => [
                        'title' => $cfg['n13_title'] ?? $cfg['n9_title'] ?? 'Route to Nurture Archive',
                        'desc' => $cfg['n13_desc'] ?? $cfg['n9_desc'] ?? 'Moves to cold nurture queue with 12-month retention.',
                        'meta' => 'Nurture · Keep 12 mos',
                        'action' => 'archive',
                        'config' => ['retention_months' => $cfg['n13_retention'] ?? $cfg['n9_retention'] ?? 12],
                    ],
                ],
            ];

            $edges = [
                // Stage 1 -> SDR -> Outreach -> Stage 2: Contacted
                ['id' => 'e1', 'type' => 'polyline', 'sourceNodeId' => 'n1', 'targetNodeId' => 'n2', 'sourceAnchorId' => 'n1_1', 'targetAnchorId' => 'n2_3', 'startPoint' => ['x' => 228, 'y' => 160], 'endPoint' => ['x' => 360, 'y' => 160], 'properties' => ['branch' => '']],
                ['id' => 'e2', 'type' => 'polyline', 'sourceNodeId' => 'n2', 'targetNodeId' => 'n3', 'sourceAnchorId' => 'n2_1', 'targetAnchorId' => 'n3_3', 'startPoint' => ['x' => 600, 'y' => 160], 'endPoint' => ['x' => 660, 'y' => 160], 'properties' => ['branch' => '']],
                ['id' => 'e3', 'type' => 'polyline', 'sourceNodeId' => 'n3', 'targetNodeId' => 'n4', 'sourceAnchorId' => 'n3_1', 'targetAnchorId' => 'n4_3', 'startPoint' => ['x' => 900, 'y' => 160], 'endPoint' => ['x' => 960, 'y' => 160], 'properties' => ['branch' => '']],

                // Stage 2: Contacted -> Discovery -> Stage 3: Working -> Evaluation Decision
                ['id' => 'e4', 'type' => 'polyline', 'sourceNodeId' => 'n4', 'targetNodeId' => 'n5', 'sourceAnchorId' => 'n4_2', 'targetAnchorId' => 'n5_0', 'startPoint' => ['x' => 1080, 'y' => 208], 'endPoint' => ['x' => 1080, 'y' => 332], 'properties' => ['branch' => '']],
                ['id' => 'e5', 'type' => 'polyline', 'sourceNodeId' => 'n5', 'targetNodeId' => 'n6', 'sourceAnchorId' => 'n5_3', 'targetAnchorId' => 'n6_1', 'startPoint' => ['x' => 960, 'y' => 380], 'endPoint' => ['x' => 900, 'y' => 380], 'properties' => ['branch' => '']],
                ['id' => 'e6', 'type' => 'polyline', 'sourceNodeId' => 'n6', 'targetNodeId' => 'n7', 'sourceAnchorId' => 'n6_3', 'targetAnchorId' => 'n7_1', 'startPoint' => ['x' => 660, 'y' => 380], 'endPoint' => ['x' => 600, 'y' => 380], 'properties' => ['branch' => '']],

                // Branch YES -> Stage 4: Qualified -> Stage 5: Converted -> Create Account & Deal
                ['id' => 'e7', 'type' => 'polyline', 'sourceNodeId' => 'n7', 'targetNodeId' => 'n8', 'sourceAnchorId' => 'n7_3', 'targetAnchorId' => 'n8_1', 'startPoint' => ['x' => 360, 'y' => 380], 'endPoint' => ['x' => 300, 'y' => 380], 'properties' => ['branch' => 'yes'], 'text' => ['value' => '✓ Yes (qualified)']],
                ['id' => 'e8', 'type' => 'polyline', 'sourceNodeId' => 'n8', 'targetNodeId' => 'n9', 'sourceAnchorId' => 'n8_2', 'targetAnchorId' => 'n9_0', 'startPoint' => ['x' => 180, 'y' => 428], 'endPoint' => ['x' => 180, 'y' => 532], 'properties' => ['branch' => '']],
                ['id' => 'e9', 'type' => 'polyline', 'sourceNodeId' => 'n9', 'targetNodeId' => 'n10', 'sourceAnchorId' => 'n9_2', 'targetAnchorId' => 'n10_0', 'startPoint' => ['x' => 180, 'y' => 628], 'endPoint' => ['x' => 180, 'y' => 712], 'properties' => ['branch' => '']],

                // Branch NO -> Set Disqualified -> Disqualify Reason -> Nurture Archive
                ['id' => 'e10', 'type' => 'polyline', 'sourceNodeId' => 'n7', 'targetNodeId' => 'n11', 'sourceAnchorId' => 'n7_2', 'targetAnchorId' => 'n11_0', 'startPoint' => ['x' => 480, 'y' => 455], 'endPoint' => ['x' => 780, 'y' => 532], 'properties' => ['branch' => 'no'], 'text' => ['value' => '✕ No (disqualified)']],
                ['id' => 'e11', 'type' => 'polyline', 'sourceNodeId' => 'n11', 'targetNodeId' => 'n12', 'sourceAnchorId' => 'n11_1', 'targetAnchorId' => 'n12_3', 'startPoint' => ['x' => 900, 'y' => 580], 'endPoint' => ['x' => 960, 'y' => 580], 'properties' => ['branch' => '']],
                ['id' => 'e12', 'type' => 'polyline', 'sourceNodeId' => 'n12', 'targetNodeId' => 'n13', 'sourceAnchorId' => 'n12_2', 'targetAnchorId' => 'n13_0', 'startPoint' => ['x' => 1080, 'y' => 628], 'endPoint' => ['x' => 1080, 'y' => 712], 'properties' => ['branch' => '']],
            ];

            return ['nodes' => $nodes, 'edges' => $edges];
        };

        // 2. Seed 4 Workflows (Sample Lead 1, Sample Lead 2, Sample Lead 3, Sample Lead 4)
        $g1 = $makeGraph([
            'n1_title' => '1. Lead Inbound Created (New)',
            'n1_desc'  => 'Captures incoming lead from Web-to-Lead, API or Ads.',
            'n2_title' => 'Route SDR Assignment',
            'n3_title' => 'Initial Outreach Call',
            'n4_title' => '2. Set Stage: Contacted',
            'n5_act_title' => 'Requirements Discovery',
            'n6_stage_title' => '3. Set Stage: Working',
            'n7_title' => 'Evaluate MQL Criteria',
            'n7_score' => 60,
            'n8_stage_title' => '4. Set Stage: Qualified',
            'n9_title' => '5. Convert Qualified Lead',
            'n10_title' => 'Create Deal & Account',
            'n11_title' => 'Set Stage: Disqualified',
            'n12_title' => 'Disqualify Unfit Lead',
            'n13_title' => 'Route to Nurture Archive',
        ]);
        $wf1 = Workflow::create([
            'name' => 'Sample Lead 1 · Salesforce Lead Lifecycle & Conversion (Lead lifecycle)',
            'target_object' => 'lead',
            'status' => 'active',
            'graph' => $g1,
            'revision' => 12,
            'published_graph' => $g1,
            'published_version' => 1,
            'published_at' => now()->subMinutes(12),
            'created_by' => $admin->id,
        ]);

        $g2 = $makeGraph([
            'n1_title' => 'Trial Signup Created',
            'n1_desc'  => 'Self-service SaaS trial account created via public web app.',
            'n2_title' => 'Route Inbound SDR',
            'n3_title' => 'Send Onboarding Sequence',
            'n3_type'  => 'email',
            'n3_subject' => 'Product Tour & Getting Started Guide',
            'n3_meta'  => 'Type: email · Auto-sequence',
            'n4_title' => 'Update Status: Active Trial',
            'n5_title' => 'Product Usage Fit Score',
            'n5_score' => 50,
            'n6_title' => 'Convert to Paid Customer',
            'n7_title' => 'Create Account & Contract',
            'n8_title' => 'Mark Inactive Trial',
            'n8_reason' => 'no_response',
            'n8_reason_label' => 'Unresponsive',
            'n9_title' => 'Recycle to Retargeting',
        ]);
        $wf2 = Workflow::create([
            'name' => 'Sample Lead 2 · High-Velocity Inbound Routing',
            'target_object' => 'lead',
            'status' => 'active',
            'graph' => $g2,
            'revision' => 6,
            'published_graph' => $g2,
            'published_version' => 2,
            'published_at' => now()->subHours(2),
            'created_by' => $admin->id,
        ]);

        $g3 = $makeGraph([
            'n1_title' => 'Partner Lead Submission',
            'n1_desc'  => 'New co-sell deal registered through the Partner Portal.',
            'n2_title' => 'Assign Strategic AE',
            'n3_title' => 'Schedule Joint Intro Call',
            'n3_type'  => 'meeting',
            'n3_subject' => 'Partner Co-Sell Discovery Call',
            'n3_meta'  => 'Type: meeting · Joint call',
            'n4_title' => 'Update Status: Partner Qualified',
            'n5_title' => 'Validate Deal Potential',
            'n5_score' => 40,
            'n6_title' => 'Convert Partner Deal',
            'n7_title' => 'Create Account & Co-Sell Deal',
            'n8_title' => 'Reject Partner Lead',
            'n8_reason' => 'not_a_fit',
            'n8_reason_label' => 'Not a Fit',
            'n9_title' => 'Route to Partner Review',
        ]);
        $wf3 = Workflow::create([
            'name' => 'Sample Lead 3 · Partner Referral Fast-Track',
            'target_object' => 'lead',
            'status' => 'active',
            'graph' => $g3,
            'revision' => 4,
            'published_graph' => $g3,
            'published_version' => 1,
            'published_at' => now()->subHours(5),
            'created_by' => $admin->id,
        ]);

        $g4 = $makeGraph([
            'n1_title' => 'Re-engagement Click',
            'n1_desc'  => 'Cold lead clicks re-engagement newsletter or webinar invite.',
            'n2_title' => 'Assign Outbound BDR',
            'n3_title' => 'Log Re-engagement Call',
            'n3_type'  => 'call',
            'n3_subject' => 'Follow-up on Recent Activity',
            'n3_meta'  => 'Type: call · Re-outreach',
            'n4_title' => 'Update Status: Re-contacted',
            'n5_title' => 'Evaluate Renewal Interest',
            'n5_score' => 70,
            'n6_title' => 'Re-activate Pipeline Deal',
            'n7_title' => 'Sync Existing Account & Deal',
            'n8_title' => 'Mark Cold Disqualified',
            'n8_reason' => 'no_budget',
            'n8_reason_label' => 'No Budget',
            'n9_title' => 'Route to Long-Term Archive',
        ]);
        $wf4 = Workflow::create([
            'name' => 'Sample Lead 4 · Cold Outbound & Nurture Recycling',
            'target_object' => 'lead',
            'status' => 'active',
            'graph' => $g4,
            'revision' => 8,
            'published_graph' => $g4,
            'published_version' => 1,
            'published_at' => now()->subDays(1),
            'created_by' => $admin->id,
        ]);

        // 3. Seed realistic CRM Leads (Salesforce / Zoho style B2B leads)
        Lead::withoutEvents(function () use ($rep1, $rep2, $wf1, $wf2, $wf3, $wf4) {
            // Lead 1: Enterprise MQL -> Converted to Deal & Account
            $l1 = Lead::create([
                'name' => 'Sarah Jenkins',
                'email' => 'sarah.jenkins@cloudscale.io',
                'company' => 'CloudScale Technologies, Inc.',
                'score' => 92,
                'status' => 'converted',
                'stage' => 'qualified',
                'source' => 'web',
                'country' => 'US',
                'owner_id' => $rep1->id,
                'converted_at' => now()->subDays(2),
            ]);
            $acct1 = Account::create(['lead_id' => $l1->id, 'name' => $l1->company, 'owner_id' => $rep1->id]);
            Opportunity::create(['lead_id' => $l1->id, 'account_id' => $acct1->id, 'name' => $l1->company . ' – Enterprise Cloud Deal', 'stage' => 'prospecting', 'owner_id' => $rep1->id]);
            Contact::create(['lead_id' => $l1->id, 'account_id' => $acct1->id, 'name' => $l1->name, 'email' => $l1->email]);
            $l1->activities()->create(['type' => 'call', 'subject' => 'Initial Discovery Call (SLA 15m)']);

            // Lead 2: Logistics Director -> Converted to Deal & Account
            $l2 = Lead::create([
                'name' => 'Marcus Vance',
                'email' => 'm.vance@apexlogistics.com',
                'company' => 'Apex Global Logistics Ltd',
                'score' => 84,
                'status' => 'converted',
                'stage' => 'qualified',
                'source' => 'partner',
                'country' => 'UK',
                'owner_id' => $rep2->id,
                'converted_at' => now()->subDay(),
            ]);
            $acct2 = Account::create(['lead_id' => $l2->id, 'name' => $l2->company, 'owner_id' => $rep2->id]);
            Opportunity::create(['lead_id' => $l2->id, 'account_id' => $acct2->id, 'name' => $l2->company . ' – Fleet Workflow Automation', 'stage' => 'prospecting', 'owner_id' => $rep2->id]);
            Contact::create(['lead_id' => $l2->id, 'account_id' => $acct2->id, 'name' => $l2->name, 'email' => $l2->email]);
            $l2->activities()->create(['type' => 'call', 'subject' => 'Initial Discovery Call (SLA 15m)']);

            // Lead 3: FinTech Head of Platform -> Converted
            $l3 = Lead::create([
                'name' => 'Elena Rostova',
                'email' => 'elena@fintechcore.de',
                'company' => 'FinTech Core Solutions GmbH',
                'score' => 78,
                'status' => 'converted',
                'stage' => 'qualified',
                'source' => 'inbound',
                'country' => 'DE',
                'owner_id' => $rep1->id,
                'converted_at' => now()->subHours(8),
            ]);
            $acct3 = Account::create(['lead_id' => $l3->id, 'name' => $l3->company, 'owner_id' => $rep1->id]);
            Opportunity::create(['lead_id' => $l3->id, 'account_id' => $acct3->id, 'name' => $l3->company . ' – Core API Integration', 'stage' => 'prospecting', 'owner_id' => $rep1->id]);
            Contact::create(['lead_id' => $l3->id, 'account_id' => $acct3->id, 'name' => $l3->name, 'email' => $l3->email]);
            $l3->activities()->create(['type' => 'call', 'subject' => 'Initial Discovery Call (SLA 15m)']);

            // Lead 4: Cold Small Business -> Disqualified (not a fit)
            $l4 = Lead::create([
                'name' => 'David Miller',
                'email' => 'dave@daveshardware.com',
                'company' => "Dave's Hardware & Tools",
                'score' => 32,
                'status' => 'disqualified',
                'stage' => 'unqualified',
                'source' => 'web',
                'country' => 'US',
                'owner_id' => $rep2->id,
                'disqualified_reason' => 'not_a_fit',
                'archived_at' => now()->subDays(5),
                'purge_after' => now()->addMonths(12),
            ]);
            $l4->activities()->create(['type' => 'note', 'subject' => 'Disqualified: Solo retail shop, no B2B requirement']);

            // Lead 5: Student Researcher -> Disqualified (no budget)
            $l5 = Lead::create([
                'name' => 'Chloe Bennett',
                'email' => 'chloe@edutest.org',
                'company' => 'EduPortal Research Lab',
                'score' => 18,
                'status' => 'disqualified',
                'stage' => 'unqualified',
                'source' => 'web',
                'country' => 'US',
                'disqualified_reason' => 'no_budget',
                'archived_at' => now()->subDays(3),
                'purge_after' => now()->addMonths(12),
            ]);

            // Lead 6: Active New Inbound Lead ready for immediate live workflow testing
            $l6 = Lead::create([
                'name' => 'Alexander Wright',
                'email' => 'a.wright@cybergrid.com',
                'company' => 'CyberGrid Systems',
                'score' => 88,
                'status' => 'new',
                'stage' => 'new',
                'source' => 'web',
                'country' => 'US',
            ]);

            // Helper to seed workflow run with steps
            $recordRun = function ($wf, $lead, $status = 'completed', $branch = 'yes') {
                $run = WorkflowRun::create([
                    'workflow_id' => $wf->id,
                    'version' => $wf->published_version ?? 1,
                    'subject_type' => 'lead',
                    'subject_id' => $lead->id,
                    'status' => $status,
                    'started_at' => now()->subMinutes(rand(10, 240)),
                    'finished_at' => now()->subMinutes(rand(1, 9)),
                ]);

                $nodeTypes = ['trigger', 'assign', 'activity', 'stage', 'decision', ($branch === 'yes' ? 'convert' : 'disqualify'), ($branch === 'yes' ? 'account' : 'archive')];
                foreach ($nodeTypes as $idx => $t) {
                    WorkflowRunStep::create([
                        'workflow_run_id' => $run->id,
                        'node_id' => 'n' . ($idx + 1),
                        'node_type' => $t,
                        'branch' => ($t === 'decision' ? $branch : null),
                        'status' => 'completed',
                        'executed_at' => now(),
                    ]);
                }
            };

            // Seed runs across workflows so the RUNS column displays real activity
            $recordRun($wf1, $l1, 'completed', 'yes');
            $recordRun($wf1, $l2, 'completed', 'yes');
            $recordRun($wf1, $l3, 'completed', 'yes');
            $recordRun($wf1, $l4, 'completed', 'no');

            $recordRun($wf2, $l1, 'completed', 'yes');
            $recordRun($wf2, $l2, 'completed', 'yes');
            $recordRun($wf2, $l5, 'completed', 'no');

            $recordRun($wf3, $l2, 'completed', 'yes');
            $recordRun($wf3, $l3, 'completed', 'yes');

            $recordRun($wf4, $l4, 'completed', 'no');
        });
    }
}
