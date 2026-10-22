<?php

namespace App\Workflows;

/** Server-side twin of resources/js/workflow/validate.js. The server is the authority. */
final class GraphValidator
{
    public static function allowedTypes(): array
    {
        return array_merge(['trigger', 'decision', 'circle', 'rect', 'diamond', 'sticky_note', 'annotation'], array_keys(config('workflows.actions', [])));
    }

    /** @return array<int, array{node: ?string, message: string}> */
    public static function validate(array $raw): array
    {
        $g = new Graph($raw);
        $nodes = $g->nodes();
        $errors = [];
        $add = function (?string $node, string $msg) use (&$errors) { $errors[] = ['node' => $node, 'message' => $msg]; };

        $triggers = array_filter($nodes, fn ($n) => in_array($n['type'], ['trigger', 'circle'], true));
        if (count($triggers) !== 1) {
            $add(null, 'A workflow needs exactly one trigger start event.');
        }

        foreach ($raw['edges'] ?? [] as $e) {
            if (!isset($nodes[$e['sourceNodeId']], $nodes[$e['targetNodeId']])) {
                $add(null, 'A connection points to a node that no longer exists.');
            }
        }

        foreach ($nodes as $id => $n) {
            if (!in_array($n['type'], self::allowedTypes(), true)) {
                $add($id, "Unknown node type “{$n['type']}”.");
            }
            if (in_array($n['type'], ['sticky_note', 'annotation'], true)) {
                continue; // annotations don't require connections
            }

            $nodeTitle = $n['properties']['title'] ?? $id;
            $out = $g->outgoing($id);
            $actionType = $n['type'] === 'rect' ? ($n['properties']['action'] ?? $n['properties']['action_type'] ?? '') : $n['type'];
            if (in_array($actionType, config('workflows.terminal', []), true) && $out) {
                $add($id, "Terminal step “{$nodeTitle}” ends the workflow and cannot have outgoing connections.");
            }
            if (in_array($n['type'], ['decision', 'diamond'], true)) {
                $branches = array_map(function ($e) {
                    $b = $e['properties']['branch'] ?? '';
                    if (!in_array($b, ['yes', 'no'], true)) {
                        $tv = strtolower(trim((string) ($e['text']['value'] ?? (is_string($e['text'] ?? null) ? $e['text'] : ''))));
                        if (str_contains($tv, 'yes')) return 'yes';
                        if (str_contains($tv, 'no')) return 'no';
                    }
                    return $b;
                }, $out);
                foreach (['yes', 'no'] as $b) {
                    if (!in_array($b, $branches, true)) {
                        $add($id, "Decision “{$nodeTitle}” needs a connected “" . strtoupper($b) . "” branch.");
                    }
                }
            }
        }

        // Reachability from the trigger.
        if (count($triggers) === 1) {
            $seen = [];
            $stack = [array_key_first($triggers)];
            while ($stack) {
                $id = array_pop($stack);
                if (isset($seen[$id])) continue;
                $seen[$id] = true;
                foreach ($g->outgoing($id) as $e) $stack[] = $e['targetNodeId'];
            }
            foreach ($nodes as $id => $n) {
                if (!in_array($n['type'], ['sticky_note', 'annotation'], true) && !isset($seen[$id])) {
                    $nodeTitle = $n['properties']['title'] ?? $id;
                    $add($id, "Step “{$nodeTitle}” is not connected to the trigger start event.");
                }
            }
        }

        // Cycle detection (DFS colouring).
        $color = [];
        $hasCycle = function (string $id) use (&$hasCycle, &$color, $g): bool {
            $color[$id] = 1;
            foreach ($g->outgoing($id) as $e) {
                $t = $e['targetNodeId'];
                if (($color[$t] ?? 0) === 1) return true;
                if (($color[$t] ?? 0) === 0 && $g->node($t) && $hasCycle($t)) return true;
            }
            $color[$id] = 2;
            return false;
        };
        foreach (array_keys($nodes) as $id) {
            if (($color[$id] ?? 0) === 0 && $hasCycle($id)) {
                $add($id, 'The workflow loops back on itself.');
                break;
            }
        }

        return $errors;
    }
}
