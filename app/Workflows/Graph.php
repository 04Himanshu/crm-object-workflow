<?php

namespace App\Workflows;

use Illuminate\Support\Str;

/** Read-only helper around the LogicFlow graph JSON, plus a sanitizer for untrusted input. */
final class Graph
{
    private array $nodes = [];
    private array $out = [];

    public function __construct(array $graph)
    {
        foreach ($graph['nodes'] ?? [] as $n) {
            $this->nodes[$n['id']] = $n;
        }
        foreach ($graph['edges'] ?? [] as $e) {
            $this->out[$e['sourceNodeId']][] = $e;
        }
    }

    public function nodes(): array { return $this->nodes; }

    public function node(string $id): ?array { return $this->nodes[$id] ?? null; }

    public function outgoing(string $id): array { return $this->out[$id] ?? []; }

    public function trigger(): ?array
    {
        foreach ($this->nodes as $n) {
            if (in_array($n['type'], ['trigger', 'circle'], true)) return $n;
        }
        return null;
    }

    /** Next node after $nodeId. For decisions pass 'yes' or 'no'. */
    public function next(string $nodeId, ?string $branch = null): ?array
    {
        $edges = $this->outgoing($nodeId);
        if ($branch !== null) {
            $edges = array_values(array_filter($edges, function ($e) use ($branch) {
                $b = $e['properties']['branch'] ?? '';
                if (!in_array($b, ['yes', 'no'], true)) {
                    $tv = strtolower(trim((string) ($e['text']['value'] ?? (is_string($e['text'] ?? null) ? $e['text'] : ''))));
                    if (str_contains($tv, 'yes')) $b = 'yes';
                    elseif (str_contains($tv, 'no')) $b = 'no';
                }
                return $b === $branch;
            }));
        }
        return isset($edges[0]) ? $this->node($edges[0]['targetNodeId']) : null;
    }

    /** Keep only the fields we understand; never store arbitrary browser state. */
    public static function sanitize(array $g): array
    {
        $nodes = array_map(function ($n) {
            $p = $n['properties'] ?? [];
            $cfg = is_array($p['config'] ?? null) ? array_filter($p['config'], 'is_scalar') : [];
            return [
                'id' => (string) $n['id'],
                'type' => $n['type'],
                'x' => (float) $n['x'],
                'y' => (float) $n['y'],
                'properties' => [
                    'title' => Str::limit((string) ($p['title'] ?? ''), 120, ''),
                    'desc' => Str::limit((string) ($p['desc'] ?? ''), 300, ''),
                    'meta' => Str::limit((string) ($p['meta'] ?? ''), 120, ''),
                    'action' => Str::limit((string) ($p['action'] ?? $p['action_type'] ?? ''), 64, ''),
                    'config' => $cfg,
                ],
            ];
        }, $g['nodes'] ?? []);

        $edges = array_map(function ($e) {
            $branch = $e['properties']['branch'] ?? '';
            $textVal = is_array($e['text'] ?? null) 
                ? ($e['text']['value'] ?? '') 
                : (is_string($e['text'] ?? null) ? $e['text'] : '');

            // Fallback: deduce branch from text label if not explicitly set
            if (!in_array($branch, ['yes', 'no'], true) && !empty($textVal)) {
                $tv = strtolower(trim($textVal));
                if (str_contains($tv, 'yes')) {
                    $branch = 'yes';
                } elseif (str_contains($tv, 'no')) {
                    $branch = 'no';
                }
            }

            $edge = [
                'id' => (string) $e['id'],
                'type' => in_array($e['type'] ?? '', ['polyline', 'flow-edge'], true) ? $e['type'] : 'polyline',
                'sourceNodeId' => (string) $e['sourceNodeId'],
                'targetNodeId' => (string) $e['targetNodeId'],
                'sourceAnchorId' => isset($e['sourceAnchorId']) ? (string) $e['sourceAnchorId'] : null,
                'targetAnchorId' => isset($e['targetAnchorId']) ? (string) $e['targetAnchorId'] : null,
                'startPoint' => $e['startPoint'] ?? null,
                'endPoint' => $e['endPoint'] ?? null,
                'properties' => ['branch' => in_array($branch, ['yes', 'no'], true) ? $branch : ''],
            ];
            if (!empty($textVal)) {
                $edgeText = ['value' => Str::limit((string) $textVal, 60, '')];
                if (isset($e['text']['x'], $e['text']['y']) && ((float) $e['text']['x'] != 0 || (float) $e['text']['y'] != 0)) {
                    $edgeText['x'] = (float) $e['text']['x'];
                    $edgeText['y'] = (float) $e['text']['y'];
                }
                $edge['text'] = $edgeText;
            }
            return $edge;
        }, $g['edges'] ?? []);

        return ['nodes' => $nodes, 'edges' => $edges];
    }
}
