<?php

namespace App\Http\Middleware;

use App\Support\Security\ModuleUnlock;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequireModulePassword
{
    public function __construct(private ModuleUnlock $unlock) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        $this->unlock->definition($module);   // unknown key => exception (fail closed)

        if ($user && $this->unlock->isUnlocked($user, $module)) {
            $this->unlock->touch($user, $module);

            $response = $next($request);
            $response->headers->set('Cache-Control', 'no-store, private');

            return $response;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'    => 'Password confirmation required.',
                'unlock_url' => route('module.unlock.show', $module),
            ], 423);
        }

        $this->rememberIntended($request, $module);

        return redirect()->route('module.unlock.show', $module);
    }

    /** GET: come back to this exact URL. Other methods: come back to the page they were on. */
    private function rememberIntended(Request $request, string $module): void
    {
        $key = "module_unlock_intended.{$module}";
        $url = $request->isMethod('GET') ? $request->fullUrl() : $request->headers->get('referer');

        $request->session()->forget($key);

        if ($url && Str::startsWith($url, $request->getSchemeAndHttpHost())) {
            $request->session()->put($key, $url);
        }
    }
}