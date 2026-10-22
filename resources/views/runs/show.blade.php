@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 900px;">
    <div style="margin-bottom: 20px;">
        <a href="{{ route('runs.index') }}" class="btn btn-sm">&larr; Back to Runs</a>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 style="font-size: 20px; font-weight: 700;">Workflow Run #{{ $run->id }}</h1>
                <p style="color: var(--mute); font-size: 13.5px; margin-top: 4px;">
                    Workflow: <strong>{{ $run->workflow?->name ?: 'Workflow #'.$run->workflow_id }}</strong> (Version {{ $run->version }})
                </p>
            </div>
            <div>
                @if($run->status === 'completed')
                    <span class="badge badge-completed">Completed</span>
                @elseif($run->status === 'running')
                    <span class="badge badge-running">Running</span>
                @else
                    <span class="badge badge-failed">Failed</span>
                @endif
            </div>
        </div>

        @if($run->error)
            <div style="margin-top: 16px; padding: 12px; background: var(--danger-bg); border-radius: var(--radius-sm); border: 1px solid var(--danger); color: var(--danger); font-size: 13px;">
                <strong>Execution Error:</strong> {{ $run->error }}
            </div>
        @endif

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; border-top: 1px solid var(--line); padding-top: 16px;">
            <div>
                <div style="font-size: 12px; color: var(--mute);">Lead Subject</div>
                <div style="font-weight: 600; font-size: 14px;">{{ $run->lead?->name ?: 'Lead #'.$run->subject_id }}</div>
            </div>
            <div>
                <div style="font-size: 12px; color: var(--mute);">Started At</div>
                <div style="font-size: 13.5px;">{{ $run->started_at?->toDayDateTimeString() ?: '—' }}</div>
            </div>
            <div>
                <div style="font-size: 12px; color: var(--mute);">Finished At</div>
                <div style="font-size: 13.5px;">{{ $run->finished_at?->toDayDateTimeString() ?: '—' }}</div>
            </div>
        </div>
    </div>

    <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Step-by-Step Execution Log</h2>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Node ID</th>
                    <th>Node Type</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Step Output</th>
                    <th>Executed At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($run->steps as $index => $step)
                    <tr>
                        <td style="color: var(--mute);">{{ $index + 1 }}</td>
                        <td><code>{{ $step->node_id }}</code></td>
                        <td><span class="badge" style="background: var(--bg); border: 1px solid var(--line);">{{ $step->node_type }}</span></td>
                        <td>
                            @if($step->branch === 'yes')
                                <span class="badge" style="background: #16a34a22; color: #16a34a;">YES</span>
                            @elseif($step->branch === 'no')
                                <span class="badge" style="background: #dc262622; color: #dc2626;">NO</span>
                            @else
                                <span style="color: var(--mute);">—</span>
                            @endif
                        </td>
                        <td>
                            @if($step->status === 'completed')
                                <span class="badge badge-completed">&#x2713; OK</span>
                            @else
                                <span class="badge badge-failed">&#x2717; Failed</span>
                            @endif
                        </td>
                        <td>
                            @if($step->output)
                                <pre style="font-size: 11.5px; background: var(--bg); padding: 4px 8px; border-radius: 4px; overflow-x: auto; max-width: 250px;">{{ json_encode($step->output, JSON_PRETTY_PRINT) }}</pre>
                            @elseif($step->error)
                                <span style="color: var(--danger); font-size: 12px;">{{ $step->error }}</span>
                            @else
                                <span style="color: var(--mute);">—</span>
                            @endif
                        </td>
                        <td style="color: var(--mute); font-size: 12px;">
                            {{ $step->executed_at ? $step->executed_at->format('H:i:s.v') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--mute); padding: 24px;">No steps recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
