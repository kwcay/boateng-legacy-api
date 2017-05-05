<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\ParameterBag;

class CamelCaseRequest
{
    /**
     * Attributes which should not be camel cased.
     *
     * @var array
     */
    protected $except = [
        'password',
        'password_confirmation',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->update($request->query);
        $this->update($request->request);
        $this->update($request->json());

        return $next($request);
    }

    /**
     * @param ParameterBag $params
     */
    protected function update(ParameterBag $params)
    {
        foreach ($params->all() as $key => $value) {
            if (! $params->has(snake_case($key))) {
                $params->set(snake_case($key), $value);
            }
        }
    }
}
