<?php

namespace App\Workflows\Actions;

use App\Models\Lead;

class ConvertLead implements NodeAction
{
    public function handle(Lead $lead, array $config, bool $dry): array
    {
        if (!$dry && $lead->status !== 'converted') {
            $lead->update(['status' => 'converted', 'stage' => 'converted', 'converted_at' => now()]);
        }
        return ['status' => 'converted'];
    }
}
