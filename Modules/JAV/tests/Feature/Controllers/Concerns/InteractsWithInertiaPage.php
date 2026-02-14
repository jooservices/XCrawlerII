<?php

namespace Modules\JAV\Tests\Feature\Controllers\Concerns;

use Illuminate\Testing\TestResponse;

trait InteractsWithInertiaPage
{
    /**
     * @param array<int, string> $requiredProps
     */
    protected function assertInertiaPage(TestResponse $response, string $component, array $requiredProps = []): void
    {
        $response->assertOk();
        $response->assertViewHas('page');

        $page = $response->viewData('page');
        if (is_string($page)) {
            $page = json_decode($page, true, 512, JSON_THROW_ON_ERROR);
        }

        $this->assertIsArray($page);
        $this->assertSame($component, $page['component'] ?? null);

        $props = $page['props'] ?? [];
        $this->assertIsArray($props);

        foreach ($requiredProps as $prop) {
            $this->assertArrayHasKey($prop, $props);
        }
    }
}
