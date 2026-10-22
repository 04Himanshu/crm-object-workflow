<?php

namespace App\Http\Controllers;

use App\Models\WorkflowRun;

class RunController extends Controller
{
    public function index()
    {
        $runs = WorkflowRun::with(['workflow', 'lead', 'steps'])->latest()->get();
        return view('runs.index', compact('runs'));
    }

    public function show(WorkflowRun $run)
    {
        $run->load(['workflow', 'lead', 'steps']);
        return view('runs.show', compact('run'));
    }
}
