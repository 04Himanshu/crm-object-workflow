@extends('layouts.app')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Leads & Execution Testing</h1>
            <p class="page-subtitle">Create leads with various scores to test active workflows, round-robin assignments, and automated CRM record creation.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <form method="POST" action="{{ route('leads.processQueue') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn" title="Run queue:work --stop-when-empty">
                    &#x2699; Run Queue Worker Now
                </button>
            </form>
            <button class="btn btn-primary" onclick="document.getElementById('create-lead-modal').style.display='flex'">
                + New Lead
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-3" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div>
                <div class="stat-val">{{ $leads->count() }}</div>
                <div class="stat-lbl">Total Leads</div>
            </div>
            <div class="stat-icon stat-icon-pri">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-val">{{ $leads->where('status', 'converted')->count() }}</div>
                <div class="stat-lbl">Converted to Deals</div>
            </div>
            <div class="stat-icon stat-icon-success">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-val">{{ $leads->where('status', 'disqualified')->count() }}</div>
                <div class="stat-lbl">Disqualified</div>
            </div>
            <div class="stat-icon stat-icon-warning">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Lead Name</th>
                    <th>Company</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th>Stage</th>
                    <th>Owner (Rep)</th>
                    <th>Generated Records</th>
                    <th>Activities</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--ink);">{{ $lead->name }}</div>
                            <div style="font-size: 12px; color: var(--mute);">{{ $lead->email ?: 'No email' }}</div>
                        </td>
                        <td style="font-weight: 500;">{{ $lead->company ?: '—' }}</td>
                        <td>
                            <span style="font-weight: 700; font-size: 13.5px; color: {{ $lead->score >= 60 ? 'var(--success)' : 'var(--mute)' }};">
                                {{ $lead->score }}
                            </span>
                        </td>
                        <td>
                            @if($lead->status === 'converted')
                                <span class="badge badge-active"><span class="badge-dot"></span>Converted</span>
                            @elseif($lead->status === 'disqualified')
                                <span class="badge badge-failed"><span class="badge-dot"></span>Disqualified ({{ $lead->disqualified_reason ?: 'Unfit' }})</span>
                            @elseif($lead->status === 'nurturing')
                                <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;"><span class="badge-dot" style="background: #d97706;"></span>Nurturing</span>
                            @elseif($lead->status === 'qualified')
                                <span class="badge" style="background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe;"><span class="badge-dot" style="background: #7c3aed;"></span>Qualified</span>
                            @elseif($lead->status === 'working')
                                <span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;"><span class="badge-dot" style="background: #0284c7;"></span>Working</span>
                            @elseif($lead->status === 'contacted')
                                <span class="badge" style="background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4;"><span class="badge-dot" style="background: #0d9488;"></span>Contacted</span>
                            @else
                                <span class="badge badge-draft"><span class="badge-dot"></span>{{ ucfirst($lead->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $stageLabels = [
                                    'new' => '1. New',
                                    'contacted' => '2. Contacted',
                                    'working' => '3. Working',
                                    'qualified' => '4. Qualified',
                                    'converted' => '5. Converted',
                                    'nurturing' => 'Nurturing',
                                    'unqualified' => 'Unqualified',
                                    'disqualified' => 'Disqualified',
                                ];
                            @endphp
                            <span class="badge badge-pill">
                                {{ $stageLabels[$lead->stage] ?? ucfirst($lead->stage) }}
                            </span>
                        </td>
                        <td>
                            @if($lead->owner)
                                <span style="font-size: 13px; font-weight: 500;">{{ $lead->owner->name }}</span>
                            @else
                                <span style="color: var(--mute); font-style: italic;">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                @if($lead->account)
                                    <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">Acct #{{ $lead->account->id }}</span>
                                @endif
                                @if($lead->opportunity)
                                    <span class="badge" style="background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;">Deal #{{ $lead->opportunity->id }}</span>
                                @endif
                                @if($lead->contact)
                                    <span class="badge" style="background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd;">Contact</span>
                                @endif
                                @if(!$lead->account && !$lead->opportunity && !$lead->contact)
                                    <span style="color: var(--mute); font-size: 12px;">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 12.5px; font-weight: 600; color: var(--ink);">{{ $lead->activities->count() }}</span>
                        </td>
                        <td style="text-align: right;">
                            <form method="POST" action="{{ route('leads.destroy', $lead) }}" style="display: inline;" onsubmit="return confirm('Delete this lead?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon-danger" title="Delete lead">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 48px 16px; color: var(--mute);">
                            <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: var(--bg); color: var(--mute); margin-bottom: 12px;">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ink);">No leads yet</p>
                            <p style="font-size: 13px; margin-top: 4px;">Create a lead below to test the automatic execution of your active workflow.</p>
                            <button class="btn btn-primary" style="margin-top: 16px;" onclick="document.getElementById('create-lead-modal').style.display='flex'">
                                Create Test Lead
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for creating Lead -->
<div id="create-lead-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; width: 100%; max-width: 500px; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 18px; font-weight: 700;">Add Test Lead</h2>
            <button onclick="document.getElementById('create-lead-modal').style.display='none'" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--mute);">&times;</button>
        </div>

        <!-- Presets -->
        <div style="background: var(--bg); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 10px; margin-bottom: 16px;">
            <div style="font-size: 11px; font-weight: 700; color: var(--mute); text-transform: uppercase; margin-bottom: 6px;">CRM Quick Presets</div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-sm" onclick="setPreset('qualified')">&#x2705; Enterprise MQL (Score 85 · CloudScale)</button>
                <button type="button" class="btn btn-sm" onclick="setPreset('disqualified')">&#x274C; Cold SMB (Score 25 · Dave's Hardware)</button>
            </div>
        </div>

        <form method="POST" action="{{ route('leads.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="l-name">Contact / Lead Full Name</label>
                <input class="form-input" type="text" id="l-name" name="name" value="Sarah Jenkins" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="l-email">Corporate Email</label>
                <input class="form-input" type="email" id="l-email" name="email" value="sarah.jenkins@cloudscale.io">
            </div>
            <div class="form-group">
                <label class="form-label" for="l-company">Company / Account Name</label>
                <input class="form-input" type="text" id="l-company" name="company" value="CloudScale Technologies, Inc.">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label class="form-label" for="l-score">Einstein / Fit Score (0-100)</label>
                    <input class="form-input" type="number" id="l-score" name="score" value="85" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="l-source">Lead Source</label>
                    <select class="form-select" id="l-source" name="source">
                        <option value="web" selected>Web-to-Lead Form (web)</option>
                        <option value="inbound">Inbound Demo Request (inbound)</option>
                        <option value="partner">Partner Referral (partner)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn" onclick="document.getElementById('create-lead-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Lead & Trigger Workflow</button>
            </div>
        </form>
    </div>
</div>

<script>
function setPreset(type) {
    if (type === 'qualified') {
        document.getElementById('l-name').value = 'Enterprise Lead ' + Math.floor(Math.random() * 900 + 100);
        document.getElementById('l-company').value = 'CloudScale Technologies, Inc.';
        document.getElementById('l-email').value = 'enterprise@cloudscale.io';
        document.getElementById('l-score').value = 85;
        document.getElementById('l-source').value = 'web';
    } else {
        document.getElementById('l-name').value = 'Cold Lead ' + Math.floor(Math.random() * 900 + 100);
        document.getElementById('l-company').value = "Dave's Hardware & Tools";
        document.getElementById('l-email').value = 'dave@daveshardware.com';
        document.getElementById('l-score').value = 25;
        document.getElementById('l-source').value = 'web';
    }
}
</script>
@endsection
