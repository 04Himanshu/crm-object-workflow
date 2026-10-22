<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Workflow;
use App\Workflows\Graph;
use App\Workflows\GraphValidator;
use App\Workflows\WorkflowEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkflowController extends Controller
{
    public function index()
    {
        $workflows = Workflow::withCount('runs')->latest()->get();
        return view('workflows.index', compact('workflows'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:120']);
        $wf = Workflow::create($data + ['created_by' => $request->user()?->id]);
        return redirect()->route('workflows.show', $wf);
    }

    public function show(Workflow $workflow)
    {
        return view('workflows.builder', compact('workflow'));
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();
        return redirect()->route('workflows.index')->with('success', 'Workflow deleted successfully.');
    }

    /** Autosave of the draft. Compare-and-swap on `revision` stops two tabs overwriting each other. */
    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'revision' => 'required|integer|min:1',
            'graph' => 'required|array',
            'graph.nodes' => 'required|array|max:200',
            'graph.nodes.*.id' => 'required|string|max:64',
            'graph.nodes.*.type' => ['required', Rule::in(GraphValidator::allowedTypes())],
            'graph.nodes.*.x' => 'required|numeric',
            'graph.nodes.*.y' => 'required|numeric',
            'graph.nodes.*.properties' => 'nullable|array',
            'graph.edges' => 'present|array|max:400',
            'graph.edges.*.id' => 'required|string|max:64',
            'graph.edges.*.sourceNodeId' => 'required|string|max:64',
            'graph.edges.*.targetNodeId' => 'required|string|max:64',
            'graph.edges.*.properties' => 'nullable|array',
            'graph.edges.*.properties.branch' => 'nullable|string',
            'graph.edges.*.text' => 'nullable',
        ]);

        $graph = Graph::sanitize($request->input('graph', []));

        $changed = Workflow::whereKey($workflow->id)
            ->where('revision', $data['revision'])
            ->update(['graph' => json_encode($graph), 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);

        if (!$changed) {
            return response()->json(['message' => 'Changed elsewhere.', 'revision' => $workflow->fresh()->revision], 409);
        }

        return response()->json(['revision' => $data['revision'] + 1]);
    }

    public function publish(Workflow $workflow): JsonResponse
    {
        $errors = $workflow->graph ? GraphValidator::validate($workflow->graph) : [['node' => null, 'message' => 'Nothing to publish yet.']];
        if ($errors) return response()->json(['errors' => $errors], 422);

        $workflow->update([
            'published_graph' => $workflow->graph,
            'published_version' => $workflow->published_version + 1,
            'published_at' => now(),
            'status' => 'active',
        ]);

        return response()->json(['version' => $workflow->published_version]);
    }

    /** Dry run on the saved draft with sample data. Nothing is written. */
    public function simulate(Request $request, Workflow $workflow, WorkflowEngine $engine): JsonResponse
    {
        $request->validate(['sample' => 'nullable|array']);
        $errors = $workflow->graph ? GraphValidator::validate($workflow->graph) : [['node' => null, 'message' => 'Nothing to simulate yet.']];
        if ($errors) return response()->json(['errors' => $errors], 422);

        $lead = (new Lead)->forceFill(Arr::only($request->input('sample', []), ['name', 'email', 'score', 'status', 'stage', 'source', 'company', 'country']));
        $steps = $engine->walk(new Graph($workflow->graph), $lead, true);

        return response()->json(['steps' => $steps]);
    }

    public function latestRun(Workflow $workflow): JsonResponse
    {
        $run = $workflow->runs()->latest('id')->with('steps')->first();
        if (!$run) return response()->json(['status' => null, 'steps' => []]);

        return response()->json([
            'status' => $run->status,
            'steps' => $run->steps->map(fn ($s) => ['node_id' => $s->node_id, 'status' => $s->status, 'branch' => $s->branch, 'error' => $s->error]),
        ]);
    }
}
