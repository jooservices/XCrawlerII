<?php

namespace Modules\Core\Observability;

class RedactionService
{
    public function redact(array $context): array
    {
        $redactKeys = array_map('strtolower', (array) config('services.obs.redact_keys', []));

        return $this->redactValue($context, $redactKeys);
    }

    private function redactValue(array $value, array $redactKeys): array
    {
        $sanitized = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array(strtolower($key), $redactKeys, true)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($item)) {
                $sanitized[$key] = $this->redactValue($item, $redactKeys);

                continue;
            }

            $sanitized[$key] = $item;
        }

        return $sanitized;
    }
}
