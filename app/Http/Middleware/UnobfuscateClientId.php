<?php

namespace App\Http\Middleware;

use App;
use Closure;

class UnobfuscateClientId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->has('client_id')) {
            $obfuscator = App::make('Obfuscator');
            $request->request->set('client_id', $obfuscator->decode($request->get('client_id')));
        }

        return $next($request);
    }
}