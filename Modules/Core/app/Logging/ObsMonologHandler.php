<?php

namespace Modules\Core\Logging;

use DateTimeInterface;
use Modules\Core\Observability\Contracts\TelemetryEmitterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Stringable;
use Throwable;

class ObsMonologHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        if (! (bool) config('services.obs.enabled', false)) {
            return;
        }

        if (($record->context['__obs_skip'] ?? false) === true) {
            return;
        }

        try {
            app(TelemetryEmitterInterface::class)->emit(
                'app.log',
                [
                    ...$this->normalizeContext($record->context),
                    'channel' => $record->channel,
                    'datetime' => $record->datetime->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM),
                ],
                strtolower($record->level->getName()),
                $record->message,
            );
        } catch (Throwable $exception) {
            error_log('OBS monolog handler failed: '.$exception->getMessage());
        }
    }

    private function normalizeContext(array $context): array
    {
        $normalized = [];

        foreach ($context as $key => $value) {
            $normalized[$key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value): mixed
    {
        $normalized = $value;

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item);
            }

        } elseif ($value instanceof DateTimeInterface) {
            $normalized = $value->format(DATE_ATOM);
        } elseif ($value instanceof Throwable) {
            $normalized = [
                'type' => $value::class,
                'message' => $value->getMessage(),
                'code' => $value->getCode(),
            ];
        } elseif ($value instanceof Stringable) {
            $normalized = (string) $value;
        } elseif (! is_null($value) && ! is_scalar($value)) {
            $normalized = [
                'type' => $value::class,
            ];
        }

        return $normalized;
    }
}
