<?php

/**
 * Demonstration script to store REAL JAV data in the database.
 * Run this with: php Modules/JAV/scripts/demo_store_data.php
 */

require __DIR__.'/../../../vendor/autoload.php';

$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\JavManager;

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  JAV Storage Demonstration - REAL Database\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$manager = new JavManager;

// Create a demo item
$item = new Item(
    id: 'demo001',
    title: 'DEMO001 - Demonstration Item',
    url: '/torrent/demo001',
    image: 'https://example.com/demo001.jpg',
    date: Carbon::now(),
    code: 'DEMO001',
    tags: collect(['Demo', 'Real Data', 'Production']),
    size: 4.2,
    description: 'This is a REAL demonstration record stored in your database!',
    actresses: collect(['Demo Actress 1', 'Demo Actress 2', 'Demo Actress 3']),
    download: '/download/demo001.torrent'
);

echo "📝 Storing item...\n";
$jav = $manager->store($item, 'demo-source');

echo "✅ SUCCESS! Data stored in database:\n\n";
echo "  Database ID: {$jav->id}\n";
echo "  Code: {$jav->code}\n";
echo "  Title: {$jav->title}\n";
echo "  Source: {$jav->source}\n";
echo "  Size: {$jav->size} GB\n";
echo '  Tags: '.json_encode($jav->tags)."\n";
echo '  Actresses: '.json_encode($jav->actresses)."\n";
echo "  Created: {$jav->created_at}\n";
echo "  Updated: {$jav->updated_at}\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 Database Statistics:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$total = Jav::count();
echo "  Total records in jav table: {$total}\n\n";

if ($total > 0) {
    echo "  Recent records:\n";
    Jav::orderBy('created_at', 'desc')
        ->limit(5)
        ->get()
        ->each(function ($j) {
            echo sprintf(
                "    - ID: %d | Code: %s | Source: %s | Size: %.2f GB\n",
                $j->id,
                $j->code,
                $j->source,
                $j->size ?? 0
            );
        });
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔍 Query the database directly:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "  SELECT * FROM jav WHERE code = 'DEMO001';\n";
echo "  SELECT * FROM jav ORDER BY created_at DESC LIMIT 10;\n";
echo "\n";
