<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

class SetTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $this->resolveTimezone($request);

        if ($timezone) {
            Date::setLocale($request->attributes->get('locale', app()->getLocale()));
            Config::set('app.timezone', $timezone);
            date_default_timezone_set($timezone);
        }

        return $next($request);
    }

    private function resolveTimezone(Request $request): ?string
    {
        $branchId = $request->attributes->get('branch_id');

        if (! $branchId && $request->hasSession()) {
            $branchId = $request->session()->get('active_branch_id');
        }

        $branch = $branchId ? \App\Models\Branch::query()->find($branchId) : null;

        return business_timezone($branch);
    }
}
