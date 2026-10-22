<?php

namespace App\Workflows;

use App\Models\Lead;
use InvalidArgumentException;

final class DecisionEvaluator
{
    public static function passes(Lead $lead, array $c): bool
    {
        $field = (string) ($c['field'] ?? '');
        if (!preg_match('/^[a-z0-9_]+$/', $field)) {
            throw new InvalidArgumentException('Decision field is missing or invalid.');
        }

        $actual = $lead->getAttribute($field);
        $expected = $c['value'] ?? null;
        if (is_numeric($actual) && is_numeric($expected)) {
            $actual += 0;
            $expected += 0;
        }

        return match ($c['op'] ?? '=') {
            '>=' => $actual >= $expected,
            '>' => $actual > $expected,
            '<=' => $actual <= $expected,
            '<' => $actual < $expected,
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            default => throw new InvalidArgumentException('Unknown operator.'),
        };
    }
}
