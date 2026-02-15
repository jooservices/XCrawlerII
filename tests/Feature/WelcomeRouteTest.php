<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomeRouteTest extends TestCase
{
    public function test_root_route_renders_welcome_module_launcher(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewHas('page');

        $page = $response->viewData('page');
        if (is_string($page)) {
            $page = json_decode($page, true, 512, JSON_THROW_ON_ERROR);
        }

        $this->assertIsArray($page);
        $this->assertSame('Welcome/Index', $page['component'] ?? null);
        $this->assertArrayHasKey('modules', $page['props'] ?? []);
    }
}
