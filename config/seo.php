<?php

return [
    'site_name' => env('SEO_SITE_NAME', env('APP_NAME', 'XCrawler')),
    'default_title' => env('SEO_DEFAULT_TITLE', env('APP_NAME', 'XCrawler')),
    'title_suffix' => env('SEO_TITLE_SUFFIX', env('APP_NAME', 'XCrawler')),
    'default_description' => env('SEO_DEFAULT_DESCRIPTION', 'XCrawler platform for crawling, indexing, and search workflows.'),
    'default_robots_public' => env('SEO_DEFAULT_ROBOTS_PUBLIC', 'index,follow'),
    'default_robots_private' => env('SEO_DEFAULT_ROBOTS_PRIVATE', 'noindex,nofollow'),
    'default_og_image' => env('SEO_DEFAULT_OG_IMAGE', '/images/og-default.png'),
    'twitter_card' => env('SEO_TWITTER_CARD', 'summary_large_image'),
    'twitter_site' => env('SEO_TWITTER_SITE', ''),
    'organization_name' => env('SEO_ORG_NAME', env('APP_NAME', 'XCrawler')),
    'organization_logo' => env('SEO_ORG_LOGO', '/images/logo.png'),
];
