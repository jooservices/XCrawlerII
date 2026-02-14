<?php

namespace Modules\JAV\Traits;

use Elastic\Elasticsearch\Client;

trait ElasticsearchHelpers
{
    private function isElasticsearchAvailable(?string $index = null): bool
    {
        try {
            /** @var Client $client */
            $client = app(Client::class);

            if (!$client->ping()) {
                return false;
            }

            if ($index) {
                return $client->indices()->exists(['index' => $index])->asBool();
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
