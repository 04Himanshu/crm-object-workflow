<?php

namespace App\Workflows\Actions;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class AssignLead implements NodeAction
{
    public function handle(Lead $lead, array $config, bool $dry): array
    {
        if (($config['strategy'] ?? 'round_robin') === 'owner') {
            $owner = (int) ($config['owner_id'] ?? 0);
        } else {
            $ids = User::where('is_sales_rep', true)->orderBy('id')->pluck('id'); // adjust to your rep definition
            $owner = $ids->isEmpty() ? 0 : ($dry ? $ids->first() : $ids[Cache::increment('workflow:round_robin') % $ids->count()]);
        }

        if (!$owner && !$dry) throw new RuntimeException('No sales rep available for assignment.');
        if (!$dry && $lead->owner_id !== $owner) $lead->update(['owner_id' => $owner]);

        return ['owner_id' => $owner ?: null];
    }
}
