<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use Modules\JAV\Tests\TestCase;

class AuthGuardEdgeTest extends TestCase
{
    public function test_guest_cannot_access_search_suggest_api_endpoint(): void
    {
        $this->getJson(route('jav.api.search.suggest', ['q' => 'alpha']))->assertUnauthorized();
    }

    public function test_guest_cannot_access_dashboard_items_api_endpoint(): void
    {
        $this->getJson(route('jav.api.dashboard.items'))->assertUnauthorized();
    }

    public function test_guest_cannot_access_watchlist_check_api_endpoint(): void
    {
        $this->getJson(route('jav.api.watchlist.check', 1))->assertUnauthorized();
    }

    public function test_guest_cannot_access_notification_read_all_api_endpoint(): void
    {
        $this->postJson(route('jav.api.notifications.read-all'))->assertUnauthorized();
    }
}
