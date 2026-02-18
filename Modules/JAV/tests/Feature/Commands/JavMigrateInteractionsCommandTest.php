<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Artisan;
use Mockery;
use Modules\JAV\Console\JavMigrateInteractionsCommand;
use Modules\JAV\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class JavMigrateInteractionsCommandTest extends TestCase
{
    public function test_command_rejects_invalid_mongo_mode(): void
    {
        $this->artisan('jav:migrate:interactions', [
            '--mongo' => 'invalid',
        ])->assertExitCode(2);
    }

    public function test_command_rejects_invalid_search_mode(): void
    {
        $this->artisan('jav:migrate:interactions', [
            '--search' => 'invalid',
        ])->assertExitCode(2);
    }

    public function test_command_rejects_search_reset_without_confirm_flag(): void
    {
        $this->artisan('jav:migrate:interactions', [
            '--search' => 'reset',
        ])->assertExitCode(2);
    }

    public function test_command_runs_expected_subcommands(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('jav:sync:analytics', Mockery::on(function (array $payload): bool {
                return ($payload['--days'] ?? []) === [7];
            }), Mockery::any())
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->once()
            ->with('jav:sync:recommendations', Mockery::on(function (array $payload): bool {
                return ($payload['--user-id'] ?? []) === [10, 11]
                    && ($payload['--limit'] ?? null) === 12;
            }), Mockery::any())
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->once()
            ->with('jav:sync:search', Mockery::on(function (array $payload): bool {
                return ($payload['--mode'] ?? null) === 'sync';
            }), Mockery::any())
            ->andReturn(0);

        $command = app(JavMigrateInteractionsCommand::class);
        $command->setLaravel($this->app);

        $input = new ArrayInput([
            '--days' => [7],
            '--user-id' => [10, 11],
            '--limit' => 12,
            '--search' => 'sync',
        ]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
    }
}
