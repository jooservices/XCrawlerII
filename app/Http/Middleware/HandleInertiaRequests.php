<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $defaultOgImage = (string) config('seo.default_og_image');

        if (! str_starts_with($defaultOgImage, 'http')) {
            $defaultOgImage = url($defaultOgImage);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'seo' => [
                'siteName' => (string) config('seo.site_name'),
                'defaultTitle' => (string) config('seo.default_title'),
                'titleSuffix' => (string) config('seo.title_suffix'),
                'defaultDescription' => (string) config('seo.default_description'),
                'defaultRobotsPublic' => (string) config('seo.default_robots_public'),
                'defaultRobotsPrivate' => (string) config('seo.default_robots_private'),
                'defaultOgImage' => $defaultOgImage,
                'twitterCard' => (string) config('seo.twitter_card'),
                'twitterSite' => (string) config('seo.twitter_site'),
            ],
        ];
    }
}
