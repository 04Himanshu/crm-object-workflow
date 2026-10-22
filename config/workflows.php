<?php

use App\Workflows\Actions;

return [
    // Node type => action class. Add a class here + an entry in resources/js/workflow/nodes.js to add a new node.
    'actions' => [
        'assign'     => Actions\AssignLead::class,
        'activity'   => Actions\LogActivity::class,
        'stage'      => Actions\UpdateStage::class,
        'convert'    => Actions\ConvertLead::class,
        'account'    => Actions\CreateAccountOpportunity::class,
        'disqualify' => Actions\DisqualifyLead::class,
        'archive'    => Actions\ArchiveLead::class,
    ],

    // Node types that must not have outgoing connections.
    'terminal' => ['archive'],

    'max_steps' => 50,
    'queue'     => 'workflows',
];
