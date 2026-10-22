<?php

namespace App\Workflows\Actions;

use App\Models\Lead;

class LogActivity implements NodeAction
{
    public function handle(Lead $lead, array $config, bool $dry): array
    {
        $data = ['type' => $config['activity_type'] ?? 'note', 'subject' => $config['subject'] ?? 'Workflow activity'];
        if (!$dry) $lead->activities()->firstOrCreate($data); // relation assumed: Lead::activities()
        return $data;
    }
}
