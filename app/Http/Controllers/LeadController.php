<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class LeadController extends Controller
{
    public function index()
    {
        $leads = Lead::with(['owner', 'activities', 'account', 'opportunity', 'contact'])->latest()->get();
        $reps = User::where('is_sales_rep', true)->get();
        return view('leads.index', compact('leads', 'reps'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email|max:120',
            'company' => 'nullable|string|max:120',
            'score' => 'required|integer|min:0|max:100',
            'source' => 'nullable|string|max:60',
            'country' => 'nullable|string|max:60',
        ]);

        $lead = Lead::create($data + ['status' => 'new', 'stage' => 'new']);

        return redirect()->route('leads.index')->with('success', "Lead '{$lead->name}' created! Workflow trigger job queued.");
    }

    public function processQueue()
    {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--queue' => 'workflows,default',
        ]);

        $output = Artisan::output();
        return redirect()->route('leads.index')->with('success', 'Queue processed successfully! ' . ($output ? trim($output) : 'No pending jobs.'));
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Lead removed.');
    }
}
