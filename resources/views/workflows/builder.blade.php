@extends('layouts.app')

@section('content')
<div class="wf-app" id="wf-app"
     style="--wf-offset: 46px;"
     data-csrf="{{ csrf_token() }}"
     data-update-url="{{ route('workflows.update', $workflow) }}"
     data-publish-url="{{ route('workflows.publish', $workflow) }}"
     data-simulate-url="{{ route('workflows.simulate', $workflow) }}"
     data-runs-url="{{ route('workflows.runs.latest', $workflow) }}">

  {{-- Tier 1: Main Workflow Navigation & Production Bar --}}
  <header class="wf-navbar">
    <div class="wf-navbar-left">
      <nav class="wf-bc" aria-label="Breadcrumb">
        <a href="{{ route('workflows.index') }}" class="wf-bc-link" title="Return to Workflows Dashboard">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          <span>Workflows</span>
        </a>
        <span class="wf-bc-sep">/</span>
        <span class="wf-bc-title" title="{{ $workflow->name }}">{{ $workflow->name }}</span>
      </nav>

      <div class="wf-meta-tags">
        <span class="chip-status {{ $workflow->status === 'active' ? 'active' : '' }}" id="wf-status">
          <span class="chip-dot"></span>
          {{ $workflow->status === 'active' ? 'Active · v'.$workflow->published_version : ucfirst($workflow->status) }}
        </span>
        <span class="wf-badge-target">Lead CRM</span>
        <span class="wf-save-pill" id="wf-save">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span>Saved</span>
        </span>
        <span class="sub issues" id="wf-issues" hidden></span>
      </div>
    </div>

    <div class="wf-navbar-right">
      {{-- Simulation Lead Persona --}}
      <div class="wf-sim-scenario">
        <label for="outcome" class="wf-sim-label">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Sample Lead:</span>
        </label>
        <select id="outcome" title="Select Lead Scenario for dry-run simulation">
          <option value="yes">Sarah Jenkins (Score 85 · Qualified & Converted)</option>
          <option value="no">Dave's Hardware (Score 20 · Disqualified)</option>
        </select>
      </div>

      <div class="wf-nav-actions">
        <button class="btn-nav btn-sim" id="sim" title="Run workflow dry-run simulation">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          <span>Simulate</span>
        </button>
        <button class="btn-nav" id="lastrun" title="Inspect last live run execution">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span>Last Run</span>
        </button>
        <div class="wf-nav-divider"></div>
        <button class="btn-nav btn-publish" id="publish" title="Publish current draft as active live version">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
          <span>Publish Version</span>
        </button>
      </div>
    </div>
  </header>

  {{-- Tier 2: Canvas Toolbar & Blueprint Actions --}}
  <div class="wf-toolbar">
    {{-- Left: History, Zoom, Layout & Phases --}}
    <div class="wf-tb-group">
      <div class="wf-btn-cluster">
        <button class="wf-tb-btn" id="undo" title="Undo (Ctrl+Z)">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
          <span>Undo</span>
        </button>
        <button class="wf-tb-btn" id="redo" title="Redo (Ctrl+Y)">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg>
          <span>Redo</span>
        </button>
      </div>

      <div class="wf-tb-sep"></div>

      <div class="wf-zoom-cluster">
        <button class="wf-tb-btn ic" id="zo" title="Zoom out">&minus;</button>
        <span class="wf-zoom-val" id="zv">100%</span>
        <button class="wf-tb-btn ic" id="zi" title="Zoom in">+</button>
        <button class="wf-tb-btn" id="fit" title="Fit workflow diagram into screen">Fit to Screen</button>
      </div>

      <div class="wf-tb-sep"></div>

      <div class="wf-btn-cluster">
        <button class="wf-tb-btn" id="autolayout" title="Auto-align and untangle workflow nodes">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M10 6.5h4M6.5 10v4M17.5 10v4M10 17.5h4" stroke-dasharray="2 2"/></svg>
          <span>Auto-Layout</span>
        </button>
        <button class="wf-tb-btn" id="swimlanes-toggle" title="Toggle Visual Workflow Phase Swimlanes">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
          <span>Phases</span>
        </button>
        <button class="wf-tb-btn" id="minimap-toggle" title="Toggle Navigator MiniMap">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
          <span>MiniMap</span>
        </button>
      </div>
    </div>

    {{-- Right: Export, Import, History, Shortcuts & Reset --}}
    <div class="wf-tb-group">
      <div class="wf-btn-cluster">
        <button class="wf-tb-btn" id="snapshot" title="Export workflow blueprint diagram as PNG image">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
          <span>PNG</span>
        </button>
        <button class="wf-tb-btn" id="export-json" title="Download workflow blueprint JSON">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          <span>JSON</span>
        </button>
        <button class="wf-tb-btn" id="btn-import-json" title="Import workflow blueprint from JSON file">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span>Import</span>
        </button>
        <input type="file" id="input-import-json" accept=".json" style="display: none;">
      </div>

      <div class="wf-tb-sep"></div>

      <button class="wf-tb-btn" id="btn-history" title="View Version History & Restore Past Drafts">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>History</span>
      </button>

      <button class="wf-tb-btn ic" id="btn-shortcuts" title="Keyboard Shortcuts Cheat Sheet (?)">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </button>

      <div class="wf-tb-sep"></div>

      <button class="wf-tb-btn btn-reset-link" id="reset" title="Reset workflow to default template">
        <span>Reset</span>
      </button>
    </div>
  </div>

  <main class="wf-main">
    <aside id="pal"></aside>
    <section id="stage">
      <div id="wf-swimlanes" class="wf-swimlanes" style="display: none;">
        <div class="wf-lane-col" style="left: 60px; width: 280px;">
          <div class="wf-lane-header">1. Ingestion (New) & Assignment</div>
        </div>
        <div class="wf-lane-col" style="left: 360px; width: 580px;">
          <div class="wf-lane-header">2. Outreach (Contacted) & 3. Discovery (Working)</div>
        </div>
        <div class="wf-lane-col" style="left: 960px; width: 340px;">
          <div class="wf-lane-header">Qualification Gateway (MQL Criteria)</div>
        </div>
        <div class="wf-lane-col" style="left: 1320px; width: 580px;">
          <div class="wf-lane-header">4. Qualified, 5. Converted & Outcomes</div>
        </div>
      </div>
      <div id="lf"></div>
      <div id="status">Drag nodes from the left, connect the dots, click to edit.</div>
    </section>
    <aside id="insp"></aside>
  </main>
