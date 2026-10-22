@extends('layouts.app')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Workflows</h1>
            <p class="page-subtitle">Design, simulate, and automate enterprise lead lifecycle pipelines with visual LogicFlow BPMN graphs.</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="document.getElementById('create-modal').style.display='flex'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create Workflow
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-3" style="margin-bottom: 28px;">
        <div class="stat-card">
            <div>
                <div class="stat-val">{{ $workflows->count() }}</div>
                <div class="stat-lbl">Configured Workflows</div>
                <div class="stat-meta">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Ready for simulation & execution
                </div>
            </div>
            <div class="stat-icon stat-icon-pri">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-val">{{ $workflows->where('status', 'active')->count() }}</div>
                <div class="stat-lbl">Active & Published</div>
                <div class="stat-meta">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    Real-time lead triggers active
                </div>
            </div>
            <div class="stat-icon stat-icon-success">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-val">{{ $workflows->sum('runs_count') }}</div>
                <div class="stat-lbl">Total Executions</div>
                <div class="stat-meta" style="color: #b45309;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    Automated lead actions run
                </div>
            </div>
            <div class="stat-icon stat-icon-warning">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            </div>
        </div>
    </div>

    <!-- Workflows Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="min-width: 280px;">Workflow Name</th>
                    <th>Status</th>
                    <th>Target</th>
                    <th>Revision</th>
                    <th>Published Version</th>
                    <th>Runs</th>
                    <th>Last Published</th>
                    <th style="text-align: right; min-width: 170px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workflows as $wf)
                    <tr>
                        <td>
                            <div class="wf-name-cell">
                                <div class="wf-icon-box">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="12" r="3"></circle><line x1="9" y1="6" x2="15" y2="12"></line><line x1="9" y1="18" x2="15" y2="12"></line></svg>
                                </div>
                                <div>
                                    <a href="{{ route('workflows.show', $wf) }}" class="wf-title-link">
                                        {{ $wf->name }}
                                    </a>
                                    <div class="wf-subtitle">
                                        @if(str_contains(strtolower($wf->name), 'sample lead 1'))
                                            Enterprise MQL scoring, territory SDR assignment & deal sync
                                        @elseif(str_contains(strtolower($wf->name), 'sample lead 2'))
                                            High-velocity inbound trial signups & automated discovery call
                                        @elseif(str_contains(strtolower($wf->name), 'sample lead 3'))
                                            Co-sell partner portal submission with fast-track AE routing
                                        @elseif(str_contains(strtolower($wf->name), 'sample lead 4'))
                                            Cold outbound re-engagement campaign with nurture recycling
                                        @else
                                            Visual BPMN pipeline for lead qualification and conversion
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($wf->status === 'active')
                                <span class="badge badge-active">
                                    <span class="badge-dot"></span>
                                    Active · v{{ $wf->published_version ?? 1 }}
                                </span>
                            @else
                                <span class="badge badge-draft">
                                    <span class="badge-dot"></span>
                                    Draft
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="pill-target">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                {{ ucfirst($wf->target_object) }}
                            </span>
                        </td>
                        <td>
                            <span class="pill-version">rev #{{ $wf->revision }}</span>
                        </td>
                        <td>
                            <span class="pill-version">{{ $wf->published_version ? 'v'.$wf->published_version : 'None' }}</span>
                        </td>
                        <td>
                            <span class="pill-runs">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                                {{ $wf->runs_count }}
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--mute);">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                {{ $wf->published_at ? $wf->published_at->diffForHumans() : '—' }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <a href="{{ route('workflows.show', $wf) }}" class="btn-builder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    Open Builder
                                </a>
                                <form method="POST" action="{{ route('workflows.destroy', $wf) }}" onsubmit="return confirm('Delete this workflow?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon-danger" title="Delete Workflow">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 56px 16px; color: var(--mute);">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: #64748b;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            </div>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ink);">No workflows created yet</p>
                            <p style="font-size: 13px; margin-top: 4px;">Click the button below to build your first lead lifecycle workflow.</p>
                            <button class="btn btn-primary" style="margin-top: 16px;" onclick="document.getElementById('create-modal').style.display='flex'">
                                Create Workflow
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for creating workflow -->
<div id="create-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Create Lead Workflow</h2>
            <button class="modal-close" onclick="document.getElementById('create-modal').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="{{ route('workflows.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="wf-name">Workflow Name</label>
                <input class="form-input" type="text" id="wf-name" name="name" placeholder="e.g. Sample Lead 5 · Inbound Outreach Flow" required autofocus>
                <div class="form-help">Give this pipeline a descriptive name matching its CRM lead routing purpose.</div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
                <button type="button" class="btn" onclick="document.getElementById('create-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create & Open Builder</button>
            </div>
        </form>
    </div>
</div>
@endsection
