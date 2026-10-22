<?php

namespace App\Workflows\Actions;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;

class CreateAccountOpportunity implements NodeAction
{
    public function handle(Lead $lead, array $config, bool $dry): array
    {
        $withContact = ($config['create_contact'] ?? '1') === '1';
        if ($dry) return ['account' => true, 'opportunity' => true, 'contact' => $withContact];

        return DB::transaction(function () use ($lead, $withContact) {
            // firstOrCreate keyed on lead_id keeps retries from creating duplicates.
            $account = Account::firstOrCreate(['lead_id' => $lead->id], ['name' => $lead->company ?: $lead->name, 'owner_id' => $lead->owner_id]);
            $opp = Opportunity::firstOrCreate(['lead_id' => $lead->id], ['account_id' => $account->id, 'name' => ($lead->company ?: $lead->name) . ' – New deal', 'stage' => 'prospecting', 'owner_id' => $lead->owner_id]);
            $contact = $withContact
                ? Contact::firstOrCreate(['lead_id' => $lead->id], ['account_id' => $account->id, 'name' => $lead->name, 'email' => $lead->email])
                : null;

            return ['account_id' => $account->id, 'opportunity_id' => $opp->id, 'contact_id' => $contact?->id];
        });
    }
}
