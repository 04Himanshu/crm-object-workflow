<?php

namespace App\Workflows;

use App\Models\Lead;

final class Interpolator
{
    /**
     * Recursively interpolates dynamic lead tokens within workflow configurations.
     * Supports formats: {{lead.name}}, {{lead.company}}, {{score}}, {{email}}, etc.
     */
    public static function interpolate(mixed $value, Lead $lead): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = self::interpolate($v, $lead);
            }
            return $result;
        }

        if (!is_string($value) || !str_contains($value, '{{')) {
            return $value;
        }

        return (string) preg_replace_callback('/\{\{\s*(?:lead\.)?([a-z0-9_]+)\s*\}\}/i', function ($matches) use ($lead) {
            $field = strtolower($matches[1]);
            $val = $lead->getAttribute($field);
            if ($val !== null) {
                return (string) $val;
            }
            if ($field === 'owner' && $lead->relationLoaded('owner') && $lead->owner) {
                return (string) $lead->owner->name;
            }
            return $matches[0];
        }, $value);
    }
}
