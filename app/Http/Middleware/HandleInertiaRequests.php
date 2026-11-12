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
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'celebrations' => fn () => $request->session()->get('celebrations', []),
            ],
            'habitatTypes' => fn () => collect(config('habitats.types'))
                ->map(fn (array $config, string $type): array => [
                    'type' => $type,
                    'label' => $config['label'],
                    'singular' => $config['singular'],
                    'plural' => $config['plural'],
                    'route' => $config['route'],
                    'member_noun' => $config['member_noun'],
                    'themes' => $config['themes'],
                ])
                ->all(),
        ];
    }
}
