@extends('layouts.app')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Workflow Execution History</h1>
            <p class="page-subtitle">Track every executed lead through the workflow engine, inspect step outputs and diagnose errors.</p>
        </div>
        <div>
            <form method="POST" action="{{ route('leads.processQueue') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn" title="Run queue worker">
                    &#x2699; Process Queue Now
                </button>
            </form>
        </div>
    </div>

    <!-- Runs Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Run ID</th>
                    <th>Workflow</th>
                    <th>Subject (Lead)</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Steps Completed</th>
                    <th>Started</th>
                    <th>Duration</th>
                    <th style="text-align: right;">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $run)
                    <tr>
                        <td><strong>#{{ $run->id }}</strong></td>
                        <td>
                            @if($run->workflow)
                                <a href="{{ route('workflows.show', $run->workflow) }}" style="color: var(--pri); font-weight: 600;">
                                    {{ $run->workflow->name }}
                                </a>
                            @else
                                <span style="color: var(--mute);">Deleted workflow</span>
                            @endif
                        </td>
                        <td>
                            @if($run->lead)
                                <div><strong>{{ $run->lead->name }}</strong></div>
                                <div style="font-size: 12px; color: var(--mute);">Score: {{ $run->lead->score }} · {{ $run->lead->status }}</div>
                            @else
                                <span style="color: var(--mute);">Lead #{{ $run->subject_id }}</span>
                            @endif
                        </td>
                        <td>v{{ $run->version }}</td>
                        <td>
                            @if($run->status === 'completed')
                                <span class="badge badge-completed">Completed</span>
                            @elseif($run->status === 'running')
                                <span class="badge badge-running">Running</span>
                            @else
                                <span class="badge badge-failed">Failed</span>
                            @endif
                        </td>
                        <td>{{ $run->steps->count() }} step{{ $run->steps->count() === 1 ? '' : 's' }}</td>
                        <td style="color: var(--mute);">{{ $run->started_at ? $run->started_at->diffForHumans() : '—' }}</td>
                        <td style="color: var(--mute); font-family: monospace;">
                            @if($run->started_at && $run->finished_at)
                                {{ $run->started_at->diffInMilliseconds($run->finished_at) }}ms
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('runs.show', $run) }}" class="btn btn-sm">
                                View Steps &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 48px 16px; color: var(--mute);">
                            <div style="font-size: 36px; margin-bottom: 8px;">&#x23F3;</div>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ink);">No executions recorded yet</p>
                            <p style="font-size: 13px; margin-top: 4px;">When a lead is created and an active workflow exists, execution runs appear here.</p>
                            <a href="{{ route('leads.index') }}" class="btn btn-primary" style="margin-top: 16px;">
                                Go to Leads Lab
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
