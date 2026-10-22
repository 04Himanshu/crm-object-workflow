<?php

namespace App\Workflows\Actions;

use App\Models\Lead;

class ArchiveLead implements NodeAction
{
    public function handle(Lead $lead, array $config, bool $dry): array
    {
        $months = max(1, (int) ($config['retention_months'] ?? 12));
        if (!$dry && !$lead->archived_at) {
            $updates = [
                'archived_at' => now(),
                'purge_after' => now()->addMonths($months),
            ];
            if (!in_array($lead->status, ['converted', 'disqualified'], true)) {
                $updates['status'] = 'nurturing';
                $updates['stage'] = 'nurturing';
            }
            $lead->update($updates);
        }
        return ['archived' => true, 'retention_months' => $months];
    }
}
