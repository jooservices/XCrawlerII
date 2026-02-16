<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Modules\Core\Observability\Contracts\TelemetryEmitterInterface;
use Modules\Core\Services\DependencyHealthService;

class ObsDependenciesHealthCommand extends Command
{
    protected $signature = 'obs:dependencies-health
                            {--only= : Comma-separated dependencies (mysql,redis,elasticsearch,mongodb)}
                            {--fail-on-down : Exit with failure if any dependency is down}';

    protected $description = 'Check dependency health and emit dependency.health telemetry events';

    public function __construct(
        private readonly DependencyHealthService $dependencyHealthService,
        private readonly TelemetryEmitterInterface $telemetryEmitter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $only = trim((string) $this->option('only'));
        $dependencies = $only === '' ? [] : array_filter(array_map('trim', explode(',', $only)));

        $report = $this->dependencyHealthService->collect(array_values($dependencies));

        $rows = [];
        $downCount = 0;

        foreach ($report as $dependency => $result) {
            $status = (string) ($result['status'] ?? 'unknown');
            $level = $status === 'up' ? 'info' : 'warning';

            if ($status === 'down') {
                $downCount++;
            }

            $this->telemetryEmitter->emit(
                'dependency.health',
                [
                    ...$result,
                    'dependency' => $dependency,
                ],
                $level,
                'Dependency health probe executed'
            );

            $rows[] = [
                $dependency,
                $status,
                is_int($result['latency_ms'] ?? null) ? (string) $result['latency_ms'] : '-',
                $result['error'] ?? '-',
            ];
        }

        $this->table(['Dependency', 'Status', 'Latency(ms)', 'Error'], $rows);

        if ((bool) $this->option('fail-on-down') && $downCount > 0) {
            $this->error("{$downCount} dependencies are down.");

            return self::FAILURE;
        }

        $this->info('Dependency health probe completed.');

        return self::SUCCESS;
    }
}
