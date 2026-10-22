<?php

namespace App\Workflows\Actions;

use App\Models\Lead;

class UpdateStage implements NodeAction
{
    public function handle(Lead $lead, array $config, bool $dry): array
    {
        $stage = $config['stage'] ?? 'qualified';
        if (!$dry) {
            // In Salesforce, a converted lead cannot be demoted back to previous stages
            if ($lead->stage !== 'converted' && $lead->status !== 'converted') {
                $lead->update([
                    'stage' => $stage,
                    'status' => $stage,
                ]);
            }
        }
        return ['stage' => $stage];
    }
}
