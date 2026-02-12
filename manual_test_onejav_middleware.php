<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\JAV\Services\Clients\OnejavClient;
try {
    echo "Creating Client...\n";
    $client = new OnejavClient();
    echo "Sending Request...\n";
    $response = $client->get('/');
    echo "Status: " . $response->status() . "\n";
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
