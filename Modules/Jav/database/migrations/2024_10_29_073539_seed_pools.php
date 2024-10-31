<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pools = [
            [
                'server_ip' => '192.168.1.41',
                'server_name' => 'xcrawler1',
                'server_wan_ip' => 'wan5',
                'name' => 'xcrawler1',
                'description' => 'JAV worker with WAN1',
            ],
            [
                'server_ip' => '192.168.1.42',
                'server_name' => 'xcrawler2',
                'server_wan_ip' => 'wan6',
                'name' => 'xcrawler2',
                'description' => 'JAV worker with WAN6',
            ],
            [
                'server_ip' => '192.168.1.43',
                'server_name' => 'xcrawler3',
                'server_wan_ip' => 'wan7',
                'name' => 'xcrawler3',
                'description' => 'JAV worker with WAN3',
            ],
            [
                'server_ip' => '192.168.1.44',
                'server_name' => 'xcrawler4',
                'server_wan_ip' => 'wan8',
                'name' => 'xcrawler4',
                'description' => 'JAV worker with WAN8',
            ],
            [
                'server_ip' => '192.168.1.45',
                'server_name' => 'xcrawler5',
                'server_wan_ip' => 'vpn1',
                'name' => 'xcrawler5',
                'description' => 'JAV worker with VPN1',
            ],
            [
                'server_ip' => '192.168.1.46',
                'server_name' => 'xcrawler6',
                'server_wan_ip' => 'vpn2',
                'name' => 'xcrawler6',
                'description' => 'JAV worker with VPN2',
            ],
            [
                'server_ip' => '192.168.1.47',
                'server_name' => 'xcrawler7',
                'server_wan_ip' => 'vpn3',
                'name' => 'xcrawler7',
                'description' => 'JAV worker with VPN3',
            ],
            [
                'server_ip' => '192.168.1.48',
                'server_name' => 'xcrawler8',
                'server_wan_ip' => 'vpn4',
                'name' => 'xcrawler8',
                'description' => 'JAV worker with VPN4',
            ],
        ];

        \Modules\Core\Models\Pool::insert($pools);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
