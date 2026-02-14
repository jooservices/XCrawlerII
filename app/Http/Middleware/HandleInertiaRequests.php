<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\JAV\Models\UserLikeNotification;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'preferences' => $request->user()->preferences,
                    // Frontend checks role/permission slugs (e.g. "admin", "view-users").
                    'roles' => $request->user()->roles->pluck('slug')->values(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('slug')->values(),
                ] : null,
            ],
            'notifications' => [
                'count' => fn (): int => $request->user()
                    ? (int) $request->user()->javNotifications()->unread()->count()
                    : 0,
                'items' => fn (): array => $request->user()
                    ? $request->user()
                        ->javNotifications()
                        ->with('jav:id,uuid,code,title')
                        ->unread()
                        ->latest('id')
                        ->limit(8)
                        ->get()
                        ->map(static function (UserLikeNotification $notification): array {
                            return [
                                'id' => (int) $notification->id,
                                'title' => (string) $notification->title,
                                'payload' => $notification->payload ?? [],
                                'jav' => $notification->jav ? [
                                    'uuid' => $notification->jav->uuid,
                                    'code' => $notification->jav->code,
                                    'title' => $notification->jav->title,
                                ] : null,
                            ];
                        })
                        ->all()
                    : [],
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
        ]);
    }
}
