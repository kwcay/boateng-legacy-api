<?php

namespace App\Http\Middleware;

use Closure;
use App\Tracker;

class TrackRequests
{
    /**
     *
     */
    private $tracker;

    /**
     *
     */
    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Track all request attempts
        $version    = substr($request->path(), 0, strpos($request->path(), '/'));
        $params     = [];
        $endpoint   = '';

        if ($request->route()) {
            $version    = $request->route()->getPrefix();
            $params     = $request->route()->parameters();
            $endpoint   = $request->route()->uri();
            $endpoint   = rtrim(str_replace($version, '', substr($endpoint, 0, strpos($endpoint, '{'))), '/');
        }

        $this->tracker->addEvent('access', [
            'method'        => $request->method(),
            'root'          => $request->root(),
            'version'       => $version,
            'endpoint'      => $endpoint,
            'input'         => array_merge($params, $request->toArray()),
            'fingerprint'   => $request->fingerprint(),
            'user'          => $request->user() ? $request->user()->uniqueId : '',
        ]);

        return $response;
    }

    public function terminate($request, $response)
    {
        // Store the session data
        $this->tracker->persist();
    }
}
