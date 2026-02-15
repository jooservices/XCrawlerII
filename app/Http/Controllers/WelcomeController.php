<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome/Index', [
            'modules' => [
                [
                    'name' => 'JAV',
                    'description' => 'One of our products for metadata operations and discovery workflows.',
                    'route' => '/jav/dashboard',
                    'enabled' => true,
                ],
            ],
        ]);
    }
}
