<?php

namespace Tests\Feature\Seo;

use Tests\TestCase;

class TechnicalSeoTest extends TestCase
{
    public function test_robots_txt_is_available_with_sitemap_reference(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Sitemap:', false);
    }

    public function test_sitemap_xml_is_available_with_url_entries(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<urlset', false);
        $response->assertSee('<loc>', false);
    }
}
