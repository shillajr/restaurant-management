<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Localization;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class SetEntityLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = Localization::default();

        $user = $request->user();

        if ($user) {
            $user->loadMissing('entity.generalSettings');
            $entityLocale = $user->entity?->generalSettings?->language;

            if (Localization::isSupported($entityLocale)) {
                $locale = (string) $entityLocale;
            }
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        $localeMeta = Localization::get($locale);
        $regional = (string) ($localeMeta['regional'] ?? '');

        if ($regional !== '') {
            setlocale(LC_TIME, $regional.'.UTF-8', $regional);
        }

        app()->instance('activeLocaleMeta', $localeMeta);

        return $next($request);
    }
}
