<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class JavCommand extends Command
{
    protected $signature = 'jav:sync
                            {--only=* : content|idols|search|recommendations}
                            {--provider=* : onejav|141jav|ffjav (content only)}
                            {--type=* : new|popular|daily|tags (content only)}
                            {--date= : YYYY-MM-DD for daily type (content only)}
                            {--queue= : Queue name override for queued jobs}
                            {--concurrency=3 : Idol sync concurrency}
                            {--search-mode=sync : sync|reset}
                            {--confirm-reset : Required when search mode is reset}
                            {--user-id=* : Recommendation user IDs}
                            {--limit=30 : Recommendation limit per user}';

    protected $description = 'Run all JAV sync flows (AIO) or selected components';

    public function handle(): int
    {
        $componentsOption = (array) $this->option('only');
        $components = $componentsOption === []
            ? ['content', 'idols', 'search', 'recommendations']
            : array_values(array_unique($componentsOption));

        foreach ($components as $component) {
            if (! in_array($component, ['content', 'idols', 'search', 'recommendations'], true)) {
                $this->error('Invalid component in --only. Supported: content, idols, search, recommendations');

                return self::INVALID;
            }
        }

        $hasFailure = false;
        $queueOption = trim((string) $this->option('queue'));
        $idolQueue = $queueOption !== '' ? $queueOption : (string) config('jav.idol_queue', 'xcity');
        $types = (array) $this->option('type');
        $providersOption = (array) $this->option('provider');
        $providers = $providersOption === [] ? ['onejav', '141jav', 'ffjav'] : array_values(array_unique($providersOption));
        $date = $this->option('date');

        if (in_array('content', $components, true)) {
            foreach ($providers as $provider) {
                $payload = [
                    'provider' => $provider,
                ];

                if ($queueOption !== '') {
                    $payload['--queue'] = $queueOption;
                }

                if ($types !== []) {
                    $payload['--type'] = $types;
                }
                if (is_string($date) && $date !== '') {
                    $payload['--date'] = $date;
                }

                $exitCode = Artisan::call('jav:sync:content', $payload, $this->output);
                $hasFailure = $hasFailure || $exitCode !== self::SUCCESS;
            }
        }

        if (in_array('idols', $components, true)) {
            $exitCode = Artisan::call('jav:sync:idols', [
                '--concurrency' => (int) $this->option('concurrency'),
                '--queue' => $idolQueue,
            ], $this->output);
            $hasFailure = $hasFailure || $exitCode !== self::SUCCESS;
        }

        if (in_array('search', $components, true)) {
            $searchMode = (string) $this->option('search-mode');
            if ($searchMode === 'reset' && ! $this->option('confirm-reset')) {
                $this->error('Refusing search reset without --confirm-reset');

                return self::INVALID;
            }

            $exitCode = Artisan::call('jav:sync:search', [
                '--mode' => $searchMode,
            ], $this->output);
            $hasFailure = $hasFailure || $exitCode !== self::SUCCESS;
        }

        if (in_array('recommendations', $components, true)) {
            $payload = [
                '--limit' => (int) $this->option('limit'),
            ];
            $userIds = (array) $this->option('user-id');
            if ($userIds !== []) {
                $payload['--user-id'] = $userIds;
            }

            $exitCode = Artisan::call('jav:sync:recommendations', $payload, $this->output);
            $hasFailure = $hasFailure || $exitCode !== self::SUCCESS;
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }
}
