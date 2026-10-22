<?php

namespace App\Workflows\Actions;

use App\Models\Lead;

class DisqualifyLead implements NodeAction
{
    public function handle(Lead $lead, array $config, bool $dry): array
    {
        $reason = $config['reason'] ?? 'not_a_fit';
        if (!$dry) {
            $lead->update(['status' => 'disqualified', 'stage' => 'disqualified', 'disqualified_reason' => $reason]);
        }
        return ['status' => 'disqualified', 'reason' => $reason];
    }
}
