<?php

namespace App\Workflows;

use App\Models\Lead;
use RuntimeException;
use Throwable;

final class WorkflowEngine
{
    /**
     * Walk the graph from the trigger. $dry = true runs decisions but makes no changes (used by Simulate).
     * $record receives every step (completed or failed) so callers can persist it.
     */
    public function walk(Graph $graph, Lead $lead, bool $dry = false, ?callable $record = null): array
    {
        $steps = [];
        $node = $graph->trigger();
        $guard = 0;
        $max = (int) config('workflows.max_steps', 50);

        while ($node) {
            if (++$guard > $max) {
                throw new RuntimeException('Workflow exceeded the maximum number of steps.');
            }

            $rawType = $node['type'];
            $props = $node['properties'] ?? [];
            $cfg = $props['config'] ?? [];
            $type = match ($rawType) {
                'circle' => 'trigger',
                'diamond' => 'decision',
                'rect' => $props['action'] ?? $props['action_type'] ?? ($cfg['action'] ?? 'assign'),
                default => $rawType,
            };
            $branch = null;
            $output = [];

            try {
                $interpolatedCfg = Interpolator::interpolate($cfg, $lead);
                if ($type === 'decision') {
                    $pass = DecisionEvaluator::passes($lead, $interpolatedCfg);
                    $branch = $pass ? 'yes' : 'no';
                    $output = ['passed' => $pass];
                } elseif ($type !== 'trigger') {
                    $class = config("workflows.actions.$type");
                    if (!$class) throw new RuntimeException("No action registered for “$type”.");
                    $output = app($class)->handle($lead, $interpolatedCfg, $dry);
                }
            } catch (Throwable $e) {
                $failed = ['node_id' => $node['id'], 'node_type' => $type, 'branch' => null, 'status' => 'failed', 'output' => null, 'error' => $e->getMessage()];
                $steps[] = $failed;
                if ($record) $record($failed);
                throw $e;
            }

            $step = ['node_id' => $node['id'], 'node_type' => $type, 'branch' => $branch, 'status' => 'completed', 'output' => $output, 'error' => null];
            $steps[] = $step;
            if ($record) $record($step);

            $node = $graph->next($node['id'], $branch);
        }

        return $steps;
    }
}
