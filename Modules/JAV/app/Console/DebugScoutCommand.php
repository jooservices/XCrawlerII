<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Modules\JAV\Models\Jav;

class DebugScoutCommand extends Command
{
    protected $signature = 'jav:debug-scout';
    protected $description = 'Debug Scout Elasticsearch mapping';

    public function handle()
    {
        $this->info('Debugging Scout for Jav model...');

        $jav = new Jav();
        $this->info('Searchable As: ' . $jav->searchableAs());
        $this->info('Scout Key Name: ' . $jav->getScoutKeyName());

        try {
            $engine = $jav->searchableUsing();
            $this->info('Engine: ' . get_class($engine));

            if (method_exists($engine, 'ElasticClient')) {
                // For matchish/laravel-scout-elasticsearch
                // Actually matchish engine doesn't expose client easily directly, 
                // but we can try to resolve the client from container if it's bound.
            }

            // Try to get mapping via ElasticSearch client if available
            if (class_exists(\Elasticsearch\Client::class) || interface_exists(\Elastic\Elasticsearch\Client::class)) {
                $client = app(\Elastic\Elasticsearch\Client::class);
                $params = ['index' => $jav->searchableAs()];
                $response = $client->indices()->getMapping($params);
                $this->info('Mapping:');
                $this->line(json_encode($response->asArray(), JSON_PRETTY_PRINT));
            } else {
                $this->warn('Elasticsearch client class not found or could not be resolved.');
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