</div>

{{-- Keyboard Shortcuts Modal --}}
<div class="wf-modal-overlay" id="modal-shortcuts" style="display: none;">
  <div class="wf-modal-dialog">
    <div class="wf-modal-head">
      <div class="wf-modal-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>Keyboard Shortcuts Cheat Sheet</span>
      </div>
      <button class="wf-modal-close" id="close-shortcuts">&times;</button>
    </div>
    <div class="wf-modal-body">
      <table class="wf-shortcuts-table">
        <tbody>
          <tr><td><kbd>Ctrl</kbd> + <kbd>Z</kbd></td><td>Undo last action / node move</td></tr>
          <tr><td><kbd>Ctrl</kbd> + <kbd>Y</kbd> / <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>Z</kbd></td><td>Redo undone action</td></tr>
          <tr><td><kbd>Delete</kbd> or <kbd>Backspace</kbd></td><td>Delete selected node or connection line</td></tr>
          <tr><td><kbd>Space</kbd> + <kbd>Drag</kbd></td><td>Pan across the workflow canvas</td></tr>
          <tr><td><kbd>Ctrl</kbd> + <kbd>Scroll</kbd></td><td>Zoom in and out of the canvas</td></tr>
          <tr><td><kbd>Shift</kbd> + <kbd>Click / Drag</kbd></td><td>Multi-select multiple nodes for batch moving</td></tr>
          <tr><td><kbd>Esc</kbd></td><td>Deselect active node / Close properties inspector</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Version History Modal --}}
<div class="wf-modal-overlay" id="modal-history" style="display: none;">
  <div class="wf-modal-dialog" style="max-width: 580px;">
    <div class="wf-modal-head">
      <div class="wf-modal-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>Workflow Version History</span>
      </div>
      <button class="wf-modal-close" id="close-history">&times;</button>
    </div>
    <div class="wf-modal-body">
      <div class="wf-history-list">
        <div class="wf-history-item active">
          <div class="wf-history-info">
            <div class="wf-history-badge draft">Current Working Draft</div>
            <div class="wf-history-desc">Revision {{ $workflow->revision }} · Auto-saved in browser buffer</div>
            <div class="wf-history-time">Last modified: {{ $workflow->updated_at?->diffForHumans() ?? 'Just now' }}</div>
          </div>
          <div class="wf-history-actions">
            <span class="chip-current">Active in Editor</span>
          </div>
        </div>

        @if($workflow->published_version)
        <div class="wf-history-item">
          <div class="wf-history-info">
            <div class="wf-history-badge published">Published v{{ $workflow->published_version }}</div>
            <div class="wf-history-desc">Live production blueprint handling incoming leads</div>
            <div class="wf-history-time">Published: {{ $workflow->published_at ? $workflow->published_at->format('M d, Y · H:i') : 'Recently' }}</div>
          </div>
          <div class="wf-history-actions">
            <button class="btn btn-sm" id="btn-revert-published">Restore to Editor</button>
          </div>
        </div>
        @else
        <div class="wf-history-item empty">
          <p style="color: var(--mute); font-size: 12px; margin: 0;">No versions published yet. Click "Publish" in the toolbar to create version 1.</p>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- JSON Export & Blueprint Preview Modal --}}
<div class="wf-modal-overlay" id="modal-json-export" style="display: none;">
  <div class="wf-modal-dialog" style="max-width: 680px; width: 90%;">
    <div class="wf-modal-head">
      <div class="wf-modal-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Workflow JSON Blueprint</span>
      </div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" class="btn-copy-code" id="btn-copy-json" title="Copy full JSON to clipboard">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          <span id="copy-json-label">Copy JSON</span>
        </button>
        <button class="wf-modal-close" id="close-json-export">&times;</button>
      </div>
    </div>
    <div class="wf-modal-body" style="padding: 16px 20px;">
      <p style="font-size: 12px; color: var(--mute); margin-bottom: 12px;">
        Complete workflow blueprint specification including nodes, actions, branching conditions, and routing logic.
      </p>
      <div class="wf-json-card">
        <pre class="wf-json-code-wrap"><code id="wf-json-code"></code></pre>
      </div>
      <div class="wf-modal-foot">
        <span class="wf-json-stats" id="wf-json-stats">0 nodes · 0 connections</span>
        <div style="display: flex; align-items: center; gap: 8px;">
          <button type="button" class="btn-modal-secondary" id="btn-cancel-json">Close</button>
          <button type="button" class="btn-modal-primary" id="btn-download-json" title="Download JSON file to your device">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>Export JSON (.json)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- JSON_HEX_* flags stop a "</script>" inside a node title from breaking out of the tag. --}}
<script type="application/json" id="wf-data">{!! json_encode([
    'id' => $workflow->id,
    'name' => $workflow->name,
    'graph' => $workflow->graph,
    'revision' => $workflow->revision,
    'published_version' => $workflow->published_version,
    'published_graph' => $workflow->published_graph,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    @vite(['resources/js/workflow/builder.js'])
@endpush
