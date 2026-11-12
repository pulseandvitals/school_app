<?php

namespace App\Http\Middleware;

use App\Enums\HabitatType;
use App\Models\Habitat;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the habitat type of a module's routes (/aquariums or /gardens) to the
 * request, and 404s when a bound habitat belongs to the other module.
 */
class ResolveHabitatType
{
    public const ATTRIBUTE = 'habitatType';

    public function handle(Request $request, Closure $next, string $type): Response
    {
        $habitatType = HabitatType::from($type);
        $habitat = $request->route('habitat');

        if ($habitat instanceof Habitat && $habitat->type !== $habitatType) {
            abort(404);
        }

        $request->attributes->set(self::ATTRIBUTE, $habitatType);

        return $next($request);
    }
}
